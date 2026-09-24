<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FeatureSettings extends Settings
{
    public bool $enable_free_ai_classification;

    public static function group(): string
    {
        return 'features';
    }
}
