<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterProject extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Deploy Your APIs Hub Instance';
    }

    public function getTitle(): string
    {
        return 'Deploy Instance';
    }

    protected function getSubmitFormAction(): \Filament\Actions\Action
    {
        return parent::getSubmitFormAction()
            ->label('Provision Instance Now');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Instance Deployment')
                    ->description('Provision a new dedicated APIs Hub instance on our high-performance infrastructure.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Project / Business Name')
                            ->placeholder('e.g. Acme Marketing')
                            ->required(),
                        TextInput::make('subdomain')
                            ->label('Subdomain / Unique Identifier')
                            ->prefix('https://')
                            ->suffix('.apis-hub.dev')
                            ->placeholder('acme')
                            ->required()
                            ->unique('projects', 'subdomain')
                            ->alphaDash(),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $project = Project::create([
            'name' => $data['name'],
            'subdomain' => $data['subdomain'],
            'owner_id' => auth()->id(), // Ensuring the current user is recorded as owner
            // Generating default monitoring/admin tokens
            'monitoring_token' => (string) \Illuminate\Support\Str::uuid(),
            'remote_admin_api_key' => bin2hex(random_bytes(32)),
        ]);

        $project->users()->attach(auth()->user());

        return $project;
    }
}
