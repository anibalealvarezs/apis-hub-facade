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
            $isEdit = $form->getLivewire() instanceof \Filament\Resources\Pages\EditRecord;
            return $form
                ->schema([
                    ... \App\Services\Analytics\KpiFormBuilder::getSchema($isEdit),
                ])
                ->disabled(!auth()->user()->can('edit_preferences'));
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
                    Tables\Columns\TextColumn::make('calculation_type')
                        ->formatStateUsing(fn (string $state) => \App\Services\Analytics\KpiFormBuilder::getCalculationTypeOptions()[$state] ?? $state)
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('dashboards_count')
                        ->counts('dashboards')
                        ->label('Widgets')
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
                    Tables\Filters\SelectFilter::make('dashboards')
                        ->label('Dashboards')
                        ->multiple()
                        ->preload()
                        ->options(fn () => \App\Models\Dashboard::pluck('name', 'id')->toArray())
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['values'])) {
                                $query->whereHas('dashboards', function ($q) use ($data) {
                                    $q->whereIn('dashboards.id', $data['values']);
                                });
                            }
                        }),
                    Tables\Filters\SelectFilter::make('calculation_type')
                        ->options(\App\Services\Analytics\KpiFormBuilder::getCalculationTypeOptions()),
                    Tables\Filters\SelectFilter::make('asset_group')
                        ->label('Asset Group')
                        ->options(fn () => \App\Models\AssetGroup::pluck('name', 'id')->toArray())
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['value'])) {
                                $val = $data['value'];
                                $query->where(function ($q) use ($val) {
                                    $q->where('filters', 'like', '%"global_asset_group":"' . $val . '"%')
                                      ->orWhere('filters', 'like', '%"dependent_asset_group":"' . $val . '"%')
                                      ->orWhere('filters', 'like', '%"independent_asset_group":"' . $val . '"%');
                                });
                            }
                        }),
                    Tables\Filters\SelectFilter::make('metric')
                        ->label('Metric')
                        ->options(\App\Services\Analytics\KpiFormBuilder::getAllMetricOptions())
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['value'])) {
                                $val = $data['value'];
                                $query->where(function ($q) use ($val) {
                                    $q->where('filters', 'like', '%"dependent_metric":"' . $val . '"%')
                                      ->orWhere('filters', 'like', '%"independent_metric":"' . $val . '"%');
                                });
                            }
                        }),
                    Tables\Filters\SelectFilter::make('channel')
                        ->label('Channel')
                        ->options(fn () => \App\Services\Analytics\KpiFormBuilder::getActiveChannels())
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['value'])) {
                                $val = $data['value'];
                                $query->where(function ($q) use ($val) {
                                    $q->where('filters', 'like', '%"dependent_channel":"' . $val . '"%')
                                      ->orWhere('filters', 'like', '%"independent_channel":"' . $val . '"%');
                                });
                            }
                        }),
                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Status'),
                ])
                ->actions([
                \App\Services\Analytics\KpiExecuteActionBuilder::configure(
                    Tables\Actions\Action::make('execute'),
                    fn ($record) => $record ? ($record->filters['_ui_state'] ?? []) : [],
                    fn ($record) => $record ? $record->calculation_type : null
                ),
                    Tables\Actions\Action::make('preview')
                        ->label(__('Preview'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('gray')
                        ->modalHeading(__('KPI Configuration Summary'))
                        ->modalContent(function (CustomKpi $record) {
                            $state = array_merge(
                                $record->toArray(),
                                $record->filters['_ui_state'] ?? []
                            );
                            return \App\Services\Analytics\KpiFormBuilder::generateSummaryHtml(function ($key) use ($state) {
                                return \Illuminate\Support\Arr::get($state, $key);
                            });
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
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
