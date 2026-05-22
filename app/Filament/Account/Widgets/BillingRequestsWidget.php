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
        $query = DB::table('billing_profile_project')
            ->join('billing_profiles', 'billing_profile_project.billing_profile_id', '=', 'billing_profiles.id')
            ->join('projects', 'billing_profile_project.project_id', '=', 'projects.id')
            ->join('users', 'billing_profile_project.assigned_by_user_id', '=', 'users.id')
            ->where('billing_profiles.user_id', auth()->id())
            ->where('billing_profile_project.status', 'pending')
            ->select(
                'billing_profile_project.id as pivot_id',
                'billing_profiles.name as profile_name',
                'projects.name as project_name',
                'users.name as requested_by',
                'users.email as requested_by_email',
                'billing_profile_project.created_at'
            );

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('profile_name')
                    ->label('Billing Profile'),
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project'),
                Tables\Columns\TextColumn::make('requested_by')
                    ->label('Requested By')
                    ->description(fn ($record) => $record->requested_by_email),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Requested At'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function ($record) {
                        DB::table('billing_profile_project')
                            ->where('id', $record->pivot_id)
                            ->update(['status' => 'approved']);
                    }),
                Tables\Actions\Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        DB::table('billing_profile_project')
                            ->where('id', $record->pivot_id)
                            ->update(['status' => 'rejected']);
                    }),
            ])
            ->emptyStateHeading('No pending requests')
            ->emptyStateDescription('When a user assigns your shared profile to a project, it will appear here for your approval.');
    }
}
