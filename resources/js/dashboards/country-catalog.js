/**
 * Country Catalog and ISO code conversion helpers.
 */

export const COUNTRY_NAME_TO_CODE = {
    'Aruba':'ABW','Afghanistan':'AFG','Angola':'AGO','Anguilla':'AIA','Åland Islands':'ALA','Albania':'ALB',
    'Andorra':'AND','United Arab Emirates':'ARE','Argentina':'ARG','Armenia':'ARM','American Samoa':'ASM',
    'Antarctica':'ATA','French Southern Territories':'ATF','Antigua and Barbuda':'ATG','Australia':'AUS',
    'Austria':'AUT','Azerbaijan':'AZE','Burundi':'BDI','Belgium':'BEL','Benin':'BEN',
    'Bonaire, Sint Eustatius and Saba':'BES','Burkina Faso':'BFA','Bangladesh':'BGD','Bulgaria':'BGR',
    'Bahrain':'BHR','Bahamas':'BHS','Bosnia and Herzegovina':'BIH','Saint Barthélemy':'BLM','Belarus':'BLR',
    'Belize':'BLZ','Bermuda':'BMU','Bolivia':'BOL','Brazil':'BRA','Barbados':'BRB',
    'Brunei Darussalam':'BRN','Bhutan':'BTN','Bouvet Island':'BVT','Botswana':'BWA',
    'Central African Republic':'CAF','Canada':'CAN','Cocos (Keeling) Islands':'CCK','Switzerland':'CHE',
    'Chile':'CHL','China':'CHN',"Côte d'Ivoire":'CIV','Cameroon':'CMR',
    'Congo, Democratic Republic of the':'COD','Congo':'COG','Cook Islands':'COK','Colombia':'COL',
    'Comoros':'COM','Cabo Verde':'CPV','Costa Rica':'CRI','Cuba':'CUB','Curaçao':'CUW',
    'Christmas Island':'CXR','Cayman Islands':'CYM','Cyprus':'CYP','Czechia':'CZE','Germany':'DEU',
    'Djibouti':'DJI','Dominica':'DMA','Denmark':'DNK','Dominican Republic':'DOM','Algeria':'DZA',
    'Ecuador':'ECU','Egypt':'EGY','Eritrea':'ERI','Western Sahara':'ESH','Spain':'ESP','Estonia':'EST',
    'Ethiopia':'ETH','Finland':'FIN','Fiji':'FJI','Falkland Islands (Malvinas)':'FLK','France':'FRA',
    'Faroe Islands':'FRO','Micronesia':'FSM','Gabon':'GAB','United Kingdom':'GBR','Georgia':'GEO',
    'Guernsey':'GGY','Ghana':'GHA','Gibraltar':'GIB','Guinea':'GIN','Guadeloupe':'GLP','Gambia':'GMB',
    'Guinea-Bissau':'GNB','Equatorial Guinea':'GNQ','Greece':'GRC','Grenada':'GRD','Greenland':'GRL',
    'Guatemala':'GTM','French Guiana':'GUF','Guam':'GUM','Guyana':'GUY','Hong Kong':'HKG',
    'Heard Island and McDonald Islands':'HMD','Honduras':'HND','Croatia':'HRV','Haiti':'HTI',
    'Hungary':'HUN','Indonesia':'IDN','Isle of Man':'IMN','India':'IND',
    'British Indian Ocean Territory':'IOT','Ireland':'IRL','Iran':'IRN','Iraq':'IRQ','Iceland':'ISL',
    'Israel':'ISR','Italy':'ITA','Jamaica':'JAM','Jersey':'JEY','Jordan':'JOR','Japan':'JPN',
    'Kazakhstan':'KAZ','Kenya':'KEN','Kyrgyzstan':'KGZ','Cambodia':'KHM','Kiribati':'KIR',
    'Saint Kitts and Nevis':'KNA','Korea, Republic of':'KOR','Kuwait':'KWT',
    "Lao People's Democratic Republic":'LAO','Lebanon':'LBN','Liberia':'LBR','Libya':'LBY',
    'Saint Lucia':'LCA','Liechtenstein':'LIE','Sri Lanka':'LKA','Lesotho':'LSO','Lithuania':'LTU',
    'Luxembourg':'LUX','Latvia':'LVA','Macao':'MAC','Saint Martin (French part)':'MAF','Morocco':'MAR',
    'Monaco':'MCO','Moldova':'MDA','Madagascar':'MDG','Maldives':'MDV','Mexico':'MEX',
    'Marshall Islands':'MHL','North Macedonia':'MKD','Mali':'MLI','Malta':'MLT','Myanmar':'MMR',
    'Montenegro':'MNE','Mongolia':'MNG','Northern Mariana Islands':'MNP','Mozambique':'MOZ',
    'Mauritania':'MRT','Montserrat':'MSR','Martinique':'MTQ','Mauritius':'MUS','Malawi':'MWI',
    'Malaysia':'MYS','Mayotte':'MYT','Namibia':'NAM','New Caledonia':'NCL','Niger':'NER',
    'Norfolk Island':'NFK','Nigeria':'NGA','Nicaragua':'NIC','Niue':'NIU','Netherlands':'NLD',
    'Norway':'NOR','Nepal':'NPL','Nauru':'NRU','New Zealand':'NZL','Oman':'OMN','Pakistan':'PAK',
    'Panama':'PAN','Pitcairn':'PCN','Peru':'PER','Philippines':'PHL','Palau':'PLW',
    'Papua New Guinea':'PNG','Poland':'POL','Puerto Rico':'PRI',
    "Korea, Democratic People's Republic of":'PRK','Portugal':'PRT','Paraguay':'PRY',
    'Palestine, State of':'PSE','French Polynesia':'PYF','Qatar':'QAT','Réunion':'REU',
    'Romania':'ROU','Russian Federation':'RUS','Rwanda':'RWA','Saudi Arabia':'SAU','Sudan':'SDN',
    'Senegal':'SEN','Singapore':'SGP','South Georgia and the South Sandwich Islands':'SGS',
    'Saint Helena, Ascension and Tristan da Cunha':'SHN','Svalbard and Jan Mayen':'SJM',
    'Solomon Islands':'SLB','Sierra Leone':'SLE','El Salvador':'SLV','San Marino':'SMR','Somalia':'SOM',
    'Saint Pierre and Miquelon':'SPM','Serbia':'SRB','South Sudan':'SSD',
    'Sao Tome and Principe':'STP','Suriname':'SUR','Slovakia':'SVK','Slovenia':'SVN','Sweden':'SWE',
    'Eswatini':'SWZ','Sint Maarten (Dutch part)':'SXM','Seychelles':'SYC',
    'Syrian Arab Republic':'SYR','Turks and Caicos Islands':'TCA','Chad':'TCD','Togo':'TGO',
    'Thailand':'THA','Tajikistan':'TJK','Tokelau':'TKL','Turkmenistan':'TKM','Timor-Leste':'TLS',
    'Tonga':'TON','Trinidad and Tobago':'TTO','Tunisia':'TUN','Türkiye':'TUR','Tuvalu':'TUV',
    'Taiwan':'TWN','Tanzania':'TZA','Uganda':'UGA','Ukraine':'UKR',
    'United States Minor Outlying Islands':'UMI','Uruguay':'URY','United States of America':'USA','United States':'USA','USA':'USA',
    'Uzbekistan':'UZB','Holy See':'VAT','Saint Vincent and the Grenadines':'VCT','Venezuela':'VEN',
    'Virgin Islands (British)':'VGB','Virgin Islands (U.S.)':'VIR','Viet Nam':'VNM','Vietnam':'VNM','Vanuatu':'VUT',
    'Wallis and Futuna':'WLF','Samoa':'WSM','Yemen':'YEM','South Africa':'ZAF','Zambia':'ZMB',
    'Zimbabwe':'ZWE','Others':'OTH','Unknown':'UNK',
};

export const ISO3_TO_ISO2 = {
    'AFG':'AF','ALA':'AX','ALB':'AL','DZA':'DZ','ASM':'AS','AND':'AD','AGO':'AO','AIA':'AI','ATA':'AQ','ATG':'AG','ARG':'AR','ARM':'AM','ABW':'AW','AUS':'AU','AUT':'AT','AZE':'AZ',
    'BHS':'BS','BHR':'BH','BGD':'BD','BRB':'BB','BLR':'BY','BEL':'BE','BLZ':'BZ','BEN':'BJ','BMU':'BM','BTN':'BT','BOL':'BO','BES':'BQ','BIH':'BA','BWA':'BW','BVT':'BV','BRA':'BR',
    'IOT':'IO','BRN':'BN','BGR':'BG','BFA':'BF','BDI':'BI','CPV':'CV','KHM':'KH','CMR':'CM','CAN':'CA','CYM':'KY','CAF':'CF','TCD':'TD','CHL':'CL','CHN':'CN','CXR':'CX','CCK':'CC',
    'COL':'CO','COM':'KM','COG':'CG','COD':'CD','COK':'CK','CRI':'CR','CIV':'CI','HRV':'HR','CUB':'CU','CUW':'CW','CYP':'CY','CZE':'CZ','DNK':'DK','DJI':'DJ','DMA':'DM','DOM':'DO',
    'ECU':'EC','EGY':'EG','SLV':'SV','GNQ':'GQ','ERI':'ER','EST':'EE','SWZ':'SZ','ETH':'ET','FLK':'FK','FRO':'FO','FJI':'FJ','FIN':'FI','FRA':'FR','GUF':'GF','PYF':'PF','ATF':'TF',
    'GAB':'GA','GMB':'GM','GEO':'GE','DEU':'DE','GHA':'GH','GIB':'GI','GRC':'GR','GRL':'GL','GRD':'GD','GLP':'GP','GUM':'GU','GTM':'GT','GGY':'GG','GIN':'GN','GNB':'GW','GUY':'GY',
    'HTI':'HT','HMD':'HM','VAT':'VA','HND':'HN','HKG':'HK','HUN':'HU','ISL':'IS','IND':'IN','IDN':'ID','IRN':'IR','IRQ':'IQ','IRL':'IE','IMN':'IM','ISR':'IL','ITA':'IT','JAM':'JM',
    'JPN':'JP','JEY':'JE','JOR':'JO','KAZ':'KZ','KEN':'KE','KIR':'KI','PRK':'KP','KOR':'KR','KWT':'KW','KGZ':'KG','LAO':'LA','LVA':'LV','LBN':'LB','LSO':'LS','LBR':'LR','LBY':'LY',
    'LIE':'LI','LTU':'LT','LUX':'LU','MAC':'MO','MDG':'MG','MWI':'MW','MYS':'MY','MDV':'MV','MLI':'ML','MLT':'MT','MHL':'MH','MTQ':'MQ','MRT':'MR','MUS':'MU','MYT':'YT','MEX':'MX',
    'FSM':'FM','MDA':'MD','MCO':'MC','MNG':'MN','MNE':'ME','MSR':'MS','MAR':'MA','MOZ':'MZ','MMR':'MM','NAM':'NA','NRU':'NR','NPL':'NP','NLD':'NL','NCL':'NC','NZL':'NZ','NIC':'NI',
    'NER':'NE','NGA':'NG','NIU':'NU','NFK':'NF','MKD':'MK','MNP':'MP','NOR':'NO','OMN':'OM','PAK':'PK','PLW':'PW','PSE':'PS','PAN':'PA','PNG':'PG','PRY':'PY','PER':'PE','PHL':'PH',
    'PCN':'PN','POL':'PL','PRT':'PT','PRI':'PR','QAT':'QA','REU':'RE','ROU':'RO','RUS':'RU','RWA':'RW','BLM':'BL','SHN':'SH','KNA':'KN','LCA':'LC','MAF':'MF','SPM':'PM','VCT':'VC',
    'WSM':'WS','SMR':'SM','STP':'ST','SAU':'SA','SEN':'SN','SRB':'RS','SYC':'SC','SLE':'SL','SGP':'SG','SXM':'SX','SVK':'SK','SVN':'SI','SLB':'SB','SOM':'SO','ZAF':'ZA','SGS':'GS',
    'SSD':'SS','ESP':'ES','LKA':'LK','SDN':'SD','SUR':'SR','SJM':'SJ','SWE':'SE','CHE':'CH','SYR':'SY','TWN':'TW','TJK':'TJ','TZA':'TZ','THA':'TH','TLS':'TL','TGO':'TG','TKL':'TK',
    'TON':'TO','TTO':'TT','TUN':'TN','TUR':'TR','TKM':'TM','TCA':'TC','TUV':'TV','UGA':'UG','UKR':'UA','ARE':'AE','GBR':'GB','USA':'US','UMI':'UM','URY':'UY','UZB':'UZ','VUT':'VU',
    'VEN':'VE','VNM':'VN','VGB':'VG','VIR':'VI','WLF':'WF','ESH':'EH','YEM':'YE','ZMB':'ZM','ZWE':'ZW'
};

/**
 * Converts a 3-letter ISO code to a 2-letter ISO code.
 */
export function convertIso3ToIso2(iso3) {
    if (!iso3) return null;
    return ISO3_TO_ISO2[iso3.toUpperCase()] || null;
}

/**
 * Converts a 2-letter or 3-letter country code to full canonical country name.
 */
export function countryCodeToName(alpha2) {
    if (!alpha2) return null;
    const code2 = alpha2.toUpperCase();
    for (const [name, iso3] of Object.entries(COUNTRY_NAME_TO_CODE)) {
        if (convertIso3ToIso2(iso3) === code2 || iso3 === code2) {
            return name;
        }
    }
    return alpha2;
}
