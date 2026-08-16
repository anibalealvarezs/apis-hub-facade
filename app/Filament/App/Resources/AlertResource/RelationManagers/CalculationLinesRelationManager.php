<?php

namespace App\Filament\App\Resources\AlertResource\RelationManagers;

use App\Services\DeployerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class CalculationLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'calculationLines';

    public function form(Form $form): Form
    {
        $alert = $this->getOwnerRecord();
        $channel = $alert?->source_config['channel'] ?? null;

        return $form
            ->schema([
                Forms\Components\Select::make('target_asset_platform_id')
                    ->label(__('Target Asset'))
                    ->options(function (Forms\Get $get, ?\App\Models\AlertCalculationLine $record) use ($channel) {
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

                        if ($record && isset($record->asset_filter['asset_platform_id'])) {
                            $currentId = (string) $record->asset_filter['asset_platform_id'];
                            if ($currentId !== 'all' && !isset($options[$currentId])) {
                                $options[$currentId] = $record->label ?? $currentId;
                            }
                        }

                        return $options;
                    })
                    ->default('all')
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\AlertCalculationLine $record) {
                        $currentVal = $record?->asset_filter['asset_platform_id'] ?? null;
                        Log::info('[AlertDebug] HydroState for line ID ' . ($record?->id ?? 'new') . ': asset_filter in DB=' . json_encode($record?->asset_filter) . ', resolved val=' . var_export($currentVal, true));
                        if ($currentVal) {
                            $component->state((string) $currentVal);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($channel) {
                        Log::info('[AlertDebug] Target Asset Select state updated to: ' . var_export($state, true));
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
                    ->label(__('Calculation Line Label'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('Line Label'))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('asset_filter')
                    ->label(__('Target Asset Filter'))
                    ->formatStateUsing(function ($state, \App\Models\AlertCalculationLine $record, RelationManager $livewire) {
                        Log::info('[AlertDebug] Rendering Table Column asset_filter for line ID ' . $record->id . '. raw $state=' . json_encode($state) . ', raw DB asset_filter=' . json_encode($record->asset_filter) . ', raw DB label=' . $record->label);

                        $assetId = is_array($state) ? ($state['asset_platform_id'] ?? null) : null;
                        if (!$assetId && is_array($record->asset_filter)) {
                            $assetId = $record->asset_filter['asset_platform_id'] ?? null;
                        }

                        if (!$assetId || $assetId === 'all') {
                            return __('All Assets Combined');
                        }

                        $alert = $livewire->getOwnerRecord();
                        $channel = $alert?->source_config['channel'] ?? null;
                        $assets = $channel ? \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($channel) : [];

                        if (empty($assets)) {
                            $activeChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
                            foreach (array_keys($activeChannels) as $ch) {
                                $chAssets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($ch);
                                if (isset($chAssets[$assetId])) {
                                    $assets = $chAssets;
                                    break;
                                }
                            }
                        }

                        $displayName = $assets[$assetId]['name'] ?? $assetId;
                        Log::info('[AlertDebug] Resolved column display name for assetId ' . var_export($assetId, true) . ' => ' . var_export($displayName, true));

                        return $displayName;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        Log::info('[AlertDebug] CreateAction mutateFormDataUsing input data=' . json_encode($data));

                        $assetId = $data['target_asset_platform_id']
                            ?? $data['asset_filter']['asset_platform_id']
                            ?? $data['asset_filter.asset_platform_id']
                            ?? 'all';

                        $data['asset_filter'] = ['asset_platform_id' => (string) $assetId];
                        unset($data['target_asset_platform_id'], $data['asset_filter.asset_platform_id']);

                        Log::info('[AlertDebug] CreateAction mutateFormDataUsing output data=' . json_encode($data));

                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $alert = $livewire->getOwnerRecord();
                        if ($alert?->project) {
                            app(DeployerService::class)->syncAlertConfig($alert->project);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        Log::info('[AlertDebug] EditAction mutateFormDataUsing input data=' . json_encode($data));

                        $assetId = $data['target_asset_platform_id']
                            ?? $data['asset_filter']['asset_platform_id']
                            ?? $data['asset_filter.asset_platform_id']
                            ?? 'all';

                        $data['asset_filter'] = ['asset_platform_id' => (string) $assetId];
                        unset($data['target_asset_platform_id'], $data['asset_filter.asset_platform_id']);

                        Log::info('[AlertDebug] EditAction mutateFormDataUsing output data=' . json_encode($data));

                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $alert = $livewire->getOwnerRecord();
                        if ($alert?->project) {
                            app(DeployerService::class)->syncAlertConfig($alert->project);
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire) {
                        $alert = $livewire->getOwnerRecord();
                        if ($alert?->project) {
                            app(DeployerService::class)->syncAlertConfig($alert->project);
                        }
                    }),
            ]);
    }
}
