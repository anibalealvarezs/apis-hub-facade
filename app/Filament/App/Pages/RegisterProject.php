<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RegisterProject extends RegisterTenant
{
    public function mount(): void
    {
        parent::mount();

        if (!Auth::user()->canCreateMoreProjects()) {
            \Filament\Notifications\Notification::make()
                ->title('Project Limit Reached')
                ->body('You have reached the maximum number of projects for your current tier. Please upgrade your subscription to create more projects.')
                ->danger()
                ->persistent()
                ->send();

            redirect()->route('filament.account.pages.account-subscription');
        }
    }

    public static function getLabel(): string
    {
        return 'Create Your APIs Hub Project';
    }

    public function getTitle(): string
    {
        return 'Setup Your New Project';
    }

    protected function getSubmitFormAction(): \Filament\Actions\Action
    {
        return parent::getSubmitFormAction()
            ->label('Create Project & Deploy');
    }

    protected function getRedirectUrl(): string
    {
        return SyncSettings::getUrl([
            'tenant' => $this->tenant,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Project Setup')
                    ->description('Define your project identity. This will create a dedicated workspace on our high-performance cloud infrastructure.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Project / Business Name')
                            ->placeholder('e.g. Acme Marketing')
                            ->required(),
                        TextInput::make('subdomain')
                            ->label('Subdomain / Unique Identifier')
                            ->prefix('https://')
                            ->suffix(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                return (config('app.env') !== 'production') ? "-dev.{$domain}" : ".{$domain}";
                            })
                            ->placeholder('acme')
                            ->required()
                            ->unique('projects', 'subdomain')
                            ->alphaDash()
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (config('app.env') === 'production' && str_ends_with($value, '-dev')) {
                                        $fail('No se permiten subdominios terminados en "-dev" en el entorno de producción para evitar colisiones con entornos de desarrollo.');
                                    }
                                };
                            })
                            ->helperText(function () {
                                $msg = 'Caution: This identifier is permanent and cannot be changed after creation.';
                                if (config('app.env') !== 'production') {
                                    $msg .= ' (Non-production Environment: "-dev" will be automatically appended to your subdomain to prevent SSL routing issues).';
                                }
                                return $msg;
                            }),
                        Checkbox::make('deploy_immediately')
                            ->label('Desplegar infraestructura inmediatamente')
                            ->helperText('Si se marca, el sistema aprovisionará los contenedores en el servidor ahora. De lo contrario, se creará el proyecto solo lógicamente.')
                            ->default(false),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $server = \App\Models\Server::where('is_ready', true)->first();
        
        if (!$server) {
            \Filament\Notifications\Notification::make()
                ->title('No Ready Server Found')
                ->body('Could not assign a server to your project. Deployment will be queued.')
                ->warning()
                ->send();
        }

        $subdomain = $data['subdomain'];
        if (config('app.env') !== 'production' && !str_ends_with($subdomain, '-dev')) {
            $subdomain .= '-dev';
        }

        $project = Project::create([
            'name' => $data['name'],
            'subdomain' => $subdomain,
            'server_id' => $server?->id,
            'user_id' => Auth::id(), 
            'git_repo' => 'https://github.com/anibalealvarezs/apis-hub.git', // Default repo
            'git_branch' => 'main',
            'monitoring_token' => (string) \Illuminate\Support\Str::uuid(),
            'remote_admin_api_key' => bin2hex(random_bytes(32)),
            'is_active' => true,
        ]);

        $user = Auth::user();
        $project->users()->attach($user);

        // Asignar el rol de administrador de proyecto al creador de forma forzada y directa
        // Esto evita problemas de caché de Spatie durante la sesión activa
        $role = \Spatie\Permission\Models\Role::where('name', 'project_owner')->first();
        if ($role) {
            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $role->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'project_id' => $project->id,
            ]);
        }

        if ($server && ($data['deploy_immediately'] ?? false)) {
            // Trigger remote deployment via asynchronous Job
            \App\Jobs\DeployProjectJob::dispatch($project);
            
            \Filament\Notifications\Notification::make()
                ->title('Project Created & Deployment Queued')
                ->body('The infrastructure is being provisioned in the background. Please configure your data synchronization settings.')
                ->success()
                ->persistent()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Proyecto Creado')
                ->body('El proyecto ha sido registrado, pero el despliegue de infraestructura fue omitido.')
                ->success()
                ->send();
        }

        return $project;
    }
}
