<?php

namespace App\Domain\ChannelProfiles\Profiles;

use App\Domain\ChannelProfiles\AbstractChannelProfile;

class GoogleAnalyticsProfile extends AbstractChannelProfile
{
    public function getChannelKey(): string
    {
        return 'google_analytics';
    }

    public function getLabel(): string
    {
        return 'Google Analytics';
    }

    public function getSchemaDefinition(): array
    {
        return [
            'type' => $this->getChannelKey(),
            'fields' => [
                'enabled' => $this->configurableField('boolean', true),
                'cache_history_range' => $this->configurableField('string', '2 years', [
                    '7 days' => '7 Days',
                    '14 days' => '14 Days',
                    '30 days' => '30 Days',
                    '90 days' => '90 Days',
                    '18 months' => '18 Months',
                    '2 years' => '2 Years',
                ]),
                // Fixed system configuration variables - DO NOT let user edit
                'cron_recent_hour' => $this->systemField('integer', 10),
                'cron_recent_minute' => $this->systemField('integer', 0),
                'max_workers' => $this->systemField('integer', 3),
                'granular_sync' => $this->systemField('boolean', false),
                
                'feature_toggles' => $this->systemField('object', [
                    'cache_aggregations' => false,
                ]),
                
                'assets' => $this->configurableField('object', [], null, [
                    'schema' => [
                        'properties' => [
                            'type' => 'array',
                            'default' => [],
                            'item_schema' => [
                                'platformId' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'enabled' => ['type' => 'boolean', 'default' => false],
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
