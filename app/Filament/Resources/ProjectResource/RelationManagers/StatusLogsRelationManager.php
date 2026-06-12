<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event_type'),
                Forms\Components\Toggle::make('is_active'),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Log Date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Active')),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'manual_toggle' => 'warning',
                        'archive' => 'danger',
                        'restore' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('Admin'))
                    ->placeholder(__('System')),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only
            ]);
    }
}
