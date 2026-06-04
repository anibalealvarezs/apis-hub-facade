<?php

namespace App\Filament\Account\Resources\BillingProfileResource\Pages;

use App\Filament\Account\Resources\BillingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Enums\UserTier;
use App\Filament\App\Pages\ProjectSettings;
use Filament\Notifications\Notification;

class EditBillingProfile extends EditRecord
{
    protected static string $resource = BillingProfileResource::class;
    
    protected ?UserTier $previousTier = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousTier = $this->record->tier;
        return $data;
    }

    protected function afterSave(): void
    {
        $newTier = $this->record->tier;
        
        if ($this->previousTier === UserTier::FREE && $newTier !== UserTier::FREE) {
            $project = $this->record->projects()->first();
            
            if ($project) {
                Notification::make()
                    ->title(__('Billing Profile Upgraded'))
                    ->success()
                    ->body(__('Your billing profile has been upgraded! To apply your new infrastructure limits, please perform a Redeployment.'))
                    ->persistent()
                    ->send();
                    
                $this->redirect(ProjectSettings::getUrl(parameters: ['tenant' => $project->subdomain]));
            }
        }
    }
}
