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
            // Gráfico 2: Inversión en Ads vs Conversiones / Ventas Reales (GA4) — COMBO CHART
            [
                'id' => 110,
                'name' => 'Eficacia Publicitaria: Inversión Total vs Conversiones (GA4)',
                'grid_x' => 6, 'grid_y' => 4, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'combo_chart',
                'source_type' => 'cross_channel',
                'calculation_type' => 'series',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['spend', 'conversions'],
                    'granularity' => 'daily',
                ],
            ],

            // ─── BLOQUE 4: DISTRIBUCIÓN GLOBAL & ATRIBUCIÓN (FILA 4, h: 4) ───
            // Gráfico 3: Mix de Adquisición de Tráfico (Donut)
            [
                'id' => 111,
                'name' => 'Mix de Adquisición de Tráfico por Canal',
                'grid_x' => 0, 'grid_y' => 8, 'grid_w' => 4, 'grid_h' => 4,
                'widget_type' => 'pie_chart',
                'source_type' => 'cross_channel',
                'calculation_type' => 'breakdown',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'metrics' => ['traffic_share'],
                    'pie_style' => 'donut',
                ],
            ],
            // Gráfico 4: Distribución Geográfica Global GSC (Choropleth Map)
            [
                'id' => 113,
                'name' => 'Distribución Geográfica de Tráfico Orgánico (GSC)',
                'grid_x' => 4, 'grid_y' => 8, 'grid_w' => 8, 'grid_h' => 4,
                'widget_type' => 'choropleth_map',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'breakdown',
                'resolved_controls' => [
                    'channel' => 'google_search_console',
                    'metrics' => ['clicks', 'impressions', 'ctr', 'position'],
                    'granularity' => 'country',
                ],
            ],

            // ─── BLOQUE 5: DESGLOSE DIMENSIONAL Y RENDIMIENTO (FILA 5, h: 4) ───
            // Tabla 1: Páginas de Destino Más Efectivas (GSC - Dimensión URL)
            [
                'id' => 114,
                'name' => 'Páginas de Destino Más Efectivas (GSC)',
                'grid_x' => 0, 'grid_y' => 12, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'table',
                'source_type' => 'predefined_kpi',
                'calculation_type' => 'table',
                'resolved_controls' => [
                    'channel' => 'google_search_console',
                    'granularity' => 'dimensions.page',
                    'metrics' => ['clicks', 'ctr', 'impressions', 'position'],
                ],
            ],
            // Tabla 2: Top Campañas & Canales de Captación (Dimensión Campaña)
            [
                'id' => 112,
                'name' => 'Top Campañas & Canales de Adquisición',
                'grid_x' => 6, 'grid_y' => 12, 'grid_w' => 6, 'grid_h' => 4,
                'widget_type' => 'table',
                'source_type' => 'cross_channel',
                'calculation_type' => 'table',
                'resolved_controls' => [
                    'channel' => 'cross_channel',
                    'granularity' => 'campaign',
                ],
            ],
        ];
    }

    /**
     * Generate daily dates for series charts between start and end dates.
     */
    protected function getDates(?string $startDate = null, ?string $endDate = null): array
    {
        $end = $endDate ? strtotime($endDate) : time();
        $start = $startDate ? strtotime($startDate) : strtotime('-30 days', $end);

        // Sanity guards
        if ($start > $end) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }

        // Limit range between 1 and 365 days
        $days = (int) max(1, min(365, round(($end - $start) / 86400) + 1));

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days", $start));
        }

        return $dates;
    }

    /**
     * Return mock data for a given widget ID, asset group ('1', '2', '3'), and optional date range.
     */
    public function getWidgetData(int $widgetId, string $groupId, string $lang = 'es', ?string $dateStart = null, ?string $dateEnd = null): array
    {
        $dates = $this->getDates($dateStart, $dateEnd);
        $daysCount = count($dates);
        // Scaling factor relative to 30 days standard
        $dateScale = $daysCount / 30.0;
        $isEs = ($lang === 'es');

        switch ($widgetId) {
            // ─── 101: Inversión Publicitaria Total (Meta Ads + Google Ads) ───
            case 101:
                if ($groupId === '3') { // Argentina
                    return [
                        'success' => true,
                        'widget_type' => 'tile',
                        'data' => [
                            'value' => (int) round(11790000 * $dateScale),
                            'previous' => (int) round(10090000 * $dateScale),
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
                            'value' => (int) round(17070 * $dateScale),
                            'previous' => (int) round(15860 * $dateScale),
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
                            'value' => (int) round(26050 * $dateScale),
                            'previous' => (int) round(23660 * $dateScale),
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
                            'value' => (int) round(54820000 * $dateScale),
                            'previous' => (int) round(45300000 * $dateScale),
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
                            'value' => (int) round(74250 * $dateScale),
                            'previous' => (int) round(66890 * $dateScale),
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
                            'value' => (int) round(108420 * $dateScale),
                            'previous' => (int) round(95950 * $dateScale),
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
                $sessions = (int) round(($groupId === '3' ? 58900 : ($groupId === '2' ? 76400 : 124800)) * $dateScale);
                $prev = (int) round(($groupId === '3' ? 49100 : ($groupId === '2' ? 70100 : 112500)) * $dateScale);
                $pct = round((($sessions - $prev) / max(1, $prev)) * 100, 1);
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
                // Subscribers count is state-based, but grows with date scale slightly
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
                $reach = (int) round(($groupId === '3' ? 285400 : ($groupId === '2' ? 210800 : 412500)) * min(2.5, max(0.4, $dateScale)));
                $prevReach = (int) round(($groupId === '3' ? 241200 : ($groupId === '2' ? 194500 : 374000)) * min(2.5, max(0.4, $dateScale)));
                $pct = round((($reach - $prevReach) / max(1, $prevReach)) * 100, 1);
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
                                'label' => 'GSC — Clics Orgánicos',
                                'channel' => 'google_search_console',
                                'data' => $gscData,
                                'borderColor' => '#6366f1', // Indigo
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => '[Meta Ads] — Clics',
                                'channel' => 'meta_ads',
                                'data' => $metaData,
                                'borderColor' => '#3b82f6', // Blue
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => '[GAds] — Clics',
                                'channel' => 'google_ads',
                                'data' => $gadsData,
                                'borderColor' => '#f59e0b', // Amber
                                'backgroundColor' => 'transparent',
                                'tension' => 0.35,
                                'borderWidth' => 2,
                            ],
                            [
                                'label' => '[Mailchimp] — Clics Campañas',
                                'channel' => 'mailchimp',
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

            // ─── 110: Inversión en Ads vs Conversiones Web Reales (GA4) [Combo Chart] ───
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
                    'widget_type' => 'combo_chart',
                    'data' => [
                        'labels' => $dates,
                        'datasets' => [
                            [
                                'label' => $isEs ? '[Paid Media] — Inversión Total' : '[Paid Media] — Total Ad Spend',
                                'channel' => 'paid_media',
                                'data' => $spendValues,
                                'type' => 'bar',
                                'backgroundColor' => 'rgba(239, 68, 68, 0.65)',
                                'borderColor' => '#ef4444',
                                'borderWidth' => 1,
                                'borderRadius' => 4,
                                'yAxisID' => 'y',
                                'metric' => 'spend',
                            ],
                            [
                                'label' => $isEs ? '[Google Analytics] — Conversiones' : '[Google Analytics] — Conversions',
                                'channel' => 'google_analytics',
                                'data' => $convValues,
                                'type' => 'line',
                                'borderColor' => '#10b981',
                                'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                                'borderWidth' => 2.5,
                                'tension' => 0.35,
                                'fill' => false,
                                'yAxisID' => 'y1',
                                'metric' => 'conversions',
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'cross_channel',
                        'metrics' => ['spend', 'conversions'],
                        'granularity' => 'daily',
                        'combo_chart_config' => [
                            'left_axis' => 'spend',
                            'right_axis' => 'conversions',
                        ],
                    ],
                ];

            // ─── 111: Mix de Adquisición de Tráfico por Canal (Donut) ───
            case 111:
                return [
                    'success' => true,
                    'widget_type' => 'pie_chart',
                    'data' => [
                        'pie_style' => 'donut',
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
                    'controls' => [
                        'pie_style' => 'donut',
                    ],
                ];

            // ─── 113: Distribución Geográfica Global GSC (Choropleth Map) ───
            case 113:
                // Define geographic data tailored to asset group
                if ($groupId === '3') {
                    // Argentina focus with Latam distribution
                    $mapDataClicks = [
                        'AR' => 28420,
                        'CL' => 6410,
                        'UY' => 4820,
                        'BR' => 3120,
                        'MX' => 2950,
                        'CO' => 2180,
                        'PE' => 1840,
                        'US' => 1420,
                        'ES' => 1120,
                    ];
                    $mapDataImpressions = [
                        'AR' => 380400,
                        'CL' => 92100,
                        'UY' => 74300,
                        'BR' => 52100,
                        'MX' => 48900,
                        'CO' => 36200,
                        'PE' => 31400,
                        'US' => 28400,
                        'ES' => 22100,
                    ];
                    $mapDataCtr = [
                        'AR' => 7.47,
                        'CL' => 6.96,
                        'UY' => 6.49,
                        'BR' => 5.99,
                        'MX' => 6.03,
                        'CO' => 6.02,
                        'PE' => 5.86,
                        'US' => 5.00,
                        'ES' => 5.07,
                    ];
                    $mapDataPos = [
                        'AR' => 3.2,
                        'CL' => 4.8,
                        'UY' => 4.5,
                        'BR' => 7.2,
                        'MX' => 6.8,
                        'CO' => 7.1,
                        'PE' => 7.4,
                        'US' => 11.2,
                        'ES' => 10.4,
                    ];
                } elseif ($groupId === '2') {
                    // Europe focus
                    $mapDataClicks = [
                        'ES' => 18420,
                        'FR' => 14210,
                        'DE' => 11840,
                        'IT' => 9650,
                        'GB' => 8420,
                        'PT' => 6120,
                        'NL' => 4890,
                        'BE' => 3950,
                        'US' => 2410,
                    ];
                    $mapDataImpressions = [
                        'ES' => 264000,
                        'FR' => 218000,
                        'DE' => 189000,
                        'IT' => 152000,
                        'GB' => 134000,
                        'PT' => 98000,
                        'NL' => 79000,
                        'BE' => 64000,
                        'US' => 48000,
                    ];
                    $mapDataCtr = [
                        'ES' => 6.98,
                        'FR' => 6.52,
                        'DE' => 6.26,
                        'IT' => 6.35,
                        'GB' => 6.28,
                        'PT' => 6.24,
                        'NL' => 6.19,
                        'BE' => 6.17,
                        'US' => 5.02,
                    ];
                    $mapDataPos = [
                        'ES' => 3.8,
                        'FR' => 4.2,
                        'DE' => 4.6,
                        'IT' => 5.1,
                        'GB' => 5.4,
                        'PT' => 4.9,
                        'NL' => 5.8,
                        'BE' => 5.9,
                        'US' => 9.8,
                    ];
                } else {
                    // US / Global focus
                    $mapDataClicks = [
                        'US' => 34820,
                        'CA' => 8940,
                        'GB' => 7120,
                        'AU' => 5410,
                        'DE' => 4210,
                        'MX' => 3890,
                        'ES' => 3420,
                        'FR' => 2950,
                        'BR' => 2610,
                    ];
                    $mapDataImpressions = [
                        'US' => 482000,
                        'CA' => 124000,
                        'GB' => 102000,
                        'AU' => 78000,
                        'DE' => 61000,
                        'MX' => 56000,
                        'ES' => 49000,
                        'FR' => 42000,
                        'BR' => 38000,
                    ];
                    $mapDataCtr = [
                        'US' => 7.22,
                        'CA' => 7.21,
                        'GB' => 6.98,
                        'AU' => 6.94,
                        'DE' => 6.90,
                        'MX' => 6.95,
                        'ES' => 6.98,
                        'FR' => 7.02,
                        'BR' => 6.87,
                    ];
                    $mapDataPos = [
                        'US' => 3.4,
                        'CA' => 4.1,
                        'GB' => 4.5,
                        'AU' => 4.8,
                        'DE' => 5.9,
                        'MX' => 6.2,
                        'ES' => 6.5,
                        'FR' => 6.8,
                        'BR' => 7.2,
                    ];
                }

                $countryNames = [
                    'AR' => 'Argentina', 'CL' => 'Chile', 'UY' => 'Uruguay', 'BR' => 'Brazil',
                    'MX' => 'Mexico', 'CO' => 'Colombia', 'PE' => 'Peru', 'US' => 'United States',
                    'ES' => 'Spain', 'FR' => 'France', 'DE' => 'Germany', 'IT' => 'Italy',
                    'GB' => 'United Kingdom', 'PT' => 'Portugal', 'NL' => 'Netherlands',
                    'BE' => 'Belgium', 'CA' => 'Canada', 'AU' => 'Australia',
                ];

                $buildLeaderboard = function ($mapArr) use ($countryNames) {
                    $lb = [];
                    foreach ($mapArr as $c => $val) {
                        $lb[] = [
                            'code' => $c,
                            'name' => $countryNames[$c] ?? $c,
                            'value' => $val,
                        ];
                    }
                    usort($lb, fn($a, $b) => $b['value'] <=> $a['value']);
                    return $lb;
                };

                return [
                    'success' => true,
                    'widget_type' => 'choropleth_map',
                    'data' => [
                        'metric' => 'clicks',
                        'metric_label' => $isEs ? 'Clics Orgánicos' : 'Organic Clicks',
                        'color' => '#6366f1',
                        'map_data' => $mapDataClicks,
                        'leaderboard' => $buildLeaderboard($mapDataClicks),
                        'series_metrics' => [
                            [
                                'metric' => 'clicks',
                                'metric_label' => $isEs ? 'Clics Orgánicos' : 'Organic Clicks',
                                'color' => '#6366f1',
                                'map_data' => $mapDataClicks,
                                'leaderboard' => $buildLeaderboard($mapDataClicks),
                            ],
                            [
                                'metric' => 'impressions',
                                'metric_label' => $isEs ? 'Impresiones' : 'Impressions',
                                'color' => '#8b5cf6',
                                'map_data' => $mapDataImpressions,
                                'leaderboard' => $buildLeaderboard($mapDataImpressions),
                            ],
                            [
                                'metric' => 'ctr',
                                'metric_label' => $isEs ? 'CTR Promedio' : 'Average CTR',
                                'color' => '#0ea5e9',
                                'map_data' => $mapDataCtr,
                                'leaderboard' => $buildLeaderboard($mapDataCtr),
                            ],
                            [
                                'metric' => 'position',
                                'metric_label' => $isEs ? 'Posición Promedio' : 'Average Position',
                                'color' => '#10b981',
                                'map_data' => $mapDataPos,
                                'leaderboard' => $buildLeaderboard($mapDataPos),
                            ],
                        ],
                    ],
                    'controls' => [
                        'channel' => 'google_search_console',
                        'metrics' => ['clicks', 'impressions', 'ctr', 'position'],
                        'granularity' => 'country',
                    ],
                ];

            // ─── 112: Top Campañas & Canales de Adquisición (Tabla) ───
            case 112:
                $currencySymbol = $groupId === '3' ? '$ ' : ($groupId === '2' ? '€ ' : '$ ');
                $currencySuffix = $groupId === '3' ? ' ARS' : ($groupId === '2' ? ' EUR' : ' USD');

                $columns = [
                    ['key' => 'channel', 'label' => $isEs ? 'Canal' : 'Channel'],
                    ['key' => 'campaign', 'label' => $isEs ? 'Campaña / Fuente' : 'Campaign / Source'],
                    ['key' => 'clicks', 'label' => $isEs ? 'Clics / Visitas' : 'Clicks / Visits'],
                    ['key' => 'spend', 'label' => $isEs ? 'Inversión' : 'Spend'],
                    ['key' => 'cpa', 'label' => $isEs ? 'CPA / Costo' : 'CPA / Cost'],
                    ['key' => 'conversions', 'label' => $isEs ? 'Conversiones' : 'Conversions'],
                ];

                if ($groupId === '3') {
                    $raw = [
                        ['Meta Ads', 'Retargeting Catálogo Dinámico', '14,820', '$ 3,120,000', '$ 6,450', '484'],
                        ['Google Ads', 'Búsqueda PMax — Hogar & Deco', '9,410', '$ 2,840,000', '$ 8,900', '319'],
                        ['Google Search Console', 'Consultas Non-Branded (SEO)', '31,950', '$ 0.00', '$ 0.00 (Org)', '612'],
                        ['Mailchimp', 'Newsletter Semanal & Promociones', '4,890', '$ 0.00', '$ 0.00 (Email)', '278'],
                        ['Meta Ads', 'Prospección Lookalike 1% Compradores', '11,430', '$ 2,280,000', '$ 9,800', '232'],
                        ['Meta Orgánico', 'Reels Virales Instagram & FB', '7,210', '$ 0.00', '$ 0.00 (Social)', '145'],
                    ];
                } elseif ($groupId === '2') {
                    $raw = [
                        ['Meta Ads', 'EU Apparel Spring Collection', '9,840', '€ 4,820', '€ 18.40', '262'],
                        ['Google Ads', 'Google Shopping — Footwear EU', '6,920', '€ 3,950', '€ 22.10', '179'],
                        ['Google Search Console', 'Top Landing Pages (Organic SEO)', '26,840', '€ 0.00', '€ 0.00 (Org)', '428'],
                        ['Mailchimp', 'VIP Members Club Digest', '3,920', '€ 0.00', '€ 0.00 (Email)', '184'],
                        ['Meta Ads', 'Instagram Stories Conversions', '7,150', '€ 3,120', '€ 21.50', '145'],
                        ['Meta Orgánico', 'Colecciones & Lookbooks', '4,810', '€ 0.00', '€ 0.00 (Social)', '96'],
                    ];
                } else {
                    $raw = [
                        ['Meta Ads', 'US Top Sellers Advantage+', '16,920', '$ 7,850', '$ 24.20', '324'],
                        ['Google Ads', 'PMax Search & Shopping USA', '12,410', '$ 6,240', '$ 28.50', '219'],
                        ['Google Search Console', 'Organic Search Queries (SEO)', '42,310', '$ 0.00', '$ 0.00 (Org)', '715'],
                        ['Mailchimp', 'Weekly Flash Sale & VIP Club', '6,840', '$ 0.00', '$ 0.00 (Email)', '342'],
                        ['Meta Ads', 'Retargeting Carrito Abandonado', '8,450', '$ 3,820', '$ 19.80', '193'],
                        ['Meta Orgánico', 'Instagram Community & Posts', '9,120', '$ 0.00', '$ 0.00 (Social)', '168'],
                    ];
                }

                $rows = array_map(function ($r) {
                    return [
                        'channel' => $r[0],
                        'campaign' => $r[1],
                        'clicks' => $r[2],
                        'spend' => $r[3],
                        'cpa' => $r[4],
                        'conversions' => $r[5],
                    ];
                }, $raw);

                return [
                    'success' => true,
                    'widget_type' => 'table',
                    'data' => [
                        'columns' => $columns,
                        'rows' => $rows,
                    ],
                    'controls' => [
                        'block_first_col' => true,
                    ],
                ];

            // ─── 114: Páginas de Destino Más Efectivas (GSC - Dimensión URL) ───
            case 114:
                $columns = [
                    ['key' => 'page', 'label' => $isEs ? 'Página / URL' : 'Page / URL'],
                    ['key' => 'clicks', 'label' => $isEs ? 'Clics' : 'Clicks'],
                    ['key' => 'ctr', 'label' => 'CTR (%)'],
                    ['key' => 'impressions', 'label' => $isEs ? 'Impresiones' : 'Impressions'],
                    ['key' => 'position', 'label' => $isEs ? 'Posición Media' : 'Average Position'],
                ];

                if ($groupId === '3') {
                    // Argentina localized URLs
                    $rawPages = [
                        ['/', '18,450', '9.1%', '202,750', '3.1'],
                        ['/ar', '9,820', '8.4%', '116,900', '3.8'],
                        ['/ar/living/sofas-seccionales', '5,420', '12.8%', '42,340', '2.4'],
                        ['/ar/dormitorio/camas-sommiers', '4,150', '11.5%', '36,080', '2.9'],
                        ['/ar/deco/iluminacion-calida', '3,290', '10.2%', '32,250', '3.5'],
                        ['/ar/comedor/mesas-petiribi', '2,840', '14.1%', '20,140', '1.8'],
                        ['/ar/ofertas/hot-sale', '2,610', '16.5%', '15,810', '1.5'],
                        ['/ar/blog/tendencias-deco-2026', '1,980', '7.2%', '27,500', '4.2'],
                    ];
                } elseif ($groupId === '2') {
                    // Europe localized URLs
                    $rawPages = [
                        ['/', '21,340', '8.8%', '242,500', '3.4'],
                        ['/es/coleccion-primavera', '8,920', '13.2%', '67,570', '2.1'],
                        ['/eu/apparel/calzado-urbano', '6,450', '10.8%', '59,720', '2.8'],
                        ['/eu/apparel/chaquetas-eco', '4,890', '9.4%', '52,020', '3.6'],
                        ['/es/outlet/temporada-pasada', '3,780', '15.6%', '24,230', '1.7'],
                        ['/eu/running/zapatillas-pro', '3,120', '11.1%', '28,100', '2.9'],
                        ['/es/blog/guia-tallas-calzado', '2,450', '7.8%', '31,410', '4.5'],
                    ];
                } else {
                    // US / Global localized URLs (mirroring Marcela Rodriguez SEO structure)
                    $rawPages = [
                        ['/', '28,510', '9.6%', '297,000', '3.1'],
                        ['/en', '12,420', '8.2%', '151,460', '3.9'],
                        ['/en/categories/best-sellers', '7,840', '13.9%', '56,400', '2.1'],
                        ['/en/catalog/new-arrivals', '5,920', '11.4%', '51,920', '2.7'],
                        ['/en/shop/furniture-living', '4,890', '10.1%', '48,410', '3.3'],
                        ['/en/clearance/special-deals', '3,950', '17.2%', '22,960', '1.4'],
                        ['/en/blog/interior-design-tips', '2,840', '6.9%', '41,150', '4.8'],
                    ];
                }

                $pageRows = array_map(function ($p) {
                    return [
                        'page' => $p[0],
                        'clicks' => $p[1],
                        'ctr' => $p[2],
                        'impressions' => $p[3],
                        'position' => $p[4],
                    ];
                }, $rawPages);

                return [
                    'success' => true,
                    'widget_type' => 'table',
                    'data' => [
                        'columns' => $columns,
                        'rows' => $pageRows,
                    ],
                    'controls' => [
                        'block_first_col' => true,
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
