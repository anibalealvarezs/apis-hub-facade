<?php

namespace App\Filament\App\Pages;

use App\Models\ProjectTransfer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

class ProjectSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    public static function getNavigationLabel(): string
    {
        return __('Project Settings');
    }

    public function getTitle(): string
    {
        return __('Project Settings');
    }
    protected static ?string $slug = 'project-settings';
    protected static string $view = 'filament.app.pages.project-settings';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 100;

    /**
     * Asegura que solo el propietario (trueOwner) pueda ver esta página.
     * Aunque los administradores también podrían ver configuraciones, 
     * por ahora la restringiremos o limitaremos sus acciones.
     */
    public static function canAccess(): bool
    {
        $project = Filament::getTenant();
        // Permitimos acceso, pero restringiremos las acciones internamente
        return true; 
    }

    private function getProject(): ?\App\Models\Project
    {
        $project = Filament::getTenant();
        if (!$project) {
            $subdomain = request()->route('tenant') ?? request()->tenant;
            if ($subdomain) {
                $project = \App\Models\Project::where('subdomain', $subdomain)->first();
            }
            if (!$project) {
                $project = \App\Models\Project::first();
            }
        }
        return $project;
    }

    protected function getHeaderActions(): array
    {
        $project = $this->getProject();
        if (!$project) {
            return [];
        }
        $user = auth()->user();

        $isSuspended = !$project->is_active || $project->billing_status === 'suspended';
        $actions = [];

        $actions[] = Action::make('unsuspend')
            ->label(__('Reactivate Project'))
            ->color('success')
            ->icon('heroicon-o-play-circle')
            ->visible(fn () => $user->can('manage_billing') && $project->billing_status === 'suspended')
            ->requiresConfirmation()
            ->modalHeading(__('Attempt to Reactivate Project'))
            ->modalDescription(__('The system will verify if your current billing plan has available quota to reactivate this project.'))
            ->action(function () use ($project) {
                if (!$project->billing_profile_id) {
                    Notification::make()
                        ->title(__('No Billing Profile'))
                        ->body(__('This project has no assigned billing profile. Please assign one in the billing settings.'))
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }

                $billingService = app(\App\Services\BillingLifecycleService::class);
                $profile = $project->billingProfile;
                $maxProjects = $billingService->getMaxProjectsForTier($profile->tier);
                
                $activeCount = $profile->projects()->where('billing_status', 'active')->count();

                if ($activeCount >= $maxProjects) {
                    Notification::make()
                        ->title(__('Project limit reached'))
                        ->body(__('The assigned billing profile only allows :limit active projects. You must upgrade your plan or suspend another project first.', ['limit' => $maxProjects]))
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }

                // Unsuspend
                $project->update([
                    'billing_status' => 'active',
                    'is_active' => true,
                ]);

                // Dispatch jobs
                \App\Jobs\RestoreProjectDomainJob::dispatch($project);
                \App\Jobs\DeployProjectJob::dispatch($project);

                Notification::make()
                    ->title(__('Project Reactivating'))
                    ->body(__('The project has been reactivated. Infrastructure is booting up in the background.'))
                    ->success()
                    ->send();
                
                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('edit_settings')
            ->label(__('Edit Preferences'))
            ->color('gray')
            ->icon('heroicon-o-pencil-square')
            ->disabled($isSuspended)
            ->visible(fn () => $user->can('edit_preferences'))
            ->fillForm(fn () => [
                'timezone' => $project->timezone ?? 'UTC',
            ])
            ->form([
                Select::make('timezone')
                    ->label(__('Timezone'))
                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                    ->searchable()
                    ->required()
                    ->helperText(__('The timezone used by your virtual APIs Hub server to schedule tasks and log events.')),
            ])
            ->action(function (array $data) use ($project) {
                $project->update([
                    'timezone' => $data['timezone'],
                ]);

                // Dispatch deployment to apply the timezone to the remote container
                \App\Jobs\DeployProjectJob::dispatch($project);

                Notification::make()
                    ->title(__('Preferences updated and Deployment initiated'))
                    ->body(__('Changes will be applied to the server in a couple of minutes.'))
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('deploy_initial')
            ->label(__('Deploy Initial Infrastructure'))
            ->color('success')
            ->icon('heroicon-o-rocket-launch')
            ->disabled($isSuspended)
            ->visible(fn () => $user->can('deploy_project') && is_null($project->last_deployed_at))
            ->requiresConfirmation()
            ->modalHeading(__('Deploy Infrastructure'))
            ->modalDescription(__('This will provision the container and database on the remote server. Are you sure you want to continue?'))
            ->action(function () use ($project) {
                if (!$project->hasConfiguredAssets()) {
                    Notification::make()
                        ->title(__('Cannot deploy'))
                        ->body(__('You cannot deploy infrastructure without configuring at least one asset to sync in Data Sources.'))
                        ->danger()
                        ->persistent()
                        ->send();
                    return redirect(request()->header('Referer'));
                }

                \App\Jobs\DeployProjectJob::dispatch($project);
                
                Notification::make()
                    ->title(__('Deployment Initiated'))
                    ->body(__('Infrastructure is being provisioned in the background. This may take a couple of minutes.'))
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('redeploy')
            ->label(__('Apply Changes (Redeploy)'))
            ->color('success')
            ->icon('heroicon-o-cloud-arrow-up')
            ->disabled($isSuspended)
            ->visible(fn () => $user->can('deploy_project') && !is_null($project->last_deployed_at))
            ->requiresConfirmation()
            ->modalHeading(__('Redeploy Infrastructure'))
            ->modalDescription(__('This will rebuild the remote containers to apply any environment changes. Continue?'))
            ->action(function () use ($project) {
                if (!$project->hasConfiguredAssets()) {
                    Notification::make()
                        ->title(__('Cannot deploy'))
                        ->body(__('You cannot deploy infrastructure without configuring at least one asset to sync in Data Sources.'))
                        ->danger()
                        ->persistent()
                        ->send();
                    return redirect(request()->header('Referer'));
                }

                \App\Jobs\DeployProjectJob::dispatch($project);
                
                Notification::make()
                    ->title(__('Redeployment Initiated'))
                    ->body(__('Infrastructure is being updated in the background.'))
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('transfer')
                ->label(__('Transfer Ownership'))
                ->color('warning')
                ->icon('heroicon-o-arrows-right-left')
                ->disabled($isSuspended)
                ->visible(function () use ($user, $project) {
                    if (!$user->can('transfer_project')) return false;
                    $hasPending = \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->exists();
                    return !$hasPending;
                })
                ->requiresConfirmation()
                ->modalHeading(__('Transfer Project'))
                ->modalDescription(__('Select an active collaborator of this project to transfer absolute ownership.'))
                ->modalSubmitAction(function (\Filament\Actions\StaticAction $action) use ($project) {
                    $hasCollaborators = $project->users()->where('users.id', '!=', auth()->id())->count() > 0;
                    if (!$hasCollaborators) {
                        $action->hidden();
                    }
                })
                ->form([
                    \Filament\Forms\Components\Placeholder::make('no_collaborators')
                        ->label('')
                        ->hidden(function () use ($project) {
                            return $project->users()->where('users.id', '!=', auth()->id())->count() > 0;
                        })
                        ->content(new \Illuminate\Support\HtmlString('<div class="text-amber-600 font-medium bg-amber-50 p-4 rounded-lg border border-amber-200">' . __('To transfer this project, you must first invite a collaborator from the "Collaborators" tab and they must accept the invitation.') . '</div>')),
                    Select::make('to_user_id')
                        ->label(__('New Owner'))
                        ->hidden(function () use ($project) {
                            return $project->users()->where('users.id', '!=', auth()->id())->count() == 0;
                        })
                        ->options(function () use ($project) {
                            return $project->users()
                                ->where('users.id', '!=', auth()->id())
                                ->pluck('name', 'users.id');
                        })
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\Toggle::make('retain_access')
                        ->hidden(function () use ($project) {
                            return $project->users()->where('users.id', '!=', auth()->id())->count() == 0;
                        })
                        ->label(__('Retain access as collaborator'))
                        ->helperText(__('When transferring ownership, you will be automatically added as a collaborator so you do not lose access to the project.'))
                        ->default(false),
                    \Filament\Forms\Components\Radio::make('billing_action')
                        ->hidden(function () use ($project) {
                            return $project->users()->where('users.id', '!=', auth()->id())->count() == 0;
                        })
                        ->label(__('Billing Profile'))
                        ->required()
                        ->options(function (\Filament\Forms\Get $get) use ($project) {
                            $bp = $project->billingProfile;
                            if (!$bp) {
                                return ['keep_bp' => __('No billing profile (Project is already inactive)')];
                            }
                            
                            $toUserId = $get('to_user_id');
                            if (!$toUserId) {
                                return [];
                            }

                            // If Sender owns the BP
                            if ($bp->user_id === auth()->id()) {
                                return [
                                    'share_sender_bp' => __('Share my billing profile with the receiver'),
                                    'remove_bp' => __('Unlink my billing profile (Recommended)'),
                                ];
                            }
                            
                            // If Receiver owns the BP
                            if ($bp->user_id == $toUserId) {
                                return [
                                    'keep_bp' => __('Keep current profile (Already owned by receiver)'),
                                ];
                            }

                            // If Third Party owns the BP
                            $hasAccess = \Illuminate\Support\Facades\DB::table('billing_profile_user')
                                ->where('billing_profile_id', $bp->id)
                                ->where('user_id', $toUserId)
                                ->exists();

                            if ($hasAccess) {
                                return ['keep_bp' => __('Keep current profile (Receiver has access to third-party billing)')];
                            } else {
                                return ['remove_bp' => __('Remove profile (Receiver does not have access to current billing)')];
                            }
                        })
                        ->afterStateHydrated(function (\Filament\Forms\Components\Radio $component, \Filament\Forms\Get $get) use ($project) {
                            $bp = $project->billingProfile;
                            if (!$bp) {
                                $component->state('keep_bp');
                                $component->disabled();
                                return;
                            }
                            $toUserId = $get('to_user_id');
                            if (!$toUserId) return;
                            
                            if ($bp->user_id == $toUserId) {
                                $component->state('keep_bp');
                                $component->disabled();
                            } elseif ($bp->user_id !== auth()->id()) {
                                $hasAccess = \Illuminate\Support\Facades\DB::table('billing_profile_user')
                                    ->where('billing_profile_id', $bp->id)
                                    ->where('user_id', $toUserId)
                                    ->exists();
                                $component->state($hasAccess ? 'keep_bp' : 'remove_bp');
                                $component->disabled();
                            } else {
                                $component->disabled(false);
                            }
                        }),
                ])
                ->action(function (array $data) use ($project) {
                    $toUser = User::find($data['to_user_id']);
                    
                    if (!$toUser) {
                        Notification::make()->title(__('User not found'))->danger()->send();
                        return;
                    }

                    // Eliminar transferencias previas (o marcarlas como canceladas)
                    ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);

                    // Crear nueva transferencia
                    $transfer = ProjectTransfer::create([
                        'project_id' => $project->id,
                        'from_user_id' => auth()->id(),
                        'to_user_id' => $toUser->id,
                        'token' => Str::random(64),
                        'expires_at' => now()->addHours(48),
                        'retain_access' => $data['retain_access'] ?? false,
                        'billing_action' => $data['billing_action'] ?? 'keep_bp',
                    ]);

                    // Enviar correo
                    \Illuminate\Support\Facades\Mail::to($toUser->email)->send(
                        new \App\Mail\ProjectTransferMail($transfer, $project, $toUser)
                    );

                    Notification::make()
                        ->title(__('Transfer Initiated'))
                        ->body(__('An email has been sent to :name to accept the project transfer.', ['name' => $toUser->name]))
                        ->success()
                        ->send();
                });

        $actions[] = Action::make('cancel_transfer')
                ->label(__('Cancel Transfer'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(function () use ($user, $project) {
                    if (!$user->can('transfer_project')) return false;
                    return \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->exists();
                })
                ->requiresConfirmation()
                ->modalHeading(__('Cancel Transfer'))
                ->modalDescription(__('Are you sure you want to cancel the pending transfer? The link sent to the recipient will become invalid.'))
                ->action(function () use ($project) {
                    $transfer = \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->first();
                        
                    if ($transfer) {
                        $transfer->update(['status' => 'cancelled']);
                        // TODO: Enviar correo al destinatario notificando la cancelación
                        Notification::make()
                            ->title(__('Transfer Cancelled'))
                            ->body(__('The transfer has been successfully cancelled.'))
                            ->success()
                            ->send();
                    }
                });

        $actions[] = Action::make('delete')
                ->label(__('Delete Project'))
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->disabled($isSuspended)
                ->visible(fn () => $user->can('delete_project'))
                ->requiresConfirmation()
                ->modalHeading(__('Delete Project'))
                ->modalDescription(__('Deleting this project will immediately block access to the domain and data. You have 30 days to recover it before its infrastructure is permanently destroyed.'))
                ->form([
                    TextInput::make('confirmation')
                        ->label(__('Type ":name" to confirm', ['name' => $project->name]))
                        ->required()
                        ->rule(function () use ($project) {
                            return function (string $attribute, $value, \Closure $fail) use ($project) {
                                if ($value !== $project->name) {
                                    $fail(__('Project name does not match.'));
                                }
                            };
                        }),
                ])
                ->action(function () use ($project) {
                    // Soft delete del proyecto
                    $project->delete();

                    // Despachar Job para suspender el contenedor/dominio en Caddy
                    \App\Jobs\SuspendProjectDomainJob::dispatch($project);

                    // Redirigir a otro proyecto activo o a la pantalla de creación
                    $nextProject = auth()->user()->projects()
                        ->where('is_active', true)
                        ->first();

                    Notification::make()
                        ->title(__('Project Deleted'))
                        ->body(__('The project has been moved to the trash. You have 30 days to restore it.'))
                        ->success()
                        ->send();

                    if ($nextProject) {
                        redirect()->route('filament.app.pages.dashboard', ['tenant' => $nextProject->subdomain]);
                    } else {
                        redirect()->route('filament.app.tenant.registration');
                    }
                });

        return $actions;
    }

    protected function getViewData(): array
    {
        $project = $this->getProject();
        if (!$project) {
            return [
                'logs' => collect(),
            ];
        }
        
        return [
            'logs' => $project->deploymentLogs()->latest()->take(5)->get(),
        ];
    }
}
