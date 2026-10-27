<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoDashboardTest extends TestCase
{
    public function test_demo_page_loads_in_spanish()
    {
        $response = $this->get('/es/demo');
        $response->assertStatus(200);
        $response->assertSee('APIs Hub');
        $response->assertSee('demo-asset-group');
        $response->assertSee('demo-grid-stack');
        $response->assertSee('Dashboard Omnicanal');
    }

    public function test_demo_page_loads_in_english()
    {
        $response = $this->get('/demo?lang=en');
        $response->assertStatus(200);
        $response->assertSee('APIs Hub');
        $response->assertSee('Omnichannel Dashboard');
    }

    public function test_demo_data_sources_page_loads_in_spanish_and_english()
    {
        $es = $this->get('/es/demo/data-sources');
        $es->assertStatus(200);
        $es->assertSee('Configuración de Fuentes de Datos');
        $es->assertSee('Google Search Console');
        $es->assertSee('Uso del Tier');

        $en = $this->get('/demo/data-sources?lang=en');
        $en->assertStatus(200);
        $en->assertSee('APIs Hub');
        $en->assertSee('Data Sources Configuration');
        $en->assertSee('Google Search Console');
        $en->assertSee('Tier Usage');
    }

    public function test_demo_asset_groups_page_loads_in_spanish_and_english()
    {
        $es = $this->get('/es/demo/asset-groups');
        $es->assertStatus(200);
        $es->assertSee('Grupos de Activos');
        $es->assertSee('Marca Principal (Global / EE.UU.)');
        $es->assertSee('Línea Apparel & Calzado (Europa)');

        $en = $this->get('/demo/asset-groups?lang=en');
        $en->assertStatus(200);
        $en->assertSee('APIs Hub');
        $en->assertSee('Asset Groups');
        $en->assertSee('Main Brand (Global / US)');
        $en->assertSee('Apparel & Footwear Line (Europe)');
    }

    public function test_demo_intelligence_page_loads_in_spanish_and_english()
    {
        $es = $this->get('/es/demo/intelligence');
        $es->assertStatus(200);
        $es->assertSee('Data Intelligence');
        $es->assertSee('Alerta de CPA Blended Elevado');
        $es->assertSee('Model Context Protocol');

        $en = $this->get('/demo/intelligence?lang=en');
        $en->assertStatus(200);
        $en->assertSee('Data Intelligence');
        $en->assertSee('High Blended CPA Warning');
        $en->assertSee('summarize_performance');
    }

    public function test_demo_collaboration_page_loads_in_spanish_and_english()
    {
        $es = $this->get('/es/demo/collaboration');
        $es->assertStatus(200);
        $es->assertSee('Colaboración en Equipo');
        $es->assertSee('Sophia Vance');
        $es->assertSee('Propietario del Proyecto');
        $es->assertSee('project_owner');

        $en = $this->get('/demo/collaboration?lang=en');
        $en->assertStatus(200);
        $en->assertSee('Team Collaboration');
        $en->assertSee('Sophia Vance');
        $en->assertSee('Project Owner');
        $en->assertSee('project_owner');
    }

    public function test_demo_widget_data_for_argentina()
    {
        // Widget 101 is Meta Ads Spend
        $response = $this->postJson('/demo/api/widget/101/data', [
            'controls' => [
                'asset_group' => '3', // Argentina
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'tile',
            'data' => [
                'suffix' => ' ARS',
            ],
        ]);
    }

    public function test_demo_widget_data_for_europe()
    {
        $response = $this->postJson('/demo/api/widget/101/data', [
            'controls' => [
                'asset_group' => '2', // Europe
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'tile',
            'data' => [
                'suffix' => ' EUR',
            ],
        ]);
    }

    public function test_demo_chart_data_sessions()
    {
        // Widget 109 is Multi-channel traffic line chart
        $response = $this->postJson('/demo/api/widget/109/data', [
            'controls' => [
                'asset_group' => '1',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'widget_type',
            'data' => [
                'datasets',
                'labels',
            ],
        ]);
    }

    public function test_demo_donut_traffic_share()
    {
        // Widget 111 is traffic share donut (pie_chart with donut style)
        $response = $this->postJson('/demo/api/widget/111/data', [
            'controls' => [
                'asset_group' => '1',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'pie_chart',
        ]);
    }

    public function test_demo_choropleth_map()
    {
        // Widget 113 is GSC Global Choropleth Map
        $response = $this->postJson('/demo/api/widget/113/data', [
            'controls' => [
                'asset_group' => '3', // Argentina
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'choropleth_map',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'metric',
                'map_data',
                'leaderboard',
                'series_metrics',
            ],
        ]);
    }

    public function test_demo_table_gsc_landing_pages()
    {
        // Widget 114 is GSC Landing Pages table
        $response = $this->postJson('/demo/api/widget/114/data', [
            'controls' => [
                'asset_group' => '3', // Argentina
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'table',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'columns',
                'rows',
            ],
        ]);
    }

    public function test_demo_table_top_campaigns()
    {
        // Widget 112 is top campaigns table
        $response = $this->postJson('/demo/api/widget/112/data', [
            'controls' => [
                'asset_group' => '3', // Argentina
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'widget_type' => 'table',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'columns',
                'rows',
            ],
        ]);
    }
}
