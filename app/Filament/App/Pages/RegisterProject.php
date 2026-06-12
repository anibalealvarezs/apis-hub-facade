<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
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
                ->title(__('Billing Profile Required'))
                ->body(__('You must create a billing profile before you can register a new project.'))
                ->danger()
                ->persistent()
                ->send();

            redirect()->route('filament.account.resources.billing-profiles.create');
            return;
        }

        if (!Auth::user()->canCreateMoreProjects() && $this->data['mode'] !== 'join') {
            \Filament\Notifications\Notification::make()
                ->title(__('Project Limit Reached'))
                ->body(__('You have reached the maximum number of projects for your current tier. Please upgrade your subscription to create more projects.'))
                ->danger()
                ->persistent()
                ->send();

            redirect()->route('filament.account.pages.account-subscription');
            return;
        }
    }

    public static function getLabel(): string
    {
        return __('Create or Join a Project');
    }

    public function getTitle(): string
    {
        if (property_exists($this, 'data') && isset($this->data['mode']) && $this->data['mode'] === 'join') {
            return __('Join an Existing Project');
        }
        return __('Setup Your New Project');
    }

    protected function getSubmitFormAction(): \Filament\Actions\Action
    {
        return parent::getSubmitFormAction()
            ->label(fn ($get) => $get('mode') === 'join' ? __('Join Project') : __('Create Project & Deploy'));
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
                ToggleButtons::make('mode')
                    ->label(__('What would you like to do?'))
                    ->options([
                        'create' => __('Create a new project'),
                        'join' => __('Join an existing project'),
                    ])
                    ->default('create')
                    ->inline()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'join') {
                            $set('name', null);
                            $set('subdomain', null);
                        } else {
                            $set('share_code', null);
                        }
                    }),
                Section::make(__('Project Setup'))
                    ->description(__('Define your project identity. This will create a dedicated workspace on our high-performance cloud infrastructure.'))
                    ->visible(fn ($get) => $get('mode') === 'create')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Project / Business Name'))
                            ->placeholder(__('e.g. Acme Marketing'))
                            ->required(),
                        TextInput::make('subdomain')
                            ->label(__('Subdomain / Unique Identifier'))
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
                                        $fail(__('No \'-dev\' subdomains are allowed in the production environment to prevent collisions with development environments.'));
                                    }
                                    
                                    $reservedFile = database_path('data/reserved_subdomains.json');
                                    $reserved = file_exists($reservedFile) ? json_decode(file_get_contents($reservedFile), true) : [];
                                    $cleanValue = strtolower(str_replace('-dev', '', $value));
                                    
                                    if (in_array($cleanValue, $reserved)) {
                                        $fail(__('The subdomain ":subdomain" is reserved for internal infrastructure and cannot be used.', ['subdomain' => $cleanValue]));
                                    }
                                };
                            })
                            ->helperText(function () {
                                $msg = __('Caution: This identifier is permanent and cannot be changed after creation.');
                                if (config('app.env') !== 'production') {
                                    $msg .= ' ' . __('(Non-production Environment: "-dev" will be automatically appended to your subdomain to prevent SSL routing issues).');
                                }
                                return $msg;
                            }),
                    ]),
                Section::make(__('Join Existing Project'))
                    ->description(__('Enter the share code provided by the project owner to join their project as a collaborator.'))
                    ->visible(fn ($get) => $get('mode') === 'join')
                    ->schema([
                        TextInput::make('share_code')
                            ->label(__('Share Code'))
                            ->placeholder('APISHUB-XXXX-XXXX')
                            ->required()
                            ->helperText(__('Paste the code shared by the project owner. Your request will be sent for approval.')),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = Auth::user();

        // Join an existing project via share code
        if (($data['mode'] ?? '') === 'join') {
            $token = \App\Models\OneTimeShareToken::where('token', $data['share_code'])
                ->whereNull('used_at')
                ->first();

            if (!$token) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'share_code' => __('The invitation code is invalid, expired, or has already been used.'),
                ]);
            }

            // Validar límites de plan gratuito (máximo 1 proyecto en total)
            if ($user->hasOnlyFreeProfiles() && $user->getTotalAccessibleProjectsCount() >= 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'share_code' => __('If you only have a free tier profile, you can only access one project. To join a project as a collaborator, you must delete the project from your free tier profile.'),
                ]);
            }

            // Crear solicitud de colaboración / invitación
            $project = $token->project;
            \App\Models\ProjectInvitation::create([
                'project_id' => $project->id,
                'email' => $user->email,
                'role' => 'collaborator',
                'token' => \Illuminate\Support\Str::random(32),
                'expires_at' => now()->addDays(7),
            ]);

            // Marcar el token como usado
            $token->update(['used_at' => now()]);

            \Filament\Notifications\Notification::make()
                ->title(__('Collaboration Request Sent'))
                ->body(__('Your request to join the project has been registered. The project owner must approve your access.'))
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
                ->title(__('No Ready Server Found'))
                ->body(__('Could not assign a server to your project. Deployment will be queued.'))
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

            // Clear caches so the new role is immediately recognized by Filament and Spatie
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }

        \Filament\Notifications\Notification::make()
            ->title(__('Project Created'))
            ->body(__('The project has been registered. Please configure your data sources before starting the infrastructure deployment.'))
            ->success()
            ->send();

        return $project;
    }
}
