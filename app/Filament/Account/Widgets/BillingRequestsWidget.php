<?php

namespace App\Filament\Account\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use App\Models\BillingProfile;
use App\Models\Project;

class BillingRequestsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Billing Assignments';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Find all pending assignments for billing profiles owned by this user
        $query = Project::query()
            ->with(['billingProfile', 'user'])
            ->whereHas('billingProfile', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->where('billing_status', 'pending_approval');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label('Billing Profile'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Project'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Requested By')
                    ->description(fn (Project $record) => $record->user?->email),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Requested At'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (Project $record) {
                        $profile = $record->billingProfile;
                        if ($profile) {
                            $maxProjects = app(\App\Services\BillingLifecycleService::class)
                                ->getMaxProjectsForTier($profile->tier);
                            $currentProjectsCount = $profile->projects()
                                ->where('billing_status', 'active')
                                ->count();

                            if ($currentProjectsCount >= $maxProjects) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Capacity Limit Reached')
                                    ->body("This billing profile ({$profile->name}) has reached its limit of {$maxProjects} projects. You cannot approve this request until you upgrade your plan or remove other projects.")
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                return;
                            }
                        }

                        $record->update([
                            'billing_status' => 'active',
                            'is_active' => true,
                        ]);
                    }),
                Tables\Actions\Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(function (Project $record) {
                        // Reset the project billing profile to recipient's default FREE profile
                        $defaultProfile = $record->user->billingProfiles()->where('is_default', true)->first();
                        $record->update([
                            'billing_profile_id' => $defaultProfile?->id,
                            'billing_status' => 'suspended',
                            'is_active' => false,
                        ]);
                    }),
            ])
            ->emptyStateHeading('No pending requests')
            ->emptyStateDescription('When a collaborator assigns your shared profile to a project, it will appear here for your approval.');
    }
}
