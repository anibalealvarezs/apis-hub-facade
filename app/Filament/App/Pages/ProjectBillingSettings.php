<?php

namespace App\Filament\App\Pages;

use App\Models\BillingProfile;
use App\Notifications\BillingProfileAssignmentRequestedNotification;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;

class ProjectBillingSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.app.pages.project-billing-settings';

    public function getTitle(): string
    {
        return __('Billing & Subscription');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('Billing & Subscription');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_billing');
    }

    public function mount()
    {
        abort_unless(auth()->user()->can('manage_billing'), 403, __('You do not have permission to manage billing.'));
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->assignProfileAction(),
        ];
    }

    public function assignProfileAction(): Action
    {
        return Action::make('assign_profile')
            ->label(__('Change Billing Profile'))
            ->icon('heroicon-o-pencil')
            ->color('warning')
            ->form([
                Forms\Components\Select::make('billing_profile_id')
                    ->label(__('Available Billing Profiles (Owned & Shared)'))
                    ->options(function () {
                        return auth()->user()->getAvailableBillingProfiles()->pluck('display_name', 'id');
                    })
                    ->required(),
            ])
            ->action(function (array $data) {
                $profile = BillingProfile::find($data['billing_profile_id']);
                $project = filament()->getTenant();

                // Check if the target profile has capacity to accept this project
                $maxProjects = app(\App\Services\BillingLifecycleService::class)
                    ->getMaxProjectsForTier($profile->tier);
                $currentProjectsCount = $profile->projects()
                    ->where('billing_status', 'active')
                    ->count();

                if ($currentProjectsCount >= $maxProjects) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('Profile Capacity Exceeded'))
                        ->body(__('The selected billing profile (:profile) has reached its maximum project limit of :limit for the :tier plan. Please upgrade that profile first.', ['profile' => $profile->display_name, 'limit' => $maxProjects, 'tier' => ucfirst($profile->tier->value ?? $profile->tier)]))
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                // Check if user is the owner of the profile
                if ($profile->user_id === auth()->id()) {
                    $project->update([
                        'billing_profile_id' => $profile->id,
                        'billing_status' => 'active',
                        'is_active' => true,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title(__('Billing Profile Assigned'))
                        ->success()
                        ->send();
                } else {
                    // Shared profile of a third party
                    $isShared = $profile->sharedWithUsers()->where('users.id', auth()->id())->exists();
                    if (! $isShared) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('Error'))
                            ->body(__('The selected billing profile is not shared with you.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $project->update([
                        'billing_profile_id' => $profile->id,
                        'billing_status' => 'pending_approval',
                        'is_active' => false,
                      ]);

                    \Filament\Notifications\Notification::make()
                        ->title(__('Assignment Requested'))
                        ->body(__('The billing profile owner must approve this request before it can be activated.'))
                        ->warning()
                        ->send();

                    $profile->user->notify(new BillingProfileAssignmentRequestedNotification(
                        billingProfile: $profile,
                        project: $project,
                        requesterName: auth()->user()->name,
                    ));
                }
            });
    }

    protected function getViewData(): array
    {
        $project = filament()->getTenant();

        $cacheKey = "project_{$project->id}_billing_page_data";

        $cachedData = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($project) {
            $billingProfile = $project->billingProfile()->first();

            $starts = null;
            $ends = null;
            if ($billingProfile) {
                $starts = $billingProfile->current_cycle_starts_at ?? $billingProfile->created_at ?? now()->startOfMonth();
                $ends = $billingProfile->current_cycle_ends_at ?? $starts->copy()->addMonth();
            }

            return [
                'billingProfile' => $billingProfile,
                'cycleStarts' => $starts,
                'cycleEnds' => $ends,
            ];
        });

        return [
            'project' => $project, // Always use the fresh tenant provided by Filament
            'billingProfile' => $cachedData['billingProfile'],
            'cycleStarts' => $cachedData['cycleStarts'],
            'cycleEnds' => $cachedData['cycleEnds'],
        ];
    }
}
