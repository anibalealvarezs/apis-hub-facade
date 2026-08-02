<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DashboardResource\Pages;
use App\Filament\App\Resources\DashboardResource\RelationManagers;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
        
        if (!auth()->user()->can('view_data')) {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhereHas('sharedUsers', function ($sq) {
                      $sq->where('users.id', auth()->id());
                  });
            });
        }
        
        return $query;
    }

    public static function canCreate(): bool
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project || !$project->billingProfile) {
            return false;
        }

        $currentCount = Dashboard::where('project_id', $project->id)->count();
        $maxDashboards = app(\App\Services\BillingLifecycleService::class)
            ->getMaxPrivateDashboardsForTier($project->billingProfile->tier);

        return $currentCount < $maxDashboards;
    }

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
                Forms\Components\Section::make(__('Dashboard Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_public')
                            ->label(__('Public (accessible by any project collaborator and via shared link)'))
                            ->default(false)
                            ->disabled(function (?\Illuminate\Database\Eloquent\Model $record) {
                                $project = \Filament\Facades\Filament::getTenant();
                                $tier = $project->billingProfile?->tier ?? \App\Enums\UserTier::FREE;
                                $maxPublic = app(\App\Services\BillingLifecycleService::class)->getMaxPublicDashboardsForTier($tier);
                                
                                if ($maxPublic === 0) {
                                    return true;
                                }
                                
                                if ($record && $record->is_public) {
                                    return false;
                                }

                                $currentPublic = Dashboard::where('project_id', $project->id)->where('is_public', true)->count();
                                return $currentPublic >= $maxPublic;
                            })
                            ->helperText(function (?\Illuminate\Database\Eloquent\Model $record) {
                                $project = \Filament\Facades\Filament::getTenant();
                                $tier = $project->billingProfile?->tier ?? \App\Enums\UserTier::FREE;
                                $maxPublic = app(\App\Services\BillingLifecycleService::class)->getMaxPublicDashboardsForTier($tier);
                                
                                if ($maxPublic === 0) {
                                    return __('Public dashboards are not available on your current plan.');
                                }
                                
                                if ($record && $record->is_public) {
                                    return null;
                                }
                                
                                $currentPublic = Dashboard::where('project_id', $project->id)->where('is_public', true)->count();
                                if ($currentPublic >= $maxPublic) {
                                    return __('You have reached the limit of public dashboards for your plan.');
                                }
                                return null;
                            }),
                        Forms\Components\Toggle::make('is_default')
                            ->label(__('Set as default dashboard'))
                            ->default(false),
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
                    ->label(__('Widgets'))
                    ->counts('widgets')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Created by'))
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_builder')
                    ->label(__('Open Builder'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Dashboard $record): string => DashboardResource::getUrl('builder', ['record' => $record]))
                    ->visible(fn (Dashboard $record) => !$record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\Action::make('open_view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Dashboard $record): string => DashboardResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (Dashboard $record) => !$record->trashed()),
                Tables\Actions\Action::make('set_default')
                    ->label(__('Set as default'))
                    ->icon('heroicon-o-star')
                    ->action(fn (Dashboard $record) => app(\App\Services\DashboardService::class)->setDefaultDashboard($record))
                    ->visible(fn (Dashboard $record) => !$record->trashed() && !$record->is_default && auth()->user()->can('edit_preferences')),
                Tables\Actions\Action::make('duplicate')
                    ->label(__('Duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->action(fn (Dashboard $record) => app(\App\Services\DashboardService::class)->cloneDashboard($record))
                    ->visible(fn (Dashboard $record) => !$record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Dashboard $record) => !$record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn (Dashboard $record) => $record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn (Dashboard $record) => $record->trashed() && auth()->user()->can('edit_preferences')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                    Tables\Actions\BulkAction::make('pruneVersions')
                        ->label(__('Prune Versions'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->form([
                            \Filament\Forms\Components\Select::make('months')
                                ->label(__('Delete versions older than'))
                                ->options([3 => '3 months', 6 => '6 months', 12 => '12 months'])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $cutoff = now()->subMonths((int) $data['months']);
                            foreach ($records as $record) {
                                $record->getVersions()
                                    ->where('created_at', '<', $cutoff)
                                    ->where('version_number', '>', 1)
                                    ->delete();
                            }
                            \Filament\Notifications\Notification::make()
                                ->title(__('Old versions pruned successfully'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                ]),
            ])
            ->defaultSort('is_default', 'desc')
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VersionsRelationManager::class,
        ];
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
