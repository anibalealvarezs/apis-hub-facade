<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DerivedMetricResource\Pages;
use App\Models\DerivedMetric;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DerivedMetricResource extends Resource
{
    protected static ?string $model = DerivedMetric::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Exploration & Telemetry';

    public static function canCreate(): bool
    {
        if (! auth()->user()->can('edit_preferences')) {
            return false;
        }

        $project = \Filament\Facades\Filament::getTenant();
        if (! $project || ! $project->billingProfile) {
            return false;
        }

        $currentCount = DerivedMetric::where('project_id', $project->id)->count();
        $max = app(\App\Services\BillingLifecycleService::class)
            ->getMaxDerivedMetricsForTier($project->billingProfile->tier);

        return $currentCount < $max;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('edit_preferences');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('edit_preferences');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_data');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->nullable()
                            ->rows(3),
                        Forms\Components\Select::make('output_granularity')
                            ->label(__('Output Granularity'))
                            ->options([
                                '' => __('Dynamic (user selects at widget level)'),
                                'daily' => __('Daily'),
                                'weekly' => __('Weekly'),
                                'monthly' => __('Monthly'),
                                'quarterly' => __('Quarterly'),
                                'annually' => __('Annually'),
                            ])
                            ->default('')
                            ->helperText(__('Fixed granularity locks the Derived Metric to a specific time resolution. Dynamic allows the widget viewer to choose.')),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make(__('Source Series'))
                    ->schema([
                        Forms\Components\Repeater::make('source_series')
                            ->label(__('Define the time series inputs for your formula'))
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label(__('Label'))
                                    ->maxLength(255)
                                    ->helperText(__('Display name for this series (e.g. "Facebook Spend")')),
                                Forms\Components\Select::make('channel')
                                    ->label(__('Channel'))
                                    ->options(fn () => \App\Services\Analytics\KpiFormBuilder::getActiveChannels())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('metric', null);
                                        $set('asset_group', null);
                                        $set('asset_filter', null);
                                    }),
                                Forms\Components\Select::make('metric')
                                    ->label(__('Metric'))
                                    ->options(fn (Forms\Get $get) => ! empty($get('channel'))
                                        ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($get('channel'))
                                        : [])
                                    ->required(),
                                Forms\Components\Select::make('granularity')
                                    ->label(__('Granularity'))
                                    ->options([
                                        'daily' => __('Daily'),
                                        'weekly' => __('Weekly'),
                                        'monthly' => __('Monthly'),
                                        'quarterly' => __('Quarterly'),
                                        'annually' => __('Annually'),
                                    ])
                                    ->default('daily'),
                                Forms\Components\Select::make('asset_group')
                                    ->label(__('Asset Group'))
                                    ->options(fn () => \App\Services\Analytics\KpiFormBuilder::getAssetGroupOptions())
                                    ->disabled(fn (Forms\Get $get) => filled($get('asset_filter')))
                                    ->live(),
                                Forms\Components\Select::make('asset_filter')
                                    ->label(__('Asset Filter'))
                                    ->multiple()
                                    ->options(fn (Forms\Get $get) => ! empty($get('channel'))
                                        ? \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($get('channel'))
                                        : [])
                                    ->disabled(fn (Forms\Get $get) => filled($get('asset_group')))
                                    ->live(),
                            ])
                            ->columns(3)
                            ->defaultItems(2)
                            ->addActionLabel(__('Add Source Series'))
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['channel'] ?? null),
                    ]),

                Forms\Components\Section::make(__('Formula'))
                    ->schema([
                        Forms\Components\Hidden::make('ast'),
                        Forms\Components\ViewField::make('ast')
                            ->label(__('Build Formula'))
                            ->view('filament.app.components.formula-editor')
                            ->viewData(fn (Forms\Get $get): array => [
                                'seriesKeys' => collect($get('source_series') ?? [])
                                    ->map(fn ($s, $i) => $s['key'] ?? chr(97 + $i))
                                    ->values()
                                    ->all(),
                            ])
                            ->helperText(__('Use source series keys (a, b, c…) and operators to define the formula. Click "Refresh keys" after adding/removing series.')),
                    ]),
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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('output_granularity')
                    ->formatStateUsing(fn ($state) => $state ?: __('Dynamic'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('widgets_count')
                    ->counts('widgets')
                    ->label(__('Widgets'))
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status')),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label(__('Preview'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
                    ->modalHeading(__('Derived Metric Preview'))
                    ->modalContent(function (DerivedMetric $record) {
                        $sourceKeys = array_column($record->source_series ?? [], 'key');
                        $astJson = json_encode($record->ast, JSON_PRETTY_PRINT);
                        $seriesJson = json_encode($record->source_series, JSON_PRETTY_PRINT);

                        return new \Illuminate\Support\HtmlString(
                            '<div class="space-y-4">'
                            . '<div><strong>' . __('Formula (AST)') . ':</strong><pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">' . $astJson . '</pre></div>'
                            . '<div><strong>' . __('Source Series') . ':</strong><pre style="background: #1f2937; color: #60a5fa; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">' . $seriesJson . '</pre></div>'
                            . '<div><strong>' . __('Source Keys') . ':</strong> ' . implode(', ', $sourceKeys) . '</div>'
                            . '</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('Duplicate'))
                    ->excludeAttributes(['id', 'widgets_count'])
                    ->beforeReplicaSaved(function (DerivedMetric $replica) {
                        $replica->name = $replica->name . ' (copy)';
                    })
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('flushCache')
                    ->label(__('Flush Cache'))
                    ->icon('heroicon-o-x-circle')
                    ->action(function (DerivedMetric $record) {
                        \App\Models\DerivedMetricResult::where('derived_metric_id', $record->id)->delete();
                        \Filament\Notifications\Notification::make()
                            ->title(__('Cache flushed successfully'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Flush Cache'))
                    ->modalDescription(__('This will clear all cached results for this Derived Metric.'))
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDerivedMetrics::route('/'),
            'create' => Pages\CreateDerivedMetric::route('/create'),
            'edit' => Pages\EditDerivedMetric::route('/{record}/edit'),
        ];
    }
}
