<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\BillingProfile;

class ProjectBillingSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.app.pages.project-billing-settings';

    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?string $title = 'Billing & Subscription';

    public function mount()
    {
        // For now, only the true owner of the project can manage billing.
        abort_unless(filament()->getTenant()->user_id === auth()->id(), 403, 'Only the project owner can manage billing.');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BillingProfile::query()
                    ->where('id', filament()->getTenant()->billing_profile_id)
            )
            ->heading('Perfil de Facturación Asignado')
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nombre del Perfil')
                    ->description(fn ($record) => $record->reference_name ? "Razón Social / Nombre: {$record->name}" : null),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('tier')
                    ->label('Plan / Subscription')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('project_status')
                    ->label('Estado de Pago')
                    ->badge()
                    ->state(fn () => filament()->getTenant()->billing_status)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending_approval' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('current_cycle_starts_at')
                    ->label('Inicio de Ciclo')
                    ->dateTime('d M, Y')
                    ->state(fn ($record) => $record->current_cycle_starts_at ?? $record->created_at ?? now()->startOfMonth()),
                Tables\Columns\TextColumn::make('current_cycle_ends_at')
                    ->label('Próxima Renovación')
                    ->dateTime('d M, Y')
                    ->state(function ($record) {
                        if ($record->current_cycle_ends_at) {
                            return $record->current_cycle_ends_at;
                        }
                        $starts = $record->created_at ?? now()->startOfMonth();
                        return $starts->copy()->addMonth();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('assign_profile')
                    ->label('Cambiar Perfil de Facturación')
                    ->icon('heroicon-o-pencil')
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
                    }),
            ])
            ->actions([]);
    }
}
