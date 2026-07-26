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
        $isEdit = $form->getLivewire() instanceof \Filament\Resources\Pages\EditRecord;

        $buttonClasses = 'fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold shadow-sm ring-1 transition duration-75 focus:outline-none disabled:pointer-events-none disabled:opacity-70';
        $primaryClasses = $buttonClasses . ' bg-primary-600 text-white hover:bg-primary-500 focus:ring-primary-500 ring-primary-600/20 dark:bg-primary-500 dark:text-white dark:hover:bg-primary-400 dark:focus:ring-primary-400 dark:ring-primary-500/20';
        $grayClasses = $buttonClasses . ' bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500 ring-gray-300 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10 dark:ring-white/20';

        return $form
            ->schema([
                Forms\Components\Hidden::make('_builder_step')->default('1_series'),
                Forms\Components\Hidden::make('_step_history')->default('[]'),

                // ── Step 1: Source Series ──────────────────────────────────
                Forms\Components\Section::make(__('Source Series'))
                    ->description(__('Define the time series inputs for your formula.'))
                    ->schema([
                        Forms\Components\Repeater::make('source_series')
                            ->label(__('Add at least two series to create a formula'))
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
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        if (empty($get('label')) && filled($get('channel')) && filled($get('metric'))) {
                                            $channelLabel = str($get('channel'))->replace('_', ' ')->title()->toString();
                                            $metricLabel = str($get('metric'))->replace('_', ' ')->title()->toString();
                                            $set('label', $channelLabel . ' - ' . $metricLabel);
                                        }
                                    }),
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
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? (isset($state['channel'], $state['metric']) ? str($state['channel'])->replace('_', ' ')->title() . ' - ' . str($state['metric'])->replace('_', ' ')->title() : $state['channel'] ?? null)),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('nextToFormula')
                                ->label(__('Next: Define Formula'))
                                ->icon('heroicon-o-arrow-right')
                                ->iconPosition('after')
                                ->color('primary')
                                ->extraAttributes(['class' => $primaryClasses])
                                ->requiresConfirmation()
                                ->modalHeading(__('Define Formula'))
                                ->modalDescription(__('Proceed to the formula editor where you can define how your source series are combined.'))
                                ->modalSubmitActionLabel(__('Continue'))
                                ->modalCancelActionLabel(__('Go Back'))
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                    $history[] = $get('_builder_step');
                                    $set('_step_history', json_encode($history));
                                    $set('_builder_step', '2_formula');
                                }),
                        ]),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('_builder_step') === '1_series'),

                // ── Step 2: Formula ───────────────────────────────────────
                Forms\Components\Section::make(__('Formula'))
                    ->description(__('Define how your source series are combined.'))
                    ->schema([
                        Forms\Components\Hidden::make('ast'),
                        Forms\Components\ViewField::make('_formula_editor')
                            ->label(__('Build Formula'))
                            ->view('filament.app.components.formula-editor')
                            ->viewData(function (Forms\Get $get): array {
                                $sd = $get('source_series') ?? [];
                                $sd = is_array($sd) ? array_values($sd) : [];
                                $ast = $get('ast');
                                $seriesKeys = [];
                                foreach ($sd as $i => $s) {
                                    $seriesKeys[] = $s['key'] ?? chr(97 + $i);
                                }
                                return [
                                    'seriesData' => $sd,
                                    'seriesKeys' => $seriesKeys,
                                    'initialAst' => $ast,
                                    'astStatePath' => 'data.ast',
                                ];
                            })
                            ->helperText(__('Use source series keys (a, b, c…) and operators to define the formula. Click "Refresh keys" after adding/removing series.')),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('backToSeries')
                                ->label(__('Back: Source Series'))
                                ->icon('heroicon-o-arrow-left')
                                ->color('gray')
                                ->extraAttributes(['class' => $grayClasses])
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                    $prevStep = array_pop($history) ?? '1_series';
                                    $set('_step_history', json_encode($history));
                                    $set('_builder_step', $prevStep);
                                }),
                            Forms\Components\Actions\Action::make('nextToDetails')
                                ->label(__('Next: Details'))
                                ->icon('heroicon-o-arrow-right')
                                ->iconPosition('after')
                                ->color('primary')
                                ->extraAttributes(['class' => $primaryClasses])
                                ->requiresConfirmation()
                                ->modalHeading(__('Add Details'))
                                ->modalDescription(__('Proceed to add a name, description, and output granularity.'))
                                ->modalSubmitActionLabel(__('Continue'))
                                ->modalCancelActionLabel(__('Go Back'))
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                    $history[] = $get('_builder_step');
                                    $set('_step_history', json_encode($history));
                                    $set('_builder_step', '3_details');
                                }),
                        ]),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('_builder_step') === '2_formula'),

                // ── Step 3: Details ───────────────────────────────────────
                Forms\Components\Section::make(__('Details'))
                    ->description(__('Name your derived metric and configure output settings.'))
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
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('backToFormula')
                                ->label(__('Back: Formula'))
                                ->icon('heroicon-o-arrow-left')
                                ->color('gray')
                                ->extraAttributes(['class' => $grayClasses])
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                    $prevStep = array_pop($history) ?? '2_formula';
                                    $set('_step_history', json_encode($history));
                                    $set('_builder_step', $prevStep);
                                }),
                            Forms\Components\Actions\Action::make('createDerivedMetric')
                                ->label(fn () => $isEdit ? __('Save Changes') : __('Create Derived Metric'))
                                ->icon('heroicon-o-check-circle')
                                ->color('primary')
                                ->extraAttributes(['class' => $primaryClasses . ' fi-btn-create'])
                                ->submit($isEdit ? 'save' : 'create')
                                ->visible(fn () => $isEdit ? auth()->user()->can('edit_preferences') : true),
                        ]),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('_builder_step') === '3_details'),
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

                        $widgetDataController = new \App\Http\Controllers\Api\DashboardWidgetDataController(
                            app(\App\Services\WidgetDataService::class),
                            app(\App\Services\RemoteEngineService::class)
                        );

                        $fetchedSeries = [];
                        foreach ($sourceSeries as $series) {
                            $key = $series['key'];
                            $channel = $series['channel'] ?? null;
                            $metric = $series['metric'] ?? null;

                            if (empty($channel) || empty($metric)) {
                                $fetchedSeries[$key] = [];
                                continue;
                            }

                            $assetFilter = $series['asset_filter'] ?? null;
                            $extractedAssets = null;
                            if (! empty($assetFilter) && is_array($assetFilter)) {
                                $validAssets = $widgetDataController->getValidAssetsForChannel($project, $channel);
                                $filtered = array_intersect($assetFilter, $validAssets);
                                $extractedAssets = ! empty($filtered) ? array_values($filtered) : null;
                            } else {
                                $runtimeAsset = $data["runtime_asset_{$key}"] ?? null;
                                if (! empty($runtimeAsset)) {
                                    $extractedAssets = is_array($runtimeAsset) ? $runtimeAsset : [$runtimeAsset];
                                }
                            }

                            $payload = [
                                'tenant' => $project->id,
                                'account' => $extractedAssets,
                                'dateStart' => $dateStart,
                                'dateEnd' => $dateEnd,
                                'granularity' => $series['granularity'] ?? $granularity,
                                'metrics' => [$metric],
                            ];

                            try {
                                $channelResponse = $widgetDataController->forwardToChannelEndpoint($channel, 'chart', $payload);
                                $seriesData = $widgetDataController->extractTimeSeriesFromResponse($channelResponse, $metric);
                                $fetchedSeries[$key] = $seriesData;
                            } catch (\Throwable $e) {
                                $fetchedSeries[$key] = [];
                            }
                        }

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

                        $remoteEngineService = app(\App\Services\RemoteEngineService::class);
                        $result = $remoteEngineService->computeKpi($project, $computePayload);

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
