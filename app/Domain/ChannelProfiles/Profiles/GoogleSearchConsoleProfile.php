<?php

namespace App\Domain\ChannelProfiles\Profiles;

use App\Domain\ChannelProfiles\AbstractChannelProfile;

class GoogleSearchConsoleProfile extends AbstractChannelProfile
{
    public function getChannelKey(): string
    {
        return 'google_search_console';
    }

    public function getLabel(): string
    {
        return 'Google Search Console';
    }

    public function getSchemaDefinition(): array
    {
        return [
            'type' => $this->getChannelKey(),
            'fields' => [
                'enabled' => $this->configurableField('boolean', true),
                'cache_history_range' => $this->configurableField('string', '16 months', [
                    '1 month' => '1 Month',
                    '3 months' => '3 Months',
                    '6 months' => '6 Months',
                    '1 year' => '1 Year',
                    '16 months' => '16 Months (Max)',
                ]),
                // Fixed system configuration variables - DO NOT let user edit
                'cron_recent_hour' => $this->systemField('integer', 5),
                'cron_recent_minute' => $this->systemField('integer', 0),
                'max_workers' => $this->systemField('integer', 4),
                'granular_sync' => $this->systemField('boolean', true),
                
                'calculate_synthetics' => $this->systemField('boolean', true),
                
                'feature_toggles' => $this->systemField('object', [
                    'cache_aggregations' => true,
                ]),
                
                'assets' => $this->configurableField('object', [], null, [
                    'schema' => [
                        'sites' => [
                            'type' => 'array',
                            'default' => [],
                            'item_schema' => [
                                'url' => ['type' => 'string'],
                                'title' => ['type' => 'string'],
                                'hostname' => ['type' => 'string'],
                                'enabled' => ['type' => 'boolean', 'default' => false],
                                'target_countries' => ['type' => 'object', 'default' => []],
                                'target_keywords' => ['type' => 'object', 'default' => []],
                                'lost_access' => ['type' => 'boolean', 'default' => false],
                                'data' => ['type' => 'object']
                            ]
                        ]
                    ]
                ]),
            ],
        ];
    }
}
