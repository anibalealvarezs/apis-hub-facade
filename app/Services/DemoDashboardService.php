<?php

namespace App\Services;

class DemoDashboardService
{
    /**
     * Return list of 3 demo asset groups.
     */
    public function getAssetGroups(): array
    {
        return [
            '1' => __('Marca Principal (Global / EE.UU.)'),
            '2' => __('Línea Apparel & Calzado (Europa)'),
            '3' => __('Aura Hogar & Deco (Argentina)'),
        ];
    }

    /**
     * Asset Group to Channel Accounts mapping.
     */
    public function getChannelAssetGroupMap(): array
    {
        return [
            'meta_ads' => [
                '1' => ['act_global_us_101'],
                '2' => ['act_europe_eu_102'],
                '3' => ['act_argentina_ar_103'],
            ],
            'meta_organic' => [
                '1' => ['fb_page_global', 'ig_global'],
                '2' => ['fb_page_eu', 'ig_eu'],
                '3' => ['fb_page_ar', 'ig_ar'],
            ],
            'google_search_console' => [
                '1' => ['sc-domain:auraliving.com'],
                '2' => ['sc-domain:wear.auraliving.com'],
                '3' => ['sc-domain:ar.auraliving.com'],
            ],
            'google_ads' => [
                '1' => ['gads_global_us_201'],
                '2' => ['gads_europe_eu_202'],
                '3' => ['gads_argentina_ar_203'],
            ],
            'google_analytics' => [
                '1' => ['ga4_prop_global'],
                '2' => ['ga4_prop_eu'],
                '3' => ['ga4_prop_ar'],
            ],
            'mailchimp' => [
                '1' => ['mc_list_global'],
                '2' => ['mc_list_eu'],
                '3' => ['mc_list_ar'],
            ],
        ];
    }

    /**
     * Dashboard layout & widget definitions for GridStack.
     */
    public function getDashboardDefinition(): array
    {
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-30 days'));

        return [
            'name' => [
                'es' => 'Demo Interactivo — Rendimiento Omnicanal Unificado',
                'en' => 'Interactive Demo — Unified Omnichannel Performance',
            ],
            'description' => [
                'es' => 'Métricas normalizadas en tiempo real de 6 canales (Meta Ads, Meta Organic, GSC, Google Ads, GA4 y Mailchimp) en 3 grupos de activos.',
                'en' => 'Real-time normalized metrics across 6 channels (Meta Ads, Meta Organic, GSC, Google Ads, GA4, Mailchimp) for 3 asset groups.',
            ],
            'controls' => [
                'date_start' => $start,
                'date_end' => $today,
                'zero_handling' => 'remove',
                'show_asset_group_selector' => true,
                'asset_group' => ['1', '2', '3'],
            ],
            'widgets' => $this->getWidgetDefinitions(),
        ];
    }

    /**
     * Widget metadata matching GridStack coordinates and types.
     */
    public function getWidgetDefinitions(): array
    {
        return [
            // Row 1: KPI Tiles (Meta Ads, Google Ads, GA4, Mailchimp)
            [
                'id' => 101,
                'name' => 'Meta Ads • Inversión',
                'grid_x' => 0, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'meta_ads',
                    'metrics' => ['spend'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 102,
                'name' => 'Google Ads • Inversión',
                'grid_x' => 3, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'google_ads',
                    'metrics' => ['spend'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 103,
                'name' => 'Google Analytics • Usuarios',
                'grid_x' => 6, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'google_analytics',
                    'metrics' => ['active_users'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 104,
                'name' => 'Mailchimp • Suscriptores',
                'grid_x' => 9, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'mailchimp',
                    'metrics' => ['subscribers'],
                    'tile_style' => 'simple',
                ],
            ],

            // Row 2: Secondary Tiles (Meta Organic Reach, GSC Clicks, GSC Avg Position, Mailchimp Open Rate)
            [
                'id' => 105,
                'name' => 'Meta Orgánico • Alcance',
                'grid_x' => 0, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'meta_organic',
                    'metrics' => ['reach'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 106,
                'name' => 'Search Console • Clics',
                'grid_x' => 3, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'google_search_console',
                    'metrics' => ['clicks'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 107,
                'name' => 'Search Console • Posición',
                'grid_x' => 6, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_average',
                'resolved_controls' => [
                    'channel' => 'google_search_console',
                    'metrics' => ['position'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 108,
                'name' => 'Mailchimp • Tasa Apertura',
                'grid_x' => 9, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_average',
                'resolved_controls' => [
                    'channel' => 'mailchimp',
                    'metrics' => ['open_rate'],
                    'tile_style' => 'simple',
                ],
            ],

            // Row 3: Charts
            // Left Chart: GA4 Sesiones & Tráfico Diario (Line Chart)
            [
                'id' => 109,
                'name' => 'Google Analytics — Tendencia Diaria de Sesiones',
                'grid_x' => 0, 'grid_y' => 4, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'line_chart',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'google_analytics',
                    'metrics' => ['sessions'],
                    'granularity' => 'daily',
                ],
            ],
            // Right Chart: Meta Ads vs Google Ads Inversión Diaria (Line Chart)
            [
                'id' => 110,
                'name' => 'Paid Media — Comparativa Inversión Diaria (Meta vs Google)',
                'grid_x' => 6, 'grid_y' => 4, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'line_chart',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'meta_ads',
                    'metrics' => ['spend'],
                    'granularity' => 'daily',
                ],
            ],

            // Row 4: GSC & Organic Insights
            [
                'id' => 111,
                'name' => 'Search Console — Tendencia de Clics vs Impresiones',
                'grid_x' => 0, 'grid_y' => 8, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'bar_chart',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'google_search_console',
                    'metrics' => ['clicks'],
                    'granularity' => 'daily',
                ],
            ],
            [
                'id' => 112,
                'name' => 'Meta Orgánico — Tasa de Interacción (Engagement Rate)',
                'grid_x' => 6, 'grid_y' => 8, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'line_chart',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'meta_organic',
                    'metrics' => ['engagement_rate'],
                    'granularity' => 'daily',
                ],
            ],
        ];
    }

    /**
     * Generate 30 daily dates for series charts.
     */
    protected function getDates(): array
    {
        $dates = [];
        for ($i = 29; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-$i days"));
        }
        return $dates;
    }

    /**
     * Return mock data for a given widget ID and asset group ('1', '2', '3').
     */
    public function getWidgetData(int $widgetId, string $groupId, string $lang = 'es'): array
    {
        $dates = $this->getDates();
        $isEs = ($lang === 'es');

        switch ($widgetId) {
            case 101: // Meta Ads Spend & ROAS
                if ($groupId === '3') { // Argentina
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 6840000,
                            'previous' => 5770000,
                            'percentage' => 18.5,
                            'prefix' => '$ ',
                            'suffix' => ' ARS',
                            'label' => $isEs ? 'Inversión Total Meta Ads' : 'Total Meta Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } elseif ($groupId === '2') { // Europa
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 9420,
                            'previous' => 8710,
                            'percentage' => 8.1,
                            'prefix' => '€ ',
                            'suffix' => ' EUR',
                            'label' => $isEs ? 'Inversión Total Meta Ads' : 'Total Meta Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } else { // Global / US
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 14850,
                            'previous' => 13210,
                            'percentage' => 12.4,
                            'prefix' => '$ ',
                            'suffix' => ' USD',
                            'label' => $isEs ? 'Inversión Total Meta Ads' : 'Total Meta Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                }

            case 102: // Google Ads Spend
                if ($groupId === '3') { // Argentina
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 4950000,
                            'previous' => 4320000,
                            'percentage' => 14.6,
                            'prefix' => '$ ',
                            'suffix' => ' ARS',
                            'label' => $isEs ? 'Inversión Google Ads' : 'Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } elseif ($groupId === '2') { // Europa
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 7650,
                            'previous' => 7150,
                            'percentage' => 7.0,
                            'prefix' => '€ ',
                            'suffix' => ' EUR',
                            'label' => $isEs ? 'Inversión Google Ads' : 'Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } else { // Global / US
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 11200,
                            'previous' => 10450,
                            'percentage' => 7.2,
                            'prefix' => '$ ',
                            'suffix' => ' USD',
                            'label' => $isEs ? 'Inversión Google Ads' : 'Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                }

            case 103: // GA4 Active Users
                $users = $groupId === '3' ? 38400 : ($groupId === '2' ? 51200 : 79400);
                $prevUsers = $groupId === '3' ? 31200 : ($groupId === '2' ? 46800 : 71500);
                $pct = round((($users - $prevUsers) / $prevUsers) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $users,
                        'previous' => $prevUsers,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Usuarios Activos (GA4)' : 'Active Users (GA4)',
                        'format' => 'number',
                    ],
                ];

            case 104: // Mailchimp Subscribers
                $subs = $groupId === '3' ? 21150 : ($groupId === '2' ? 16300 : 28450);
                $prevSubs = $groupId === '3' ? 18970 : ($groupId === '2' ? 15410 : 27210);
                $pct = round((($subs - $prevSubs) / $prevSubs) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $subs,
                        'previous' => $prevSubs,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Audiencia Suscripta Mailchimp' : 'Total Mailchimp Audience',
                        'format' => 'number',
                    ],
                ];

            case 105: // Meta Organic Reach
                $reach = $groupId === '3' ? 142800 : ($groupId === '2' ? 96400 : 184200);
                $prevReach = $groupId === '3' ? 115100 : ($groupId === '2' ? 88000 : 161300);
                $pct = round((($reach - $prevReach) / $prevReach) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $reach,
                        'previous' => $prevReach,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Alcance Orgánico Total (FB + IG)' : 'Total Organic Reach (FB + IG)',
                        'format' => 'number',
                    ],
                ];

            case 106: // GSC Clicks
                $clicks = $groupId === '3' ? 31950 : ($groupId === '2' ? 26840 : 42310);
                $prevClicks = $groupId === '3' ? 24880 : ($groupId === '2' ? 23300 : 37840);
                $pct = round((($clicks - $prevClicks) / $prevClicks) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $clicks,
                        'previous' => $prevClicks,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Clics Orgánicos SEO (GSC)' : 'Organic SEO Clicks (GSC)',
                        'format' => 'number',
                    ],
                ];

            case 107: // GSC Position
                $pos = $groupId === '3' ? 6.2 : ($groupId === '2' ? 8.4 : 7.8);
                $prevPos = $groupId === '3' ? 8.3 : ($groupId === '2' ? 9.3 : 9.2);
                $diff = round($prevPos - $pos, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $pos,
                        'previous' => $prevPos,
                        'percentage' => $diff > 0 ? "+$diff" : "$diff",
                        'suffix' => ' pos',
                        'label' => $isEs ? 'Posición Media en Búsqueda (GSC)' : 'Average Search Position (GSC)',
                        'format' => 'number',
                    ],
                ];

            case 108: // Mailchimp Open Rate
                $rate = $groupId === '3' ? 46.1 : ($groupId === '2' ? 42.6 : 39.2);
                $prevRate = $groupId === '3' ? 41.5 : ($groupId === '2' ? 39.8 : 37.1);
                $pct = round((($rate - $prevRate) / $prevRate) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $rate,
                        'previous' => $prevRate,
                        'percentage' => $pct,
                        'suffix' => '%',
                        'label' => $isEs ? 'Tasa de Apertura de Correo' : 'Email Open Rate',
                        'format' => 'percentage',
                    ],
                ];

            case 109: // GA4 Daily Sessions Series
                $baseSessions = $groupId === '3' ? 1400 : ($groupId === '2' ? 1900 : 2800);
                $values = [];
                foreach ($dates as $idx => $d) {
                    $dayOfWeek = (int) date('N', strtotime($d));
                    $factor = ($dayOfWeek >= 6) ? 0.72 : 1.05 + sin($idx * 0.4) * 0.18;
                    $values[] = (int) round($baseSessions * $factor + ($idx * 15));
                }
                return [
                    'success' => true,
                    'widget_type' => 'line_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => $isEs ? 'Sesiones Diarias GA4' : 'Daily GA4 Sessions',
                                'data' => $values,
                                'borderColor' => '#10b981',
                                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                                'fill' => true,
                                'tension' => 0.35,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'google_analytics',
                        'metrics' => ['sessions'],
                        'granularity' => 'daily',
                    ],
                ];

            case 110: // Meta Ads vs Google Ads Inversión Diaria
                $metaBase = $groupId === '3' ? 220000 : ($groupId === '2' ? 310 : 490);
                $metaValues = [];
                foreach ($dates as $idx => $d) {
                    $metaValues[] = (int) round($metaBase * (0.9 + sin($idx * 0.3) * 0.25));
                }
                return [
                    'success' => true,
                    'widget_type' => 'line_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => $isEs ? 'Inversión Diaria Meta Ads' : 'Daily Meta Ads Spend',
                                'data' => $metaValues,
                                'borderColor' => '#3b82f6',
                                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                                'fill' => true,
                                'currency' => true,
                                'tension' => 0.35,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'meta_ads',
                        'metrics' => ['spend'],
                        'granularity' => 'daily',
                    ],
                ];

            case 111: // GSC Clicks Bar Chart
                $baseClicks = $groupId === '3' ? 1050 : ($groupId === '2' ? 890 : 1410);
                $clickValues = [];
                foreach ($dates as $idx => $d) {
                    $dayOfWeek = (int) date('N', strtotime($d));
                    $weekendDrop = ($dayOfWeek >= 6) ? 0.65 : 1.0;
                    $clickValues[] = (int) round(($baseClicks + ($idx * 10)) * $weekendDrop);
                }
                return [
                    'success' => true,
                    'widget_type' => 'bar_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => $isEs ? 'Clics Orgánicos Diarios' : 'Daily Organic Clicks',
                                'data' => $clickValues,
                                'backgroundColor' => '#6366f1',
                                'borderRadius' => 4,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'google_search_console',
                        'metrics' => ['clicks'],
                        'granularity' => 'daily',
                    ],
                ];

            case 112: // Meta Organic Engagement Rate Line Chart
                $baseEng = $groupId === '3' ? 6.4 : ($groupId === '2' ? 5.1 : 4.6);
                $engValues = [];
                foreach ($dates as $idx => $d) {
                    $engValues[] = round($baseEng + sin($idx * 0.5) * 0.8, 2);
                }
                return [
                    'success' => true,
                    'widget_type' => 'line_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => 'Engagement Rate (%)',
                                'data' => $engValues,
                                'borderColor' => '#0ea5e9',
                                'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                                'fill' => true,
                                'percentage' => true,
                                'tension' => 0.35,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'meta_organic',
                        'metrics' => ['engagement_rate'],
                        'granularity' => 'daily',
                    ],
                ];

            default:
                return [
                    'success' => false,
                    'error' => 'Widget not found',
                ];
        }
    }
}
