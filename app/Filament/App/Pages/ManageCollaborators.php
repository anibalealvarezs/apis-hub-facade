<?php

namespace App\Filament\App\Pages;

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\ProjectUserAllowedAsset;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ManageCollaborators extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.app.pages.manage-collaborators';

    public static function getNavigationLabel(): string
    {
        return __('Team & Collaborators');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public function getTitle(): string
    {
        return __('Team & Collaborators');
    }

    /**
     * Asegurar que solo dueños/admins del proyecto puedan ver esta página.
     */
    public static function canAccess(): bool
    {
        // Temporalmente permitimos a todos, o podemos usar policies.
        // Filament Shield maneja permisos de páginas como page_ManageCollaborators
        return true;
    }

    /**
     * Definir la tabla de miembros activos
     */
    public function table(Table $table): Table
    {
        $project = Filament::getTenant();

        return $table
            ->query(User::query()->whereHas('projects', fn ($q) => $q->where('projects.id', $project->id)))
            ->columns([
                TextColumn::make('name')->label(__('Name')),
                TextColumn::make('email')->label(__('Email')),
                TextColumn::make('project_roles')
                    ->label(__('Role in this Project'))
                    ->getStateUsing(function (User $record) use ($project) {
                        // Usamos DB directa para evitar que el HasRoles de Spatie intente
                        // sobre-filtrar basándose en un team_id estático nulo.
                        return \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_id', $record->id)
                            ->where('model_has_roles.project_id', $project->id)
                            ->pluck('roles.name')
                            ->map(fn ($name) => Str::headline($name))
                            ->join(', ') ?: __('No specific role');
                    }),
                TextColumn::make('can_expel')
                    ->label(__('Can Expel'))
                    ->badge()
                    ->getStateUsing(fn (User $record) => $record->can('manage_collaborators') ? __('Yes') : __('No'))
                    ->color(fn (User $record) => $record->can('manage_collaborators') ? 'success' : 'danger'),
            ])
            ->actions([
                Action::make('abandon')
                    ->label(__('Abandon Project'))
                    ->color('warning')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->requiresConfirmation()
                    ->visible(function (User $record) use ($project) {
                        if ($record->id !== auth()->id()) {
                            return false;
                        }

                        return ! \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_id', $record->id)
                            ->where('model_has_roles.project_id', $project->id)
                            ->where('roles.name', 'project_owner')
                            ->exists();
                    })
                    ->action(function (User $record) use ($project) {
                        $record->projects()->detach($project->id);

                        $editorAndOwnerIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereIn('roles.name', ['project_editor', 'project_owner'])
                            ->where('model_has_roles.project_id', $project->id)
                            ->where('model_has_roles.model_id', '!=', $record->id)
                            ->pluck('model_has_roles.model_id')
                            ->unique()
                            ->values()
                            ->toArray();

                        $usersToNotify = User::whereIn('id', $editorAndOwnerIds)->get();
                        foreach ($usersToNotify as $notifyUser) {
                            $notifyUser->notify(new \App\Notifications\CollaboratorLeftProject($project, $record->name));
                        }

                        return redirect(Filament::getUrl())->with('success', __('You have abandoned the project'));
                    }),
                Action::make('remove')
                    ->label(__('Remove'))
                    ->color('danger')
                    ->icon('heroicon-o-user-minus')
                    ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended')
                    ->requiresConfirmation()
                    ->hidden(function (User $record) use ($project) {
                        if (! auth()->user()->can('manage_collaborators')) {
                            return true;
                        }

                        // Un project owner no puede ser expulsado de la colaboración
                        return \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_id', $record->id)
                            ->where('model_has_roles.project_id', $project->id)
                            ->where('roles.name', 'project_owner')
                            ->exists();
                    })
                    ->action(function (User $record) use ($project) {
                        if ($record->id === auth()->id()) {
                            Notification::make()->danger()->title(__('You cannot remove yourself'))->send();

                            return;
                        }

                        $record->projects()->detach($project->id);

                        $record->notify(new \App\Notifications\MemberExpelledFromProject($project));

                        Notification::make()->success()->title(__('User removed from project'))->send();
                    }),
                Action::make('manage_assets')
                    ->label(__('Manage Assets'))
                    ->icon('heroicon-o-shield-check')
                    ->modalHeading(fn (User $record) => __('Asset scoping:') . " {$record->name}")
                    ->modalDescription(__('Restrict which assets this user can see in dashboards. When "Allow all" is on, the user sees every enabled asset for that channel.'))
                    ->modalWidth('2xl')
                    ->hidden(function (User $record) use ($project) {
                        if (!auth()->user()->can('manage_collaborators')) {
                            return true;
                        }

                        return \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_id', $record->id)
                            ->where('model_has_roles.project_id', $project->id)
                            ->where('roles.name', 'project_owner')
                            ->exists();
                    })
                    ->mountUsing(function (\Filament\Forms\Form $form, User $record) use ($project) {
                        $allowedAssets = ProjectUserAllowedAsset::where('project_id', $project->id)
                            ->where('user_id', $record->id)
                            ->get()
                            ->keyBy('channel');

                        $data = [];
                        foreach ($this->getActiveChannels($project) as $channel => $label) {
                            $existing = $allowedAssets->get($channel);
                            $data["allow_all_{$channel}"] = !$existing || $existing->allowed_assets === null;
                            $data["assets_{$channel}"] = $existing && $existing->allowed_assets !== null
                                ? $existing->allowed_assets
                                : [];
                        }
                        $form->fill($data);
                    })
                    ->form(fn (User $record) => $this->buildAssetScopeForm($record, $project))
                    ->action(function (array $data, User $record) use ($project) {
                        foreach ($this->getActiveChannels($project) as $channel => $label) {
                            $allowAll = $data["allow_all_{$channel}"] ?? false;

                            if ($allowAll) {
                                ProjectUserAllowedAsset::updateOrCreate(
                                    ['project_id' => $project->id, 'user_id' => $record->id, 'channel' => $channel],
                                    ['allowed_assets' => null]
                                );
                            } else {
                                $assets = $data["assets_{$channel}"] ?? [];
                                ProjectUserAllowedAsset::updateOrCreate(
                                    ['project_id' => $project->id, 'user_id' => $record->id, 'channel' => $channel],
                                    ['allowed_assets' => $assets]
                                );
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title(__('Asset scoping updated for :name', ['name' => $record->name]))
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label(__('Invite Collaborator'))
                    ->icon('heroicon-o-envelope')
                    ->hidden(fn () => ! auth()->user()->can('manage_collaborators'))
                    ->disabled(fn () => ! Filament::getTenant()->is_active || Filament::getTenant()->billing_status === 'suspended' || Filament::getTenant()->billingProfile?->tier === \App\Enums\UserTier::FREE)
                    ->tooltip(function () {
                        $tenant = Filament::getTenant();
                        if (! $tenant->is_active || $tenant->billing_status === 'suspended') {
                            return __('Project is inactive or suspended.');
                        }
                        if ($tenant->billingProfile?->tier === \App\Enums\UserTier::FREE) {
                            return __('Upgrade to a paid plan to invite collaborators.');
                        }

                        return null;
                    })
                    ->form([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label(__('Collaborator Email')),
                        Select::make('role')
                            ->label(__('Project Role'))
                            ->options(
                                Role::whereIn('name', ['project_editor', 'project_viewer', 'project_user'])->pluck('name', 'name')
                            )
                            ->required(),
                    ])
                    ->action(function (array $data) use ($project) {
                        // 1. Verificar si ya es miembro
                        $alreadyMember = $project->users()->where('email', $data['email'])->exists();
                        if ($alreadyMember) {
                            Notification::make()->danger()->title(__('This user is already a member of the project.'))->send();

                            return;
                        }

                        // 2. Verificar si ya hay invitación pendiente
                        $alreadyInvited = ProjectInvitation::where('project_id', $project->id)
                            ->where('email', $data['email'])
                            ->exists();

                        if ($alreadyInvited) {
                            Notification::make()->warning()->title(__('An invitation is already pending for this email.'))->send();

                            return;
                        }

                        // 3. Crear invitación
                        $invitation = ProjectInvitation::create([
                            'project_id' => $project->id,
                            'email' => $data['email'],
                            'role' => $data['role'],
                            'token' => Str::random(32),
                            'expires_at' => now()->addDays(7),
                        ]);

                        // 4. Enviar correo
                        Mail::to($data['email'])->send(new ProjectInvitationMail($invitation));

                        $editorAndOwnerIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereIn('roles.name', ['project_editor', 'project_owner'])
                            ->where('model_has_roles.project_id', $project->id)
                            ->pluck('model_has_roles.model_id')
                            ->unique()
                            ->values()
                            ->toArray();

                        $usersToNotify = User::whereIn('id', $editorAndOwnerIds)->get();
                        foreach ($usersToNotify as $notifyUser) {
                            $notifyUser->notify(new \App\Notifications\InvitationSent($project, $data['email'], $data['role']));
                        }

                        $inviteUrl = url("/app/invitations/{$invitation->token}/accept");

                        Notification::make()
                            ->success()
                            ->title(__('Invitation sent via email.'))
                            ->body(__('Share this link with the collaborator if they don\'t receive the email:') . ' ' . $inviteUrl)
                            ->send();
                    }),
            ]);
    }

    protected function getActiveChannels($project): array
    {
        if (!$project || empty($project->sync_config)) {
            return [];
        }

        $validChannels = ['facebook_marketing', 'facebook_organic', 'google_search_console'];
        $active = [];

        foreach ($project->sync_config as $channel => $data) {
            if (in_array($channel, $validChannels) && !empty($data['enabled'])) {
                $active[$channel] = Str::headline(str_replace('_', ' ', $channel));
            }
        }

        return $active;
    }

    protected function getAssetsForChannel($project, string $channel): array
    {
        $config = $project->sync_config[$channel] ?? [];
        $assets = [];
        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops'];

        $searchIn = function ($items) use (&$assets) {
            if (!is_array($items)) {
                return;
            }
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['enabled']) && empty($item['lost_access'])) {
                    $id = $item['id'] ?? $item['url'] ?? '';
                    $name = $item['name'] ?? $item['url'] ?? $id;
                    if ($id) {
                        $assets[$id] = $name;
                    }
                }
            }
        };

        foreach ($assetKeys as $assetKey) {
            if (!empty($config[$assetKey]) && is_array($config[$assetKey])) {
                $searchIn($config[$assetKey]);
            }
        }

        if (!empty($config['assets']) && is_array($config['assets'])) {
            foreach ($assetKeys as $assetKey) {
                if (!empty($config['assets'][$assetKey]) && is_array($config['assets'][$assetKey])) {
                    $searchIn($config['assets'][$assetKey]);
                }
            }
        }

        return $assets;
    }

    protected function buildAssetScopeForm(User $record, $project): array
    {
        $schema = [];

        foreach ($this->getActiveChannels($project) as $channel => $label) {
            $assets = $this->getAssetsForChannel($project, $channel);

            if (empty($assets)) {
                continue;
            }

            $schema[] = Section::make($label)
                ->description(__('Restrict which :label assets this user can access', ['label' => $label]))
                ->schema([
                    Toggle::make("allow_all_{$channel}")
                        ->label(__('Allow all :label assets', ['label' => $label]))
                        ->default(true)
                        ->reactive(),
                    Select::make("assets_{$channel}")
                        ->label(__('Select specific assets'))
                        ->options($assets)
                        ->multiple()
                        ->visible(fn (callable $get) => !$get("allow_all_{$channel}")),
                ])
                ->columns(1);
        }

        return $schema;
    }
}
