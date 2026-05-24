<?php

namespace App\Domain\ChannelProfiles\Profiles;

use App\Domain\ChannelProfiles\AbstractChannelProfile;

class FacebookMarketingProfile extends AbstractChannelProfile
{
    public function getChannelKey(): string
    {
        return 'facebook_marketing';
    }

    public function getLabel(): string
    {
        return 'Facebook Marketing';
    }

    public function getSchemaDefinition(): array
    {
        return [
            'type' => $this->getChannelKey(),
            'fields' => [
                'enabled' => $this->configurableField('boolean', true),
                'cache_history_range' => $this->configurableField('string', '2 years', [
                    '1 month' => '1 Month',
                    '3 months' => '3 Months',
                    '6 months' => '6 Months',
                    '1 year' => '1 Year',
                    '2 years' => '2 Years',
                ]),
                // Fixed system configuration variables - DO NOT let user edit
                'cron_entities_hour' => $this->systemField('integer', 2),
                'cron_entities_minute' => $this->systemField('integer', 0),
                'cron_recent_hour' => $this->systemField('integer', 5),
                'cron_recent_minute' => $this->systemField('integer', 0),
                'max_workers' => $this->systemField('integer', 2),
                'granular_sync' => $this->systemField('boolean', true),
                
                'metrics_strategy' => $this->systemField('string', 'default'),
                'metrics_config' => $this->systemField('object', []),
                'CAMPAIGN' => $this->systemField('object', ['cache_include' => '']),
                'ADSET' => $this->systemField('object', ['cache_include' => '']),
                'AD' => $this->systemField('object', ['cache_include' => '']),
                
                'assets' => $this->configurableField('object', [], null, [
                    'schema' => [
                        'ad_accounts' => [
                            'type' => 'array',
                            'default' => [],
                            'item_schema' => [
                                'id' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'enabled' => ['type' => 'boolean', 'default' => true],
                                'lost_access' => ['type' => 'boolean', 'default' => false],
                            ]
                        ]
                    ]
                ]),
            ],
        ];
    }
}
