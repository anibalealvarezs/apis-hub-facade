<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DashboardResource\Pages;
use App\Models\Dashboard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DashboardResource extends Resource
{
    protected static ?string $model = Dashboard::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $cluster = \App\Filament\App\Clusters\Dashboards::class;

    public static function getNavigationLabel(): string
    {
        return __('All Dashboards');
    }

    public static function getModelLabel(): string
    {
        return __('Dashboard');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Dashboards');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dashboard Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public (accessible by any project collaborator and via shared link)')
                            ->default(false),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Set as default dashboard')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('project_id', auth()->user()->currentProject?->id))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('widgets_count')
                    ->label('Widgets')
                    ->counts('widgets')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created by')
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
                Tables\Filters\TernaryFilter::make('is_public'),
                Tables\Filters\TernaryFilter::make('is_default'),
            ])
            ->actions([
                Tables\Actions\Action::make('open_builder')
                    ->label('Open Builder')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Dashboard $record): string => DashboardResource::getUrl('builder', ['record' => $record]))
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\Action::make('open_view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Dashboard $record): string => DashboardResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('set_default')
                    ->label('Set as default')
                    ->icon('heroicon-o-star')
                    ->action(fn (Dashboard $record) => app(\App\Services\DashboardService::class)->setDefaultDashboard($record))
                    ->visible(fn (Dashboard $record) => !$record->is_default && auth()->user()->can('edit_preferences')),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(fn (Dashboard $record) => app(\App\Services\DashboardService::class)->cloneDashboard($record))
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                ]),
            ])
            ->defaultSort('is_default', 'desc')
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDashboards::route('/'),
            'create' => Pages\CreateDashboard::route('/create'),
            'edit' => Pages\EditDashboard::route('/{record}/edit'),
            'builder' => Pages\DashboardBuilder::route('/{record}/builder'),
            'view' => Pages\DashboardView::route('/{record}'),
        ];
    }
}
