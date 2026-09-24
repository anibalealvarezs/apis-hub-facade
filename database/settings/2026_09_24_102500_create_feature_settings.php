<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('features.enable_free_ai_classification', false);
    }

    public function down(): void
    {
        $this->migrator->delete('features.enable_free_ai_classification');
    }
};
