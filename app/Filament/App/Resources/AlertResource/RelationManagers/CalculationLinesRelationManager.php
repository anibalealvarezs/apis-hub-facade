<?php

namespace App\Filament\App\Resources\AlertResource\RelationManagers;

use App\Services\DeployerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                        if ($record && isset($record->asset_filter['asset_platform_id'])) {
                            $component->state((string) $record->asset_filter['asset_platform_id']);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($channel) {
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
                        $assetId = is_array($state) ? ($state['asset_platform_id'] ?? 'all') : 'all';
                        if ($assetId === 'all' || empty($assetId)) {
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

                        return $assets[$assetId]['name'] ?? $record->label ?? $assetId;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $assetId = $data['target_asset_platform_id']
                            ?? $data['asset_filter']['asset_platform_id']
                            ?? $data['asset_filter.asset_platform_id']
                            ?? 'all';

                        $data['asset_filter'] = ['asset_platform_id' => (string) $assetId];
                        unset($data['target_asset_platform_id'], $data['asset_filter.asset_platform_id']);

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
                        $assetId = $data['target_asset_platform_id']
                            ?? $data['asset_filter']['asset_platform_id']
                            ?? $data['asset_filter.asset_platform_id']
                            ?? 'all';

                        $data['asset_filter'] = ['asset_platform_id' => (string) $assetId];
                        unset($data['target_asset_platform_id'], $data['asset_filter.asset_platform_id']);

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
