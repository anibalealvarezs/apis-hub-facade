<?php

namespace App\Services\Analytics;

final class GoogleAdsReportingSemantics
{
    public static function conversionValueNotice(): string
    {
        return __('Google Ads conversion value comes from the value configured on each conversion action. Call, contact and local actions often use a fixed default (e.g. 1 per conversion), so this figure may simply equal the conversion count and does not represent revenue. ROAS inherits the same limitation.');
    }
}
