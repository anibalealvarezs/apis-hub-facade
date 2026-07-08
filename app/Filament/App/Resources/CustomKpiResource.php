<?php

    namespace App\Filament\App\Resources;

    use App\Filament\App\Resources\CustomKpiResource\Pages;
    use App\Filament\App\Resources\CustomKpiResource\RelationManagers;
    use App\Models\CustomKpi;
    use App\Services\Analytics\KpiFormBuilder;
    use App\Services\Analytics\KpiPayloadBuilder;
    use App\Services\RemoteEngineService;
    use Filament\Forms;
    use Filament\Forms\Form;
    use Filament\Forms\Get;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\SoftDeletingScope;
    use Illuminate\Support\HtmlString;

    class CustomKpiResource extends Resource
    {
        protected static ?string $model = CustomKpi::class;

        protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

        public static function canCreate(): bool
        {
            $project = \Filament\Facades\Filament::getTenant();
            if (!$project || !$project->billingProfile) {
                return false;
            }

            $currentCount = CustomKpi::where('project_id', $project->id)->count();
            $maxKpis = app(\App\Services\BillingLifecycleService::class)
                ->getMaxCustomKpisForTier($project->billingProfile->tier);

            return $currentCount < $maxKpis;
        }

        public static function getNavigationGroup(): ?string
        {
            return __('Exploration & Telemetry');
        }

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            Forms\Components\Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                        ])->columns(2),

                    ... \App\Services\Analytics\KpiFormBuilder::getSchema(),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean()
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
                    //
                ])
                ->actions([
                Tables\Actions\Action::make('execute')
                    ->label(__('Execute KPI'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form(function (CustomKpi $record) {
                        $uiState = $record->filters['_ui_state'] ?? [];
                        $fields = [];

                        if (empty($uiState['start_date'])) {
                            $fields[] = Forms\Components\DatePicker::make('start_date')
                                ->label(__('Start Date'));
                        }
                        if (empty($uiState['end_date'])) {
                            $fields[] = Forms\Components\DatePicker::make('end_date')
                                ->label(__('End Date'));
                        }
                        if (empty($uiState['granularity'])) {
                            $fields[] = Forms\Components\Select::make('granularity')
                                ->label(__('Granularity'))
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                ])
                                ->default('daily');
                        }

                        if (empty($uiState['dependent_channel'])) {
                            $fields[] = Forms\Components\Select::make('runtime_dependent_channel')
                                ->label(__('Dependent Channel'))
                                ->options(fn () => KpiFormBuilder::getActiveChannels())
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('runtime_dependent_metric', null));
                        }

                        if (empty($uiState['dependent_metric'])) {
                            $fields[] = Forms\Components\Select::make('runtime_dependent_metric')
                                ->label(__('Dependent Metric'))
                                ->options(function (Get $get) use ($uiState) {
                                    $channel = $get('runtime_dependent_channel') ?? $uiState['dependent_channel'] ?? null;
                                    return KpiFormBuilder::getMetricOptionsForChannel($channel);
                                })
                                ->live();
                        }

                        if (empty($uiState['dependent_asset_filter'])) {
                            $fields[] = Forms\Components\Select::make('runtime_dependent_asset_filter')
                                ->label(__('Dependent Asset Filter'))
                                ->options(function (Get $get) use ($uiState) {
                                    $channel = $get('runtime_dependent_channel') ?? $uiState['dependent_channel'] ?? null;
                                    $options = KpiFormBuilder::getAssetOptionsForChannel($channel);

                                    if (!empty($uiState['dependent_asset_group'])) {
                                        $group = \App\Models\AssetGroup::find($uiState['dependent_asset_group']);
                                        if ($group) {
                                            $allowedAssets = $group->active_items->where('channel', $channel)->pluck('asset_id')->toArray();
                                            $options = array_intersect_key($options, array_flip($allowedAssets));
                                        }
                                    }

                                    return $options;
                                })
                                ->live();
                        }

                        $independents = $uiState['independent_variables'] ?? [];
                        $idx = 0;
                        foreach ($independents as $var) {
                            $prefix = "runtime_independent_{$idx}";

                            if (empty($var['independent_channel'])) {
                                $fields[] = Forms\Components\Select::make("{$prefix}_channel")
                                    ->label(__('Variable ' . ($idx + 1) . ' - Channel'))
                                    ->options(fn () => KpiFormBuilder::getActiveChannels())
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set("{$prefix}_metric", null));
                            }

                            if (empty($var['independent_metric'])) {
                                $fields[] = Forms\Components\Select::make("{$prefix}_metric")
                                    ->label(__('Variable ' . ($idx + 1) . ' - Metric'))
                                        ->options(function (Get $get) use ($var, $idx) {
                                        $channel = $get("runtime_independent_{$idx}_channel") ?? $var['independent_channel'] ?? null;
                                        return KpiFormBuilder::getMetricOptionsForChannel($channel);
                                    })
                                    ->live();
                            }

                            if (empty($var['independent_asset_filter'])) {
                                $fields[] = Forms\Components\Select::make("{$prefix}_asset_filter")
                                    ->label(__('Variable ' . ($idx + 1) . ' - Asset Filter'))
                                    ->options(function (Get $get) use ($var, $idx) {
                                        $channel = $get("runtime_independent_{$idx}_channel") ?? $var['independent_channel'] ?? null;
                                        $options = KpiFormBuilder::getAssetOptionsForChannel($channel);

                                        if (!empty($var['independent_asset_group'])) {
                                            $group = \App\Models\AssetGroup::find($var['independent_asset_group']);
                                            if ($group) {
                                                $allowedAssets = $group->active_items->where('channel', $channel)->pluck('asset_id')->toArray();
                                                $options = array_intersect_key($options, array_flip($allowedAssets));
                                            }
                                        }

                                        return $options;
                                    })
                                    ->live();
                            }

                            $idx++;
                        }

                        if (empty($uiState['zero_handling'])) {
                            $fields[] = Forms\Components\Select::make('zero_handling')
                                ->label(__('Zero Handling'))
                                ->options([
                                    'remove' => 'Remove Zeroes',
                                    'trim' => 'Trim Leading/Trailing Zeroes',
                                    'keep' => 'Keep Zeroes',
                                ])
                                ->default('trim')
                                ->helperText(__('How to treat zero values in the time series before analysis.'));
                        }

                        $fields[] = Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('previewPayload')
                                ->label(__('Preview Payload'))
                                ->icon('heroicon-o-code-bracket')
                                ->color('gray')
                                ->modalHeading(__('Payload Preview'))
                                ->modalContent(function (Get $get, CustomKpi $record) {
                                    $uiState = $record->filters['_ui_state'] ?? [];

                                    foreach (['start_date', 'end_date', 'granularity'] as $field) {
                                        $val = $get($field);
                                        if (!empty($val)) {
                                            $uiState[$field] = $val;
                                        }
                                    }

                                    $channel = $get('runtime_dependent_channel');
                                    if (!empty($channel)) {
                                        $uiState['dependent_channel'] = $channel;
                                    }
                                    $metric = $get('runtime_dependent_metric');
                                    if (!empty($metric)) {
                                        $uiState['dependent_metric'] = $metric;
                                    }
                                    $asset = $get('runtime_dependent_asset_filter');
                                    if (!empty($asset)) {
                                        $uiState['dependent_asset_filter'] = $asset;
                                    }

                                    $independents = $uiState['independent_variables'] ?? [];
                                    $idx = 0;
                                    foreach ($independents as $key => $var) {
                                        $prefix = "runtime_independent_{$idx}";
                                        $ch = $get("{$prefix}_channel");
                                        $me = $get("{$prefix}_metric");
                                        $as = $get("{$prefix}_asset_filter");
                                        if (!empty($ch)) {
                                            $independents[$key]['independent_channel'] = $ch;
                                        }
                                        if (!empty($me)) {
                                            $independents[$key]['independent_metric'] = $me;
                                        }
                                        if (!empty($as)) {
                                            $independents[$key]['independent_asset_filter'] = $as;
                                        }
                                        $idx++;
                                    }
                                    $uiState['independent_variables'] = $independents;

                                    $payload = KpiPayloadBuilder::build(
                                        $record->calculation_type,
                                        $uiState
                                    );

                                    return new HtmlString(
                                        '<pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">'
                                        . json_encode($payload, JSON_PRETTY_PRINT)
                                        . '</pre>'
                                    );
                                })
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Close'),
                        ])
                            ->visible(fn () => auth()->user()->can('edit_preferences') && config('app.env') !== 'production');

                        return $fields;
                    })
                    ->action(function (array $data, CustomKpi $record, RemoteEngineService $service) {
                        $uiState = $record->filters['_ui_state'] ?? [];

                        if (!empty($data['runtime_dependent_channel'])) {
                            $uiState['dependent_channel'] = $data['runtime_dependent_channel'];
                        }
                        if (!empty($data['runtime_dependent_metric'])) {
                            $uiState['dependent_metric'] = $data['runtime_dependent_metric'];
                        }
                        if (!empty($data['runtime_dependent_asset_filter'])) {
                            $uiState['dependent_asset_filter'] = $data['runtime_dependent_asset_filter'];
                        }

                        $independents = $uiState['independent_variables'] ?? [];
                        $idx = 0;
                        foreach ($independents as $key => $var) {
                            $prefix = "runtime_independent_{$idx}";
                            if (!empty($data["{$prefix}_channel"])) {
                                $independents[$key]['independent_channel'] = $data["{$prefix}_channel"];
                            }
                            if (!empty($data["{$prefix}_metric"])) {
                                $independents[$key]['independent_metric'] = $data["{$prefix}_metric"];
                            }
                            if (!empty($data["{$prefix}_asset_filter"])) {
                                $independents[$key]['independent_asset_filter'] = $data["{$prefix}_asset_filter"];
                            }
                            $idx++;
                        }
                        $uiState['independent_variables'] = $independents;

                        $payload = KpiPayloadBuilder::build(
                            $record->calculation_type,
                            $uiState,
                            $data
                        );

                        $project = \Filament\Facades\Filament::getTenant();
                        $result = $service->computeKpi($project, $payload);

                        if (isset($result['success']) && $result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Execution Successful'))
                                ->success()
                                ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">'.json_encode($result['data'] ?? [], JSON_PRETTY_PRINT).'</pre>')
                                ->persistent()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Execution Failed'))
                                ->danger()
                                ->body($result['message'] ?? 'An unknown error occurred.')
                                ->persistent()
                                ->send();
                        }
                    }),
                    Tables\Actions\Action::make('debugPayload')
                        ->label(__('Debug Payload'))
                        ->icon('heroicon-o-code-bracket')
                        ->color('gray')
                        ->visible(fn() => auth()->user()->can('edit_preferences') && config('app.env') !== 'production')
                        ->modalHeading(__('Payload Debugger'))
                        ->modalContent(function (CustomKpi $record) {
                            $uiState = $record->filters['_ui_state'] ?? [];
                            $payload = KpiPayloadBuilder::build(
                                $record->calculation_type,
                                $uiState
                            );

                            return new HtmlString('<pre style="background: #1f2937; color: #10b981; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem;">'.json_encode($payload, JSON_PRETTY_PRINT).'</pre>');
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Tables\Actions\ReplicateAction::make()
                        ->label(__('Duplicate'))
                        ->excludeAttributes(['id'])
                        ->beforeReplicaSaved(function (CustomKpi $replica) {
                            $replica->name = $replica->name . ' (copy)';
                        })
                        ->visible(fn() => auth()->user()->can('edit_preferences')),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => auth()->user()->can('edit_preferences')),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make()
                            ->visible(fn() => auth()->user()->can('edit_preferences')),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index'  => Pages\ListCustomKpis::route('/'),
                'create' => Pages\CreateCustomKpi::route('/create'),
                'edit'   => Pages\EditCustomKpi::route('/{record}/edit'),
            ];
        }
    }
