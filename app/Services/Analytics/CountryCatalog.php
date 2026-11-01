<?php

namespace App\Services\Analytics;

final class CountryCatalog
{
    /**
     * Map from ISO-3166-1 alpha-2 code to canonical country name.
     */
    public const ISO2_TO_NAME = [
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

    /**
     * Map from ISO-3166-1 alpha-3 code to alpha-2 code.
     */
    public const ISO3_TO_ISO2 = [
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

    /**
     * Map from lowercased name or common alias to ISO alpha-2 code.
     */
    public const NAME_TO_ISO2 = [
        'aruba' => 'AW', 'afghanistan' => 'AF', 'angola' => 'AO', 'anguilla' => 'AI', 'åland islands' => 'AX', 'aland islands' => 'AX',
        'albania' => 'AL', 'andorra' => 'AD', 'united arab emirates' => 'AE', 'argentina' => 'AR', 'armenia' => 'AM',
        'american samoa' => 'AS', 'antarctica' => 'AQ', 'french southern territories' => 'TF', 'antigua and barbuda' => 'AG',
        'australia' => 'AU', 'austria' => 'AT', 'azerbaijan' => 'AZ', 'burundi' => 'BI', 'belgium' => 'BE', 'benin' => 'BJ',
        'bonaire, sint eustatius and saba' => 'BQ', 'burkina faso' => 'BF', 'bangladesh' => 'BD', 'bulgaria' => 'BG',
        'bahrain' => 'BH', 'bahamas' => 'BS', 'bosnia and herzegovina' => 'BA', 'saint barthélemy' => 'BL', 'saint barthelemy' => 'BL',
        'belarus' => 'BY', 'belize' => 'BZ', 'bermuda' => 'BM', 'bolivia' => 'BO', 'brazil' => 'BR', 'barbados' => 'BB',
        'brunei darussalam' => 'BN', 'brunei' => 'BN', 'bhutan' => 'BT', 'bouvet island' => 'BV', 'botswana' => 'BW',
        'central african republic' => 'CF', 'canada' => 'CA', 'cocos (keeling) islands' => 'CC', 'switzerland' => 'CH',
        'chile' => 'CL', 'china' => 'CN', "côte d'ivoire" => 'CI', "cote d'ivoire" => 'CI', 'ivory coast' => 'CI', 'cameroon' => 'CM',
        'congo, democratic republic of the' => 'CD', 'dr congo' => 'CD', 'congo' => 'CG', 'cook islands' => 'CK', 'colombia' => 'CO',
        'comoros' => 'KM', 'cabo verde' => 'CV', 'cape verde' => 'CV', 'costa rica' => 'CR', 'cuba' => 'CU', 'curaçao' => 'CW', 'curacao' => 'CW',
        'christmas island' => 'CX', 'cayman islands' => 'KY', 'cyprus' => 'CY', 'czechia' => 'CZ', 'czech republic' => 'CZ', 'germany' => 'DE',
        'djibouti' => 'DJ', 'dominica' => 'DM', 'denmark' => 'DK', 'dominican republic' => 'DO', 'algeria' => 'DZ',
        'ecuador' => 'EC', 'egypt' => 'EG', 'eritrea' => 'ER', 'western sahara' => 'EH', 'spain' => 'ES', 'estonia' => 'EE',
        'ethiopia' => 'ET', 'finland' => 'FI', 'fiji' => 'FJ', 'falkland islands (malvinas)' => 'FK', 'falkland islands' => 'FK', 'france' => 'FR',
        'faroe islands' => 'FO', 'micronesia' => 'FM', 'gabon' => 'GA', 'united kingdom' => 'GB', 'great britain' => 'GB', 'georgia' => 'GE',
        'guernsey' => 'GG', 'ghana' => 'GH', 'gibraltar' => 'GI', 'guinea' => 'GN', 'guadeloupe' => 'GP', 'gambia' => 'GM',
        'guinea-bissau' => 'GW', 'equatorial guinea' => 'GQ', 'greece' => 'GR', 'grenada' => 'GD', 'greenland' => 'GL',
        'guatemala' => 'GT', 'french guiana' => 'GF', 'guam' => 'GU', 'guyana' => 'GY', 'hong kong' => 'HK',
        'heard island and mcdonald islands' => 'HM', 'honduras' => 'HN', 'croatia' => 'HR', 'haiti' => 'HT',
        'hungary' => 'HU', 'indonesia' => 'ID', 'isle of man' => 'IM', 'india' => 'IN',
        'british indian ocean territory' => 'IO', 'ireland' => 'IE', 'iran' => 'IR', 'iraq' => 'IQ', 'iceland' => 'IS',
        'israel' => 'IL', 'italy' => 'IT', 'jamaica' => 'JM', 'jersey' => 'JE', 'jordan' => 'JOR', 'japan' => 'JP',
        'kazakhstan' => 'KZ', 'kenya' => 'KE', 'kyrgyzstan' => 'KG', 'cambodia' => 'KH', 'kiribati' => 'KI',
        'saint kitts and nevis' => 'KN', 'korea, republic of' => 'KR', 'south korea' => 'KR', 'kuwait' => 'KW',
        "lao people's democratic republic" => 'LA', 'laos' => 'LA', 'lebanon' => 'LB', 'liberia' => 'LR', 'libya' => 'LY',
        'saint lucia' => 'LC', 'liechtenstein' => 'LI', 'sri lanka' => 'LK', 'lesotho' => 'LS', 'lithuania' => 'LT',
        'luxembourg' => 'LU', 'latvia' => 'LV', 'macao' => 'MO', 'macau' => 'MO', 'saint martin (french part)' => 'MF', 'saint martin' => 'MF', 'morocco' => 'MA',
        'monaco' => 'MC', 'moldova' => 'MD', 'madagascar' => 'MG', 'maldives' => 'MV', 'mexico' => 'MX',
        'marshall islands' => 'MH', 'north macedonia' => 'MK', 'mali' => 'ML', 'malta' => 'MT', 'myanmar' => 'MM',
        'montenegro' => 'ME', 'mongolia' => 'MN', 'northern mariana islands' => 'MP', 'mozambique' => 'MZ',
        'mauritania' => 'MR', 'montserrat' => 'MS', 'martinique' => 'MQ', 'mauritius' => 'MU', 'malawi' => 'MW',
        'malaysia' => 'MY', 'mayotte' => 'YT', 'namibia' => 'NA', 'new caledonia' => 'NC', 'niger' => 'NE',
        'norfolk island' => 'NF', 'nigeria' => 'NG', 'nicaragua' => 'NI', 'niue' => 'NU', 'netherlands' => 'NL',
        'norway' => 'NO', 'nepal' => 'NP', 'nauru' => 'NR', 'new zealand' => 'NZ', 'oman' => 'OM', 'pakistan' => 'PK',
        'panama' => 'PA', 'pitcairn' => 'PN', 'peru' => 'PE', 'philippines' => 'PH', 'palau' => 'PW',
        'papua new guinea' => 'PG', 'poland' => 'PL', 'puerto rico' => 'PR',
        "korea, democratic people's republic of" => 'KP', 'north korea' => 'KP', 'portugal' => 'PT', 'paraguay' => 'PY',
        'palestine, state of' => 'PS', 'palestine' => 'PS', 'french polynesia' => 'PF', 'qatar' => 'QA', 'réunion' => 'RE', 'reunion' => 'RE',
        'romania' => 'RO', 'russian federation' => 'RU', 'russia' => 'RU', 'rwanda' => 'RW', 'saudi arabia' => 'SA', 'sudan' => 'SD',
        'senegal' => 'SN', 'singapore' => 'SG', 'south georgia and the south sandwich islands' => 'GS',
        'saint helena, ascension and tristan da cunha' => 'SH', 'saint helena' => 'SH', 'svalbard and jan mayen' => 'SJ',
        'solomon islands' => 'SB', 'sierra leone' => 'SL', 'el salvador' => 'SV', 'san marino' => 'SM', 'somalia' => 'SO',
        'saint pierre and miquelon' => 'PM', 'serbia' => 'RS', 'south sudan' => 'SS',
        'sao tome and principe' => 'ST', 'suriname' => 'SR', 'slovakia' => 'SK', 'slovenia' => 'SI', 'sweden' => 'SE',
        'eswatini' => 'SZ', 'swaziland' => 'SZ', 'sint maarten (dutch part)' => 'SX', 'sint maarten' => 'SX', 'seychelles' => 'SC',
        'syrian arab republic' => 'SY', 'syria' => 'SY', 'turks and caicos islands' => 'TC', 'chad' => 'TD', 'togo' => 'TG',
        'thailand' => 'TH', 'tajikistan' => 'TJ', 'tokelau' => 'TK', 'turkmenistan' => 'TM', 'timor-leste' => 'TL',
        'tonga' => 'TO', 'trinidad and tobago' => 'TT', 'tunisia' => 'TN', 'türkiye' => 'TR', 'turkey' => 'TR', 'tuvalu' => 'TV',
        'taiwan' => 'TW', 'tanzania' => 'TZ', 'uganda' => 'UG', 'ukraine' => 'UA',
        'united states minor outlying islands' => 'UM', 'uruguay' => 'UY', 'united states of america' => 'US', 'united states' => 'US', 'usa' => 'US',
        'uzbekistan' => 'UZ', 'holy see' => 'VA', 'vatican' => 'VA', 'saint vincent and the grenadines' => 'VC', 'venezuela' => 'VE',
        'virgin islands (british)' => 'VG', 'british virgin islands' => 'VG', 'virgin islands (u.s.)' => 'VI', 'u.s. virgin islands' => 'VI', 'viet nam' => 'VN', 'vietnam' => 'VN', 'vanuatu' => 'VU',
        'wallis and futuna' => 'WF', 'samoa' => 'WS', 'yemen' => 'YE', 'south africa' => 'ZA', 'zambia' => 'ZM',
        'zimbabwe' => 'ZW'
    ];

    /**
     * Resolve any country code (ISO2 or ISO3) or name to canonical full country name.
     */
    public static function nameFromCode(string $code): string
    {
        $code = trim(strtoupper($code));
        if ($code === '' || $code === 'UNKNOWN' || $code === 'NULL' || $code === '(NOT SET)') {
            return 'Unknown';
        }

        if (strlen($code) === 2 && isset(self::ISO2_TO_NAME[$code])) {
            return self::ISO2_TO_NAME[$code];
        }

        if (strlen($code) === 3 && isset(self::ISO3_TO_ISO2[$code])) {
            $code2 = self::ISO3_TO_ISO2[$code];
            return self::ISO2_TO_NAME[$code2] ?? $code;
        }

        return $code;
    }

    /**
     * Resolve raw input (code, name, alias) to [iso2, normalizedName].
     */
    public static function resolveIso2AndName(?string $rawName): array
    {
        if (empty($rawName)) {
            return [null, 'Unknown'];
        }

        $clean = trim((string) $rawName);
        $cleanWithoutRatio = preg_replace('/\s*\(?%\)?$/', '', $clean);
        $lower = strtolower(trim($cleanWithoutRatio));

        // Direct ISO2
        if (strlen($lower) === 2 && ctype_alpha($lower)) {
            $u2 = strtoupper($lower);
            return [$u2, self::ISO2_TO_NAME[$u2] ?? $cleanWithoutRatio];
        }

        // ISO3 code
        if (strlen($lower) === 3 && ctype_alpha($lower)) {
            $u3 = strtoupper($lower);
            if (isset(self::ISO3_TO_ISO2[$u3])) {
                $u2 = self::ISO3_TO_ISO2[$u3];
                return [$u2, self::ISO2_TO_NAME[$u2] ?? $cleanWithoutRatio];
            }
        }

        // Country name / alias match
        if (isset(self::NAME_TO_ISO2[$lower])) {
            $u2 = self::NAME_TO_ISO2[$lower];
            return [$u2, self::ISO2_TO_NAME[$u2] ?? $cleanWithoutRatio];
        }

        // Stripped match
        $stripped = preg_replace('/[^\p{L}\p{N}\s]/u', '', $lower);
        if (isset(self::NAME_TO_ISO2[$stripped])) {
            $u2 = self::NAME_TO_ISO2[$stripped];
            return [$u2, self::ISO2_TO_NAME[$u2] ?? $cleanWithoutRatio];
        }

        return [null, $cleanWithoutRatio];
    }
}
