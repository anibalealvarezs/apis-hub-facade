<?php

namespace App\Services;

use App\Models\BillingProfile;
use App\Models\ExchangeRate;

class TaxCalculationService
{
    /**
     * Calculate tax details for an invoice based on billing country and local tax laws.
     * 
     * Rule A: Foreign Country (!= 'VE') -> Export Services (0% IVA, Total = Net)
     * Rule B: Local Country (== 'VE') -> Local Consumption (16% IVA reverse calculated, Total = Total, Net = Total / 1.16)
     * 
     * @param BillingProfile $profile
     * @param float $totalUsd
     * @return array
     */
    public function calculateTaxes(BillingProfile $profile, float $totalUsd): array
    {
        // Settings could be moved to an App Settings or DB config in the future.
        $baseCountry = 'VE';
        $localTaxRate = 16.00; // 16%
        
        $billingCountry = strtoupper($profile->country_code ?? '');

        $subtotalUsd = 0.00;
        $taxRate = 0.00;
        $taxAmountUsd = 0.00;

        if ($billingCountry === $baseCountry) {
            // Rule B: Local Consumption (Reverse IVA)
            $taxRate = $localTaxRate;
            $subtotalUsd = round($totalUsd / (1 + ($taxRate / 100)), 2);
            $taxAmountUsd = round($totalUsd - $subtotalUsd, 2);
        } else {
            // Rule A: Export (0% IVA)
            $taxRate = 0.00;
            $subtotalUsd = $totalUsd;
            $taxAmountUsd = 0.00;
        }

        // Apply Local Currency Exchange (VE -> VES via BCV)
        // Get the latest revised rate for today, or the latest rate overall.
        $latestRate = ExchangeRate::whereDate('created_at', \Carbon\Carbon::today())
            ->where('is_revised', true)
            ->latest('id')
            ->first() ?? ExchangeRate::latest('id')->first();

        $exchangeRate = $latestRate ? (float) $latestRate->rate : 36.50; // Fallback if DB empty
        $exchangeRateId = $latestRate ? $latestRate->id : null;
        
        return [
            'subtotal' => $subtotalUsd,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmountUsd,
            'total' => $totalUsd,
            'currency' => 'USD',
            
            'local_currency' => 'VES',
            'exchange_rate' => $exchangeRate,
            'exchange_rate_id' => $exchangeRateId,
            'local_subtotal' => round($subtotalUsd * $exchangeRate, 2),
            'local_tax_amount' => round($taxAmountUsd * $exchangeRate, 2),
            'local_total' => round($totalUsd * $exchangeRate, 2),
        ];
    }
}
