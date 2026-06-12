<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Models\ProjectInvitation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProjectInvitationMail;
use Filament\Notifications\Notification;

class PendingInvitationsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $project = Filament::getTenant();

        return $table
            ->query(ProjectInvitation::query()->where('project_id', $project->id))
            ->heading('Pending Invitations')
            ->description('Email invitations and join requests from users using a share code.')
            ->columns([
                TextColumn::make('email')->label('Email'),
                TextColumn::make('role')->label('Role'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn (ProjectInvitation $record) => $record->role === 'collaborator' ? 'Join Request' : 'Invitation')
                    ->color(fn (ProjectInvitation $record) => $record->role === 'collaborator' ? 'warning' : 'info'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ProjectInvitation $record) => $record->role === 'collaborator')
                    ->requiresConfirmation()
                    ->action(function (ProjectInvitation $record) use ($project) {
                        $user = \App\Models\User::where('email', $record->email)->first();
                        if (!$user) {
                            Notification::make()->danger()->title('User not found.')->send();
                            return;
                        }

                        $user->projects()->syncWithoutDetaching([$project->id]);

                        $role = \Spatie\Permission\Models\Role::where('name', 'project_user')->first();
                        if ($role) {
                            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                                'role_id' => $role->id,
                                'model_type' => get_class($user),
                                'model_id' => $user->id,
                                'project_id' => $project->id,
                            ]);
                            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                            $user->unsetRelation('roles');
                            $user->unsetRelation('permissions');
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title("{$user->name} has been approved and added to the project.")
                            ->send();
                    }),
                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->hidden(fn (ProjectInvitation $record) => $record->role === 'collaborator')
                    ->action(function (ProjectInvitation $record) {
                        Mail::to($record->email)->send(new ProjectInvitationMail($record));
                        $inviteUrl = url("/app/invitations/{$record->token}/accept");
                        Notification::make()
                            ->success()
                            ->title('Invitation resent.')
                            ->body("Link: {$inviteUrl}")
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (ProjectInvitation $record) => $record->role === 'collaborator')
                    ->requiresConfirmation()
                    ->action(function (ProjectInvitation $record) {
                        $record->delete();
                        Notification::make()->success()->title('Join request rejected.')->send();
                    }),
                Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->hidden(fn (ProjectInvitation $record) => $record->role === 'collaborator')
                    ->requiresConfirmation()
                    ->action(function (ProjectInvitation $record) {
                        $record->delete();
                        Notification::make()->success()->title('Invitation revoked.')->send();
                    })
            ]);
    }

    public function render()
    {
        return view('livewire.pending-invitations-table');
    }
}
