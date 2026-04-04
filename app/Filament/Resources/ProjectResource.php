<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages\CreateProject as CreatePage;
use App\Filament\Resources\ProjectResource\Pages\EditProject as EditPage;
use App\Filament\Resources\ProjectResource\Pages\ListProjects as ListPage;
use App\Models\Project;
use App\Services\DeployerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $navigationGroup = 'Infrastructure';

    public static function getPluralModelLabel(): string
    {
        return 'Projects';
    }

    public static function getModelLabel(): string
    {
        return 'Project';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Identity')
                    ->description('Primary details and active status.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subdomain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Assign a subdomain (e.g. "client1") for the instance. Full URL will be: subdomain.' . config('app.network_domain')),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Account Holder')
                            ->helperText('The user who owns this instance.'),
                    ])->columns(4),

                Forms\Components\Section::make('Repository & Deployment')
                    ->description('Where the source code lives and which server hosts the application.')
                    ->schema([
                        Forms\Components\TextInput::make('git_repo')
                            ->required()
                            ->default('anibalealvarezs/apis-hub'),
                        Forms\Components\TextInput::make('git_branch')
                            ->required()
                            ->default('main'),
                        Forms\Components\Select::make('server_id')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Database Configuration')
                    ->description('Isolated DB settings for this project instance.')
                    ->schema([
                        Forms\Components\TextInput::make('db_name')
                            ->placeholder('Auto-generated if empty')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_user')
                            ->placeholder('Auto-generated if empty')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->placeholder('Auto-generated if empty'),
                    ])->columns(3),

                Forms\Components\Section::make('API Credentials (Encrypted)')
                    ->description('These secrets are injected into the project instance\'s .env file.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                 Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('FB')->label('Facebook API'),
                                    Forms\Components\TextInput::make('facebook_user_token')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn ($state) => filled($state)),
                                ]),
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('GSC')->label('Google Search Console (GSC)'),
                                    Forms\Components\TextInput::make('google_refresh_token')
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn ($state) => filled($state)),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('health_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'gray',
                        'error' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')
                    ->label('Last Heartbeat')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_count')
                    ->label('Errors')
                    ->numeric()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Target Server')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('last_deployed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never deployed'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('checkNodeStatus')
                    ->label('Check Engine')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->action(function (Project $record, \App\Services\RemoteEngineService $service) {
                        $response = $service->getStatus($record);
                        $isOnline = ($response['success'] ?? false) || ($response['status'] ?? '') === 'success';

                        Notification::make()
                            ->title($isOnline ? "{$record->name} is Online" : "{$record->name} Offline")
                            ->status($isOnline ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\Action::make('triggerNodeRedeploy')
                    ->label('API Redeploy')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will trigger a background redeployment via the Node\'s internal API. Continue?')
                    ->action(function (Project $record, \App\Services\RemoteEngineService $service) {
                        $response = $service->triggerRedeploy($record);
                        
                        Notification::make()
                            ->title(($response['success'] ?? false) ? 'Redeploy Triggered' : 'Trigger Failed')
                            ->status(($response['success'] ?? false) ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\Action::make('deploy')
                    ->label('Full SSH Deploy')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will perform a full SSH-based deployment on the remote server. Continue?')
                    ->action(function (Project $record, \App\Services\DeployerService $service) {
                        $result = $service->deploy($record);
                        
                        if (($result['status'] ?? '') === 'success') {
                            Notification::make()
                                ->title('Deployment Successful')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Deployment Failed')
                                ->body($result['output'] ?? 'Unknown error')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
