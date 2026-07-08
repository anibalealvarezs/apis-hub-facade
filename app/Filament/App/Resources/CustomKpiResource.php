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
                    ... \App\Services\Analytics\KpiFormBuilder::getSchema(),
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
                \App\Services\Analytics\KpiExecuteActionBuilder::configure(
                    Tables\Actions\Action::make('execute'),
                    fn ($record) => $record ? ($record->filters['_ui_state'] ?? []) : [],
                    fn ($record) => $record ? $record->calculation_type : null
                ),
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
