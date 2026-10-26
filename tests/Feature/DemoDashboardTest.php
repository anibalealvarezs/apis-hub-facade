<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoDashboardTest extends TestCase
{
    public function test_demo_page_loads_in_spanish()
    {
        $response = $this->get('/es/demo');
        $response->assertStatus(200);
        $response->assertSee('Demo Interactivo Beta');
        $response->assertSee('demo-asset-group');
        $response->assertSee('demo-grid-stack');
    }

    public function test_demo_page_loads_in_english()
    {
        $response = $this->get('/demo?lang=en');
        $response->assertStatus(200);
        $response->assertSee('APIs Hub');
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
        // Widget 109 is GA4 daily sessions
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
                'series' => ['dates', 'values'],
                'labels',
            ],
        ]);
    }
}
