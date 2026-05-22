<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RegisterProject extends RegisterTenant
{
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
                            ->suffix('.apis-hub.cloud')
                            ->placeholder('acme')
                            ->required()
                            ->unique('projects', 'subdomain')
                            ->alphaDash()
                            ->helperText('Caution: This identifier is permanent and cannot be changed after creation.'),
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

        $project = Project::create([
            'name' => $data['name'],
            'subdomain' => $data['subdomain'],
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

        // Asignar el rol de administrador de proyecto al creador
        setPermissionsTeamId($project->id);
        if (!$user->hasRole('project_owner')) {
            $user->assignRole('project_owner');
        }

        if ($server) {
            // Trigger remote deployment via asynchronous Job
            \App\Jobs\DeployProjectJob::dispatch($project);
            
            \Filament\Notifications\Notification::make()
                ->title('Project Created & Deployment Queued')
                ->body('The infrastructure is being provisioned in the background. Please configure your data synchronization settings.')
                ->success()
                ->persistent()
                ->send();
        }

        return $project;
    }
}
