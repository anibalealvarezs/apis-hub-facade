<?php

    namespace App\Filament\Widgets;

    use App\Models\Project;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Filament\Widgets\TableWidget as BaseWidget;

    class RecentProjectsTable extends BaseWidget
    {
        protected static ?int $sort = 5;

        protected int|string|array $columnSpan = 2;

        public function table(Table $table): Table
        {
            return $table
                ->query(
                    Project::query()->with('owner')->latest('created_at')->limit(10)
                )
                ->heading('Recent Projects')
                ->description('The 10 most recently created or deployed projects.')
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->label('Project')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('subdomain')
                        ->badge()
                        ->color('gray'),

                    Tables\Columns\TextColumn::make('owner.email')
                        ->label('Owner')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('health_status')
                        ->label('Health')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'healthy' => 'success',
                            'deploying' => 'warning',
                            'error' => 'danger',
                            'offline' => 'gray',
                            default => 'gray',
                        }),

                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Active')
                        ->boolean(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Created')
                        ->dateTime()
                        ->sortable(),
                ])
                ->actions([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->url(fn(Project $record): string => \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-m-eye'),
                ])
                ->paginated(false);
        }
    }
