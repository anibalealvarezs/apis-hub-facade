<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public bool $enable_stripe;
    public bool $enable_paypal;

    public static function group(): string
    {
        return 'payment';
    }
}
