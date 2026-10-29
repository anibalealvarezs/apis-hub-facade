import { withEntityAttributes } from './entity-attributes';
import { dataTable } from './data-table';

const COUNTRY_NAME_TO_CODE = {
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
    'United States Minor Outlying Islands':'UMI','Uruguay':'URY','United States of America':'USA',
    'Uzbekistan':'UZB','Holy See':'VAT','Saint Vincent and the Grenadines':'VCT','Venezuela':'VEN',
    'Virgin Islands (British)':'VGB','Virgin Islands (U.S.)':'VIR','Viet Nam':'VNM','Vanuatu':'VUT',
    'Wallis and Futuna':'WLF','Samoa':'WSM','Yemen':'YEM','South Africa':'ZAF','Zambia':'ZMB',
    'Zimbabwe':'ZWE','Others':'OTH','Unknown':'UNK',
};

export function gscDashboard(config = {}) {
    return withEntityAttributes({
        tenantId: config.tenantId || '',
        account: config.account || '',
        selectedAccount: config.selectedAccount || '',
        accountNames: config.accountNames || {},
        dateStart: config.dateStart || '',
        dateEnd: config.dateEnd || '',
        activeTab: config.activeTab || 'queries',
        csrfToken: config.csrfToken || '',

        isSummaryLoading: false,
        isChartLoading: false,
        isTableLoading: false,

        summary: { clicks: 0, impressions: 0, ctr: 0, position: 0 },
        previous: { clicks: 0, impressions: 0, ctr: 0, position: 0 },
        chartDataRaw: [],
        tableState: dataTable({ sortCol: 'clicks', sortDir: 'desc', searchKeys: ['id'] }),
        trendData: {},
        showTrends: false,

        activeMetrics: { clicks: true, impressions: true, ctr: false, position: false },
        countryViewMode: 'map', // 'map' or 'table'

        get countryMapData() {
            if (this.activeTab !== 'countries') return {};
            const mapData = {};
            const activeMetric = this.activeMetrics.clicks ? 'clicks' : 
                                (this.activeMetrics.impressions ? 'impressions' : 
                                (this.activeMetrics.ctr ? 'ctr' : 'position'));

            (this.tableState.rows || []).forEach(row => {
                const countryName = row.id;
                const isoCode = COUNTRY_NAME_TO_CODE[countryName] || countryName;
                if (isoCode && isoCode.length === 3) {
                    // Convert 3-letter ISO to 2-letter for standard jsVectorMap world map
                    const alpha2 = this.convertIso3ToIso2(isoCode);
                    if (alpha2) {
                        mapData[alpha2] = Number(row[activeMetric]) || 0;
                    }
                } else if (isoCode && isoCode.length === 2) {
                    mapData[isoCode.toUpperCase()] = Number(row[activeMetric]) || 0;
                }
            });
            return mapData;
        },

        convertIso3ToIso2(iso3) {
            const map3to2 = {
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
            return map3to2[iso3.toUpperCase()] || null;
        },

        activeFilters: { queries: [], pages: [], countries: [], devices: [], intents: [] },

        intentOptions: config.intentOptions || {
            'transactional': 'Transactional',
            'commercial': 'Commercial',
            'informational': 'Informational',
            'navigational': 'Navigational',
            'unclassified': 'Unclassified'
        },

        get selectedCountryCodes() {
            if (!this.activeFilters.countries || !this.activeFilters.countries.length) return [];
            return this.activeFilters.countries.map(name => {
                const iso3 = COUNTRY_NAME_TO_CODE[name] || name;
                return (iso3 && iso3.length === 3) ? (this.convertIso3ToIso2(iso3) || iso3) : (iso3 ? iso3.toUpperCase() : null);
            }).filter(Boolean);
        },

        countryCodeToName(alpha2) {
            if (!alpha2) return null;
            const code2 = alpha2.toUpperCase();
            for (const [name, iso3] of Object.entries(COUNTRY_NAME_TO_CODE)) {
                if (this.convertIso3ToIso2(iso3) === code2) {
                    return name;
                }
            }
            return alpha2;
        },

        handleMapCountrySelected(code) {
            if (!code) return;
            const code2 = code.toUpperCase();

            // Match clicked region code against current table rows or mapping
            const rows = this.tableState.rows || [];
            let matchingRow = rows.find(r => {
                const rowId = (r.id || '').trim();
                if (!rowId) return false;
                if (rowId.toUpperCase() === code2) return true;
                const iso3 = COUNTRY_NAME_TO_CODE[rowId] || rowId;
                if (iso3 && (iso3.toUpperCase() === code2 || this.convertIso3ToIso2(iso3) === code2)) return true;
                return false;
            });

            const filterVal = matchingRow ? matchingRow.id : (this.countryCodeToName(code2) || code2);
            this.toggleFilter('countries', filterVal);
        },

        resetCountryFilters() {
            if (this.activeFilters.countries && this.activeFilters.countries.length > 0) {
                this.activeFilters.countries = [];
                this.saveFilters();
                this.fetchSummary();
                this.fetchChart();
            }
        },

        get hasAnyFilters() {
            return Object.values(this.activeFilters).some(arr => arr.length > 0);
        },

        restoreFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const accParam = params.get('account');
            if (accParam && this.accountNames[accParam]) {
                this.account = accParam;
                this.selectedAccount = accParam;
            }
            if (!this.selectedAccount && Object.keys(this.accountNames).length > 0) {
                const firstAcc = Object.keys(this.accountNames)[0];
                this.account = firstAcc;
                this.selectedAccount = firstAcc;
            }
            const ds = params.get('dateStart');
            if (ds && /^\d{4}-\d{2}-\d{2}$/.test(ds)) this.dateStart = ds;
            const de = params.get('dateEnd');
            if (de && /^\d{4}-\d{2}-\d{2}$/.test(de)) this.dateEnd = de;
            const tab = params.get('tab');
            if (tab && ['queries', 'pages', 'countries', 'devices', 'appearances'].includes(tab)) this.activeTab = tab;
            const metricsParam = params.get('metrics');
            if (metricsParam) {
                const enabledMetrics = metricsParam.split(',');
                Object.keys(this.activeMetrics).forEach(key => {
                    this.activeMetrics[key] = enabledMetrics.includes(key);
                });
            }
        },

        syncToUrl() {
            const params = new URLSearchParams();
            if (this.account) params.set('account', this.account);
            if (this.dateStart) params.set('dateStart', this.dateStart);
            if (this.dateEnd) params.set('dateEnd', this.dateEnd);
            if (this.activeTab) params.set('tab', this.activeTab);
            const enabledMetrics = Object.entries(this.activeMetrics).filter(([k, v]) => v).map(([k]) => k);
            if (enabledMetrics.length > 0) params.set('metrics', enabledMetrics.join(','));
            const qs = params.toString();
            const url = qs ? window.location.pathname + '?' + qs : window.location.pathname;
            history.replaceState(null, '', url);
        },

        initDashboard() {
            this.restoreFromUrl();

            const boot = () => {
                this.initChart();

                this.$watch('account', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });

                this.$watch('dateStart', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });

                this.$watch('dateEnd', () => {
                    this.syncToUrl();
                    this.trendData = {};
                    this.fetchAll();
                });

                this.$watch('tableState.pageSize', () => {
                    this.tableState.currentPage = 1;
                });

                if (this.account && this.dateStart && this.dateEnd) {
                    this.loadFilters();
                    this.fetchAll();
                }
            };

            if (window.Chart && window.dayjs) {
                boot();
            } else if (window.importChartJs && window.importDayJs) {
                Promise.all([
                    window.importChartJs(),
                    window.importDayJs()
                ]).then(([chartModule, dayjsModule]) => {
                    window.Chart = chartModule.default;
                    window.dayjs = dayjsModule.default;
                    boot();
                }).catch(err => {
                    console.error("Failed to load charting libraries", err);
                });
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.tableState.currentPage = 1;
            this.tableState.searchQuery = '';
            this.syncToUrl();
            this.fetchTable();
            if (this.$wire && typeof this.$wire.setActiveTab === 'function') {
                this.$wire.setActiveTab(tab);
            }
        },

        loadFilters() {
            if (!this.account) return;
            const saved = sessionStorage.getItem(`gsc_filters_${this.tenantId}_${this.account}`);
            if (saved) {
                try {
                    this.activeFilters = JSON.parse(saved);
                } catch (e) {
                    this.clearFiltersLocal();
                }
            } else {
                this.clearFiltersLocal();
            }
        },

        saveFilters() {
            if (!this.account) return;
            this.safeCacheSet(`gsc_filters_${this.tenantId}_${this.account}`, JSON.stringify(this.activeFilters));
        },

        clearFiltersLocal() {
            this.activeFilters = { queries: [], pages: [], countries: [], devices: [], intents: [] };
        },

        filterDropdownChanged() {
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        removeFilter(tab, val) {
            if (this.activeFilters[tab]) {
                this.activeFilters[tab] = this.activeFilters[tab].filter(item => String(item) !== String(val));
                this.saveFilters();
                this.fetchSummary();
                this.fetchChart();
                this.fetchTable();
            }
        },

        formatFilterTab(tab) {
            const map = {
                queries: 'Query',
                pages: 'Page',
                countries: 'Country',
                devices: 'Device',
                intents: 'Intent'
            };
            return map[tab] || tab;
        },

        getFilterLabel(tab, val) {
            if (tab === 'intents') {
                return this.intentOptions[val] || (String(val).charAt(0).toUpperCase() + String(val).slice(1));
            }
            return val;
        },

        getIntentBadgeClass(intent) {
            const map = {
                transactional: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700',
                commercial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700',
                informational: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700',
                navigational: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-300 dark:border-purple-700',
                unclassified: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600'
            };
            return map[String(intent).toLowerCase()] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600';
        },

        clearFilters() {
            this.clearFiltersLocal();
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        toggleFilter(tab, value) {
            if (tab === 'appearances') return;
            if (!this.activeFilters[tab]) this.activeFilters[tab] = [];
            const idx = this.activeFilters[tab].indexOf(value);
            if (idx > -1) {
                this.activeFilters[tab].splice(idx, 1);
            } else {
                this.activeFilters[tab].push(value);
            }
            this.saveFilters();
            this.fetchSummary();
            this.fetchChart();
        },

        isFilterActive(tab, value) {
            return this.activeFilters[tab] && this.activeFilters[tab].includes(value);
        },

        forceRefresh() {
            this.clearCache();
            this.trendData = {};
            this.fetchAll();
        },

        safeCacheSet(key, value) {
            try {
                sessionStorage.setItem(key, value);
                return true;
            } catch (e) {
                if (e.name === 'QuotaExceededError' || e.code === 22) {
                    Object.keys(sessionStorage).forEach(k => {
                        if (k.startsWith('gsc_') || k.startsWith('fbo_') || k.startsWith('fbm_')) {
                            sessionStorage.removeItem(k);
                        }
                    });
                    try {
                        sessionStorage.setItem(key, value);
                        return true;
                    } catch {
                        console.warn('Cache still full after eviction, skipping cache for', key);
                        return false;
                    }
                }
                console.warn('Cache write failed:', e);
                return false;
            }
        },

        clearCache() {
            const prefix = `gsc_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}`;
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(prefix)) {
                    sessionStorage.removeItem(key);
                }
            });
        },

        getCacheKey(endpoint, includeFilters = true) {
            const filterHash = includeFilters ? JSON.stringify(this.activeFilters) : 'no_filters';
            return `gsc_${this.tenantId}_${this.account}_${this.dateStart}_${this.dateEnd}_${endpoint}_${this.activeTab}_${filterHash}`;
        },

        async fetchAll() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            this.fetchSummary();
            this.fetchChart();
            this.fetchTable();
        },

        async fetchSummary() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('summary');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.summary = data.summary || { clicks: 0, impressions: 0, ctr: 0, position: 0 };
                this.previous = data.previous || { clicks: 0, impressions: 0, ctr: 0, position: 0 };
                return;
            }

            this.isSummaryLoading = true;
            try {
                const response = await fetch('/api/gsc/summary', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.summary = data.summary || { clicks: 0, impressions: 0, ctr: 0, position: 0 };
                    this.previous = data.previous || { clicks: 0, impressions: 0, ctr: 0, position: 0 };
                }
            } catch (error) {
                console.error('Error fetching summary:', error);
            } finally {
                this.isSummaryLoading = false;
            }
        },

        async fetchChart() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('chart');

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.chartDataRaw = data.chart || [];
                if (this.showTrends) {
                    this.fetchTrends();
                } else {
                    this.updateChart();
                }
                return;
            }

            this.isChartLoading = true;
            try {
                const response = await fetch('/api/gsc/chart', this.getFetchOptions());
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.chartDataRaw = data.chart || [];
                    if (this.showTrends) {
                        this.fetchTrends();
                    } else {
                        this.updateChart();
                    }
                }
            } catch (error) {
                console.error('Error fetching chart:', error);
            } finally {
                this.isChartLoading = false;
            }
        },

        async fetchTrends() {
            if (!this.showTrends || !this.chartDataRaw || this.chartDataRaw.length === 0) return;
            
            const activeKeys = Object.keys(this.activeMetrics).filter(k => this.activeMetrics[k]);
            const allowedMetrics = ['impressions', 'clicks'];
            const validMetrics = activeKeys.filter(m => allowedMetrics.includes(m));
            
            if (validMetrics.length === 0) {
                this.updateChart();
                return;
            }
            
            this.isChartLoading = true;
            
            try {
                const promises = validMetrics.map(async (metric) => {
                    const seriesDates = this.chartDataRaw.map(r => r.daily || r.date || r.metric_date).filter(Boolean);
                    const seriesValues = this.chartDataRaw.map(r => {
                        let v = r[metric];
                        return v !== undefined && v !== null && v !== '' ? parseFloat(v) : null;
                    });
                    
                    const payload = {
                        tenant: this.tenantId,
                        metric: metric,
                        series: {
                            dates: seriesDates,
                            values: seriesValues
                        }
                    };
                    
                    const response = await fetch('/api/gsc/trend', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if(data.trend) {
                        this.trendData[metric] = data.trend;
                    }
                });
                
                await Promise.all(promises);
                this.updateChart();
            } catch (error) {
                console.error('Error fetching trends:', error);
            } finally {
                this.isChartLoading = false;
            }
        },

        handleTrendToggle() {
            if (this.showTrends) {
                this.fetchTrends();
            } else {
                this.updateChart();
            }
        },

        async fetchTable() {
            if (!this.account || !this.dateStart || !this.dateEnd) return;
            const cacheKey = this.getCacheKey('table', true);

            if (sessionStorage.getItem(cacheKey)) {
                const data = JSON.parse(sessionStorage.getItem(cacheKey));
                this.tableState.rows = data.table || [];
                return;
            }

            this.isTableLoading = true;
            try {
                const response = await fetch('/api/gsc/table', this.getFetchOptions(true));
                const data = await response.json();
                if (!data.error) {
                    this.safeCacheSet(cacheKey, JSON.stringify(data));
                    this.tableState.rows = data.table || [];
                    this.tableState.currentPage = 1;
                }
            } catch (error) {
                console.error('Error fetching table:', error);
            } finally {
                this.isTableLoading = false;
            }
        },

        getFetchOptions(includeFilters = true) {
            const payload = {
                tenant: this.tenantId,
                account: this.account,
                dateStart: this.dateStart,
                dateEnd: this.dateEnd,
                activeTab: this.activeTab
            };

            if (includeFilters && this.activeFilters) {
                const translated = { ...this.activeFilters };
                if (translated.countries && translated.countries.length > 0) {
                    translated.countries = translated.countries.map(name => COUNTRY_NAME_TO_CODE[name] || name);
                }
                payload.activeFilters = translated;
            }

            return {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payload)
            };
        },

        get variance() {
            const calc = (current, prev) => {
                if (!prev || Number(prev) === 0) return 0;
                return ((Number(current) - Number(prev)) / Number(prev)) * 100;
            };
            return {
                clicks: calc(this.summary.clicks, this.previous.clicks),
                impressions: calc(this.summary.impressions, this.previous.impressions),
                ctr: calc(this.summary.ctr, this.previous.ctr),
                position: calc(this.summary.position, this.previous.position)
            };
        },

        getVarianceClass(val, invert = false) {
            if (val === 0) return 'trend-neutral';
            const isPositive = val > 0;
            if (invert) return isPositive ? 'trend-down' : 'trend-up';
            return isPositive ? 'trend-up' : 'trend-down';
        },

        getVarianceIcon(val, invert = false) {
            if (val === 0) return '-';
            const isPositive = val > 0;
            if (invert) return isPositive ? '↓' : '↑';
            return isPositive ? '↑' : '↓';
        },

        formatVariance(val) {
            if (val === 0) return '0%';
            return Math.abs(val).toFixed(1) + '%';
        },

        toggleMetric(metric) {
            this.activeMetrics[metric] = !this.activeMetrics[metric];
            this.syncToUrl();
            this.updateChart();
        },

        initChart() {
            if (!this.$refs.canvas) return;
            const ctx = this.$refs.canvas.getContext('2d');

            const config = {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#FFF',
                            bodyColor: '#E2E8F0',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var value = context.parsed.y;
                                    if (label === 'CTR') {
                                        return label + ': ' + Number(value).toFixed(2) + '%';
                                    }
                                    if (label === 'Position') {
                                        return label + ': ' + Number(value).toFixed(2);
                                    }
                                    return label + ': ' + Number(value).toLocaleString('en-US');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'var(--gsc-chart-grid)', drawBorder: false },
                            ticks: { color: 'var(--gsc-chart-ticks)' }
                        },
                        yClicks: {
                            type: 'linear',
                            position: 'left',
                            display: false,
                            grid: { color: 'var(--gsc-chart-grid)', drawBorder: false },
                            ticks: { color: '#4285F4' },
                            min: 0,
                            suggestedMax: 5
                        },
                        yImpressions: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#7E57C2' },
                            min: 0,
                            suggestedMax: 5
                        },
                        yCtr: {
                            type: 'linear',
                            position: 'left',
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#0097A7', callback: (v) => Number(v).toFixed(2) + '%' },
                            min: 0,
                            suggestedMax: 5
                        },
                        yPosition: {
                            type: 'linear',
                            position: 'right',
                            reverse: true,
                            display: false,
                            grid: { drawOnChartArea: false, drawBorder: false },
                            ticks: { color: '#F4511E', callback: (v) => Number(v).toFixed(2) },
                            min: 1
                        }
                    }
                }
            };

            this.$refs.canvas._chartInstance = new Chart(ctx, config);
        },

        updateChart() {
            if (!this.$refs.canvas) return;
            let chart = this.$refs.canvas._chartInstance;
            if (!chart || !this.chartDataRaw) return;

            const startDate = dayjs(this.dateStart);
            const endDate = dayjs(this.dateEnd);
            const daysDiff = endDate.diff(startDate, 'day');

            const fullDateRange = [];
            for (let i = 0; i <= daysDiff; i++) {
                fullDateRange.push(startDate.add(i, 'day').format('YYYY-MM-DD'));
            }

            const dataByDate = {};
            this.chartDataRaw.forEach(r => {
                if (r && (r.daily || r.date || r.metric_date)) {
                    const dateStr = dayjs(r.daily || r.date || r.metric_date).format('YYYY-MM-DD');
                    dataByDate[dateStr] = r;
                }
            });

            const paddedData = fullDateRange.map(dateStr => {
                if (dataByDate[dateStr]) return dataByDate[dateStr];
                return {
                    daily: dateStr,
                    clicks: 0,
                    impressions: 0,
                    ctr: 0,
                    position: 0
                };
            });

            const labels = paddedData.map(r => dayjs(r.daily || r.date || r.metric_date).format('MMM D'));
            const datasets = [];

            const chartData = paddedData;

            if (this.activeMetrics.clicks) {
                datasets.push({
                    label: 'Clicks',
                    data: chartData.map(r => r.clicks),
                    borderColor: '#4285F4',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yClicks',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.impressions) {
                datasets.push({
                    label: 'Impressions',
                    data: chartData.map(r => r.impressions),
                    borderColor: '#7E57C2',
                    backgroundColor: 'rgba(126, 87, 194, 0.1)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    yAxisID: 'yImpressions',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.ctr) {
                datasets.push({
                    label: 'CTR',
                    data: chartData.map(r => r.ctr * 100),
                    borderColor: '#0097A7',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yCtr',
                    tension: 0.4
                });
            }

            if (this.activeMetrics.position) {
                datasets.push({
                    label: 'Position',
                    data: chartData.map(r => r.position === 0 ? null : r.position),
                    borderColor: '#F4511E',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: false,
                    yAxisID: 'yPosition',
                    tension: 0.4
                });
            }

            if (this.showTrends) {
                const metricsColors = {
                    clicks: '#4285F4',
                    impressions: '#7E57C2',
                    ctr: '#0097A7',
                    position: '#F4511E'
                };
                
                const metricsLabels = {
                    clicks: 'Clicks',
                    impressions: 'Impressions',
                    ctr: 'CTR',
                    position: 'Position'
                };

                ['clicks', 'impressions', 'ctr', 'position'].forEach(key => {
                    if (this.activeMetrics[key] && this.trendData[key]) {
                        const trendLinear = this.trendData[key].trend_linear || [];
                        const trendSma = this.trendData[key].trend_sma || [];
                        const scaleId = 'y' + key.charAt(0).toUpperCase() + key.slice(1);

                        if (trendLinear.length) {
                            datasets.push({
                                label: metricsLabels[key] + ' (Trend)',
                                data: fullDateRange.map(d => {
                                    const point = trendLinear.find(t => t.date === d);
                                    return point ? (key === 'ctr' ? point.value * 100 : point.value) : null;
                                }),
                                borderColor: metricsColors[key],
                                borderDash: [5, 5],
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: scaleId,
                                tension: 0.4
                            });
                        }

                        if (trendSma.length) {
                            datasets.push({
                                label: metricsLabels[key] + ' (SMA 28)',
                                data: fullDateRange.map(d => {
                                    const point = trendSma.find(t => t.date === d);
                                    return point ? (key === 'ctr' ? point.value * 100 : point.value) : null;
                                }),
                                borderColor: metricsColors[key],
                                borderDash: [2, 2],
                                borderWidth: 1,
                                pointRadius: 0,
                                fill: false,
                                yAxisID: scaleId,
                                tension: 0.4
                            });
                        }
                    }
                });
            }

            let gridDrawn = false;
            const cssGridColor = getComputedStyle(document.documentElement).getPropertyValue('--gsc-chart-grid').trim();
            const cssTicksColor = getComputedStyle(document.documentElement).getPropertyValue('--gsc-chart-ticks').trim();

            chart.options.scales.x.grid.color = cssGridColor;
            chart.options.scales.x.ticks.color = cssTicksColor;

            ['clicks', 'impressions', 'ctr', 'position'].forEach(m => {
                let scaleId = 'y' + m.charAt(0).toUpperCase() + m.slice(1);
                chart.options.scales[scaleId].display = this.activeMetrics[m];
                if (this.activeMetrics[m]) {
                    if (!gridDrawn) {
                        chart.options.scales[scaleId].grid.drawOnChartArea = true;
                        chart.options.scales[scaleId].grid.color = cssGridColor;
                        gridDrawn = true;
                    } else {
                        chart.options.scales[scaleId].grid.drawOnChartArea = false;
                    }
                }
            });

            chart.data.labels = labels;
            chart.data.datasets = datasets;
            chart.update();
        },

        get maxClicks() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => r.clicks));
        },

        get maxImpressions() {
            if (!this.tableState.sortedRows.length) return 1;
            return Math.max(...this.tableState.sortedRows.map(r => r.impressions));
        },

        formatNumber(num) {
            if (num === undefined || num === null) return '0';
            return new Intl.NumberFormat('en-US').format(num);
        },

        formatPercent(num) {
            if (num === undefined || num === null) return '0%';
            return (num * 100).toFixed(2) + '%';
        },

        formatDecimals(num) {
            if (num === undefined || num === null) return '0.00';
            return Number(num).toFixed(2);
        }
    }, 'google_search_console');
}

if (typeof window !== 'undefined') {
    window.gscDashboard = gscDashboard;
}
