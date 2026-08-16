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
                    ->options(function () use ($channel) {
                        $options = ['all' => __('All Assets Combined')];
                        if ($channel) {
                            $assets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($channel);
                            foreach ($assets as $id => $info) {
                                $options[$id] = $info['name'] ?? $id;
                            }
                        } else {
                            $activeChannels = \App\Services\Analytics\KpiFormBuilder::getActiveChannels();
                            foreach (array_keys($activeChannels) as $ch) {
                                $assets = \App\Services\Analytics\KpiFormBuilder::getAllAssetsForChannel($ch);
                                foreach ($assets as $id => $info) {
                                    $options[$id] = ($info['name'] ?? $id) . ' (' . \App\Services\Analytics\KpiFormBuilder::getChannelDisplayName($ch) . ')';
                                }
                            }
                        }
                        return $options;
                    })
                    ->default('all')
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\AlertCalculationLine $record) {
                        $filter = $record?->asset_filter ?? [];
                        $component->state($filter['asset_platform_id'] ?? 'all');
                    })
                    ->dehydrated(false)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($channel) {
                        if ($state === 'all' || empty($state)) {
                            $set('label', __('All Assets Combined'));
                            $set('asset_filter', ['asset_platform_id' => 'all']);
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
                            $set('asset_filter', ['asset_platform_id' => $state]);
                        }
                    }),

                Forms\Components\TextInput::make('label')
                    ->label(__('Calculation Line Label'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\Hidden::make('asset_filter')
                    ->default(['asset_platform_id' => 'all']),
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
                    ->formatStateUsing(function ($state) {
                        $assetId = $state['asset_platform_id'] ?? 'all';
                        return $assetId === 'all' ? __('All Assets Combined') : $assetId;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (RelationManager $livewire) {
                        $alert = $livewire->getOwnerRecord();
                        if ($alert?->project) {
                            app(DeployerService::class)->syncAlertConfig($alert->project);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
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
