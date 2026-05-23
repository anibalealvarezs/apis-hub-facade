<?php

namespace Database\Seeders;

use App\Models\ApisHubRelease;
use Illuminate\Database\Seeder;

class ApisHubReleasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schemaV1 = [
            'google_search_console' => [
                'type' => 'google_search_console',
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'default' => true],
                    'cache_history_range' => ['type' => 'string', 'default' => '16 months'],
                    'cron_recent_hour' => ['type' => 'integer', 'default' => 5],
                    'cron_recent_minute' => ['type' => 'integer', 'default' => 0],
                    'max_workers' => ['type' => 'integer', 'default' => 4],
                    'granular_sync' => ['type' => 'boolean', 'default' => true],
                    'feature_toggles' => ['type' => 'object', 'default' => [
                        'calculate_synthetics' => true,
                        'cache_aggregations' => true,
                    ]],
                    'assets' => ['type' => 'object', 'default' => [], 'schema' => [
                        'sites' => [
                            'type' => 'array',
                            'default' => [],
                            'item_schema' => [
                                'url' => ['type' => 'string'],
                                'title' => ['type' => 'string'],
                                'hostname' => ['type' => 'string'],
                                'enabled' => ['type' => 'boolean', 'default' => true],
                                'target_countries' => ['type' => 'object', 'default' => []],
                                'target_keywords' => ['type' => 'object', 'default' => []],
                                'lost_access' => ['type' => 'boolean', 'default' => false],
                                'data' => ['type' => 'object']
                            ]
                        ]
                    ]]
                ]
            ],
            'facebook_marketing' => [
                'type' => 'facebook_marketing',
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'default' => true],
                    'cache_history_range' => ['type' => 'string', 'default' => '2 years'],
                    'cron_entities_hour' => ['type' => 'integer', 'default' => 2],
                    'cron_entities_minute' => ['type' => 'integer', 'default' => 0],
                    'cron_recent_hour' => ['type' => 'integer', 'default' => 5],
                    'cron_recent_minute' => ['type' => 'integer', 'default' => 0],
                    'granular_sync' => ['type' => 'boolean', 'default' => true],
                    'max_workers' => ['type' => 'integer', 'default' => 2],
                    'metrics_strategy' => ['type' => 'string', 'default' => 'default'],
                    'metrics_config' => ['type' => 'object', 'default' => []],
                    'CAMPAIGN' => ['type' => 'object', 'default' => ['cache_include' => '']],
                    'ADSET' => ['type' => 'object', 'default' => ['cache_include' => '']],
                    'AD' => ['type' => 'object', 'default' => ['cache_include' => '']],
                    'CREATIVE' => ['type' => 'object', 'default' => ['cache_include' => '']],
                    'AD_ACCOUNT' => ['type' => 'object', 'default' => [
                        'ad_account_metrics' => false,
                        'campaigns' => true,
                        'campaign_metrics' => false,
                        'adsets' => true,
                        'adset_metrics' => false,
                        'ads' => true,
                        'ad_metrics' => true,
                        'creatives' => false,
                        'creative_metrics' => false
                    ]],
                    'ad_accounts' => [
                        'type' => 'array', 
                        'default' => [],
                        'item_schema' => [
                            'id' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'enabled' => ['type' => 'boolean', 'default' => true],
                            'exclude_from_caching' => ['type' => 'boolean', 'default' => false],
                            'lost_access' => ['type' => 'boolean', 'default' => false],
                            'hostname' => ['type' => 'string', 'default' => null],
                            'created_time' => ['type' => 'string'],
                            'data' => ['type' => 'object']
                        ]
                    ]
                ]
            ],
            'facebook_organic' => [
                'type' => 'facebook_organic',
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'default' => true],
                    'cache_history_range' => ['type' => 'string', 'default' => '2 years'],
                    'cron_entities_hour' => ['type' => 'integer', 'default' => 2],
                    'cron_entities_minute' => ['type' => 'integer', 'default' => 0],
                    'cron_recent_hour' => ['type' => 'integer', 'default' => 6],
                    'cron_recent_minute' => ['type' => 'integer', 'default' => 0],
                    'granular_sync' => ['type' => 'boolean', 'default' => true],
                    'max_workers' => ['type' => 'integer', 'default' => 1],
                    'PAGE' => ['type' => 'object', 'default' => [
                        'page_metrics' => true,
                        'posts' => true,
                        'post_metrics' => true,
                        'ig_accounts' => true,
                        'ig_account_metrics' => true,
                        'ig_account_media' => true,
                        'ig_account_media_metrics' => true
                    ]],
                    'pages' => [
                        'type' => 'array', 
                        'default' => [],
                        'item_schema' => [
                            'id' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'hostname' => ['type' => 'string', 'default' => null],
                            'link' => ['type' => 'string'],
                            'enabled' => ['type' => 'boolean', 'default' => true],
                            'exclude_from_caching' => ['type' => 'boolean', 'default' => false],
                            'ig_account' => ['type' => 'string', 'default' => null],
                            'ig_account_name' => ['type' => 'string', 'default' => null],
                            'ig_accounts' => ['type' => 'boolean', 'default' => false],
                            'page_metrics' => ['type' => 'boolean', 'default' => true],
                            'posts' => ['type' => 'boolean', 'default' => true],
                            'post_metrics' => ['type' => 'boolean', 'default' => true],
                            'ig_account_metrics' => ['type' => 'boolean', 'default' => false],
                            'ig_account_media' => ['type' => 'boolean', 'default' => false],
                            'ig_account_media_metrics' => ['type' => 'boolean', 'default' => false],
                            'lost_access' => ['type' => 'boolean', 'default' => false],
                            'ig_hostname' => ['type' => 'string', 'default' => null],
                            'created_time' => ['type' => 'string', 'default' => null],
                            'ig_created_time' => ['type' => 'string', 'default' => null],
                            'data' => ['type' => 'object'],
                            'ig_data' => ['type' => 'object', 'default' => []]
                        ]
                    ]
                ]
            ]
        ];

        ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.13.2.6'], // Rama o tag estable actual
            [
                'is_active' => true,
                'supported_channels' => array_keys($schemaV1),
                'config_schemas' => $schemaV1,
            ]
        );
        
        $this->command->info('ApisHubReleases seeded successfully.');
    }
}
