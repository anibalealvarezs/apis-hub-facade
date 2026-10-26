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
                'es' => 'Visión ejecutiva cross-channel en tiempo real: correlación de tráfico, atribución y eficiencia publicitaria en 6 canales integrados (Meta Ads, Meta Orgánico, Google Ads, GSC, GA4 y Mailchimp).',
                'en' => 'Real-time cross-channel executive overview: traffic correlation, attribution, and blended ad efficiency across 6 integrated channels (Meta Ads, Meta Organic, Google Ads, GSC, GA4, and Mailchimp).',
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
            // ─── BLOQUE 1: RESUMEN EJECUTIVO (FILA 1, h: 2) ───
            [
                'id' => 101,
                'name' => 'Inversión Publicitaria Total (Blended)',
                'grid_x' => 0, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'paid_media',
                    'metrics' => ['spend'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 102,
                'name' => 'Ingresos Atribuidos Totales',
                'grid_x' => 3, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'google_analytics',
                    'metrics' => ['revenue'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 103,
                'name' => 'Tráfico Unificado (Sesiones GA4)',
                'grid_x' => 6, 'grid_y' => 0, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'google_analytics',
                    'metrics' => ['sessions'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 104,
                'name' => 'Comunidad & Suscriptores (Mailchimp)',
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

            // ─── BLOQUE 2: EFICIENCIA Y DIAGNÓSTICO ESTRATÉGICO (FILA 2, h: 2) ───
            [
                'id' => 105,
                'name' => 'Cuota de Búsqueda Pagada (SEM vs SEO)',
                'grid_x' => 0, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_average',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['paid_search_click_share'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 106,
                'name' => 'ROAS Combinado (Blended Return)',
                'grid_x' => 3, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_average',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['purchase_roas'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 107,
                'name' => 'CPA Blended (Costo por Adquisición)',
                'grid_x' => 6, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_average',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['cost_per_acquisition'],
                    'tile_style' => 'simple',
                ],
            ],
            [
                'id' => 108,
                'name' => 'Alcance Social Total (Meta Ads + Orgánico)',
                'grid_x' => 9, 'grid_y' => 2, 'grid_w' => 3, 'grid_h' => 2,
                'widget_type' => 'tile',
                'source_type' => 'derived_metric',
                'calculation_type' => 'trend_total',
                'resolved_controls' => [
                    'channel' => 'meta_combined',
                    'metrics' => ['reach'],
                    'tile_style' => 'simple',
                ],
            ],

            // ─── BLOQUE 3: TRACCIÓN TEMPORAL CROSS-CHANNEL (FILA 3, h: 4) ───
            // Gráfico 1: Evolución Multi-Canal (Meta Ads vs Google Ads vs GSC vs Mailchimp)
            [
                'id' => 109,
                'name' => 'Evolución del Tráfico Multicanal (GSC vs Ads vs Social vs Email)',
                'grid_x' => 0, 'grid_y' => 4, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'line_chart',
                'source_type' => 'cross_channel',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['clicks'],
                    'granularity' => 'daily',
                ],
            ],
            // Gráfico 2: Inversión en Ads vs Conversiones / Ventas Reales (GA4)
            [
                'id' => 110,
                'name' => 'Eficacia Publicitaria: Inversión Total vs Conversiones (GA4)',
                'grid_x' => 6, 'grid_y' => 4, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'line_chart',
                'source_type' => 'cross_channel',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['spend_vs_conversions'],
                    'granularity' => 'daily',
                ],
            ],

            // ─── BLOQUE 4: ATRIBUCIÓN Y TOP RENDIMIENTO (FILA 4, h: 4) ───
            // Gráfico 3: Mix de Adquisición de Tráfico (Donut)
            [
                'id' => 111,
                'name' => 'Mix de Adquisición de Tráfico por Canal',
                'grid_x' => 0, 'grid_y' => 8, 'grid_w' => 4, 'grid_h' => 4,
                'widget_type' => 'donut',
                'source_type' => 'cross_channel',
                'calculation_type' => 'breakdown',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['traffic_share'],
                ],
            ],
            // Tabla 1: Top Campañas & Canales de Captación
            [
                'id' => 112,
                'name' => 'Top Campañas & Canales de Adquisición',
                'grid_x' => 4, 'grid_y' => 8, 'grid_w' => 8, 'grid_h' => 4,
                'widget_type' => 'table',
                'source_type' => 'cross_channel',
                'calculation_type' => 'table',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
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
            // ─── 101: Inversión Publicitaria Total (Meta Ads + Google Ads) ───
            case 101:
                if ($groupId === '3') { // Argentina
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 11790000,
                            'previous' => 10090000,
                            'percentage' => 16.8,
                            'prefix' => '$ ',
                            'suffix' => ' ARS',
                            'label' => $isEs ? 'Meta Ads + Google Ads combinados' : 'Combined Meta & Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } elseif ($groupId === '2') { // Europa
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 17070,
                            'previous' => 15860,
                            'percentage' => 7.6,
                            'prefix' => '€ ',
                            'suffix' => ' EUR',
                            'label' => $isEs ? 'Meta Ads + Google Ads combinados' : 'Combined Meta & Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                } else { // Global / US
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 26050,
                            'previous' => 23660,
                            'percentage' => 10.1,
                            'prefix' => '$ ',
                            'suffix' => ' USD',
                            'label' => $isEs ? 'Meta Ads + Google Ads combinados' : 'Combined Meta & Google Ads Spend',
                            'format' => 'currency',
                        ],
                    ];
                }

            // ─── 102: Ingresos Atribuidos Totales ───
            case 102:
                if ($groupId === '3') {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 54820000,
                            'previous' => 45300000,
                            'percentage' => 21.0,
                            'prefix' => '$ ',
                            'suffix' => ' ARS',
                            'label' => $isEs ? 'Ingresos Totales (GA4 Omnicanal)' : 'Total Omnichannel Revenue',
                            'format' => 'currency',
                        ],
                    ];
                } elseif ($groupId === '2') {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 74250,
                            'previous' => 66890,
                            'percentage' => 11.0,
                            'prefix' => '€ ',
                            'suffix' => ' EUR',
                            'label' => $isEs ? 'Ingresos Totales (GA4 Omnicanal)' : 'Total Omnichannel Revenue',
                            'format' => 'currency',
                        ],
                    ];
                } else {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 108420,
                            'previous' => 95950,
                            'percentage' => 13.0,
                            'prefix' => '$ ',
                            'suffix' => ' USD',
                            'label' => $isEs ? 'Ingresos Totales (GA4 Omnicanal)' : 'Total Omnichannel Revenue',
                            'format' => 'currency',
                        ],
                    ];
                }

            // ─── 103: Tráfico Unificado (Sesiones GA4) ───
            case 103:
                $sessions = $groupId === '3' ? 58900 : ($groupId === '2' ? 76400 : 124800);
                $prev = $groupId === '3' ? 49100 : ($groupId === '2' ? 70100 : 112500);
                $pct = round((($sessions - $prev) / $prev) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $sessions,
                        'previous' => $prev,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Sesiones Totales en la Web' : 'Total Web Sessions',
                        'format' => 'number',
                    ],
                ];

            // ─── 104: Comunidad & Suscriptores (Mailchimp) ───
            case 104:
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
                        'label' => $isEs ? 'Audiencia Activa en Lista' : 'Active Email Subscribers',
                        'format' => 'number',
                    ],
                ];

            // ─── 105: Cuota de Búsqueda Pagada (SEM vs SEO) ───
            case 105:
                // google_ads.clicks / (google_ads.clicks + gsc.clicks)
                $share = $groupId === '3' ? 24.2 : ($groupId === '2' ? 29.5 : 27.8);
                $prevShare = $groupId === '3' ? 26.8 : ($groupId === '2' ? 31.2 : 29.4);
                $diff = round($share - $prevShare, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $share,
                        'previous' => $prevShare,
                        'percentage' => $diff,
                        'suffix' => '%',
                        'label' => $isEs ? 'Tráfico Google dependiente de pago' : 'Paid Search vs Organic Share',
                        'format' => 'percentage',
                    ],
                ];

            // ─── 106: ROAS Combinado (Blended Return on Ad Spend) ───
            case 106:
                $roas = $groupId === '3' ? 4.65 : ($groupId === '2' ? 4.35 : 4.16);
                $prevRoas = $groupId === '3' ? 4.49 : ($groupId === '2' ? 4.21 : 4.05);
                $diff = round($roas - $prevRoas, 2);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $roas,
                        'previous' => $prevRoas,
                        'percentage' => $diff > 0 ? "+$diff" : "$diff",
                        'suffix' => 'x',
                        'label' => $isEs ? 'Retorno por cada $ invertido' : 'Revenue per ad dollar spent',
                        'format' => 'number',
                    ],
                ];

            // ─── 107: CPA Blended (Costo por Adquisición) ───
            case 107:
                if ($groupId === '3') {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 8420,
                            'previous' => 9180,
                            'percentage' => -8.3,
                            'prefix' => '$ ',
                            'suffix' => ' ARS',
                            'label' => $isEs ? 'Costo medio por conversión' : 'Blended Cost per Acquisition',
                            'format' => 'currency',
                        ],
                    ];
                } elseif ($groupId === '2') {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 24.50,
                            'previous' => 26.80,
                            'percentage' => -8.6,
                            'prefix' => '€ ',
                            'suffix' => ' EUR',
                            'label' => $isEs ? 'Costo medio por conversión' : 'Blended Cost per Acquisition',
                            'format' => 'currency',
                        ],
                    ];
                } else {
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => 31.80,
                            'previous' => 34.20,
                            'percentage' => -7.0,
                            'prefix' => '$ ',
                            'suffix' => ' USD',
                            'label' => $isEs ? 'Costo medio por conversión' : 'Blended Cost per Acquisition',
                            'format' => 'currency',
                        ],
                    ];
                }

            // ─── 108: Alcance Social Total (Meta Ads + Orgánico) ───
            case 108:
                $reach = $groupId === '3' ? 285400 : ($groupId === '2' ? 210800 : 412500);
                $prevReach = $groupId === '3' ? 241200 : ($groupId === '2' ? 194500 : 374000);
                $pct = round((($reach - $prevReach) / $prevReach) * 100, 1);
                return [
                    'success' => true,
                    'widget_type' => 'tile',
                    'data' => [
                        'value' => $reach,
                        'previous' => $prevReach,
                        'percentage' => $pct,
                        'label' => $isEs ? 'Personas únicas impactadas' : 'Total Unified Social Reach',
                        'format' => 'number',
                    ],
                ];

            // ─── 109: Evolución del Tráfico Multicanal (GSC vs Ads vs Social vs Email) ───
            case 109:
                $multiplier = $groupId === '3' ? 0.75 : ($groupId === '2' ? 0.65 : 1.0);

                $gscData = [];
                $metaData = [];
                $gadsData = [];
                $emailData = [];

                foreach ($dates as $idx => $d) {
                    $dayOfWeek = (int) date('N', strtotime($d));
                    $isWeekend = ($dayOfWeek >= 6);

                    // GSC organic: weekday peak
                    $baseGsc = 1400 * $multiplier;
                    $gscFactor = $isWeekend ? 0.65 : 1.05 + sin($idx * 0.35) * 0.15;
                    $gscData[] = (int) round($baseGsc * $gscFactor);

                    // Meta Ads: higher on weekends & promo days
                    $baseMeta = 850 * $multiplier;
                    $metaFactor = $isWeekend ? 1.25 : 0.95 + cos($idx * 0.4) * 0.2;
                    $metaData[] = (int) round($baseMeta * $metaFactor);

                    // Google Ads: steady search demand
                    $baseGads = 520 * $multiplier;
                    $gadsData[] = (int) round($baseGads * (0.9 + sin($idx * 0.25) * 0.15));

                    // Mailchimp: big spikes on newsletter broadcast days (every ~7 days)
                    $isBroadcastDay = ($idx % 7 === 2);
                    $baseEmail = $isBroadcastDay ? (950 * $multiplier) : (90 * $multiplier + ($idx % 5) * 15);
                    $emailData[] = (int) round($baseEmail);
                }

                return [
                    'success' => true,
                    'widget_type' => 'line_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => 'Google Search Console (SEO)',
                                'data' => $gscData,
                                'borderColor' => '#6366f1', // Indigo
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => 'Meta Ads (Paid Social)',
                                'data' => $metaData,
                                'borderColor' => '#3b82f6', // Blue
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => 'Google Ads (Paid Search)',
                                'data' => $gadsData,
                                'borderColor' => '#f59e0b', // Amber
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => 'Mailchimp (Email Marketing)',
                                'data' => $emailData,
                                'borderColor' => '#10b981', // Emerald
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'cross_channel',
                        'metrics' => ['clicks'],
                        'granularity' => 'daily',
                    ],
                ];

            // ─── 110: Inversión en Ads vs Conversiones Web Reales (GA4) ───
            case 110:
                $spendBase = $groupId === '3' ? 390000 : ($groupId === '2' ? 570 : 860);
                $convBase = $groupId === '3' ? 46 : ($groupId === '2' ? 24 : 35);

                $spendValues = [];
                $convValues = [];

                foreach ($dates as $idx => $d) {
                    $dayOfWeek = (int) date('N', strtotime($d));
                    $spendFactor = (0.9 + sin($idx * 0.3) * 0.22);
                    $dailySpend = (int) round($spendBase * $spendFactor);
                    $spendValues[] = $dailySpend;

                    // Conversions correlate with spend + seasonal bump
                    $convFactor = $spendFactor * (0.95 + cos($idx * 0.2) * 0.15);
                    $convValues[] = (int) round($convBase * $convFactor);
                }

                return [
                    'success' => true,
                    'widget_type' => 'line_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => $isEs ? 'Inversión Publicitaria Diaria (USD/EUR/ARS)' : 'Daily Ad Spend',
                                'data' => $spendValues,
                                'borderColor' => '#ef4444', // Red
                                'backgroundColor' => 'rgba(239, 68, 68, 0.08)',
                                'fill' => true,
                                'yAxisID' => 'y',
                                'tension' => 0.3,
                            ],
                            [
                                'label' => $isEs ? 'Conversiones Registradas en GA4' : 'GA4 Conversions',
                                'data' => $convValues,
                                'borderColor' => '#10b981', // Green
                                'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                                'fill' => true,
                                'yAxisID' => 'y1',
                                'tension' => 0.3,
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'cross_channel',
                        'metrics' => ['spend', 'conversions'],
                        'granularity' => 'daily',
                    ],
                ];

            // ─── 111: Mix de Adquisición de Tráfico por Canal (Donut) ───
            case 111:
                return [
                    'success' => true,
                    'widget_type' => 'donut',
                    'data' => [
                        'labels' => [
                            $isEs ? 'Búsqueda Orgánica (GSC)' : 'Organic Search (GSC)',
                            $isEs ? 'Paid Social (Meta Ads)' : 'Paid Social (Meta Ads)',
                            $isEs ? 'Paid Search (Google Ads)' : 'Paid Search (Google Ads)',
                            $isEs ? 'Email Marketing (Mailchimp)' : 'Email Marketing (Mailchimp)',
                            $isEs ? 'Meta Orgánico (FB & IG)' : 'Meta Organic (FB & IG)',
                        ],
                        'datasets' => [
                            [
                                'data' => [42, 26, 17, 10, 5],
                                'backgroundColor' => [
                                    '#6366f1', // GSC: Indigo
                                    '#3b82f6', // Meta Ads: Blue
                                    '#f59e0b', // Google Ads: Amber
                                    '#10b981', // Mailchimp: Emerald
                                    '#0ea5e9', // Meta Organic: Sky
                                ],
                                'borderWidth' => 2,
                                'borderColor' => '#1f2937',
                            ],
                        ],
                    ],
                ];

            // ─── 112: Top Campañas & Canales de Adquisición (Tabla) ───
            case 112:
                $currencySymbol = $groupId === '3' ? '$ ' : ($groupId === '2' ? '€ ' : '$ ');
                $currencySuffix = $groupId === '3' ? ' ARS' : ($groupId === '2' ? ' EUR' : ' USD');

                if ($groupId === '3') {
                    $rows = [
                        ['Meta Ads', 'Retargeting Catálogo Dinámico', '14,820', '$ 3,120,000', '$ 6,450', '484'],
                        ['Google Ads', 'Búsqueda PMax — Hogar & Deco', '9,410', '$ 2,840,000', '$ 8,900', '319'],
                        ['Google Search Console', 'Consultas Non-Branded (SEO)', '31,950', '$ 0.00', '$ 0.00 (Org)', '612'],
                        ['Mailchimp', 'Newsletter Semanal & Promociones', '4,890', '$ 0.00', '$ 0.00 (Email)', '278'],
                        ['Meta Ads', 'Prospección Lookalike 1% Compradores', '11,430', '$ 2,280,000', '$ 9,800', '232'],
                        ['Meta Orgánico', 'Reels Virales Instagram & FB', '7,210', '$ 0.00', '$ 0.00 (Social)', '145'],
                    ];
                } elseif ($groupId === '2') {
                    $rows = [
                        ['Meta Ads', 'EU Apparel Spring Collection', '9,840', '€ 4,820', '€ 18.40', '262'],
                        ['Google Ads', 'Google Shopping — Footwear EU', '6,920', '€ 3,950', '€ 22.10', '179'],
                        ['Google Search Console', 'Top Landing Pages (Organic SEO)', '26,840', '€ 0.00', '€ 0.00 (Org)', '428'],
                        ['Mailchimp', 'VIP Members Club Digest', '3,920', '€ 0.00', '€ 0.00 (Email)', '184'],
                        ['Meta Ads', 'Instagram Stories Conversions', '7,150', '€ 3,120', '€ 21.50', '145'],
                        ['Meta Orgánico', 'Colecciones & Lookbooks', '4,810', '€ 0.00', '€ 0.00 (Social)', '96'],
                    ];
                } else {
                    $rows = [
                        ['Meta Ads', 'US Top Sellers Advantage+', '16,920', '$ 7,850', '$ 24.20', '324'],
                        ['Google Ads', 'PMax Search & Shopping USA', '12,410', '$ 6,240', '$ 28.50', '219'],
                        ['Google Search Console', 'Organic Search Queries (SEO)', '42,310', '$ 0.00', '$ 0.00 (Org)', '715'],
                        ['Mailchimp', 'Weekly Flash Sale & VIP Club', '6,840', '$ 0.00', '$ 0.00 (Email)', '342'],
                        ['Meta Ads', 'Retargeting Carrito Abandonado', '8,450', '$ 3,820', '$ 19.80', '193'],
                        ['Meta Orgánico', 'Instagram Community & Posts', '9,120', '$ 0.00', '$ 0.00 (Social)', '168'],
                    ];
                }

                return [
                    'success' => true,
                    'widget_type' => 'table',
                    'data' => [
                        'columns' => [
                            $isEs ? 'Canal' : 'Channel',
                            $isEs ? 'Campaña / Fuente' : 'Campaign / Source',
                            $isEs ? 'Clics / Visitas' : 'Clicks / Visits',
                            $isEs ? 'Inversión' : 'Spend',
                            $isEs ? 'CPA / Costo' : 'CPA / Cost',
                            $isEs ? 'Conversiones' : 'Conversions',
                        ],
                        'rows' => $rows,
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
