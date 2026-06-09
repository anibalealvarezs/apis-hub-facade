<?php

    namespace App\Filament\App\Resources;

    use App\Filament\App\Resources\CustomKpiResource\Pages;
    use App\Filament\App\Resources\CustomKpiResource\RelationManagers;
    use App\Models\CustomKpi;
    use App\Services\Analytics\KpiPayloadBuilder;
    use App\Services\RemoteEngineService;
    use Filament\Forms;
    use Filament\Forms\Form;
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
                                ->label('Active')
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
                    ->label('Execute KPI')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form(function (CustomKpi $record) {
                        $uiState = $record->filters['_ui_state'] ?? [];
                        $fields = [];

                        if (empty($uiState['start_date'])) {
                            $fields[] = Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date');
                        }
                        if (empty($uiState['end_date'])) {
                            $fields[] = Forms\Components\DatePicker::make('end_date')
                                ->label('End Date');
                        }
                        if (empty($uiState['granularity'])) {
                            $fields[] = Forms\Components\Select::make('granularity')
                                ->label('Granularity')
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                ]);
                        }

                        if (empty($uiState['dependent_asset_filter'])) {
                            $channel = $uiState['dependent_channel'] ?? null;
                            $options = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
                            if (!empty($options)) {
                                $fields[] = Forms\Components\Select::make('runtime_dependent_asset_filter')
                                    ->label('Dependent Asset Filter')
                                    ->options($options);
                            }
                        }

                        $independents = $uiState['independent_variables'] ?? [];
                        $idx = 0;
                        foreach ($independents as $var) {
                            if (empty($var['independent_asset_filter'])) {
                                $channel = $var['independent_channel'] ?? null;
                                $options = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($channel);
                                if (!empty($options)) {
                                    $fields[] = Forms\Components\Select::make("runtime_independent_asset_filter_{$idx}")
                                        ->label('Variable ' . ($idx + 1) . ' - Asset Filter')
                                        ->options($options);
                                }
                            }
                            $idx++;
                        }

                        return $fields;
                    })
                    ->action(function (array $data, CustomKpi $record, RemoteEngineService $service) {
                        $uiState = $record->filters['_ui_state'] ?? [];

                        if (!empty($data['runtime_dependent_asset_filter'])) {
                            $uiState['dependent_asset_filter'] = $data['runtime_dependent_asset_filter'];
                        }

                        $independents = $uiState['independent_variables'] ?? [];
                        $idx = 0;
                        foreach ($independents as $key => $var) {
                            $field = "runtime_independent_asset_filter_{$idx}";
                            if (!empty($data[$field])) {
                                $independents[$key]['independent_asset_filter'] = $data[$field];
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
                                ->title('Execution Successful')
                                ->success()
                                ->body('<pre style="white-space: pre-wrap; font-size: 0.75rem;">'.json_encode($result['data'] ?? [], JSON_PRETTY_PRINT).'</pre>')
                                ->persistent()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Execution Failed')
                                ->danger()
                                ->body($result['message'] ?? 'An unknown error occurred.')
                                ->persistent()
                                ->send();
                        }
                    }),
                    Tables\Actions\Action::make('debugPayload')
                        ->label('Debug Payload')
                        ->icon('heroicon-o-code-bracket')
                        ->color('gray')
                        ->visible(fn() => auth()->user()->can('edit_preferences') && config('app.env') !== 'production')
                        ->modalHeading('Payload Debugger')
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
