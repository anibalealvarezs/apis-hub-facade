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
                        Forms\Components\ViewField::make('_formula_editor')
                            ->label(__('Build Formula'))
                            ->view('filament.app.components.formula-editor')
                            ->viewData(fn (Forms\Get $get): array => {
                                $sd = $get('source_series') ?? [];
                                $ast = $get('ast');
                                \Log::info('[DM_FORM] viewData', [
                                    'source_series_type' => gettype($sd),
                                    'source_series_count' => is_array($sd) ? count($sd) : 'N/A',
                                    'source_series' => $sd,
                                    'ast_type' => gettype($ast),
                                    'ast' => $ast,
                                ]);
                                return [
                                    'seriesData' => $sd,
                                    'initialAst' => $ast,
                                    'astStatePath' => 'data.ast',
                                ];
                            })
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
                Tables\Actions\Action::make('test')
                    ->label(__('Test'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form(function (DerivedMetric $record) {
                        $fields = [];
                        $dmGranularity = $record->output_granularity;

                        if (empty($dmGranularity)) {
                            $fields[] = Forms\Components\Select::make('granularity')
                                ->label(__('Granularity'))
                                ->options([
                                    'daily' => __('Daily'),
                                    'weekly' => __('Weekly'),
                                    'monthly' => __('Monthly'),
                                ])
                                ->default('daily');
                        }

                        $fields[] = Forms\Components\DatePicker::make('date_start')
                            ->label(__('Start Date'))
                            ->default(now()->subDays(30));

                        $fields[] = Forms\Components\DatePicker::make('date_end')
                            ->label(__('End Date'))
                            ->default(now());

                        $sourceSeries = $record->source_series ?? [];
                        $runtimeAssetFields = [];
                        foreach ($sourceSeries as $series) {
                            $key = $series['key'] ?? null;
                            $channel = $series['channel'] ?? null;
                            if (empty($key) || empty($channel)) {
                                continue;
                            }
                            $hasAssetFilter = ! empty($series['asset_filter']) && is_array($series['asset_filter']);
                            if ($hasAssetFilter) {
                                continue;
                            }
                            $seriesLabel = $series['label'] ?? $series['metric'] ?? $key;
                            $channelLabel = \App\Services\Analytics\KpiFormBuilder::getActiveChannels()[$channel] ?? $channel;
                            $runtimeAssetFields[] = Forms\Components\Select::make("runtime_asset_{$key}")
                                ->label(__('Asset for :series (:channel)', ['series' => $seriesLabel, 'channel' => $channelLabel]))
                                ->options(fn () => \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel));
                        }
                        if (! empty($runtimeAssetFields)) {
                            $fields[] = Forms\Components\Section::make(__('Runtime Asset Overrides'))
                                ->schema($runtimeAssetFields)
                                ->description(__('Select assets for source series that do not have a fixed asset filter. Leave empty to use all assets.'));
                        }

                        return $fields;
                    })
                    ->action(function (DerivedMetric $record, array $data) {
                        $granularity = $record->output_granularity ?? $data['granularity'] ?? 'daily';
                        $dateStart = $data['date_start'] ?? now()->subDays(30)->format('Y-m-d');
                        $dateEnd = $data['date_end'] ?? now()->format('Y-m-d');

                        $project = \Filament\Facades\Filament::getTenant();
                        $sourceSeries = $record->source_series ?? [];

                        \Illuminate\Support\Facades\Log::debug('[DM_TEST] Action fired', [
                            'dm_id' => $record->id,
                            'project_id' => $project->id,
                            'granularity' => $granularity,
                            'dateStart' => $dateStart,
                            'dateEnd' => $dateEnd,
                            'source_series_count' => count($sourceSeries),
                            'ast' => $record->ast,
                            'data_keys' => array_keys($data),
                            'data' => $data,
                        ]);

                        $widgetDataController = new \App\Http\Controllers\Api\DashboardWidgetDataController(
                            app(\App\Services\WidgetDataService::class),
                            app(\App\Services\RemoteEngineService::class)
                        );

                        $fetchedSeries = [];
                        foreach ($sourceSeries as $series) {
                            $key = $series['key'];
                            $channel = $series['channel'] ?? null;
                            $metric = $series['metric'] ?? null;

                            \Illuminate\Support\Facades\Log::debug('[DM_TEST] Processing series', [
                                'key' => $key,
                                'channel' => $channel,
                                'metric' => $metric,
                                'series_config' => $series,
                            ]);

                            if (empty($channel) || empty($metric)) {
                                \Illuminate\Support\Facades\Log::warning('[DM_TEST] Skipping series - missing channel or metric', ['key' => $key]);
                                $fetchedSeries[$key] = [];
                                continue;
                            }

                            $assetFilter = $series['asset_filter'] ?? null;
                            $extractedAssets = null;
                            if (! empty($assetFilter) && is_array($assetFilter)) {
                                $validAssets = $widgetDataController->getValidAssetsForChannel($project, $channel);
                                $filtered = array_intersect($assetFilter, $validAssets);
                                $extractedAssets = ! empty($filtered) ? array_values($filtered) : null;
                                \Illuminate\Support\Facades\Log::debug('[DM_TEST] Asset filter applied', [
                                    'key' => $key,
                                    'asset_filter' => $assetFilter,
                                    'valid_assets' => $validAssets,
                                    'filtered' => $filtered,
                                    'extractedAssets' => $extractedAssets,
                                ]);
                            } else {
                                $runtimeAsset = $data["runtime_asset_{$key}"] ?? null;
                                if (! empty($runtimeAsset)) {
                                    $extractedAssets = is_array($runtimeAsset) ? $runtimeAsset : [$runtimeAsset];
                                }
                                \Illuminate\Support\Facades\Log::debug('[DM_TEST] No asset_filter, runtime check', [
                                    'key' => $key,
                                    'runtimeAsset' => $runtimeAsset,
                                    'extractedAssets' => $extractedAssets,
                                ]);
                            }

                            $payload = [
                                'tenant' => $project->id,
                                'account' => $extractedAssets,
                                'dateStart' => $dateStart,
                                'dateEnd' => $dateEnd,
                                'granularity' => $series['granularity'] ?? $granularity,
                                'metrics' => [$metric],
                            ];

                            \Illuminate\Support\Facades\Log::debug('[DM_TEST] Calling forwardToChannelEndpoint', [
                                'key' => $key,
                                'channel' => $channel,
                                'payload' => $payload,
                            ]);

                            try {
                                $channelResponse = $widgetDataController->forwardToChannelEndpoint($channel, 'chart', $payload);
                                \Illuminate\Support\Facades\Log::debug('[DM_TEST] Channel response received', [
                                    'key' => $key,
                                    'response_keys' => array_keys($channelResponse),
                                    'response_type' => gettype($channelResponse),
                                    'response_preview' => array_slice($channelResponse, 0, 5),
                                ]);
                                $seriesData = $widgetDataController->extractTimeSeriesFromResponse($channelResponse, $metric);
                                \Illuminate\Support\Facades\Log::debug('[DM_TEST] Extracted time series', [
                                    'key' => $key,
                                    'metric' => $metric,
                                    'seriesData_count' => count($seriesData),
                                    'seriesData_preview' => array_slice($seriesData, 0, 5),
                                ]);
                                $fetchedSeries[$key] = $seriesData;
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error('[DM_TEST] Exception fetching series', [
                                    'key' => $key,
                                    'channel' => $channel,
                                    'metric' => $metric,
                                    'exception' => get_class($e),
                                    'message' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                ]);
                                $fetchedSeries[$key] = [];
                            }
                        }

                        \Illuminate\Support\Facades\Log::debug('[DM_TEST] All series fetched', [
                            'fetchedSeries' => array_map(fn ($v) => ['count' => count($v), 'preview' => array_slice($v, 0, 3)], $fetchedSeries),
                        ]);

                        $computePayload = [
                            'ast' => $record->ast,
                            'filters' => [
                                'startDate' => $dateStart,
                                'endDate' => $dateEnd,
                                'period' => $granularity,
                                'groupBy' => [$granularity],
                            ],
                            'series_data' => $fetchedSeries,
                            'derived_metrics' => [],
                        ];

                        \Illuminate\Support\Facades\Log::debug('[DM_TEST] Calling computeKpi', [
                            'payload_keys' => array_keys($computePayload),
                            'series_data_keys' => array_keys($fetchedSeries),
                            'series_data_counts' => array_map(fn ($v) => count($v), $fetchedSeries),
                        ]);

                        $remoteEngineService = app(\App\Services\RemoteEngineService::class);
                        $result = $remoteEngineService->computeKpi($project, $computePayload);

                        \Illuminate\Support\Facades\Log::debug('[DM_TEST] computeKpi result', [
                            'success' => $result['success'] ?? false,
                            'message' => $result['message'] ?? null,
                            'data_type' => gettype($result['data'] ?? null),
                            'data_keys' => is_array($result['data'] ?? null) ? array_keys($result['data']) : null,
                            'data_preview' => is_array($result['data'] ?? null) ? array_slice($result['data'], 0, 5) : $result['data'] ?? null,
                        ]);

                        if (isset($result['success']) && $result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Test Execution Successful'))
                                ->success()
                                ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">' . json_encode($result['data'] ?? [], JSON_PRETTY_PRINT) . '</pre>')
                                ->persistent()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Test Execution Failed'))
                                ->danger()
                                ->body($result['message'] ?? __('An unknown error occurred.'))
                                ->persistent()
                                ->send();
                        }
                    }),
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
