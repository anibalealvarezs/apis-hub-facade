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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SharedWithUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'sharedWithUsers';

    protected static ?string $recordTitleAttribute = 'email';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Shared Users');
    }

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
            ->description(function () {
                if (!app(\App\Services\BillingLifecycleService::class)->canShareBillingProfile($this->ownerRecord->tier)) {
                    return new \Illuminate\Support\HtmlString('
                        <div class="p-4 mt-2 bg-warning-50 dark:bg-warning-500/10 rounded-xl text-warning-600 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    <h3 class="font-bold">' . __('Upgrade Required to Share Profile') . '</h3>
                                </div>
                                <p class="text-sm">' . __('Sharing billing profiles is exclusively available on the Enterprise tier. Please upgrade this profile to Enterprise to invite other members to use it.') . '</p>
                            </div>
                            ' . ($this->ownerRecord->user_id === auth()->id() ? '
                            <a href="/account/account-subscription?profile=' . $this->ownerRecord->id . '" class="shrink-0 inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400">
                                ' . __('Manage Subscription') . '
                            </a>
                            ' : '
                            <span class="shrink-0 inline-block px-3 py-1.5 text-sm font-medium text-warning-700 bg-warning-100 dark:bg-warning-500/20 dark:text-warning-300 rounded-lg">
                                ' . __('Please contact the billing profile owner to upgrade the subscription.') . '
                            </span>
                            ') . '
                        </div>
                    ');
                }
                return null;
            })
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
                    ->disabled(fn () => !app(\App\Services\BillingLifecycleService::class)->canShareBillingProfile($this->ownerRecord->tier))
                    ->tooltip(fn () => !app(\App\Services\BillingLifecycleService::class)->canShareBillingProfile($this->ownerRecord->tier) ? __('Only Enterprise tier can share billing profiles.') : null)
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

                            if (! $targetProfile) {
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

                        if (! empty($reassigned) || ! empty($suspended)) {
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

                                    if (! $targetProfile) {
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

                                if (! empty($reassigned) || ! empty($suspended)) {
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
