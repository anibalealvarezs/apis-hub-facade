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
        $query = \App\Models\BillingProfileProject::query()
            ->with(['billingProfile', 'project', 'assignedBy'])
            ->whereHas('billingProfile', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->where('status', 'pending');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label('Billing Profile'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project'),
                Tables\Columns\TextColumn::make('assignedBy.name')
                    ->label('Requested By')
                    ->description(fn ($record) => $record->assignedBy?->email),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Requested At'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                    }),
                Tables\Actions\Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                    }),
            ])
            ->emptyStateHeading('No pending requests')
            ->emptyStateDescription('When a user assigns your shared profile to a project, it will appear here for your approval.');
    }
}
