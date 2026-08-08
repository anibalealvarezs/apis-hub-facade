<?php

namespace App\Services\Analytics;

use Filament\Forms;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use App\Services\RemoteEngineService;

class KpiExecuteActionBuilder
{
    /**
     * Configure a Filament Action (Table Action or Page Action) to act as the KPI Execute modal.
     *
     * @param  \Filament\Actions\Action|\Filament\Tables\Actions\Action  $action
     * @param  callable  $getUiState  A closure that returns the current UI state array.
     * @param  callable  $getCalculationType  A closure that returns the calculation type string.
     * @return mixed
     */
    public static function configure($action, callable $getUiState, callable $getCalculationType)
    {
        return $action
            ->label(__('Test'))
            ->icon('heroicon-o-play')
            ->color('success')
            ->form(function (?\Illuminate\Database\Eloquent\Model $record = null) use ($getUiState, $getCalculationType) {
                $uiState = $getUiState($record);
                $globalFields = [];

                if (empty($uiState['start_date'])) {
                    $globalFields[] = Forms\Components\DatePicker::make('start_date')
                        ->label(__('Start Date'));
                }
                if (empty($uiState['end_date'])) {
                    $globalFields[] = Forms\Components\DatePicker::make('end_date')
                        ->label(__('End Date'));
                }
                if (empty($uiState['granularity'])) {
                    $globalFields[] = Forms\Components\Select::make('granularity')
                        ->label(__('Granularity'))
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'query' => 'Query',
                            'page' => 'Page',
                            'country' => 'Country',
                            'device' => 'Device',
                            'post' => 'Post',
                        ])
                        ->default('daily');
                }
                if (empty($uiState['zero_handling'])) {
                    $globalFields[] = Forms\Components\Select::make('zero_handling')
                        ->label(__('Zero Handling'))
                        ->options([
                            'remove' => 'Remove Zeroes',
                            'trim' => 'Trim Leading/Trailing Zeroes',
                            'keep' => 'Keep Zeroes',
                        ])
                        ->default('trim')
                        ->helperText(__('How to treat zero values in the time series before analysis.'));
                }

                $dependentFields = [];

                if (empty($uiState['dependent_channel'])) {
                    $dependentFields[] = Forms\Components\Select::make('runtime_dependent_channel')
                        ->label(__('Channel'))
                        ->options(fn () => KpiFormBuilder::getActiveChannels())
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('runtime_dependent_metric', null));
                }

                if (empty($uiState['dependent_metric'])) {
                    $dependentFields[] = Forms\Components\Select::make('runtime_dependent_metric')
                        ->label(__('Metric'))
                        ->options(function (Get $get) use ($uiState) {
                            $channel = $get('runtime_dependent_channel') ?? $uiState['dependent_channel'] ?? null;
                            return KpiFormBuilder::getMetricOptionsForChannel($channel);
                        })
                        ->live();
                }

                if (empty($uiState['dependent_asset_filter'])) {
                    $dependentFields[] = Forms\Components\Select::make('runtime_dependent_asset_filter')
                        ->label(__('Asset Filter'))
                        ->options(function (Get $get) use ($uiState) {
                            $channel = $get('runtime_dependent_channel') ?? $uiState['dependent_channel'] ?? null;
                            $options = KpiFormBuilder::getAssetOptionsForChannel($channel);

                            if (!empty($uiState['dependent_asset_group'])) {
                                $group = app(\App\Services\CollaboratorAssetAccessService::class)
                                    ->getAllowedAssetGroupQuery(\Filament\Facades\Filament::getTenant(), auth()->user()?->getAuthIdentifier())
                                    ->find($uiState['dependent_asset_group']);
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
                $independentCardSchemas = [];

                foreach (array_values($independents) as $var) {
                    $prefix = "runtime_independent_{$idx}";
                    $indFields = [];

                    if (empty($var['independent_channel'])) {
                        $indFields[] = Forms\Components\Select::make("{$prefix}_channel")
                            ->label(__('Channel'))
                            ->options(fn () => KpiFormBuilder::getActiveChannels())
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set("{$prefix}_metric", null));
                    }

                    if (empty($var['independent_metric'])) {
                        $indFields[] = Forms\Components\Select::make("{$prefix}_metric")
                            ->label(__('Metric'))
                            ->options(function (Get $get) use ($var, $idx) {
                                $channel = $get("runtime_independent_{$idx}_channel") ?? $var['independent_channel'] ?? null;
                                return KpiFormBuilder::getMetricOptionsForChannel($channel);
                            })
                            ->live();
                    }

                    if (empty($var['independent_asset_filter'])) {
                        $indFields[] = Forms\Components\Select::make("{$prefix}_asset_filter")
                            ->label(__('Asset Filter'))
                            ->options(function (Get $get) use ($var, $idx) {
                                $channel = $get("runtime_independent_{$idx}_channel") ?? $var['independent_channel'] ?? null;
                                $options = KpiFormBuilder::getAssetOptionsForChannel($channel);

                                if (!empty($var['independent_asset_group'])) {
                                    $group = app(\App\Services\CollaboratorAssetAccessService::class)
                                        ->getAllowedAssetGroupQuery(\Filament\Facades\Filament::getTenant(), auth()->user()?->getAuthIdentifier())
                                        ->find($var['independent_asset_group']);
                                    if ($group) {
                                        $allowedAssets = $group->active_items->where('channel', $channel)->pluck('asset_id')->toArray();
                                        $options = array_intersect_key($options, array_flip($allowedAssets));
                                    }
                                }

                                return $options;
                            })
                            ->live();
                    }

                    if (!empty($indFields)) {
                        $independentCardSchemas[] = Forms\Components\Section::make(__('Variable ' . ($idx + 1)))
                            ->schema($indFields)
                            ->columnSpan(1);
                    }

                    $idx++;
                }

                $seriesColumn = [];
                
                if (!empty($dependentFields)) {
                    $seriesColumn[] = Forms\Components\Section::make(__('Dependent Variable (Y - Explained)'))
                        ->schema($dependentFields);
                }

                if (!empty($independentCardSchemas)) {
                    $seriesColumn[] = Forms\Components\Section::make(__('Independent Variables (X - Explanatory)'))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema($independentCardSchemas)
                        ]);
                }

                $layout = [];

                if (!empty($globalFields) || !empty($seriesColumn)) {
                    $layout[] = Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Section::make(__('Global Configuration'))
                                ->schema($globalFields)
                                ->columnSpan(1)
                                ->hidden(fn () => empty($globalFields)),

                            Forms\Components\Group::make($seriesColumn)
                                ->columnSpan(empty($globalFields) ? 3 : 2)
                                ->hidden(fn () => empty($seriesColumn)),
                        ]);
                }

                $layout[] = Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('previewPayload')
                        ->label(__('Preview Payload'))
                        ->icon('heroicon-o-code-bracket')
                        ->color('gray')
                        ->modalHeading(__('Payload Preview'))
                        ->modalContent(function (Get $get, ?\Illuminate\Database\Eloquent\Model $record = null) use ($getUiState, $getCalculationType) {
                            $uiState = $getUiState($record);

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
                            $independentsSeq = array_values($independents);
                            $idx = 0;
                            foreach ($independentsSeq as $key => $var) {
                                $prefix = "runtime_independent_{$idx}";
                                $ch = $get("{$prefix}_channel");
                                $me = $get("{$prefix}_metric");
                                $as = $get("{$prefix}_asset_filter");
                                if (!empty($ch)) {
                                    $independentsSeq[$key]['independent_channel'] = $ch;
                                }
                                if (!empty($me)) {
                                    $independentsSeq[$key]['independent_metric'] = $me;
                                }
                                if (!empty($as)) {
                                    $independentsSeq[$key]['independent_asset_filter'] = $as;
                                }
                                $idx++;
                            }
                            // Re-apply to uiState (using original keys if needed, but payload builder handles seq)
                            $uiState['independent_variables'] = $independentsSeq;

                            $calcType = $getCalculationType($record);
                            if (empty($calcType)) {
                                return new HtmlString('<p class="text-danger-500">Please select a calculation type first.</p>');
                            }

                            $payload = KpiPayloadBuilder::build(
                                $calcType,
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

                return $layout;
            })
            ->action(function (array $data, RemoteEngineService $service, ?\Illuminate\Database\Eloquent\Model $record = null) use ($getUiState, $getCalculationType) {
                $uiState = $getUiState($record);

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
                $independentsSeq = array_values($independents);
                $idx = 0;
                foreach ($independentsSeq as $key => $var) {
                    $prefix = "runtime_independent_{$idx}";
                    if (!empty($data["{$prefix}_channel"])) {
                        $independentsSeq[$key]['independent_channel'] = $data["{$prefix}_channel"];
                    }
                    if (!empty($data["{$prefix}_metric"])) {
                        $independentsSeq[$key]['independent_metric'] = $data["{$prefix}_metric"];
                    }
                    if (!empty($data["{$prefix}_asset_filter"])) {
                        $independentsSeq[$key]['independent_asset_filter'] = $data["{$prefix}_asset_filter"];
                    }
                    $idx++;
                }
                $uiState['independent_variables'] = $independentsSeq;

                $calcType = $getCalculationType($record);
                if (empty($calcType)) {
                    \Filament\Notifications\Notification::make()->title(__('Missing calculation type'))->danger()->send();
                    return;
                }

                $payload = KpiPayloadBuilder::build(
                    $calcType,
                    $uiState,
                    $data
                );

                $project = \Filament\Facades\Filament::getTenant();
                KpiPayloadBuilder::swapFboIgPlatformIds(
                    $payload,
                    $project->sync_config['facebook_organic']['assets']['pages']
                        ?? $project->sync_config['facebook_organic']['pages']
                        ?? []
                );
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
            });
    }
}
