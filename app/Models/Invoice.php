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
        'subtotal',
        'tax_rate',
        'tax_amount',
        'currency',
        'local_currency',
        'exchange_rate',
        'exchange_rate_id',
        'local_subtotal',
        'local_tax_amount',
        'local_total',
        'status',
        'invoice_pdf_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'local_subtotal' => 'decimal:2',
        'local_tax_amount' => 'decimal:2',
        'local_total' => 'decimal:2',
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

    public function exchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class);
    }
}
