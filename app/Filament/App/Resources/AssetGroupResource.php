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

    public static function getNavigationGroup(): ?string
    {
        return __('Data & Integrations');
    }

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

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_data');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('edit_preferences');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('edit_preferences');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('edit_preferences');
    }

    public static function form(Form $form): Form
    {
        $project = \Filament\Facades\Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make(__('Group Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make(__('Assets'))
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
                    ->label(__('Total Assets'))
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items')
                    ->label(__('Assets Summary'))
                    ->badge()
                    ->placeholder(__('None'))
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
                Tables\Actions\ViewAction::make(),
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
