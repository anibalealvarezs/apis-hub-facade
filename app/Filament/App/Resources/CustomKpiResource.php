<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CustomKpiResource\Pages;
use App\Filament\App\Resources\CustomKpiResource\RelationManagers;
use App\Models\CustomKpi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomKpiResource extends Resource
{
    protected static ?string $model = CustomKpi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Exploration & Telemetry';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('KPI Configuration')
                    ->schema([
                        Forms\Components\Select::make('template')
                            ->label('Quick Start Template')
                            ->options(function () {
                                // We will load active channels and get templates
                                // Placeholder for now
                                return \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Auto fill logic here
                            }),

                        Forms\Components\Select::make('calculation_type')
                            ->label('Calculation Type')
                            ->options([
                                'calculate_regression' => 'Multiple Linear Regression',
                                'calculate_elasticity' => 'Elasticity',
                                'calculate_autocorrelation' => 'Autocorrelation',
                                'calculate_granger' => 'Granger Causality',
                                'calculate_macd' => 'MACD Momentum',
                                'calculate_anomaly' => 'Anomaly Detection',
                            ])
                            ->required()
                            ->live(),
                        
                        // Temporarily keeping AST as textarea until we build the full dynamic form
                        Forms\Components\Textarea::make('ast')
                            ->label('AST (JSON Payload)')
                            ->required()
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state),
                        
                        Forms\Components\KeyValue::make('filters')
                            ->label('Scope / Filters')
                            ->default(['startDate' => '', 'endDate' => '', 'groupBy' => 'daily'])
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomKpis::route('/'),
            'create' => Pages\CreateCustomKpi::route('/create'),
            'edit' => Pages\EditCustomKpi::route('/{record}/edit'),
        ];
    }
}
