<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\BillingProfile;

class ProjectBillingSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.app.pages.project-billing-settings';

    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?string $title = 'Billing & Subscription';

    public function mount()
    {
        // For now, only the true owner of the project can manage billing.
        abort_unless(filament()->getTenant()->user_id === auth()->id(), 403, 'Only the project owner can manage billing.');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BillingProfile::query()
                    ->where('id', filament()->getTenant()->billing_profile_id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Profile Name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('tier')
                    ->label('Subscription Tier')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('project_status')
                    ->label('Billing Status')
                    ->badge()
                    ->state(fn () => filament()->getTenant()->billing_status)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending_approval' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('assign_profile')
                    ->label('Change Billing Profile')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Select::make('billing_profile_id')
                            ->label('Available Profiles (Owned & Shared)')
                            ->options(function () {
                                return auth()->user()->getAvailableBillingProfiles()->pluck('name', 'id');
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
                                ->title('Profile Capacity Exceeded')
                                ->body("The selected billing profile ({$profile->name}) has reached its maximum project limit of {$maxProjects} for the " . ucfirst($profile->tier->value ?? $profile->tier) . " tier. Please upgrade its tier first.")
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
                                ->title('Profile assigned')
                                ->success()
                                ->send();
                        } else {
                            // Shared profile of a third party
                            $isShared = $profile->sharedWithUsers()->where('users.id', auth()->id())->exists();
                            if (!$isShared) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('The selected billing profile is not shared with you.')
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
                                ->title('Assignment requested')
                                ->body('The profile owner must approve this assignment before it can be used.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->actions([]);
    }
}
