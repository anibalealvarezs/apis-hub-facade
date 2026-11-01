<?php

namespace App\Services\Analytics;

final class Ga4ReportingSemantics
{
    public static function rawMetric(string $name): string
    {
        return ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')['canonical_aliases'][$name] ?? $name;
    }

    public static function displayMetric(string $name): string
    {
        $aliases = ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')['canonical_aliases'];
        return array_search($name, $aliases, true) ?: $name;
    }
    public static function metricsForScope(string $scope): array
    {
        return ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')['scopes'][$scope]['metrics'] ?? [];
    }

    public static function metricOptions(?string $scope): array
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('google_analytics');
        $metrics = $scope ? self::metricsForScope($scope) : array_values(array_unique(array_merge(...array_map(fn ($key) => self::metricsForScope($key), ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix']))));
        $aliases = ['screenPageViews' => 'pageviews', 'bounceRate' => 'bounce_rate', 'newUsers' => 'new_users', 'activeUsers' => 'reach', 'totalUsers' => 'total_users', 'totalRevenue' => 'revenue', 'averageSessionDuration' => 'average_session_duration', 'eventCount' => 'event_count'];
        $options = [];
        foreach ($metrics as $metric) {
            $definition = $tree['metrics'][$metric];
            $label = __($definition['label']);
            if (in_array($metric, ['newUsers', 'activeUsers', 'totalUsers'], true)) $label .= ' '.__('(Reported Sum)');
            $options[$aliases[$metric] ?? self::displayMetric($metric)] = $label;
        }
        return $options;
    }

    public static function userCountNotice(): string
    {
        return __('GA4 user figures are sums of the reported daily and breakdown counts. The same person can be counted more than once; these are not unique users for the selected period.');
    }

    public static function containsGa4(array $configuration): bool
    {
        foreach ($configuration as $key => $value) {
            if (($key === 'channel' || str_ends_with((string)$key, '_channel')) && in_array($value, ['google_analytics', 'google_analytics_4'], true)) return true;
            if (is_array($value) && self::containsGa4($value)) return true;
        }
        return false;
    }

    public static function isUserMetric(string $metric): bool
    {
        $metric = preg_replace('/^trend_(?:total|average)_/i', '', $metric);
        return in_array(strtolower(str_replace('_', '', $metric)), ['reach', 'activeusers', 'newusers', 'totalusers'], true);
    }

    /** Inspect selected metrics with their own channel, not unrelated series/options. */
    public static function usesGa4UserMetrics(array $configuration, ?string $inheritedChannel = null): bool
    {
        $channel = $configuration['channel'] ?? $inheritedChannel;
        $isGa4 = in_array($channel, ['google_analytics', 'google_analytics_4'], true);
        foreach (['dependent', 'independent'] as $side) {
            if (in_array($configuration[$side.'_channel'] ?? null, ['google_analytics', 'google_analytics_4'], true)
                && self::isUserMetric((string)($configuration[$side.'_metric'] ?? ''))) return true;
        }
        $series = $configuration['raw_series'] ?? $configuration['series'] ?? null;
        if (is_array($series)) {
            foreach ($series as $index => $item) {
                if (!is_array($item)) continue;
                $item['channel'] = $configuration['series_channels'][$index] ?? $item['channel'] ?? $channel;
                $item['metrics'] = $configuration['series_metrics'][$index] ?? $item['metrics'] ?? [];
                if (self::usesGa4UserMetrics($item, $channel)) return true;
            }
        } elseif (isset($configuration['series_metrics'])) {
            foreach ($configuration['series_metrics'] as $index => $metrics) {
                if (self::usesGa4UserMetrics(['channel' => $configuration['series_channels'][$index] ?? $channel, 'metrics' => $metrics])) return true;
            }
        } elseif ($isGa4) {
            foreach (array_merge((array)($configuration['metrics'] ?? []), (array)($configuration['metric'] ?? [])) as $metric) {
                if (is_string($metric) && self::isUserMetric($metric)) return true;
            }
        }
        foreach ($configuration as $key => $value) {
            if (in_array($key, ['raw_series', 'series', 'series_metrics', 'allowed_metrics', 'metric_options', 'metrics', 'metric', 'filters'], true)) continue;
            if (is_array($value) && self::usesGa4UserMetrics($value, $channel)) return true;
        }
        return false;
    }

    public static function assertSuccessfulResults(array $results): void
    {
        foreach ($results as $response) {
            if (!is_array($response) || ($response['status'] ?? null) === 'error' || isset($response['error']) || ($response['success'] ?? true) === false) {
                throw new \RuntimeException(__('The GA4 report could not be completed. Partial results have not been displayed. Please review the selected metrics or try again.'));
            }
        }
    }

    public static function assertWidgetRatesAvailable(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'previous') continue;
            if ($value === null && in_array(strtolower((string)$key), ['bouncerate', 'bounce_rate', 'averagesessionduration', 'average_session_duration'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['analytics' => __('This GA4 rate could not be calculated for part of the selected period. Session weights may be missing, duplicated, invalid, or total zero.')]);
            }
            if (is_array($value)) self::assertWidgetRatesAvailable($value);
        }
    }

    public static function countryNameFromCode(string $code): string
    {
        $code = trim(strtoupper($code));
        if ($code === '' || $code === 'UNKNOWN' || $code === 'NULL' || $code === '(NOT SET)') {
            return 'Unknown';
        }

        static $iso2ToName = [
            'AF' => 'Afghanistan', 'AX' => 'Åland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa',
            'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria',
            'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados',
            'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda',
            'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BQ' => 'Bonaire, Sint Eustatius and Saba', 'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'CV' => 'Cabo Verde',
            'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic',
            'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic of the',
            'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => "Côte d'Ivoire", 'HR' => 'Croatia', 'CU' => 'Cuba',
            'CW' => 'Curaçao', 'CY' => 'Cyprus', 'CZ' => 'Czechia', 'DK' => 'Denmark', 'DJ' => 'Djibouti',
            'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'SZ' => 'Eswatini', 'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France',
            'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon',
            'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar',
            'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam',
            'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
            'HT' => 'Haiti', 'HM' => 'Heard Island and McDonald Islands', 'VA' => 'Holy See', 'HN' => 'Honduras',
            'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia',
            'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle of Man', 'IL' => 'Israel',
            'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan',
            'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => "Korea, Democratic People's Republic of",
            'KR' => 'Korea, Republic of', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => "Lao People's Democratic Republic",
            'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya',
            'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MG' => 'Madagascar',
            'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta',
            'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte',
            'MX' => 'Mexico', 'FM' => 'Micronesia', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia',
            'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar',
            'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NC' => 'New Caledonia',
            'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue',
            'NF' => 'Norfolk Island', 'MK' => 'North Macedonia', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway',
            'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestine, State of', 'PA' => 'Panama',
            'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn',
            'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Réunion',
            'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthélemy',
            'SH' => 'Saint Helena, Ascension and Tristan da Cunha', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin (French part)', 'PM' => 'Saint Pierre and Miquelon', 'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia',
            'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore',
            'SX' => 'Sint Maarten (Dutch part)', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands',
            'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and the South Sandwich Islands',
            'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste',
            'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia',
            'TR' => 'Türkiye', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu',
            'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom',
            'US' => 'United States', 'UM' => 'United States Minor Outlying Islands', 'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands (British)', 'VI' => 'Virgin Islands (U.S.)', 'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'
        ];

        static $iso3ToIso2 = [
            'AFG'=>'AF','ALA'=>'AX','ALB'=>'AL','DZA'=>'DZ','ASM'=>'AS','AND'=>'AD','AGO'=>'AO','AIA'=>'AI','ATA'=>'AQ','ATG'=>'AG','ARG'=>'AR','ARM'=>'AM','ABW'=>'AW','AUS'=>'AU','AUT'=>'AT','AZE'=>'AZ',
            'BHS'=>'BS','BHR'=>'BH','BGD'=>'BD','BRB'=>'BB','BLR'=>'BY','BEL'=>'BE','BLZ'=>'BZ','BEN'=>'BJ','BMU'=>'BM','BTN'=>'BT','BOL'=>'BO','BES'=>'BQ','BIH'=>'BA','BWA'=>'BW','BVT'=>'BV','BRA'=>'BR',
            'IOT'=>'IO','BRN'=>'BN','BGR'=>'BG','BFA'=>'BF','BDI'=>'BI','CPV'=>'CV','KHM'=>'KH','CMR'=>'CM','CAN'=>'CA','CYM'=>'KY','CAF'=>'CF','TCD'=>'TD','CHL'=>'CL','CHN'=>'CN','CXR'=>'CX','CCK'=>'CC',
            'COL'=>'CO','COM'=>'KM','COG'=>'CG','COD'=>'CD','COK'=>'CK','CRI'=>'CR','CIV'=>'CI','HRV'=>'HR','CUB'=>'CU','CUW'=>'CW','CYP'=>'CY','CZE'=>'CZ','DNK'=>'DK','DJI'=>'DJ','DMA'=>'DM','DOM'=>'DO',
            'ECU'=>'EC','EGY'=>'EG','SLV'=>'SV','GNQ'=>'GQ','ERI'=>'ER','EST'=>'EE','SWZ'=>'SZ','ETH'=>'ET','FLK'=>'FK','FRO'=>'FO','FJI'=>'FJ','FIN'=>'FI','FRA'=>'FR','GUF'=>'GF','PYF'=>'PF','ATF'=>'TF',
            'GAB'=>'GA','GMB'=>'GM','GEO'=>'GE','DEU'=>'DE','GHA'=>'GH','GIB'=>'GI','GRC'=>'GR','GRL'=>'GL','GRD'=>'GD','GLP'=>'GP','GUM'=>'GU','GTM'=>'GT','GGY'=>'GG','GIN'=>'GN','GNB'=>'GW','GUY'=>'GY',
            'HTI'=>'HT','HMD'=>'HM','VAT'=>'VA','HND'=>'HN','HKG'=>'HK','HUN'=>'HU','ISL'=>'IS','IND'=>'IN','IDN'=>'ID','IRN'=>'IR','IRQ'=>'IQ','IRL'=>'IE','IMN'=>'IM','ISR'=>'IL','ITA'=>'IT','JAM'=>'JM',
            'JPN'=>'JP','JEY'=>'JE','JOR'=>'JO','KAZ'=>'KZ','KEN'=>'KE','KIR'=>'KI','PRK'=>'KP','KOR'=>'KR','KWT'=>'KW','KGZ'=>'KG','LAO'=>'LA','LVA'=>'LV','LBN'=>'LB','LSO'=>'LS','LBR'=>'LR','LBY'=>'LY',
            'LIE'=>'LI','LTU'=>'LT','LUX'=>'LU','MAC'=>'MO','MDG'=>'MG','MWI'=>'MW','MYS'=>'MY','MDV'=>'MV','MLI'=>'ML','MLT'=>'MT','MHL'=>'MH','MTQ'=>'MQ','MRT'=>'MR','MUS'=>'MU','MYT'=>'YT','MEX'=>'MX',
            'FSM'=>'FM','MDA'=>'MD','MCO'=>'MC','MNG'=>'MN','MNE'=>'ME','MSR'=>'MS','MAR'=>'MA','MOZ'=>'MZ','MMR'=>'MM','NAM'=>'NA','NRU'=>'NR','NPL'=>'NP','NLD'=>'NL','NCL'=>'NC','NZL'=>'NZ','NIC'=>'NI',
            'NER'=>'NE','NGA'=>'NG','NIU'=>'NU','NFK'=>'NF','MKD'=>'MK','MNP'=>'MP','NOR'=>'NO','OMN'=>'OM','PAK'=>'PK','PLW'=>'PW','PSE'=>'PS','PAN'=>'PA','PNG'=>'PG','PRY'=>'PY','PER'=>'PE','PHL'=>'PH',
            'PCN'=>'PN','POL'=>'PL','PRT'=>'PT','PRI'=>'PR','QAT'=>'QA','REU'=>'RE','ROU'=>'RO','RUS'=>'RU','RWA'=>'RW','BLM'=>'BL','SHN'=>'SH','KNA'=>'KN','LCA'=>'LC','MAF'=>'MF','SPM'=>'PM','VCT'=>'VC',
            'WSM'=>'WS','SMR'=>'SM','STP'=>'ST','SAU'=>'SA','SEN'=>'SN','SRB'=>'RS','SYC'=>'SC','SLE'=>'SL','SGP'=>'SG','SXM'=>'SX','SVK'=>'SK','SVN'=>'SI','SLB'=>'SB','SOM'=>'SO','ZAF'=>'ZA','SGS'=>'GS',
            'SSD'=>'SS','ESP'=>'ES','LKA'=>'LK','SDN'=>'SD','SUR'=>'SR','SJM'=>'SJ','SWE'=>'SE','CHE'=>'CH','SYR'=>'SY','TWN'=>'TW','TJK'=>'TJ','TZA'=>'TZ','THA'=>'TH','TLS'=>'TL','TGO'=>'TG','TKL'=>'TK',
            'TON'=>'TO','TTO'=>'TT','TUN'=>'TN','TUR'=>'TR','TKM'=>'TM','TCA'=>'TC','TUV'=>'TV','UGA'=>'UG','UKR'=>'UA','ARE'=>'AE','GBR'=>'GB','USA'=>'US','UMI'=>'UM','URY'=>'UY','UZB'=>'UZ','VUT'=>'VU',
            'VEN'=>'VE','VNM'=>'VN','VGB'=>'VG','VIR'=>'VI','WLF'=>'WF','ESH'=>'EH','YEM'=>'YE','ZMB'=>'ZM','ZWE'=>'ZW'
        ];

        if (strlen($code) === 2 && isset($iso2ToName[$code])) {
            return $iso2ToName[$code];
        }

        if (strlen($code) === 3 && isset($iso3ToIso2[$code])) {
            $code2 = $iso3ToIso2[$code];
            return $iso2ToName[$code2] ?? $code;
        }

        return $code;
    }
}
