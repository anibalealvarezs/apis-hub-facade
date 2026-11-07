<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Filament\App\Pages\CatalogEntity;
use App\Filament\App\Pages\DataSync;
use Tests\TestCase;

class CatalogAndTelemetryMetaTitleTest extends TestCase
{
    public function test_telemetry_title_without_percentage(): void
    {
        $page = new DataSync();
        $page->syncData = [];

        $this->assertSame('Sync Activity', $page->getTitle());
        $this->assertSame('Sync Activity', $page->getHeading());
    }

    public function test_telemetry_title_with_percentage(): void
    {
        $page = new DataSync();
        $page->syncData = ['completion_percentage' => 45.67];

        $this->assertSame('(45.7%) Sync Activity', $page->getTitle());
        $this->assertSame('Sync Activity', $page->getHeading());

        $page->syncData = ['completion_percentage' => 100];
        $this->assertSame('(100%) Sync Activity', $page->getTitle());

        $page->syncData = ['completion_percentage' => 0];
        $this->assertSame('(0%) Sync Activity', $page->getTitle());
    }

    public function test_catalog_entity_list_title_default(): void
    {
        $page = new CatalogEntity();
        $page->entity = 'campaigns';
        $page->channel = '';
        $page->scope = 'all';

        $this->assertSame('Campaigns', (string) $page->getTitle());
        $this->assertSame('Campaigns', (string) $page->getHeading());
    }

    public function test_catalog_entity_list_title_with_channel(): void
    {
        $page = new CatalogEntity();
        $page->entity = 'campaigns';
        $page->channel = 'facebook_marketing';
        $page->scope = 'all';

        $this->assertSame('Campaigns · Meta Ads', (string) $page->getTitle());
        $this->assertSame('Campaigns', (string) $page->getHeading());
    }

    public function test_catalog_entity_list_title_with_channel_and_scope(): void
    {
        $page = new CatalogEntity();
        $page->entity = 'campaigns';
        $page->channel = 'facebook_marketing';
        $page->scope = 'act_12345';
        $page->scopeOptions = [
            'all' => 'All accounts',
            'act_12345' => 'Main Ad Account',
        ];

        $this->assertSame('Campaigns · Meta Ads (Main Ad Account)', (string) $page->getTitle());
        $this->assertSame('Campaigns', (string) $page->getHeading());
    }

    public function test_catalog_entity_detail_title(): void
    {
        $page = new CatalogEntity();
        $page->entity = 'campaigns';
        $page->record = 'cmp_999';
        $page->profileData = [
            'record' => [
                'name' => 'Black Friday 2026',
                'channel' => 'facebook_marketing',
                'channel_label' => 'Meta Ads',
            ],
        ];

        $this->assertSame('Black Friday 2026 · Campaign (Meta Ads)', (string) $page->getTitle());
        $this->assertSame('Black Friday 2026', (string) $page->getHeading());
    }
}
