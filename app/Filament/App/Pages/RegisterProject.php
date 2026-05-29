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

        if (Auth::user()->billingProfiles()->count() === 0) {
            \Filament\Notifications\Notification::make()
                ->title('Billing Profile Required')
                ->body('You must create a billing profile before you can register a new project.')
                ->danger()
                ->persistent()
                ->send();

            redirect()->route('filament.account.resources.billing-profiles.create');
        }

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
        return DataSources::getUrl([
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
                            ->required(fn ($get) => empty($get('share_code'))),
                        TextInput::make('subdomain')
                            ->label('Subdomain / Unique Identifier')
                            ->prefix('https://')
                            ->suffix(function () {
                                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                                return (config('app.env') !== 'production') ? "-dev.{$domain}" : ".{$domain}";
                            })
                            ->placeholder('acme')
                            ->required(fn ($get) => empty($get('share_code')))
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
                        TextInput::make('share_code')
                            ->label('Código de Invitación / Compartición (Opcional)')
                            ->placeholder('APISHUB-XXXX-XXXX')
                            ->helperText('Si tienes un código para unirte a un proyecto existente, ingrésalo aquí en lugar de crear un proyecto nuevo.'),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = Auth::user();

        // 1. Interceptar si viene un código de invitación/compartición
        if (!empty($data['share_code'])) {
            $token = \App\Models\OneTimeShareToken::where('token', $data['share_code'])
                ->whereNull('used_at')
                ->first();

            if (!$token) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'share_code' => 'El código de invitación no es válido, ha expirado o ya ha sido utilizado.',
                ]);
            }

            // Validar límites de plan gratuito (máximo 1 proyecto en total)
            if ($user->hasOnlyFreeProfiles() && $user->getTotalAccessibleProjectsCount() >= 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'share_code' => 'Si solo se cuenta con un perfil propio free tier, solo se puede acceder a un único proyecto. Para poder acceder a un proyecto como colaborador, debe eliminar el proyecto de su perfil free tier.',
                ]);
            }

            // Crear solicitud de colaboración / invitación
            $project = $token->project;
            \App\Models\ProjectInvitation::create([
                'project_id' => $project->id,
                'email' => $user->email,
                'role' => 'collaborator',
                'token' => \Illuminate\Support\Str::random(32),
                'status' => 'pending',
            ]);

            // Marcar el token como usado
            $token->update(['used_at' => now()]);

            \Filament\Notifications\Notification::make()
                ->title('Solicitud de Colaboración Enviada')
                ->body('Se ha registrado tu solicitud para unirte al proyecto. El dueño del proyecto deberá aprobar tu acceso.')
                ->success()
                ->persistent()
                ->send();

            // Redireccionar al dashboard principal de Filament
            redirect()->to('/app');
            return new Project(); // Retorno dummy
        }

        // 2. Si no viene código, proceder a crear proyecto normal
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

        $defaultProfile = $user->billingProfiles()->where('is_default', true)->first() 
            ?? $user->billingProfiles()->first();

        $project = Project::create([
            'name' => $data['name'],
            'subdomain' => $subdomain,
            'server_id' => $server?->id,
            'user_id' => Auth::id(), 
            'git_repo' => 'https://github.com/anibalealvarezs/apis-hub.git', // Default repo
            'git_branch' => 'main',
            'monitoring_token' => (string) \Illuminate\Support\Str::uuid(),
            'remote_admin_api_key' => bin2hex(random_bytes(32)),
            'billing_profile_id' => $defaultProfile?->id,
            'billing_status' => 'active',
            'is_active' => true,
        ]);

        $project->users()->attach($user);

        // Asignar el rol de administrador de proyecto al creador de forma forzada y directa
        $role = \Spatie\Permission\Models\Role::where('name', 'project_owner')->first();
        if ($role) {
            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $role->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'project_id' => $project->id,
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Proyecto Creado')
            ->body('El proyecto ha sido registrado. Por favor, configura tus fuentes de datos (Data Sources) antes de iniciar el despliegue de infraestructura.')
            ->success()
            ->send();

        return $project;
    }
}
