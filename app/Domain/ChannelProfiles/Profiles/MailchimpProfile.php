<?php

namespace App\Domain\ChannelProfiles\Profiles;

use App\Domain\ChannelProfiles\AbstractChannelProfile;

class MailchimpProfile extends AbstractChannelProfile
{
    public function getChannelKey(): string
    {
        return 'mailchimp';
    }

    public function getLabel(): string
    {
        return 'Mailchimp';
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
                'cron_recent_hour' => $this->systemField('integer', 4),
                'cron_recent_minute' => $this->systemField('integer', 0),
                'max_workers' => $this->systemField('integer', 2),
                'granular_sync' => $this->systemField('boolean', true),

                'metrics_strategy' => $this->systemField('string', 'default'),
                'metrics_config' => $this->systemField('object', []),

                'feature_toggles' => $this->systemField('object', [
                    'cache_aggregations' => false,
                ]),

                'assets' => $this->configurableField('object', [], null, [
                    'schema' => [
                        'audiences' => [
                            'type' => 'array',
                            'default' => [],
                            'item_schema' => [
                                'id' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'account_id' => ['type' => 'string'],
                                'enabled' => ['type' => 'boolean', 'default' => false],
                                'lost_access' => ['type' => 'boolean', 'default' => false],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ]),
            ],
        ];
    }
}
