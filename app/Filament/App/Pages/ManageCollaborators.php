<?php

namespace App\Filament\App\Pages;

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

                        Notification::make()->success()->title(__('Invitation sent via email.'))->send();
                    }),
            ]);
    }
}
