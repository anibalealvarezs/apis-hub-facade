<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation()
            ->modalHeading('Confirm User Modification')
            ->modalDescription('Are you sure you want to save the changes made to this user? This may affect their billing and resource limits.')
            ->form(function () {
                $user = $this->getRecord();
                $newTierVal = $this->data['tier'] ?? null;
                $newTier = $newTierVal ? (\App\Enums\UserTier::tryFrom($newTierVal) ?? $user->tier) : $user->tier;
                $oldTier = $user->tier;

                $priorities = [
                    \App\Enums\UserTier::SUSPENDED->value => 0,
                    \App\Enums\UserTier::FREE->value => 1,
                    \App\Enums\UserTier::PRO->value => 2,
                    \App\Enums\UserTier::ULTRA->value => 3,
                    \App\Enums\UserTier::FOUNDER->value => 4,
                    \App\Enums\UserTier::ENTERPRISE->value => 5,
                ];

                $oldPriority = $priorities[$oldTier->value] ?? 1;
                $newPriority = $priorities[$newTier->value] ?? 1;

                if ($newPriority < $oldPriority) {
                    return [
                        \Filament\Forms\Components\TextInput::make('confirm_action')
                            ->label('Alerta de Degradación o Suspensión de Cuenta')
                            ->helperText('Estás degradando o suspendiendo a este usuario. Esto provocará la suspensión automática de sus proyectos y la detención de su infraestructura remota. Para continuar, escribe la palabra "CONFIRM" abajo.')
                            ->placeholder('CONFIRM')
                            ->required()
                            ->rules(['in:CONFIRM']),
                    ];
                }

                return [];
            });
    }
}
