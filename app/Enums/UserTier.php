<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserTier: string implements HasLabel
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PRO => 'Pro',
            self::ENTERPRISE => 'Enterprise',
        };
    }
}
