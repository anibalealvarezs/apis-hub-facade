<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AlertResource\Pages;
use App\Filament\App\Resources\AlertResource\RelationManagers;
use App\Models\Alert;
use App\Models\CustomKpi;
use App\Models\DerivedMetric;
use App\Services\AlertService;
use App\Services\BillingLifecycleService;
use App\Services\DeployerService;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    public static function getNavigationLabel(): string
    {
        return __('Alerts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Analytics');
    }

    public static function canAccess(): bool
    {
        $project = \Filament\Facades\Filament::getTenant();
        if (!$project || !$project->supportsAlerts()) {
            return false;
        }
        return auth()->user()->can('edit_preferences');
    }

    public static function canCreate(): bool
    {
        if (!auth()->user()->can('edit_preferences')) {
            return false;
        }

        $project = \Filament\Facades\Filament::getTenant();
        if (!$project || !$project->supportsAlerts() || !$project->billingProfile) {
            return false;
        }

        $currentLines = app(AlertService::class)->countTotalCalculationLines($project);
        $maxLines = app(BillingLifecycleService::class)
            ->getMaxAlertCalculationsForTier($project->billingProfile->tier);

        return $currentLines < $maxLines;
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
        $alertService = app(AlertService::class);

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make(__('Basic Information'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('Alert Name'))
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description')
                                ->label(__('Description'))
                                ->nullable()
                                ->maxLength(1000),

                            Forms\Components\Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                        ]),

                    Wizard\Step::make(__('Source Metric/KPI'))
                        ->schema([
                            Forms\Components\Select::make('source_type')
                                ->label(__('Source Type'))
                                ->options([
                                    'metric' => __('Standard Channel Metric'),
                                    'kpi' => __('Custom KPI'),
                                    'derived_metric' => __('Derived Metric'),
                                ])
                                ->required()
                                ->reactive(),

                            Forms\Components\Group::make([
                                Forms\Components\Select::make('source_config.channel')
                                    ->label(__('Channel'))
                                    ->options(function () {
                                        $active = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
                                        if (empty($active)) {
                                            $allTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
                                            foreach (array_keys($allTags) as $ch) {
                                                $active[$ch] = \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($ch);
                                            }
                                        }
                                        return $active;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('source_config.metric', null))
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'metric'),

                                Forms\Components\Select::make('source_config.metric')
                                    ->label(__('Metric'))
                                    ->options(function (Forms\Get $get) {
                                        $channel = $get('source_config.channel');
                                        if (empty($channel)) {
                                            return [];
                                        }
                                        return \App\Services\Analytics\KpiFormBuilder::getMetricOptionsForChannel($channel);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'metric'),

                                Forms\Components\Select::make('source_config.kpi_id')
                                    ->label(__('Custom KPI'))
                                    ->options(fn () => CustomKpi::where('project_id', $project?->id)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $kpi = CustomKpi::find($state);
                                            if ($kpi && !empty($kpi->unit)) {
                                                $set('unit', $kpi->unit);
                                            }
                                            if ($kpi && !empty($kpi->filters['calculation_type']) && $kpi->filters['calculation_type'] === 'calculate_regression') {
                                                $set('source_config.target_attribute', 'r_squared');
                                                $set('unit', 'percentage');
                                            }
                                        }
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'kpi'),

                                Forms\Components\Select::make('source_config.target_attribute')
                                    ->label(__('Statistical Target Metric'))
                                    ->options([
                                        'r_squared' => __('Model Fit Quality (R² / Predictability)'),
                                        'slope' => __('Regression Slope (Efficiency / Coefficient β)'),
                                        'intercept' => __('Baseline Intercept (α)'),
                                    ])
                                    ->default('r_squared')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state === 'r_squared') {
                                            $set('unit', 'percentage');
                                        } else {
                                            $set('unit', 'number');
                                        }
                                    })
                                    ->visible(function (Forms\Get $get) {
                                        if ($get('source_type') !== 'kpi') {
                                            return false;
                                        }
                                        $kpiId = $get('source_config.kpi_id');
                                        if (!$kpiId) {
                                            return false;
                                        }
                                        $kpi = CustomKpi::find($kpiId);
                                        return $kpi && !empty($kpi->filters['calculation_type']) && $kpi->filters['calculation_type'] === 'calculate_regression';
                                    }),

                                Forms\Components\Select::make('source_config.dm_id')
                                    ->label(__('Derived Metric'))
                                    ->options(fn () => DerivedMetric::where('project_id', $project?->id)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'derived_metric'),
                            ]),
                        ]),

                    Wizard\Step::make(__('Calculation Lines'))
                        ->schema([
                            Forms\Components\Placeholder::make('calculation_lines_explanation')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-gray-500 dark:text-gray-400 mb-2">' .
                                    e(__('Define which accounts or assets will be evaluated under this alert rule. Each line represents 1 calculation.')) .
                                    '</div>'
                                )),

                            Forms\Components\Repeater::make('calculationLines')
                                ->relationship('calculationLines')
                                ->schema([
                                    Forms\Components\Select::make('target_asset_platform_id')
                                        ->label(__('Target Asset'))
                                        ->options(function (Forms\Get $get) {
                                            $channel = $get('../../source_config.channel');
                                            $options = ['all' => __('All Assets Combined')];
                                            if ($channel) {
                                                $assets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($channel);
                                                foreach ($assets as $id => $info) {
                                                    $options[(string) $id] = $info['name'] ?? $id;
                                                }
                                            } else {
                                                $activeChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
                                                foreach (array_keys($activeChannels) as $ch) {
                                                    $assets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($ch);
                                                    foreach ($assets as $id => $info) {
                                                        $options[(string) $id] = ($info['name'] ?? $id) . ' (' . \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($ch) . ')';
                                                    }
                                                }
                                            }
                                            return $options;
                                        })
                                        ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\AlertCalculationLine $record, $state) {
                                            if ($record && isset($record->asset_filter['asset_platform_id'])) {
                                                $component->state((string) $record->asset_filter['asset_platform_id']);
                                            } elseif (!empty($state)) {
                                                $component->state((string) $state);
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $channel = $get('../../source_config.channel');
                                            if ($state === 'all' || empty($state)) {
                                                $set('label', __('All Assets Combined'));
                                            } else {
                                                $assets = $channel ? \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($channel) : [];
                                                if (empty($assets)) {
                                                    $activeChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
                                                    foreach (array_keys($activeChannels) as $ch) {
                                                        $chAssets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($ch);
                                                        if (isset($chAssets[$state])) {
                                                            $assets = $chAssets;
                                                            break;
                                                        }
                                                    }
                                                }
                                                $assetName = $assets[$state]['name'] ?? $state;
                                                $set('label', $assetName);
                                            }
                                        }),

                                    Forms\Components\TextInput::make('label')
                                        ->label(__('Line Label'))
                                        ->default(__('All Assets Combined'))
                                        ->required(),
                                ])
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? __('Calculation Line'))
                                ->minItems(1)
                                ->columns(2)
                                ->default([
                                    ['label' => __('All Assets Combined'), 'target_asset_platform_id' => 'all'],
                                ]),
                        ]),

                    Wizard\Step::make(__('Thresholds & Calibration'))
                        ->schema([
                            Forms\Components\View::make('filament.app.forms.components.threshold-calibrator')
                                ->columnSpanFull(),

                            Forms\Components\Select::make('aggregation_method')
                                ->label(__('Measurement Aggregation Method'))
                                ->options([
                                    'latest' => __('Latest Value'),
                                    'sum' => __('Sum over Window'),
                                    'avg' => __('Average over Window'),
                                    'min' => __('Minimum over Window'),
                                    'max' => __('Maximum over Window'),
                                ])
                                ->default('latest')
                                ->required(),

                            Forms\Components\Select::make('unit')
                                ->label(__('Threshold Unit'))
                                ->options([
                                    'number' => __('Numeric (Standard)'),
                                    'percentage' => __('Percentage (%)'),
                                    'currency' => __('Currency ($)'),
                                ])
                                ->default('number')
                                ->live()
                                ->required(),

                            Forms\Components\TextInput::make('upper_limit')
                                ->label(__('Upper Limit (Trigger if value > limit)'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->nullable()
                                ->suffix(fn (Forms\Get $get) => match ($get('unit')) {
                                    'percentage' => '%',
                                    'currency' => '$',
                                    default => __('Units'),
                                })
                                ->prefix(fn (Forms\Get $get) => $get('unit') === 'currency' ? '$' : null),

                            Forms\Components\TextInput::make('lower_limit')
                                ->label(__('Lower Limit (Trigger if value < limit)'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->nullable()
                                ->suffix(fn (Forms\Get $get) => match ($get('unit')) {
                                    'percentage' => '%',
                                    'currency' => '$',
                                    default => __('Units'),
                                })
                                ->prefix(fn (Forms\Get $get) => $get('unit') === 'currency' ? '$' : null),
                        ]),

                    Wizard\Step::make(__('Schedule'))
                        ->schema([
                            Forms\Components\Select::make('schedule_type')
                                ->label(__('Schedule Interval'))
                                ->options([
                                    'daily' => __('Daily'),
                                    'weekly' => __('Weekly'),
                                    'biweekly' => __('Bi-weekly'),
                                    'monthly' => __('Monthly'),
                                    'once' => __('One-time'),
                                ])
                                ->default('daily')
                                ->required()
                                ->reactive(),

                            Forms\Components\TimePicker::make('schedule_config.time')
                                ->label(__('Evaluation Time'))
                                ->default('08:00')
                                ->required()
                                ->reactive(),

                            Forms\Components\Select::make('schedule_config.day_of_week')
                                ->label(__('Day of Week'))
                                ->options([
                                    1 => __('Monday'),
                                    2 => __('Tuesday'),
                                    3 => __('Wednesday'),
                                    4 => __('Thursday'),
                                    5 => __('Friday'),
                                    6 => __('Saturday'),
                                    0 => __('Sunday'),
                                ])
                                ->visible(fn (Forms\Get $get) => in_array($get('schedule_type'), ['weekly', 'biweekly'])),

                            Forms\Components\CheckboxList::make('schedule_config.days_of_month')
                                ->label(__('Days of Month'))
                                ->options(array_combine(range(1, 28), range(1, 28)))
                                ->columns(7)
                                ->visible(fn (Forms\Get $get) => $get('schedule_type') === 'monthly'),

                            Forms\Components\DatePicker::make('schedule_config.date')
                                ->label(__('Execution Date'))
                                ->visible(fn (Forms\Get $get) => $get('schedule_type') === 'once'),

                            Forms\Components\Placeholder::make('sync_warning')
                                ->label('')
                                ->content(function (Forms\Get $get) use ($project, $alertService) {
                                    $time = $get('schedule_config.time');
                                    if (!$time || !$project) {
                                        return null;
                                    }
                                    $warning = $alertService->getSyncWindowWarning($project, (string) $time);
                                    if ($warning) {
                                        return new HtmlString("<div class='p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg text-amber-600 dark:text-amber-400 text-xs font-medium'>⚠️ {$warning}</div>");
                                    }
                                    return null;
                                }),
                        ]),

                    Wizard\Step::make(__('Notifications'))
                        ->schema([
                            Forms\Components\Toggle::make('notify_ui')
                                ->label(__('In-App Notifications'))
                                ->default(true),

                            Forms\Components\Toggle::make('notify_email')
                                ->label(__('Email Notifications'))
                                ->default(false),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Alert Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('Source'))
                    ->formatStateUsing(fn ($record) => app(AlertService::class)->buildAlertSummary($record))
                    ->sortable(),

                Tables\Columns\TextColumn::make('limits')
                    ->label(__('Thresholds'))
                    ->getStateUsing(function (Alert $record) {
                        $alertService = app(AlertService::class);
                        $upper = $record->upper_limit !== null ? ('> ' . $alertService->formatMetricValue($record->upper_limit, $record->unit)) : null;
                        $lower = $record->lower_limit !== null ? ('< ' . $alertService->formatMetricValue($record->lower_limit, $record->unit)) : null;
                        return implode(' | ', array_filter([$upper, $lower]));
                    })
                    ->placeholder(__('None')),

                Tables\Columns\TextColumn::make('schedule_type')
                    ->label(__('Schedule'))
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_evaluation_at')
                    ->label(__('Next Eval'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('Active'))
                    ->afterStateUpdated(function ($record, $state) {
                        if ($record->project) {
                            app(\App\Services\DeployerService::class)->syncAlertConfig($record->project);
                        }
                    })
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('evaluate')
                    ->label(__('Evaluate Now'))
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function (Alert $record) {
                        $result = app(DeployerService::class)->evaluateAlert($record);
                        if ($result['success']) {
                            $output = $result['output'] ?? '';
                            $alertService = app(AlertService::class);
                            $upper = $record->upper_limit !== null ? (float)$record->upper_limit : null;
                            $lower = $record->lower_limit !== null ? (float)$record->lower_limit : null;

                            // Highlight colored arrows in CLI output
                            $colorGreen = '#10b981';
                            $colorRed = '#ef4444';

                            $isHigherGood = $alertService->isHigherBetter($record);
                            $upColor = $isHigherGood ? $colorGreen : $colorRed;
                            $downColor = $isHigherGood ? $colorRed : $colorGreen;

                            $formattedHtml = nl2br(e($output));
                            $formattedHtml = str_replace('⚠️ TRIGGERED: Upper limit', "<strong style='color: {$upColor};'>▲ TRIGGERED: Upper limit</strong>", $formattedHtml);
                            $formattedHtml = str_replace('⚠️ TRIGGERED: Lower limit', "<strong style='color: {$downColor};'>▼ TRIGGERED: Lower limit</strong>", $formattedHtml);
                            $formattedHtml = str_replace('✅ Status: OK', "<strong style='color: {$colorGreen};'>✅ Status: OK</strong>", $formattedHtml);

                            Notification::make()
                                ->title(new \Illuminate\Support\HtmlString("<span style='font-weight: 700;'>" . __('Alert Evaluation Complete') . "</span>"))
                                ->body(new \Illuminate\Support\HtmlString($formattedHtml))
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Evaluation Failed'))
                                ->body($result['message'] ?? __('Failed to execute alert evaluation.'))
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ReplicateAction::make()
                    ->label(__('Duplicate'))
                    ->icon('heroicon-o-square-2-stack')
                    ->beforeReplicaSaved(function (Alert $replica): void {
                        $replica->is_active = false;
                        $replica->name = $replica->name . ' (' . __('Copy') . ')';
                    })
                    ->after(function (Alert $replica, Alert $record): void {
                        foreach ($record->calculationLines as $line) {
                            $replica->calculationLines()->create([
                                'label' => $line->label,
                                'asset_filter' => $line->asset_filter,
                                'sort_order' => $line->sort_order,
                            ]);
                        }
                        if ($replica->project) {
                            app(DeployerService::class)->syncAlertConfig($replica->project);
                        }
                    }),
                Tables\Actions\Action::make('sync')
                    ->label(__('Sync Now'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('secondary')
                    ->action(function (Alert $record) {
                        $synced = app(\App\Services\DeployerService::class)->syncAlertConfig($record->project);
                        if ($synced) {
                            Notification::make()->title(__('Alert configurations synchronized to tenant server!'))->success()->send();
                        } else {
                            Notification::make()->title(__('Sync failed. Please check server connectivity.'))->danger()->send();
                        }
                    }),
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
            RelationManagers\CalculationLinesRelationManager::class,
            RelationManagers\AlertLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
            'create' => Pages\CreateAlert::route('/create'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
