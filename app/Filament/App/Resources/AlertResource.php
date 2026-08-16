<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AlertResource\Pages;
use App\Filament\App\Resources\AlertResource\RelationManagers;
use App\Models\Alert;
use App\Models\CustomKpi;
use App\Models\DerivedMetric;
use App\Services\AlertService;
use App\Services\BillingLifecycleService;
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
                                Forms\Components\TextInput::make('source_config.channel')
                                    ->label(__('Channel Name (e.g. meta, google, ga4)'))
                                    ->required()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'metric'),

                                Forms\Components\TextInput::make('source_config.metric')
                                    ->label(__('Metric Key (e.g. spend, impressions, clicks)'))
                                    ->required()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'metric'),

                                Forms\Components\Select::make('source_config.kpi_id')
                                    ->label(__('Custom KPI'))
                                    ->options(fn () => CustomKpi::where('project_id', $project?->id)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'kpi'),

                                Forms\Components\Select::make('source_config.dm_id')
                                    ->label(__('Derived Metric'))
                                    ->options(fn () => DerivedMetric::where('project_id', $project?->id)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => $get('source_type') === 'derived_metric'),
                            ]),
                        ]),

                    Wizard\Step::make(__('Thresholds & Aggregation'))
                        ->schema([
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

                            Forms\Components\TextInput::make('upper_limit')
                                ->label(__('Upper Limit (Trigger if value > limit)'))
                                ->numeric()
                                ->nullable()
                                ->suffix(__('Units')),

                            Forms\Components\TextInput::make('lower_limit')
                                ->label(__('Lower Limit (Trigger if value < limit)'))
                                ->numeric()
                                ->nullable()
                                ->suffix(__('Units')),
                        ]),

                    Wizard\Step::make(__('Calculation Lines'))
                        ->schema([
                            Forms\Components\Repeater::make('calculationLines')
                                ->relationship('calculationLines')
                                ->schema([
                                    Forms\Components\TextInput::make('label')
                                        ->label(__('Line Label / Asset Name'))
                                        ->placeholder(__('e.g. Meta Ads Account #1'))
                                        ->required(),

                                    Forms\Components\KeyValue::make('asset_filter')
                                        ->label(__('Asset Filters (JSON key-value mapping)'))
                                        ->keyLabel(__('Filter Key'))
                                        ->valueLabel(__('Filter Value'))
                                        ->default(['asset_platform_id' => 'all']),
                                ])
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? __('Calculation Line'))
                                ->minItems(1)
                                ->columns(1)
                                ->default([
                                    ['label' => __('Default Calculation Line'), 'asset_filter' => ['asset_platform_id' => 'all']],
                                ]),
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
                                ->label(__('In-App Notifications (Filament Panel)'))
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
                    ->getStateUsing(fn (Alert $record) => implode(' | ', array_filter([
                        $record->upper_limit !== null ? "> {$record->upper_limit}" : null,
                        $record->lower_limit !== null ? "< {$record->lower_limit}" : null,
                    ])))
                    ->placeholder(__('None')),

                Tables\Columns\TextColumn::make('schedule_type')
                    ->label(__('Schedule'))
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_evaluation_at')
                    ->label(__('Next Eval'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
