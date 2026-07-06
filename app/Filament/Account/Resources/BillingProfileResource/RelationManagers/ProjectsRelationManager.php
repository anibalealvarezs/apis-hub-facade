<?php

namespace App\Filament\Account\Resources\BillingProfileResource\RelationManagers;

use App\Models\Project;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Associated Projects');
    }

    public function table(Table $table): Table
    {
        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];

        $countAssets = function (Project $project) use ($assetKeys): int {
            $count = 0;
            $syncConfig = $project->sync_config ?? [];
            if (! is_array($syncConfig)) {
                return 0;
            }

            foreach ($syncConfig as $channelConfig) {
                if (! is_array($channelConfig) || empty($channelConfig['enabled'])) {
                    continue;
                }

                foreach ($assetKeys as $assetKey) {
                    $assets = $channelConfig[$assetKey] ?? ($channelConfig['assets'][$assetKey] ?? null);
                    if (! is_array($assets)) {
                        continue;
                    }

                    foreach ($assets as $asset) {
                        if (is_array($asset) && ! empty($asset['enabled']) && empty($asset['lost_access'])) {
                            $count++;
                        }
                    }
                }
            }

            return $count;
        };

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Project'))
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->state(function (Project $record): string {
                        $userId = \Illuminate\Support\Facades\Auth::id();
                        $hasAccess = $record->user_id === $userId
                            || $record->users()->where('users.id', $userId)->exists();

                        if ($hasAccess) {
                            $url = url("/app/{$record->subdomain}");

                            return "<a href=\"{$url}\" class=\"text-blue-600 dark:text-blue-400 hover:underline font-medium\">" . e($record->name) . "</a>";
                        }

                        return e($record->name);
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Owner'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('assets')
                    ->label(__('Assets'))
                    ->state(fn (Project $record) => $countAssets($record))
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('share')
                    ->label(__('Share'))
                    ->html()
                    ->state(function (Project $record) use ($assetKeys, $countAssets): string {
                        $projectAssets = $countAssets($record);

                        $totalAssets = 0;
                        $bp = $this->getOwnerRecord();
                        if ($bp) {
                            foreach ($bp->projects as $p) {
                                $totalAssets += $countAssets($p);
                            }
                        }

                        $pct = $totalAssets > 0 ? round(($projectAssets / $totalAssets) * 100) : 0;
                        $color = $pct >= 50 ? '#ef4444' : ($pct >= 25 ? '#f59e0b' : '#22c55e');

                        return "
                            <div class=\"flex items-center gap-2\">
                                <span class=\"font-semibold text-xs\">{$pct}%</span>
                                <div class=\"w-12 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden\">
                                    <div style=\"width:{$pct}%;height:100%;background:{$color};border-radius:9999px\"></div>
                                </div>
                            </div>";
                    })
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('access')
                    ->label(__('Access'))
                    ->boolean()
                    ->state(function (Project $record): bool {
                        $userId = \Illuminate\Support\Facades\Auth::id();
                        return $record->user_id === $userId
                            || $record->users()->where('users.id', $userId)->exists();
                    })
                    ->alignCenter(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
