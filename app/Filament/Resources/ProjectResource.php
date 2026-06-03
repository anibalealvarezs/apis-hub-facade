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
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (config('app.env') === 'production' && str_ends_with($value, '-dev')) {
                                        $fail('No se permiten subdominios terminados en "-dev" en producción.');
                                    }
                                    
                                    $reserved = ['analytics', 'api', 'app', 'www', 'admin', 'facade', 'server', 'dev', 'test', 'demo', 'gbs'];
                                    $cleanValue = strtolower(str_replace('-dev', '', $value));
                                    
                                    if (in_array($cleanValue, $reserved)) {
                                        $fail('The subdomain "' . $cleanValue . '" is reserved for internal infrastructure and cannot be used.');
                                    }
                                };
                            })
                            ->live(onBlur: true)
                            ->disabled(fn (?Project $record) => $record !== null)
                            ->suffix(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                return (config('app.env') !== 'production') ? "-dev.{$domain}" : ".{$domain}";
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (config('app.env') !== 'production' && !str_ends_with($state, '-dev')) {
                                    return $state . '-dev';
                                }
                                return $state;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if (config('app.env') !== 'production' && !str_ends_with($state, '-dev')) {
                                    $state .= '-dev';
                                }
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
                            ->helperText(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                $msg = 'Assign a subdomain (e.g. "client1") for the instance. Checked against DB. Full URL will be: subdomain.' . $domain;
                                if (config('app.env') !== 'production') {
                                    $msg .= ' (Non-production Environment: "-dev" will be automatically appended to prevent SSL routing issues).';
                                }
                                return $msg;
                            }),
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
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Suspended'),
                Tables\Columns\TextColumn::make('billing_status')
                    ->label('Billing')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
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
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Project $record) => $record->is_active ? 'Suspend' : 'Activate')
                    ->icon(fn (Project $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Project $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Project $record) => $record->is_active ? 'Suspend Infrastructure?' : 'Resume Infrastructure?')
                    ->modalDescription(fn (Project $record) => $record->is_active 
                        ? 'This will stop all Docker containers and synchronization jobs for this instance on the remote server.'
                        : 'This will restart the containers on the server and resume sync jobs.')
                    ->disabled(fn (Project $record) => $record->health_status === 'provisioning')
                    ->action(function (Project $record, DeployerService $deployer, Tables\Actions\Action $action) {
                        $newStatus = !$record->is_active;
                        
                        // 1. SSH Command (Blocking)
                        $result = $newStatus ? $deployer->startContainers($record) : $deployer->stopContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title('Infrastructure Command Failed')
                                ->body('Could not ' . ($newStatus ? 'start' : 'stop') . ' containers: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel DB update
                            return;
                        }

                        // 2. Update DB ONLY on success
                        $record->update(['is_active' => $newStatus]);

                        // 3. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => $newStatus,
                            'event_type' => 'manual_toggle',
                            'created_by_id' => Auth::id(),
                            'notes' => $newStatus ? 'Project resumed manually' : 'Project suspended manually',
                        ]);

                        Notification::make()
                            ->title($newStatus ? 'Infrastructure Online' : 'Infrastructure Suspended')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->modalHeading('Restore Project & Resume Infra')
                    ->before(function (Project $record, DeployerService $deployer, Tables\Actions\RestoreAction $action) {
                        // 1. Quota Validation
                        if (!$record->billing_profile_id) {
                            Notification::make()
                                ->title('Missing Billing Profile')
                                ->body('This project has no billing profile assigned. Please assign one before restoring.')
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                            return;
                        }

                        $billingService = app(\App\Services\BillingLifecycleService::class);
                        $profile = $record->billingProfile;
                        $maxProjects = $billingService->getMaxProjectsForTier($profile->tier);
                        
                        // We do not count the current project since it is currently soft-deleted
                        $activeProjectsCount = $profile->projects()->where('billing_status', 'active')->count();

                        if ($activeProjectsCount >= $maxProjects) {
                            Notification::make()
                                ->title('Quota Exceeded')
                                ->body('The billing profile for this project has reached its maximum active projects limit (' . $maxProjects . '). Please upgrade the tier or suspend another project before restoring.')
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->halt();
                            return;
                        }

                        // 2. Resume containers
                        $result = $deployer->startContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title('Restoration Aborted')
                                ->body('Failed to restart containers on server: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel Restoration
                            return;
                        }
                        
                        // 2. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => true,
                            'event_type' => 'restore',
                            'created_by_id' => Auth::id(),
                            'notes' => 'Project restored from archive',
                        ]);
                    }),
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
                    ->modalHeading(fn (Project $record) => "Permanently Delete Project: {$record->subdomain}")
                    ->modalDescription(fn (Project $record) => "This will PERMANENTLY destroy all data, containers and configurations for '{$record->name}'. This action is IRREVERSIBLE.")
                    ->form([
                        Forms\Components\TextInput::make('confirm_subdomain')
                            ->label(fn (Project $record) => "Please type '{$record->subdomain}' to confirm.")
                            ->required()
                            ->rules([
                                fn (Project $record) => function (string $attribute, $value, $fail) use ($record) {
                                    if ($value !== $record->subdomain) {
                                        $fail("The subdomain you typed does not match.");
                                    }
                                },
                            ]),
                    ])
                    ->action(function (Project $record, DeployerService $deployer, array $data) {
                        // 1. SSH into server and clean up (Containers, files and Caddy)
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
                    ->modalHeading('Archive Project & Stop Infra')
                    ->modalDescription('Archiving will hide the project from the tenant view and STOP its infrastructure on the server. Data will be kept.')
                    ->before(function (Project $record, DeployerService $deployer, Tables\Actions\DeleteAction $action) {
                        // 1. Stop containers before archiving
                        $result = $deployer->stopContainers($record);

                        if ($result['status'] !== 'success') {
                             Notification::make()
                                ->title('Archive Aborted')
                                ->body('Failed to stop containers on server: ' . ($result['output'] ?? 'SSH Error'))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->halt(); // Cancel Deletion
                            return;
                        }
                        
                        // 2. Log into History
                        \App\Models\ProjectStatusLog::create([
                            'project_id' => $record->id,
                            'is_active' => false,
                            'event_type' => 'archive',
                            'created_by_id' => Auth::id(),
                            'notes' => 'Project archived (Soft deleted)',
                        ]);
                    })
                    ->successNotificationTitle('Project archived and infrastructure stopped'),
            ])
            ->bulkActions([
                // Deshabilitado el borrado masivo por seguridad (Instrucción #1)
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
            \App\Filament\Resources\ProjectResource\RelationManagers\DeploymentLogsRelationManager::class,
            \App\Filament\Resources\ProjectResource\RelationManagers\StatusLogsRelationManager::class,
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
