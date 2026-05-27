<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Models\BillingProfile;
use Filament\Actions\Action;

class ProjectBillingSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.app.pages.project-billing-settings';

    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?string $title = 'Billing & Subscription';

    public function mount()
    {
        // For now, only the true owner of the project can manage billing.
        abort_unless(filament()->getTenant()->user_id === auth()->id(), 403, 'Only the project owner can manage billing.');
    }

    protected function getActions(): array
    {
        return [
            $this->assignProfileAction(),
        ];
    }

    public function assignProfileAction(): Action
    {
        return Action::make('assign_profile')
            ->label('Cambiar Perfil de Facturación')
            ->icon('heroicon-o-pencil')
            ->color('warning')
            ->form([
                Forms\Components\Select::make('billing_profile_id')
                    ->label('Perfiles de Facturación Disponibles (Propios y Compartidos)')
                    ->options(function () {
                        return auth()->user()->getAvailableBillingProfiles()->pluck('display_name', 'id');
                    })
                    ->required(),
            ])
            ->action(function (array $data) {
                $profile = BillingProfile::find($data['billing_profile_id']);
                $project = filament()->getTenant();

                // Check if the target profile has capacity to accept this project
                $maxProjects = app(\App\Services\BillingLifecycleService::class)
                    ->getMaxProjectsForTier($profile->tier);
                $currentProjectsCount = $profile->projects()
                    ->where('billing_status', 'active')
                    ->count();

                if ($currentProjectsCount >= $maxProjects) {
                    \Filament\Notifications\Notification::make()
                        ->title('Capacidad del Perfil Superada')
                        ->body("El perfil de facturación seleccionado ({$profile->display_name}) ha alcanzado su límite máximo de proyectos de {$maxProjects} para el plan " . ucfirst($profile->tier->value ?? $profile->tier) . ". Por favor, sube de plan ese perfil primero.")
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }
                
                // Check if user is the owner of the profile
                if ($profile->user_id === auth()->id()) {
                    $project->update([
                        'billing_profile_id' => $profile->id,
                        'billing_status' => 'active',
                        'is_active' => true,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Perfil de facturación asignado')
                        ->success()
                        ->send();
                } else {
                    // Shared profile of a third party
                    $isShared = $profile->sharedWithUsers()->where('users.id', auth()->id())->exists();
                    if (!$isShared) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('El perfil de facturación seleccionado no está compartido contigo.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $project->update([
                        'billing_profile_id' => $profile->id,
                        'billing_status' => 'pending_approval',
                        'is_active' => false,
                      ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Asignación Solicitada')
                        ->body('El propietario del perfil de facturación debe aprobar esta solicitud antes de que pueda activarse.')
                        ->warning()
                        ->send();
                }
            });
    }

    protected function getViewData(): array
    {
        $project = filament()->getTenant();
        $billingProfile = $project->billingProfile;
        
        $starts = null;
        $ends = null;
        if ($billingProfile) {
            $starts = $billingProfile->current_cycle_starts_at ?? $billingProfile->created_at ?? now()->startOfMonth();
            $ends = $billingProfile->current_cycle_ends_at ?? $starts->copy()->addMonth();
        }

        return [
            'project' => $project,
            'billingProfile' => $billingProfile,
            'cycleStarts' => $starts,
            'cycleEnds' => $ends,
        ];
    }
}
