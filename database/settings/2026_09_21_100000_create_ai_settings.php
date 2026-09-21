<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->addEncrypted('ai.typesafe_admin_api_key', null);
        $this->migrator->add('ai.typesafe_admin_share_enabled', false);
    }

    public function down(): void
    {
        $this->migrator->delete('ai.typesafe_admin_api_key');
        $this->migrator->delete('ai.typesafe_admin_share_enabled');
    }
};