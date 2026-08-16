<?php

namespace App\Filament\App\Resources\AlertResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AlertLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'alertLogs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alert_name')
            ->description(new HtmlString('<div class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg text-blue-600 dark:text-blue-400 text-xs font-medium mb-3">ℹ️ <strong>Retention Notice:</strong> Alert evaluation logs are automatically pruned after 30 days. Log entries remain self-contained snapshots even if alert rules are updated or deleted.</div>'))
            ->columns([
                Tables\Columns\TextColumn::make('triggered_at')
                    ->label(__('Triggered At'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('asset_summary')
                    ->label(__('Asset / Target'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('evaluated_value')
                    ->label(__('Evaluated Value'))
                    ->numeric(2),

                Tables\Columns\TextColumn::make('threshold_value')
                    ->label(__('Threshold'))
                    ->numeric(2)
                    ->placeholder('-'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->colors([
                        'danger' => 'triggered',
                        'success' => 'ok',
                        'warning' => 'warning',
                    ]),

                Tables\Columns\IconColumn::make('notified_ui')
                    ->label(__('UI'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('notified_email')
                    ->label(__('Email'))
                    ->boolean(),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
