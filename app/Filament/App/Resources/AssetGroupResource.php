<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssetGroupResource\Pages;
use App\Filament\App\Resources\AssetGroupResource\RelationManagers;
use App\Models\AssetGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetGroupResource extends Resource
{
    protected static ?string $model = AssetGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Data & Integrations';

    public static function getNavigationLabel(): string
    {
        return __('Asset Groups');
    }

    public static function getModelLabel(): string
    {
        return __('Asset Group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Asset Groups');
    }

    public static function form(Form $form): Form
    {
        $project = \Filament\Facades\Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Group Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Assets')
                    ->schema([
                        Forms\Components\ViewField::make('assets_data')
                            ->hiddenLabel()
                            ->view('filament.app.resources.asset-group-resource.components.asset-selector')
                            ->columnSpanFull()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Assets')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items')
                    ->label('Assets Summary')
                    ->badge()
                    ->placeholder('None')
                    ->getStateUsing(function (AssetGroup $record) {
                        $items = $record->items;
                        if ($items->isEmpty()) {
                            return null;
                        }
                        
                        $grouped = $items->groupBy('channel');
                        $summary = [];
                        foreach ($grouped as $channel => $groupItems) {
                            $channelName = \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($channel);
                            $summary[] = "{$channelName} ({$groupItems->count()})";
                        }
                        
                        return $summary;
                    }),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetGroups::route('/'),
            'create' => Pages\CreateAssetGroup::route('/create'),
            'edit' => Pages\EditAssetGroup::route('/{record}/edit'),
        ];
    }
}
