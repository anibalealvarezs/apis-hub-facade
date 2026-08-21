<?php

namespace App\Livewire;

use Livewire\Component;

class OnboardingPreferences extends Component
{
    public static function canView(): bool
    {
        return true;
    }

    public static $sort = 20;

    public static function getSort(): int
    {
        return self::$sort;
    }

    public function render()
    {
        return view('livewire.onboarding-preferences');
    }
}
