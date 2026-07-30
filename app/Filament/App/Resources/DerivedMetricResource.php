<?php

    namespace App\Filament\App\Resources;

    use App\Filament\App\Resources\DerivedMetricResource\Pages;
    use App\Filament\App\Resources\DerivedMetricResource\RelationManagers;
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

        public static function getNavigationLabel(): string
        {
            return __('Derived Metrics');
        }

        public static function getNavigationGroup(): ?string
        {
            return __('Exploration & Telemetry');
        }

        public static function canCreate(): bool
        {
            if (!auth()->user()->can('edit_preferences')) {
                return false;
            }

            $project = \Filament\Facades\Filament::getTenant();
            if (!$project || !$project->billingProfile) {
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
            $primaryClasses = $buttonClasses.' bg-primary-600 text-white hover:bg-primary-500 focus:ring-primary-500 ring-primary-600/20 dark:bg-primary-500 dark:text-white dark:hover:bg-primary-400 dark:focus:ring-primary-400 dark:ring-primary-500/20';
            $grayClasses = $buttonClasses.' bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500 ring-gray-300 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10 dark:ring-white/20';

            return $form
                ->schema([
                    Forms\Components\Hidden::make('_builder_step')->default('0_intent'),
                    Forms\Components\Hidden::make('_step_history')->default('[]'),

                    // ── Step 0: Intent ─────────────────────────────────────────
                    Forms\Components\Section::make(__('Choose Build Method'))
                        ->description(__('Start from scratch or use a predefined formula template.'))
                        ->schema([
                            Forms\Components\Radio::make('_intent')
                                ->label(__('How would you like to create this Derived Metric?'))
                                ->options([
                                    'template' => __('Use a predefined template'),
                                    'scratch'  => __('Build from scratch'),
                                ])
                                ->descriptions([
                                    'template' => __('Pick from common marketing formulas like CPC, CTR, ROAS, etc.'),
                                    'scratch'  => __('Define your own source series and custom formula.'),
                                ])
                                ->live(),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('next_intent')
                                    ->label(__('Next'))
                                    ->icon('heroicon-o-arrow-right')
                                    ->iconPosition('after')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $history[] = $get('_builder_step');
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', $get('_intent') === 'template' ? '1_template' : '2_series');
                                    })
                                    ->disabled(fn (Forms\Get $get) => empty($get('_intent'))),
                            ]),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('_builder_step') === '0_intent'),

                    // ── Step 1: Template Selection ─────────────────────────────
                    Forms\Components\Section::make(__('1. Select Template'))
                        ->description(__('Choose a predefined formula to auto-fill the form.'))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Group::make([
                                        Forms\Components\Select::make('category_filter')
                                            ->label(__('Filter by category'))
                                            ->multiple()
                                            ->options(self::getDerivedMetricCategoryOptions())
                                            ->live(),
                                        Forms\Components\Select::make('template')
                                            ->label(__('Predefined Formula'))
                                            ->allowHtml()
                                            ->searchable()
                                            ->options(fn (Forms\Get $get) => self::getDerivedMetricTemplateOptions($get('category_filter') ?? []))
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                if (! $state) {
                                                    return;
                                                }
                                                $dm = \App\Services\Analytics\PredefinedDerivedMetricRegistry::getPredefined()[$state] ?? null;
                                                if (! $dm) {
                                                    return;
                                                }

                                                if (empty($get('name'))) {
                                                    $set('name', $dm['name'] ?? '');
                                                }
                                                if (empty($get('description'))) {
                                                    $set('description', $dm['description'] ?? '');
                                                }
                                                $set('format', $dm['format'] ?? 'decimal');
                                                $set('output_granularity', $dm['output_granularity'] ?? '');

                                                $activeChannels = array_keys(\App\Services\Analytics\KpiFormBuilder::getActiveChannels());
                                                $registryTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();

                                                $resolveChannel = function (string $placeholder) use ($activeChannels, $registryTags): ?string {
                                                    preg_match('/__([A-Z_]+)_CHANNEL_(\d+)__/', $placeholder, $matches);
                                                    if (empty($matches[1])) {
                                                        return null;
                                                    }
                                                    $requiredTag = strtolower($matches[1]);
                                                    $index = (int) ($matches[2] ?? 1) - 1;
                                                    $matchingChannels = [];
                                                    foreach ($activeChannels as $channel) {
                                                        $tags = $registryTags[$channel] ?? [];
                                                        if (in_array($requiredTag, $tags)) {
                                                            $matchingChannels[] = $channel;
                                                        }
                                                    }
                                                    return $matchingChannels[$index] ?? $matchingChannels[0] ?? null;
                                                };

                                                $tplSeries = $dm['source_series'] ?? [];
                                                $resolvedSeries = [];
                                                $keyIndex = 0;
                                                foreach ($tplSeries as $tpl) {
                                                    $channel = $tpl['channel'] ?? '';
                                                    if (str_starts_with($channel, '__')) {
                                                        $channel = $resolveChannel($channel);
                                                    }
                                                    $key = chr(97 + $keyIndex);
                                                    $resolvedSeries[] = [
                                                        'key'          => $key,
                                                        'label'        => $tpl['label'] ?? ($channel ? str($channel)->replace('_', ' ')->title().' - '.str($tpl['metric'] ?? '')->replace('_', ' ')->title() : 'Series '.$key),
                                                        'channel'      => $channel ?? '',
                                                        'metric'       => $tpl['metric'] ?? '',
                                                        'granularity'  => $tpl['granularity'] ?? 'daily',
                                                        'asset_group'  => '',
                                                        'asset_filter' => [],
                                                    ];
                                                    $keyIndex++;
                                                }
                                                $set('source_series', $resolvedSeries);
                                                $set('ast', $dm['ast'] ?? []);
                                            }),
                                    ]),
                                    Forms\Components\Group::make([
                                        Forms\Components\Placeholder::make('template_details')
                                            ->hiddenLabel()
                                            ->content(function (Forms\Get $get) {
                                                $templateId = $get('template');
                                                if (! $templateId) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="h-full flex items-center justify-center p-6 text-gray-500 italic bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl">'
                                                        .__('Select a template to view its details.').'</div>'
                                                    );
                                                }
                                                $dms = \App\Services\Analytics\PredefinedDerivedMetricRegistry::getPredefined();
                                                $dm = $dms[$templateId] ?? null;
                                                if (! $dm) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="text-danger-600 dark:text-danger-400 p-4 bg-danger-50 dark:bg-danger-400/10 rounded-xl">'
                                                        .__('Template details not found.').'</div>'
                                                    );
                                                }

                                                $formatLabel = match ($dm['format'] ?? 'decimal') {
                                                    'percentage' => 'Percentage (%)',
                                                    'currency'   => 'Currency ($)',
                                                    default      => 'Decimal',
                                                };
                                                $categoriesText = implode(', ', array_map(fn ($c) => __(str($c)->replace('_', ' ')->title()->toString()), $dm['categories'] ?? []));

                                                $sourcePreview = '';
                                                foreach ($dm['source_series'] ?? [] as $s) {
                                                    $channelLabel = $s['channel'] ?? '?';
                                                    $metricLabel = str($s['metric'] ?? '?')->replace('_', ' ')->title();
                                                    $sourcePreview .= '<div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">'
                                                        .'<span class="font-mono text-xs text-primary-600 dark:text-primary-400">'.$s['key'].'</span>'
                                                        .'<span>—</span>'
                                                        .'<span>'.e($metricLabel).'</span>'
                                                        .'<span class="text-xs text-gray-400">('.e($channelLabel).')</span>'
                                                        .'</div>';
                                                }

                                                $astPreview = e(json_encode($dm['ast'] ?? [], JSON_PRETTY_PRINT));

                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="space-y-4 p-6 bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm h-full">'
                                                    .'<div><h3 class="text-lg font-semibold text-gray-950 dark:text-white">'.e($dm['name']).'</h3></div>'
                                                    .'<div><p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">'.e($dm['description'] ?? '').'</p></div>'
                                                    .'<div class="flex flex-wrap gap-2">'
                                                    .'<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">'.$formatLabel.'</span>'
                                                    .'</div>'
                                                    .'<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">'.__('Source Series').'</h4>'
                                                    .$sourcePreview.'</div>'
                                                    .'<div><h4 class="text-sm font-semibold text-gray-950 dark:text-white mb-1">'.__('Formula (AST)').'</h4>'
                                                    .'<pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-x-auto">'.$astPreview.'</pre></div>'
                                                    .'</div>'
                                                );
                                            }),
                                    ]),
                                ]),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('backFromTemplate')
                                    ->label(__('Back'))
                                    ->icon('heroicon-o-arrow-left')
                                    ->color('gray')
                                    ->extraAttributes(['class' => $grayClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $prevStep = array_pop($history) ?? '0_intent';
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', $prevStep);
                                    }),
                                Forms\Components\Actions\Action::make('nextToSeries')
                                    ->label(__('Next: Source Series'))
                                    ->icon('heroicon-o-arrow-right')
                                    ->iconPosition('after')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $history[] = $get('_builder_step');
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', '2_series');
                                    })
                                    ->disabled(fn (Forms\Get $get) => empty($get('template'))),
                            ]),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('_builder_step') === '1_template'),

                    // ── Step 2: Source Series ──────────────────────────────────
                    Forms\Components\Section::make(__('2. Source Series'))
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
                                        ->options(fn() => \App\Services\Analytics\KpiFormBuilder::getActiveChannels())
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set) {
                                            $set('metric', null);
                                            $set('asset_group', null);
                                            $set('asset_filter', null);
                                        }),
                                    Forms\Components\Select::make('metric')
                                        ->label(__('Metric'))
                                        ->options(fn(Forms\Get $get) => !empty($get('channel'))
                                            ? \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($get('channel'))
                                            : [])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                            if (empty($get('label')) && filled($get('channel')) && filled($get('metric'))) {
                                                $channelLabel = str($get('channel'))->replace('_', ' ')->title()->toString();
                                                $metricLabel = str($get('metric'))->replace('_', ' ')->title()->toString();
                                                $set('label', $channelLabel.' - '.$metricLabel);
                                            }
                                        }),
                                    Forms\Components\Select::make('granularity')
                                        ->label(__('Granularity'))
                                        ->options([
                                            'daily'     => __('Daily'),
                                            'weekly'    => __('Weekly'),
                                            'monthly'   => __('Monthly'),
                                            'quarterly' => __('Quarterly'),
                                            'annually'  => __('Annually'),
                                        ])
                                        ->default('daily'),
                                    Forms\Components\Select::make('asset_group')
                                        ->label(__('Asset Group'))
                                        ->options(fn() => \App\Services\Analytics\KpiFormBuilder::getAssetGroupOptions())
                                        ->disabled(fn(Forms\Get $get) => filled($get('asset_filter')))
                                        ->live(),
                                    Forms\Components\Select::make('asset_filter')
                                        ->label(__('Asset Filter'))
                                        ->multiple()
                                        ->options(fn(Forms\Get $get) => !empty($get('channel'))
                                            ? \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($get('channel'))
                                            : [])
                                        ->disabled(fn(Forms\Get $get) => filled($get('asset_group')))
                                        ->live(),
                                ])
                                ->columns(3)
                                ->defaultItems(2)
                                ->addActionLabel(__('Add Source Series'))
                                ->itemLabel(fn(array $state): ?string => $state['label'] ?? (isset($state['channel'], $state['metric']) ? str($state['channel'])->replace('_', ' ')->title().' - '.str($state['metric'])->replace('_', ' ')->title() : $state['channel'] ?? null)),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('nextToFormula')
                                    ->label(__('Next: Define Formula'))
                                    ->icon('heroicon-o-arrow-right')
                                    ->iconPosition('after')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $history[] = $get('_builder_step');
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', '3_formula');
                                    }),
                            ]),
                        ])
                        ->visible(fn(Forms\Get $get) => $get('_builder_step') === '2_series'),

                    // ── Step 3: Formula ───────────────────────────────────────
                    Forms\Components\Section::make(__('3. Formula'))
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
                                        'seriesData'   => $sd,
                                        'seriesKeys'   => $seriesKeys,
                                        'initialAst'   => $ast,
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
                                        $prevStep = array_pop($history) ?? '2_series';
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', $prevStep);
                                    }),
                                Forms\Components\Actions\Action::make('nextToDetails')
                                    ->label(__('Next: Details'))
                                    ->icon('heroicon-o-arrow-right')
                                    ->iconPosition('after')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $history[] = $get('_builder_step');
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', '4_details');
                                    }),
                            ]),
                        ])
                        ->visible(fn(Forms\Get $get) => $get('_builder_step') === '3_formula'),

                    // ── Step 4: Details ───────────────────────────────────────
                    Forms\Components\Section::make(__('4. Details'))
                        ->description(__('Name your derived metric and configure output settings.'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->nullable()
                                ->rows(3),
                            Forms\Components\Select::make('format')
                                ->label(__('Value Format'))
                                ->options([
                                    'decimal'    => __('Decimal'),
                                    'percentage' => __('Percentage (%)'),
                                    'currency'   => __('Currency ($)'),
                                ])
                                ->default('decimal')
                                ->helperText(__('Percentage multiplies values by 100 and displays them with % formatting. Currency prefixes values with $.')),
                            Forms\Components\Select::make('output_granularity')
                                ->label(__('Output Granularity'))
                                ->options([
                                    ''          => __('Dynamic (user selects at widget level)'),
                                    'daily'     => __('Daily'),
                                    'weekly'    => __('Weekly'),
                                    'monthly'   => __('Monthly'),
                                    'quarterly' => __('Quarterly'),
                                    'annually'  => __('Annually'),
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
                                        $prevStep = array_pop($history) ?? '3_formula';
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', $prevStep);
                                    }),
                                Forms\Components\Actions\Action::make('nextToSummary')
                                    ->label(__('Next: Summary'))
                                    ->icon('heroicon-o-arrow-right')
                                    ->iconPosition('after')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $history[] = $get('_builder_step');
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', '5_summary');
                                    }),
                            ]),
                        ])
                        ->columns(2)
                        ->visible(fn(Forms\Get $get) => $get('_builder_step') === '4_details'),

                    // ── Step 5: Summary ──────────────────────────────────────
                    Forms\Components\Section::make(__('5. Summary'))
                        ->description(__('Review your Derived Metric before saving.'))
                        ->schema([
                            Forms\Components\Placeholder::make('_summary')
                                ->label(false)
                                ->content(function (Forms\Get $get) {
                                    $html = '<div class="space-y-6">';
                                    $html .= '<div class="grid grid-cols-2 gap-4">';
                                    $html .= '<div><strong>'.__('Name').':</strong> '.e($get('name') ?? '—').'</div>';
                                    $html .= '<div><strong>'.__('Status').':</strong> '.($get('is_active') ? __('Active') : __('Inactive')).'</div>';
                                    $html .= '</div>';
                                    $html .= '<div><strong>'.__('Description').':</strong> '.e($get('description') ?? '—').'</div>';
                                    $fmt = $get('format');
                                    $formatLabel = $fmt === 'percentage' ? 'Percentage (%)' : ($fmt === 'currency' ? 'Currency ($)' : ($fmt === 'decimal' ? 'Decimal' : '—'));
                                    $html .= '<div><strong>'.__('Output Granularity').':</strong> '.__($get('output_granularity') ?: 'Dynamic (user selects at widget level)').'</div>';
                                    $html .= '<div><strong>'.__('Value Format').':</strong> '.e($formatLabel).'</div>';
                                    $series = $get('source_series') ?? [];
                                    if (is_array($series) && count($series)) {
                                        $html .= '<div><strong>'.__('Source Series').':</strong><table class="table-auto w-full mt-1"><thead><tr class="text-left"><th class="pr-4">'.__('Key').'</th><th class="pr-4">'.__('Label').'</th><th class="pr-4">'.__('Channel').'</th><th class="pr-4">'.__('Metric').'</th><th>'.__('Granularity').'</th></tr></thead><tbody>';
                                        $i = 0;
                                        foreach (array_values($series) as $s) {
                                            $key = $s['key'] ?? chr(97 + $i);
                                            $html .= '<tr><td class="pr-4">'.e($key).'</td><td class="pr-4">'.e($s['label'] ?? '').'</td><td class="pr-4">'.e($s['channel'] ?? '').'</td><td class="pr-4">'.e($s['metric'] ?? '').'</td><td>'.e($s['granularity'] ?? 'daily').'</td></tr>';
                                            $i++;
                                        }
                                        $html .= '</tbody></table></div>';
                                    }
                                    $ast = $get('ast');
                                    if (is_string($ast)) {
                                        $ast = json_decode($ast, true);
                                    }
                                    if (!empty($ast)) {
                                        $html .= '<div><strong>'.__('Formula (AST)').':</strong><pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-x-auto">'.e(json_encode($ast, JSON_PRETTY_PRINT)).'</pre></div>';
                                    }
                                    $html .= '</div>';

                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('backToDetails')
                                    ->label(__('Back: Details'))
                                    ->icon('heroicon-o-arrow-left')
                                    ->color('gray')
                                    ->extraAttributes(['class' => $grayClasses])
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        $history = json_decode($get('_step_history') ?? '[]', true) ?: [];
                                        $prevStep = array_pop($history) ?? '4_details';
                                        $set('_step_history', json_encode($history));
                                        $set('_builder_step', $prevStep);
                                    }),
                                Forms\Components\Actions\Action::make('createDerivedMetric')
                                    ->label(fn() => $isEdit ? __('Save Changes') : __('Create Derived Metric'))
                                    ->icon('heroicon-o-check-circle')
                                    ->color('primary')
                                    ->extraAttributes(['class' => $primaryClasses.' fi-btn-create'])
                                    ->requiresConfirmation()
                                    ->modalHeading(fn() => $isEdit ? __('Save Changes') : __('Create Derived Metric'))
                                    ->modalDescription(fn() => $isEdit ? __('Are you sure you want to save changes to this Derived Metric?') : __('Are you sure you want to create this Derived Metric?'))
                                    ->modalSubmitActionLabel(fn() => $isEdit ? __('Save Changes') : __('Create'))
                                    ->modalCancelActionLabel(__('Cancel'))
                                    ->submit($isEdit ? 'save' : 'create')
                                    ->visible(fn() => $isEdit ? auth()->user()->can('edit_preferences') : true),
                            ]),
                        ])
                        ->visible(fn(Forms\Get $get) => $get('_builder_step') === '5_summary'),
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
                        ->formatStateUsing(fn($state) => $state ?: __('Dynamic'))
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
                                .'<div><strong>'.__('Formula (AST)').':</strong><pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">'.$astJson.'</pre></div>'
                                .'<div><strong>'.__('Source Series').':</strong><pre style="background: #1f2937; color: #60a5fa; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">'.$seriesJson.'</pre></div>'
                                .'<div><strong>'.__('Source Keys').':</strong> '.implode(', ', $sourceKeys).'</div>'
                                .'</div>'
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
                                        'daily'   => __('Daily'),
                                        'weekly'  => __('Weekly'),
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
                                $hasAssetFilter = !empty($series['asset_filter']) && is_array($series['asset_filter']);
                                if ($hasAssetFilter) {
                                    continue;
                                }
                                $seriesLabel = $series['label'] ?? $series['metric'] ?? $key;
                                $channelLabel = \App\Services\Analytics\KpiFormBuilder::getActiveChannels()[$channel] ?? $channel;
                                $runtimeAssetFields[] = Forms\Components\Select::make("runtime_asset_{$key}")
                                    ->label(__('Asset for :series (:channel)', ['series' => $seriesLabel, 'channel' => $channelLabel]))
                                    ->options(fn() => \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel));
                            }
                            if (!empty($runtimeAssetFields)) {
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
                                if (!empty($assetFilter) && is_array($assetFilter)) {
                                    $validAssets = $widgetDataController->getValidAssetsForChannel($project, $channel);
                                    $filtered = array_intersect($assetFilter, $validAssets);
                                    $extractedAssets = !empty($filtered) ? array_values($filtered) : null;
                                } else {
                                    $runtimeAsset = $data["runtime_asset_{$key}"] ?? null;
                                    if (!empty($runtimeAsset)) {
                                        $extractedAssets = is_array($runtimeAsset) ? $runtimeAsset : [$runtimeAsset];
                                    }
                                }

                                $payload = [
                                    'tenant'      => $project->id,
                                    'account'     => $extractedAssets,
                                    'dateStart'   => $dateStart,
                                    'dateEnd'     => $dateEnd,
                                    'granularity' => $series['granularity'] ?? $granularity,
                                    'metrics'     => [$metric],
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
                                'ast'             => $record->ast,
                                'filters'         => [
                                    'startDate' => $dateStart,
                                    'endDate'   => $dateEnd,
                                    'period'    => $granularity,
                                    'groupBy'   => [$granularity],
                                ],
                                'series_data'     => $fetchedSeries,
                                'derived_metrics' => [],
                            ];

                            $remoteEngineService = app(\App\Services\RemoteEngineService::class);
                            $result = $remoteEngineService->computeKpi($project, $computePayload);

                            if (isset($result['success']) && $result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Test Execution Successful'))
                                    ->success()
                                    ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">'.json_encode($result['data'] ?? [], JSON_PRETTY_PRINT).'</pre>')
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
                            $replica->name = $replica->name.' (copy)';
                        })
                        ->visible(fn() => auth()->user()->can('edit_preferences')),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('clearCache')
                        ->label(__('Clear Cache'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (DerivedMetric $record) {
                            app(\App\Services\DerivedMetricCacheService::class)->invalidateCache($record->id);
                            \Filament\Notifications\Notification::make()
                                ->title(__('Cache cleared successfully'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => auth()->user()->can('edit_preferences')),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => auth()->user()->can('edit_preferences')),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\BulkAction::make('clearCache')
                            ->label(__('Clear Cache'))
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $service = app(\App\Services\DerivedMetricCacheService::class);
                                foreach ($records as $record) {
                                    $service->invalidateCache($record->id);
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Cache cleared for selected Derived Metrics'))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn() => auth()->user()->can('edit_preferences')),
                        Tables\Actions\DeleteBulkAction::make()
                            ->visible(fn() => auth()->user()->can('edit_preferences')),
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
                                    $record->versions()
                                        ->where('created_at', '<', $cutoff)
                                        ->where('version_number', '>', 1)
                                        ->delete();
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Old versions pruned successfully'))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn() => auth()->user()->can('edit_preferences')),
                    ]),
        ]);
    }

        public static function getDerivedMetricCategoryOptions(): array
        {
            return [
                'paid_media'   => __('Paid Media'),
                'organic'      => __('Organic Social'),
                'seo'          => __('SEO / Search'),
                'cross-channel' => __('Cross-Channel'),
                'cost'         => __('Cost'),
                'performance'  => __('Performance'),
                'results'      => __('Results'),
                'engagement'   => __('Engagement'),
                'revenue'      => __('Revenue'),
                'reach'        => __('Reach'),
                'clicks'       => __('Clicks'),
                'impressions'  => __('Impressions'),
                'budget'       => __('Budget'),
            ];
        }

        public static function getDerivedMetricTemplateOptions(array $categoryFilter = []): array
        {
            $activeChannels = array_keys(\App\Services\Analytics\KpiFormBuilder::getActiveChannels());
            $available = \App\Services\Analytics\PredefinedDerivedMetricRegistry::getAvailable($activeChannels);
            $options = [];

            foreach ($available as $key => $dm) {
                $cats = $dm['categories'] ?? [];
                if (! empty($categoryFilter)) {
                    $intersection = array_intersect($categoryFilter, $cats);
                    if (count($intersection) !== count($categoryFilter)) {
                        continue;
                    }
                }

                $metrics = array_map(
                    fn ($s) => str($s['metric'] ?? '?')->replace('_', ' ')->title()->toString(),
                    $dm['source_series'] ?? []
                );

                $formulaPreview = implode(' / ', array_slice($metrics, 0, 3));
                if (count($metrics) > 3) {
                    $formulaPreview .= ' …';
                }

                $options[$key] = '<div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-gray-100">'.e($dm['name']).'</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">'.e($formulaPreview).'</span>
                </div>';
            }

            $names = [];
            foreach ($available as $key => $dm) {
                if (isset($options[$key])) {
                    $names[$key] = $dm['name'];
                }
            }
            asort($names);

            $sorted = [];
            foreach ($names as $key => $name) {
                $sorted[$key] = $options[$key];
            }

            return $sorted;
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
                'index'  => Pages\ListDerivedMetrics::route('/'),
                'create' => Pages\CreateDerivedMetric::route('/create'),
                'edit'   => Pages\EditDerivedMetric::route('/{record}/edit'),
            ];
        }
    }
