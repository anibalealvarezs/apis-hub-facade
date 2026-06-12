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
            ->heading('Invitaciones Pendientes')
            ->description('Usuarios que aún no han aceptado la invitación.')
            ->columns([
                TextColumn::make('email')->label('Email Invitado'),
                TextColumn::make('role')->label('Rol Propuesto'),
                TextColumn::make('expires_at')
                    ->label('Expira el')
                    ->dateTime()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('resend')
                    ->label('Reenviar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ProjectInvitation $record) {
                        Mail::to($record->email)->send(new ProjectInvitationMail($record));
                        $inviteUrl = url("/app/invitations/{$record->token}/accept");
                        Notification::make()
                            ->success()
                            ->title('Invitación reenviada.')
                            ->body("Link de invitación: {$inviteUrl}")
                            ->send();
                    }),
                Action::make('revoke')
                    ->label('Revocar')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function (ProjectInvitation $record) {
                        $record->delete();
                        Notification::make()->success()->title('Invitación revocada.')->send();
                    })
            ]);
    }

    public function render()
    {
        return view('livewire.pending-invitations-table');
    }
}
