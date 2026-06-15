<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'control_number',
        'fiscal_status',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $lastInvoice = self::orderBy('id', 'desc')->first();
                $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1;
                $invoice->invoice_number = str_pad($nextId, 8, '0', STR_PAD_LEFT);
            }
            
            if (empty($invoice->control_number)) {
                $invoice->control_number = 'TEMP-' . $invoice->invoice_number;
            }

            if (empty($invoice->fiscal_status)) {
                $invoice->fiscal_status = 'pending';
            }
        });

        static::saving(function ($invoice) {
            // If the control number has been changed and doesn't start with TEMP-, and it was pending, mark it as reconciled.
            if ($invoice->isDirty('control_number') && !str_starts_with($invoice->control_number, 'TEMP-')) {
                $invoice->fiscal_status = 'reconciled';
            }
        });
    }

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
