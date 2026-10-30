<?php

namespace App\Services\Analytics;

class MetaAdsBreakdownOptions
{
    public static function options(): array
    {
        return [
            'none' => 'No breakdown (totals only)',
            'demographics' => 'Demographics (Age & Gender)',
            'placement' => 'Placement (Platform & Position)',
            'geography' => 'Geography (Country)',
        ];
    }

    /** Preserve legacy defaults only for absent settings, never explicit empty selections. */
    public static function normalize(mixed $selection): string
    {
        if ($selection === null) return 'demographics';
        if (is_array($selection)) {
            $enabled = [];
            foreach ($selection as $key => $value) {
                if (is_int($key) && is_string($value)) $enabled[] = $value;
                elseif (!is_int($key) && $value) $enabled[] = $key;
            }
            if (in_array('none', $enabled, true) || in_array('unbroken', $enabled, true)) return 'none';
            $selection = $enabled[0] ?? 'none';
        }
        return is_string($selection) && isset(self::options()[$selection]) ? $selection : 'none';
    }
}
