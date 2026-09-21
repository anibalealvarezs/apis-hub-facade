<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    public ?string $typesafe_admin_api_key;
    public bool $typesafe_admin_share_enabled;

    public static function group(): string
    {
        return 'ai';
    }

    public static function encrypted(): array
    {
        return [
            'typesafe_admin_api_key',
        ];
    }
}