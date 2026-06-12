<?php

namespace App\Filament\Account\Resources\BillingProfileResource\RelationManagers;

use App\Mail\BillingInvitationMail;
use App\Models\BillingInvitation;
use App\Models\Project;
use App\Models\User;
use App\Notifications\BillingAccessRevokedNotification;
use App\Services\BillingLifecycleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SharedWithUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'sharedWithUsers';

    protected static ?string $title = 'Shared Users';
    
    protected static ?string $recordTitleAttribute = 'email';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // We use AttachAction for assigning existing users, so this form might not strictly be used for creating users.
                // But if they edit the pivot (role), we need this:
                Forms\Components\Select::make('role')
                    ->options([
                        'member' => 'Member (Can use to pay)',
                    ])
                    ->default('member')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name')),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('role')
                    ->label(__('Role'))
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('invite')
                    ->label(__('Invite User'))
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->label(__('User Email')),
                        Forms\Components\Select::make('role')
                            ->options([
                                'member' => 'Member (Can use to pay)',
                            ])
                            ->default('member')
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $profile = $livewire->ownerRecord;
                        
                        // Check if already shared
                        $alreadyShared = $profile->sharedWithUsers()->where('users.email', $data['email'])->exists();
                        if ($alreadyShared) {
                            Notification::make()
                                ->title(__('User is already sharing this profile.'))
                                ->warning()
                                ->send();
                            return;
                        }

                        // Check if already invited
                        $alreadyInvited = BillingInvitation::where('billing_profile_id', $profile->id)
                            ->where('email', $data['email'])
                            ->where('status', 'pending')
                            ->exists();
                            
                        if ($alreadyInvited) {
                            Notification::make()
                                ->title(__('User already has a pending invitation.'))
                                ->warning()
                                ->send();
                            return;
                        }

                        $invitation = BillingInvitation::create([
                            'billing_profile_id' => $profile->id,
                            'email' => $data['email'],
                            'role' => $data['role'],
                            'token' => Str::random(32),
                            'expires_at' => now()->addDays(7),
                        ]);

                        Mail::to($data['email'])->send(new BillingInvitationMail($invitation));

                        Notification::make()
                            ->title(__('Invitation Sent'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('revokeAccess')
                    ->label(__('Revoke Access'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Revoke Billing Profile Access'))
                    ->modalDescription(function (User $record): string {
                        $count = Project::where('user_id', $record->id)
                            ->where('billing_profile_id', $this->ownerRecord->id)
                            ->count();

                        if ($count === 0) {
                            return "Are you sure you want to revoke billing profile access for {$record->name}? They have no projects currently using this profile.";
                        }

                        return "Revoking access will affect {$count} project(s) owned by {$record->name}. Projects will be reassigned to their default billing profile, or suspended if quota is insufficient.";
                    })
                    ->action(function (User $record) {
                        $profile = $this->ownerRecord;
                        $affectedProjects = Project::where('user_id', $record->id)
                            ->where('billing_profile_id', $profile->id)
                            ->get();

                        $reassigned = [];
                        $suspended = [];

                        foreach ($affectedProjects as $project) {
                            $targetProfile = $record->billingProfiles()
                                ->where('is_default', true)
                                ->where('id', '!=', $profile->id)
                                ->first();

                            if (!$targetProfile) {
                                $targetProfile = $record->billingProfiles()
                                    ->where('id', '!=', $profile->id)
                                    ->first();
                            }

                            if ($targetProfile) {
                                $maxProjects = app(BillingLifecycleService::class)
                                    ->getMaxProjectsForTier($targetProfile->tier);
                                $currentCount = $targetProfile->projects()
                                    ->where('billing_status', 'active')
                                    ->count();

                                if ($currentCount < $maxProjects) {
                                    $project->update([
                                        'billing_profile_id' => $targetProfile->id,
                                        'billing_status' => 'active',
                                        'is_active' => true,
                                    ]);
                                    $reassigned[] = ['id' => $project->id, 'name' => $project->name];
                                    continue;
                                }
                            }

                            $project->update([
                                'billing_profile_id' => null,
                                'billing_status' => 'suspended',
                                'is_active' => false,
                            ]);
                            $suspended[] = ['id' => $project->id, 'name' => $project->name];
                        }

                        $profile->sharedWithUsers()->detach($record->id);

                        if (!empty($reassigned) || !empty($suspended)) {
                            $record->notify(new BillingAccessRevokedNotification(
                                billingProfileName: $profile->name,
                                reassignedProjects: $reassigned,
                                suspendedProjects: $suspended,
                            ));
                        }

                        Notification::make()
                            ->title(__('Access Revoked'))
                            ->body("Access revoked for {$record->name}. " . count($reassigned) . ' project(s) reassigned, ' . count($suspended) . ' project(s) suspended.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('revokeAccessBulk')
                        ->label(__('Revoke Access'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('Revoke Billing Profile Access'))
                        ->modalDescription(__('Are you sure you want to revoke access for the selected users? Their projects using this billing profile will be reassigned to their default profile, or suspended if quota is insufficient.'))
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $profile = $this->ownerRecord;

                            foreach ($records as $user) {
                                $affectedProjects = Project::where('user_id', $user->id)
                                    ->where('billing_profile_id', $profile->id)
                                    ->get();

                                $reassigned = [];
                                $suspended = [];

                                foreach ($affectedProjects as $project) {
                                    $targetProfile = $user->billingProfiles()
                                        ->where('is_default', true)
                                        ->where('id', '!=', $profile->id)
                                        ->first();

                                    if (!$targetProfile) {
                                        $targetProfile = $user->billingProfiles()
                                            ->where('id', '!=', $profile->id)
                                            ->first();
                                    }

                                    if ($targetProfile) {
                                        $maxProjects = app(BillingLifecycleService::class)
                                            ->getMaxProjectsForTier($targetProfile->tier);
                                        $currentCount = $targetProfile->projects()
                                            ->where('billing_status', 'active')
                                            ->count();

                                        if ($currentCount < $maxProjects) {
                                            $project->update([
                                                'billing_profile_id' => $targetProfile->id,
                                                'billing_status' => 'active',
                                                'is_active' => true,
                                            ]);
                                            $reassigned[] = ['id' => $project->id, 'name' => $project->name];
                                            continue;
                                        }
                                    }

                                    $project->update([
                                        'billing_profile_id' => null,
                                        'billing_status' => 'suspended',
                                        'is_active' => false,
                                    ]);
                                    $suspended[] = ['id' => $project->id, 'name' => $project->name];
                                }

                                if (!empty($reassigned) || !empty($suspended)) {
                                    $user->notify(new BillingAccessRevokedNotification(
                                        billingProfileName: $profile->name,
                                        reassignedProjects: $reassigned,
                                        suspendedProjects: $suspended,
                                    ));
                                }

                                $profile->sharedWithUsers()->detach($user->id);
                            }

                            Notification::make()
                                ->title(__('Access Revoked'))
                                ->body('Access revoked for ' . $records->count() . ' user(s).')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
