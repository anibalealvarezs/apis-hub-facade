<?php

namespace App\Filament\App\Resources\DashboardResource\Pages;

use App\Filament\App\Resources\DashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDashboard extends CreateRecord
{
    use \Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

    protected static string $resource = DashboardResource::class;

    public function mount(): void
    {
        $project = \Filament\Facades\Filament::getTenant();
        if ($project && $project->billingProfile) {
            $currentCount = \App\Models\Dashboard::where('project_id', $project->id)->count();
            $maxDashboards = app(\App\Services\BillingLifecycleService::class)
                ->getMaxPrivateDashboardsForTier($project->billingProfile->tier);

            if ($currentCount >= $maxDashboards) {
                \Filament\Notifications\Notification::make()
                    ->title(__('Dashboard limit reached'))
                    ->body(__('You have reached the maximum number of dashboards allowed by your plan (:limit). Please upgrade your subscription to create more.', ['limit' => $maxDashboards]))
                    ->danger()
                    ->send();

                $this->redirect(DashboardResource::getUrl('index'));
                return;
            }
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $data['project_id'] = $project->id;
        $data['user_id'] = auth()->id();

        // Enforce server-side restriction for public dashboards
        if (!empty($data['is_public']) && !app(\App\Services\FeatureGateService::class)->canAccess('public_dashboards', $project)) {
            $data['is_public'] = false;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->redirect(DashboardResource::getUrl('builder', ['record' => $this->record]));
    }
}
