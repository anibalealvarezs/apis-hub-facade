<?php

    namespace App\Filament\Widgets;

    use App\Models\Project;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Filament\Widgets\TableWidget as BaseWidget;
    use Illuminate\Database\Eloquent\Builder;

    class TopProjectsByAssetsTable extends BaseWidget
    {
        protected static ?int $sort = 6;

        protected int|string|array $columnSpan = 2;

        public function table(Table $table): Table
        {
            // 1. Fetch all active projects
            $projects = Project::with(['billingProfile', 'owner'])->where('is_active', true)->get();

            // 2. Calculate asset count for each
            $projectAssetCounts = [];
            foreach ($projects as $project) {
                $count = $this->calculateAssetCount($project->sync_config);
                if ($count > 0) {
                    $projectAssetCounts[$project->id] = $count;
                }
            }

            // 3. Sort descending by asset count and take top 10
            arsort($projectAssetCounts);
            $topProjectIds = array_slice(array_keys($projectAssetCounts), 0, 10);

            // 4. Create an Eloquent query for just these IDs
            $query = Project::query()->whereIn('id', $topProjectIds);

            // Fix for PostgreSQL/MySQL agnostic ordering by specific IDs
            if (!empty($topProjectIds)) {
                $caseSql = "CASE id ";
                foreach (array_values($topProjectIds) as $index => $id) {
                    $caseSql .= "WHEN " . (int)$id . " THEN $index ";
                }
                $caseSql .= "ELSE " . count($topProjectIds) . " END";
                
                $query->orderByRaw($caseSql);
            } else {
                // Fallback if empty to avoid SQL errors
                $query->where('id', 0);
            }

            return $table
                ->query($query)
                ->heading(__('Projects with the Most Assets'))
                ->description(__('Top 10 active projects ranked by the number of enabled integration assets.'))
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->label(__('Project'))
                        ->searchable(),

                    Tables\Columns\TextColumn::make('owner.email')
                        ->label(__('Owner')),

                    Tables\Columns\TextColumn::make('billingProfile.tier')
                        ->label(__('Tier'))
                        ->badge()
                        ->color(fn($state) => match ($state?->value ?? $state) {
                            'free' => 'gray',
                            'pro' => 'info',
                            'ultra' => 'success',
                            'founder' => 'warning',
                            'enterprise' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn($state) => ucfirst($state?->value ?? $state ?? 'None')),

                    Tables\Columns\TextColumn::make('asset_count')
                        ->label(__('Total Assets'))
                        ->getStateUsing(fn(Project $record) => $projectAssetCounts[$record->id] ?? 0)
                        ->badge()
                        ->color('primary'),
                ])
                ->actions([
                    Tables\Actions\Action::make('view')
                        ->label(__('View'))
                        ->url(fn(Project $record): string => \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-m-eye'),
                ])
                ->paginated(false);
        }

        protected function calculateAssetCount(?array $config): int
        {
            if (!$config || !is_array($config)) {
                return 0;
            }

            $count = 0;
            $scan = function ($data) use (&$scan, &$count) {
                if (!is_array($data)) {
                    return;
                }
                if (array_key_exists('enabled', $data) && (array_key_exists('id', $data) || array_key_exists('url', $data) || array_key_exists('lost_access', $data))) {
                    if (!empty($data['enabled']) && empty($data['lost_access'])) {
                        $count++;
                    }

                    return;
                }
                foreach ($data as $val) {
                    $scan($val);
                }
            };

            $scan($config);

            return $count;
        }
    }
