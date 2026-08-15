<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('info_widgets.show_gsc_data_enrichment', true);
        $this->migrator->add('info_widgets.show_fb_organic_historic_limitation', true);
        $this->migrator->add('info_widgets.show_fb_organic_rate_limits', true);
    }

    public function down(): void
    {
        $this->migrator->delete('info_widgets.show_gsc_data_enrichment');
        $this->migrator->delete('info_widgets.show_fb_organic_historic_limitation');
        $this->migrator->delete('info_widgets.show_fb_organic_rate_limits');
    }
};
