<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.enable_stripe', true);
        $this->migrator->add('payment.enable_paypal', true);
    }

    public function down(): void
    {
        $this->migrator->delete('payment.enable_stripe');
        $this->migrator->delete('payment.enable_paypal');
    }
};
