<?php

namespace App\Observers;

use App\Models\BillingProfile;
use Illuminate\Support\Facades\Cache;

class BillingProfileObserver
{
    /**
     * Handle the BillingProfile "updated" event.
     */
    public function updated(BillingProfile $billingProfile): void
    {
        // Purgar la caché de facturación de todos los proyectos que usan este perfil
        foreach ($billingProfile->projects as $project) {
            Cache::forget("project_{$project->id}_billing_page_data");
        }
    }

    /**
     * Handle the BillingProfile "deleted" event.
     */
    public function deleted(BillingProfile $billingProfile): void
    {
        foreach ($billingProfile->projects as $project) {
            Cache::forget("project_{$project->id}_billing_page_data");
        }
    }
}
