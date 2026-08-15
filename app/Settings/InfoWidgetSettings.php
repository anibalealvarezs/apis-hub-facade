<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InfoWidgetSettings extends Settings
{
    public bool $show_gsc_data_enrichment;
    public bool $show_fb_organic_historic_limitation;
    public bool $show_fb_organic_rate_limits;

    public static function group(): string
    {
        return 'info_widgets';
    }
}
