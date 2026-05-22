<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected $guarded = [];

    /**
     * Get the billing profile that owns the subscription.
     */
    public function billingProfile()
    {
        return $this->owner();
    }

    /**
     * Get the plan associated with the subscription.
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the invoices for this subscription.
     */
    public function unifiedInvoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
