<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages\CreateProject as CreatePage;
use App\Filament\Resources\ProjectResource\Pages\EditProject as EditPage;
use App\Filament\Resources\ProjectResource\Pages\ListProjects as ListPage;
use App\Models\Project;
use App\Services\DeployerService;
use App\Services\RemoteEngineService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                            ->live(onBlur: true)
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if (empty($get('db_name'))) {
                                    $set('db_name', 'apis_hub_' . str_replace('-', '_', $state));
                                }
                                if (empty($get('db_user'))) {
                                    $set('db_user', 'user_' . str_replace('-', '_', $state));
                                }
                                if (empty($get('db_password'))) {
                                    $set('db_password', \Illuminate\Support\Str::random(16));
                                }
                            })
                            ->helperText('Assign a subdomain (e.g. "client1") for the instance. Checked against DB. Full URL will be: subdomain.' . config('app.network_domain')),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(fn () => filament()->auth()->id())
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
                            ->default(1)
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Database Configuration')
                    ->description('Isolated DB settings for this project instance.')
                    ->schema([
                        Forms\Components\TextInput::make('db_name')
                            ->placeholder('Auto-generated if empty')
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_user')
                            ->placeholder('Auto-generated if empty')
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('db_password')
                            ->password()
                            ->revealable()
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->placeholder('Auto-generated if empty'),
                    ])->columns(3),



                Forms\Components\Section::make('API Credentials (Encrypted)')
                    ->description('These secrets are injected into the project instance\'s .env file.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
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
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('Pub')->label('Public Access'),
                                    Forms\Components\TextInput::make('public_api_key')
                                        ->label('Public API Key')
                                        ->password()
                                        ->revealable()
                                        ->disabled()
                                        ->helperText('Used by external consumers.'),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('checkNodeStatus')
                    ->label('Check Engine')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->action(function (Project $record, RemoteEngineService $service) {
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
                    ->action(function (Project $record, RemoteEngineService $service) {
                        $response = $service->triggerRedeploy($record);
                        
                        Notification::make()
                            ->title(($response['success'] ?? false) ? 'Redeploy Triggered' : 'Trigger Failed')
                            ->status(($response['success'] ?? false) ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\Action::make('deploy')
                    ->label('Force Async Deploy')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will dispatch a background SSH-based deployment job for the remote server. You can monitor the progress in the Edit view below. Continue?')
                    ->action(function (Project $record) {
                        \App\Jobs\DeployProjectJob::dispatch($record);
                        
                        Notification::make()
                            ->title('Deployment Job Queued')
                            ->body('You can check the logs inside the Project edit page to see the progress.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('rotateApiKey')
                    ->label('Rotate Public Key')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will generate a new Public API Key and PUSH IT to the remote node. Existing consumers with the old key will be broken. Continue?')
                    ->action(function (Project $record, RemoteEngineService $service) {
                        $newKey = \Illuminate\Support\Str::random(48);
                        $record->update(['public_api_key' => $newKey]);
                        
                        // Push to Node
                        $service->updateCredentials($record, [
                            'APP_API_KEY' => $newKey
                        ]);

                        Notification::make()
                            ->title('Key Rotated & Pushed')
                            ->body('The new key has been synchronized with the remote instance.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('hardDelete')
                    ->label('HARD DELETE (INFRA)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Permanent Infrastructure & Project Removal')
                    ->modalDescription('This will PERMANENTLY remove all server containers, volumes, and project files, then delete from database. This cannot be undone. Continue?')
                    ->action(function (Project $record, DeployerService $deployer) {
                        // 1. SSH into server and clean up
                        $result = $deployer->removeInstance($record);
                        
                        if ($result['status'] === 'success') {
                            // 2. Perform Hard Delete in DB
                            $record->forceDelete();
                            
                            Notification::make()
                                ->title('Project & Infrastructure Deleted')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cleanup Failed')
                                ->body('The infrastructure removal failed. DB record kept for manual inspection: ' . ($result['output'] ?? ''))
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })
                    ->visible(fn () => Auth::user()?->is_admin ?? false),

                Tables\Actions\DeleteAction::make()
                    ->label('Archive Project')
                    ->successNotificationTitle('Project archived (Soft deleted)'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProjectResource\RelationManagers\DeploymentLogsRelationManager::class,
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
