<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_profile_id',
        'subscription_id',
        'gateway',
        'gateway_invoice_id',
        'amount',
        'currency',
        'status',
        'invoice_pdf_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the billing profile that owns this invoice.
     */
    public function billingProfile()
    {
        return $this->belongsTo(BillingProfile::class);
    }

    /**
     * Get the subscription associated with this invoice (if any).
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
