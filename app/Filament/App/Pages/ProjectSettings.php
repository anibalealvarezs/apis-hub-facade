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
    protected static ?string $navigationLabel = 'Configuración del Proyecto';
    protected static ?string $title = 'Configuración del Proyecto';
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
        $isOwner = auth()->id() === $project->user_id;

        $isSuspended = !$project->is_active || $project->billing_status === 'suspended';
        $actions = [];

        $actions[] = Action::make('unsuspend')
            ->label('Reactivar Proyecto')
            ->color('success')
            ->icon('heroicon-o-play-circle')
            ->visible(fn () => $isOwner && $project->billing_status === 'suspended')
            ->requiresConfirmation()
            ->modalHeading('Intentar Reactivar Proyecto')
            ->modalDescription('El sistema verificará si tu plan de facturación actual tiene cupos disponibles para reactivar este proyecto.')
            ->action(function () use ($project) {
                if (!$project->billing_profile_id) {
                    Notification::make()
                        ->title('Sin Perfil de Facturación')
                        ->body('Este proyecto no tiene un perfil de facturación asignado. Por favor, asigna uno en la configuración de facturación.')
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
                        ->title('Límite de proyectos alcanzado')
                        ->body("El perfil de facturación asignado solo permite {$maxProjects} proyectos activos. Debes mejorar el plan o suspender otro proyecto primero.")
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
                    ->title('Proyecto en Reactivación')
                    ->body('El proyecto ha sido reactivado. La infraestructura se está levantando en segundo plano.')
                    ->success()
                    ->send();
                
                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('edit_settings')
            ->label('Editar Preferencias')
            ->color('gray')
            ->icon('heroicon-o-pencil-square')
            ->disabled($isSuspended)
            ->visible(fn () => $isOwner)
            ->fillForm(fn () => [
                'timezone' => $project->timezone ?? 'UTC',
            ])
            ->form([
                Select::make('timezone')
                    ->label('Zona Horaria')
                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                    ->searchable()
                    ->required()
                    ->helperText('La zona horaria utilizada por tu servidor virtual de APIs Hub para programar tareas y registrar eventos.'),
            ])
            ->action(function (array $data) use ($project) {
                $project->update([
                    'timezone' => $data['timezone'],
                ]);

                // Dispatch deployment to apply the timezone to the remote container
                \App\Jobs\DeployProjectJob::dispatch($project);

                Notification::make()
                    ->title('Preferencias actualizadas y Despliegue iniciado')
                    ->body('Los cambios se aplicarán al servidor en un par de minutos.')
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('deploy_initial')
            ->label('Desplegar Infraestructura Inicial')
            ->color('success')
            ->icon('heroicon-o-rocket-launch')
            ->disabled($isSuspended)
            ->visible(fn () => $isOwner && is_null($project->last_deployed_at))
            ->requiresConfirmation()
            ->modalHeading('Desplegar Infraestructura')
            ->modalDescription('Esto aprovisionará el contenedor y la base de datos en el servidor remoto. ¿Estás seguro de continuar?')
            ->action(function () use ($project) {
                if (!$project->hasConfiguredAssets()) {
                    Notification::make()
                        ->title('No se puede desplegar')
                        ->body('No puedes desplegar infraestructura sin configurar al menos un recurso para sincronizar en Data Sources.')
                        ->danger()
                        ->persistent()
                        ->send();
                    return redirect(request()->header('Referer'));
                }

                \App\Jobs\DeployProjectJob::dispatch($project);
                
                Notification::make()
                    ->title('Despliegue Iniciado')
                    ->body('La infraestructura se está aprovisionando en segundo plano. Esto puede tomar un par de minutos.')
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('redeploy')
            ->label('Aplicar Cambios (Redesplegar)')
            ->color('success')
            ->icon('heroicon-o-cloud-arrow-up')
            ->disabled($isSuspended)
            ->visible(fn () => $isOwner && !is_null($project->last_deployed_at))
            ->requiresConfirmation()
            ->modalHeading('Redesplegar Infraestructura')
            ->modalDescription('Esto reconstruirá los contenedores remotos para aplicar cualquier cambio de entorno. ¿Continuar?')
            ->action(function () use ($project) {
                if (!$project->hasConfiguredAssets()) {
                    Notification::make()
                        ->title('No se puede desplegar')
                        ->body('No puedes desplegar infraestructura sin configurar al menos un recurso para sincronizar en Data Sources.')
                        ->danger()
                        ->persistent()
                        ->send();
                    return redirect(request()->header('Referer'));
                }

                \App\Jobs\DeployProjectJob::dispatch($project);
                
                Notification::make()
                    ->title('Redespliegue Iniciado')
                    ->body('La infraestructura se está actualizando en segundo plano.')
                    ->success()
                    ->send();

                return redirect(request()->header('Referer'));
            });

        $actions[] = Action::make('transfer')
                ->label('Transferir Propiedad')
                ->color('warning')
                ->icon('heroicon-o-arrows-right-left')
                ->disabled($isSuspended)
                ->visible(function () use ($isOwner, $project) {
                    if (!$isOwner) return false;
                    $hasPending = \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->exists();
                    return !$hasPending;
                })
                ->requiresConfirmation()
                ->modalHeading('Transferir Proyecto')
                ->modalDescription('Selecciona a un colaborador activo de este proyecto para transferirle la propiedad absoluta.')
                ->form([
                    Select::make('to_user_id')
                        ->label('Nuevo Propietario')
                        ->options(function () use ($project) {
                            return $project->users()
                                ->where('users.id', '!=', auth()->id())
                                ->pluck('name', 'users.id');
                        })
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\Toggle::make('retain_access')
                        ->label('Mantener acceso como colaborador')
                        ->helperText('Al transferir la propiedad, serás añadido automáticamente como colaborador para no perder acceso al proyecto.')
                        ->default(false),
                    \Filament\Forms\Components\Radio::make('billing_action')
                        ->label('Perfil de Facturación')
                        ->required()
                        ->options(function (\Filament\Forms\Get $get) use ($project) {
                            $bp = $project->billingProfile;
                            if (!$bp) {
                                return ['keep_bp' => 'Sin perfil de facturación (El proyecto ya está inactivo)'];
                            }
                            
                            $toUserId = $get('to_user_id');
                            if (!$toUserId) {
                                return [];
                            }

                            // If Sender owns the BP
                            if ($bp->user_id === auth()->id()) {
                                return [
                                    'share_sender_bp' => 'Compartir mi perfil de facturación con el receptor',
                                    'remove_bp' => 'Desvincular mi perfil de facturación (Recomendado)',
                                ];
                            }
                            
                            // If Receiver owns the BP
                            if ($bp->user_id == $toUserId) {
                                return [
                                    'keep_bp' => 'Mantener el perfil actual (Ya le pertenece al receptor)',
                                ];
                            }

                            // If Third Party owns the BP
                            $hasAccess = \Illuminate\Support\Facades\DB::table('billing_profile_user')
                                ->where('billing_profile_id', $bp->id)
                                ->where('user_id', $toUserId)
                                ->exists();

                            if ($hasAccess) {
                                return ['keep_bp' => 'Mantener el perfil actual (El receptor tiene acceso a la facturación de terceros)'];
                            } else {
                                return ['remove_bp' => 'Remover el perfil (El receptor no tiene acceso a la facturación actual)'];
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
                        Notification::make()->title('Usuario no encontrado')->danger()->send();
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
                        ->title('Transferencia Iniciada')
                        ->body('Se ha enviado un correo a ' . $toUser->name . ' para que acepte la transferencia del proyecto.')
                        ->success()
                        ->send();
                });

        $actions[] = Action::make('cancel_transfer')
                ->label('Cancelar Transferencia')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(function () use ($isOwner, $project) {
                    if (!$isOwner) return false;
                    return \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->exists();
                })
                ->requiresConfirmation()
                ->modalHeading('Cancelar Transferencia')
                ->modalDescription('¿Estás seguro de que deseas cancelar la transferencia pendiente? El enlace enviado al destinatario dejará de ser válido.')
                ->action(function () use ($project) {
                    $transfer = \App\Models\ProjectTransfer::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->first();
                        
                    if ($transfer) {
                        $transfer->update(['status' => 'cancelled']);
                        // TODO: Enviar correo al destinatario notificando la cancelación
                        Notification::make()
                            ->title('Transferencia Cancelada')
                            ->body('La transferencia ha sido cancelada exitosamente.')
                            ->success()
                            ->send();
                    }
                });

        $actions[] = Action::make('delete')
                ->label('Eliminar Proyecto')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->disabled($isSuspended)
                ->visible(fn () => $isOwner)
                ->requiresConfirmation()
                ->modalHeading('Eliminar Proyecto')
                ->modalDescription('Al eliminar este proyecto se bloqueará el acceso al dominio y a los datos de manera inmediata. Tienes 30 días para recuperarlo, luego se destruirá toda su infraestructura permanentemente.')
                ->form([
                    TextInput::make('confirmation')
                        ->label('Escribe "' . $project->name . '" para confirmar')
                        ->required()
                        ->rule(function () use ($project) {
                            return function (string $attribute, $value, \Closure $fail) use ($project) {
                                if ($value !== $project->name) {
                                    $fail('El nombre del proyecto no coincide.');
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
                        ->title('Proyecto Eliminado')
                        ->body('El proyecto ha sido movido a la papelera. Tienes 30 días para restaurarlo.')
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
