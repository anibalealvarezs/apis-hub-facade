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
        // Once project roles are implemented, this can be expanded to 'admins'.
        abort_unless(filament()->getTenant()->user_id === auth()->id(), 403, 'Only the project owner can manage billing.');
    }

    public function table(Table $table): Table
    {
        $tenantId = filament()->getTenant()->id;

        return $table
            ->query(
                BillingProfile::query()
                    ->select('billing_profiles.*', 'billing_profile_project.status as pivot_status', 'billing_profile_project.is_primary as pivot_is_primary')
                    ->join('billing_profile_project', 'billing_profiles.id', '=', 'billing_profile_project.billing_profile_id')
                    ->where('billing_profile_project.project_id', $tenantId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Profile Name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('pivot_status')
                    ->label('Assignment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('pivot_is_primary')
                    ->label('Primary')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('assign_profile')
                    ->label('Assign Billing Profile')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('billing_profile_id')
                            ->label('Available Profiles (Owned & Shared)')
                            ->options(function () {
                                return auth()->user()->getAvailableBillingProfiles()->pluck('name', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (array $data) use ($tenantId) {
                        $profile = BillingProfile::find($data['billing_profile_id']);
                        
                        // Check if user is the owner
                        $status = ($profile->user_id === auth()->id()) ? 'approved' : 'pending';

                        DB::table('billing_profile_project')->updateOrInsert(
                            [
                                'billing_profile_id' => $profile->id,
                                'project_id' => $tenantId,
                            ],
                            [
                                'status' => $status,
                                'is_primary' => false,
                                'assigned_by_user_id' => auth()->id(),
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        if ($status === 'pending') {
                            \Filament\Notifications\Notification::make()
                                ->title('Assignment requested')
                                ->body('The profile owner must approve this assignment before it can be used.')
                                ->warning()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Profile assigned')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('make_primary')
                    ->label('Set Primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->pivot_status === 'approved' && !$record->pivot_is_primary)
                    ->action(function ($record) use ($tenantId) {
                        // Unset others
                        DB::table('billing_profile_project')
                            ->where('project_id', $tenantId)
                            ->update(['is_primary' => false]);
                        
                        // Set new primary
                        DB::table('billing_profile_project')
                            ->where('project_id', $tenantId)
                            ->where('billing_profile_id', $record->id)
                            ->update(['is_primary' => true]);
                    }),
                Tables\Actions\Action::make('remove')
                    ->label('Remove')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) use ($tenantId) {
                        DB::table('billing_profile_project')
                            ->where('project_id', $tenantId)
                            ->where('billing_profile_id', $record->id)
                            ->delete();
                    }),
            ]);
    }
}
