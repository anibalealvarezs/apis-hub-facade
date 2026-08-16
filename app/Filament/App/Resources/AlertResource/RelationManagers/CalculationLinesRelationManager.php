<?php

namespace App\Filament\App\Resources\AlertResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CalculationLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'calculationLines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->label(__('Calculation Line Label'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\KeyValue::make('asset_filter')
                    ->label(__('Asset Filters (JSON mapping)'))
                    ->keyLabel(__('Key'))
                    ->valueLabel(__('Value'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('Line Label'))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('asset_filter')
                    ->label(__('Asset Filter Mapping'))
                    ->formatStateUsing(fn ($state) => json_encode($state)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
