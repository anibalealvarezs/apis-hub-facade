<?php

namespace App\Filament\App\Pages;

use App\Mail\ProjectInvitationMail;
use App\Models\AssetGroup;
use App\Models\ProjectInvitation;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
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
            ->query(User::query()->whereHas('projects', function ($q) use ($project) {
                $q->where('projects.id', $project->id);
            })->when(!auth()->user()->can('view_settings'), function ($query) {
                $query->where('id', auth()->id());
            }))
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
                TextColumn::make('asset_access')
                    ->label(__('Asset access'))
                    ->badge()
                    ->getStateUsing(function (User $record) use ($project) {
                        $service = app(\App\Services\CollaboratorAssetAccessService::class);

                        if ($service->isUnrestricted($project, $record->id)) {
                            return __('All assets');
                        }

                        $hasGroups = $service->getSharedAssetGroups($project, $record->id)->isNotEmpty();

                        return $hasGroups ? __('Custom list') : __('No access');
                    })
                    ->color(function (User $record) use ($project) {
                        $service = app(\App\Services\CollaboratorAssetAccessService::class);

                        if ($service->isUnrestricted($project, $record->id)) {
                            return 'success';
                        }

                        return $service->getSharedAssetGroupIds($project, $record->id) ? 'warning' : 'danger';
                    })
                    ->action(
                        Action::make('view_asset_list')
                            ->modalHeading(fn (Action $action) => __('Asset access:') . ' ' . ($action->getRecord()?->name ?? ''))
                            ->modalWidth('2xl')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('Close'))
                            ->disabled(function (Action $action) use ($project) {
                                $record = $action->getRecord();

                                if (! $record instanceof User) {
                                    return true;
                                }

                                $service = app(\App\Services\CollaboratorAssetAccessService::class);

                                return $service->isUnrestricted($project, $record->id)
                                    || $service->getSharedAssetGroups($project, $record->id)->isEmpty();
                            })
                            ->modalContent(function (Action $action) use ($project) {
                                $record = $action->getRecord();

                                if (! $record instanceof User) {
                                    return null;
                                }

                                $sharedGroups = app(\App\Services\CollaboratorAssetAccessService::class)
                                    ->getSharedAssetGroups($project, $record->id)
                                    ->load('items');

                                return view('filament.modals.collaborator-asset-list', [
                                    'user' => $record,
                                    'project' => $project,
                                    'sharedGroups' => $sharedGroups,
                                ]);
                            })
                    ),
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
                    ->label(__('Manage Asset Groups'))
                    ->icon('heroicon-o-shield-check')
                    ->modalHeading(fn (User $record) => __('Asset access:') . " {$record->name}")
                    ->modalDescription(__('When "Allow all assets" is off, this user only sees assets inside the selected asset groups.'))
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
                        $service = app(\App\Services\CollaboratorAssetAccessService::class);

                        $form->fill([
                            'asset_access_unrestricted' => $service->isUnrestricted($project, $record->id),
                            'asset_group_ids' => $service->getSharedAssetGroupIds($project, $record->id),
                        ]);
                    })
                    ->form(fn () => [
                        Toggle::make('asset_access_unrestricted')
                            ->label(__('Allow all assets'))
                            ->helperText(__('When on, this user can see every enabled asset in the project. When off, only the selected asset groups are shared.'))
                            ->default(true)
                            ->reactive(),
                        CheckboxList::make('asset_group_ids')
                            ->label(__('Shared asset groups'))
                            ->options(
                                AssetGroup::where('project_id', $project->id)
                                    ->withCount('items')
                                    ->get()
                                    ->mapWithKeys(fn ($group) => [$group->id => "{$group->name} ({$group->items_count})"])
                                    ->toArray()
                            )
                            ->columns(2)
                            ->visible(fn (Get $get) => ! $get('asset_access_unrestricted')),
                    ])
                    ->action(function (array $data, User $record) use ($project) {
                        \Illuminate\Support\Facades\DB::table('project_user')
                            ->where('project_id', $project->id)
                            ->where('user_id', $record->id)
                            ->update(['asset_access_unrestricted' => (bool) ($data['asset_access_unrestricted'] ?? true)]);

                        ProjectUserAssetGroup::where('project_id', $project->id)
                            ->where('user_id', $record->id)
                            ->delete();

                        foreach (($data['asset_group_ids'] ?? []) as $groupId) {
                            ProjectUserAssetGroup::create([
                                'project_id' => $project->id,
                                'user_id' => $record->id,
                                'asset_group_id' => $groupId,
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title(__('Asset access updated for :name', ['name' => $record->name]))
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label(__('Invite Collaborator'))
                    ->icon('heroicon-o-envelope')
                    ->hidden(fn () => ! auth()->user()->can('manage_collaborators'))
                    ->disabled(function () {
                        $tenant = Filament::getTenant();
                        if (! $tenant->is_active || $tenant->billing_status === 'suspended') {
                            return true;
                        }
                        return !app(\App\Services\BillingLifecycleService::class)->canInviteCollaborators($tenant->billingProfile?->tier ?? \App\Enums\UserTier::FREE);
                    })
                    ->tooltip(function () {
                        $tenant = Filament::getTenant();
                        if (! $tenant->is_active || $tenant->billing_status === 'suspended') {
                            return __('Project is inactive or suspended.');
                        }
                        if (!app(\App\Services\BillingLifecycleService::class)->canInviteCollaborators($tenant->billingProfile?->tier ?? \App\Enums\UserTier::FREE)) {
                            return __('Upgrade to Ultra or Enterprise plan to invite collaborators.');
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
}
