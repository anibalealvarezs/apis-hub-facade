<?php

namespace App\Filament\App\Resources\DerivedMetricResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Changed by'),
                Tables\Columns\TextColumn::make('change_summary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->defaultSort('version_number', 'desc')
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Version')
                    ->modalDescription('This will overwrite the current state with this version. A new version will be created for the current state before restoring.')
                    ->action(function ($record) {
                        $owner = $this->getOwnerRecord();
                        $owner->createVersion('Before restore to v' . $record->version_number);
                        $owner->restoreVersion($record->id);
                    })
                    ->successRedirectUrl(url()->current()),
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function ($record) {
                        $previousVersion = $record->where('version_number', $record->version_number - 1)
                            ->where('derived_metric_id', $record->derived_metric_id)
                            ->first();

                        return view('filament.modals.version-diff', [
                            'version' => $record,
                            'previousVersion' => $previousVersion,
                        ]);
                    }),
            ]);
    }
}
