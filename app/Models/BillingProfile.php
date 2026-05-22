<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class BillingProfile extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'user_id',
        'is_default',
        'type',
        'name',
        'tax_id',
        'address_line_1',
        'city',
        'state',
        'postal_code',
        'country_code',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'paypal_id',
        'paypal_email',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the user that owns the billing profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoices associated with this billing profile.
     */
    public function unifiedInvoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Override Cashier's default customer name.
     */
    public function stripeName()
    {
        return $this->name;
    }
}
