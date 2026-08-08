<?php

namespace App\Domain\ChannelProfiles\Profiles;

use App\Domain\ChannelProfiles\AbstractChannelProfile;

class FacebookOrganicProfile extends AbstractChannelProfile
{
    public function getChannelKey(): string
    {
        return 'facebook_organic';
    }

    public function getLabel(): string
    {
        return 'Facebook Organic';
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
                'cron_recent_hour' => $this->systemField('integer', 6),
                'cron_recent_minute' => $this->systemField('integer', 0),
                'max_workers' => $this->systemField('integer', 1),
                'granular_sync' => $this->systemField('boolean', true),
                
                'PAGE' => $this->systemField('object', [
                    'page_metrics' => true,
                    'posts' => false,
                    'post_metrics' => false,
                    'ig_accounts' => true,
                    'ig_account_metrics' => true,
                    'ig_account_media' => true,
                    'ig_account_media_metrics' => true
                ]),
                
                'pages' => $this->configurableField('array', [], null, [
                    'item_schema' => [
                        'id' => ['type' => 'string'],
                        'url' => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'hostname' => ['type' => 'string', 'default' => null],
                        'link' => ['type' => 'string'],
                        'enabled' => ['type' => 'boolean', 'default' => false],
                        'exclude_from_caching' => ['type' => 'boolean', 'default' => false],
                        'ig_account' => ['type' => 'string', 'default' => null],
                        'ig_account_name' => ['type' => 'string', 'default' => null],
                        'ig_accounts' => ['type' => 'boolean', 'default' => true],
                        'page_metrics' => ['type' => 'boolean', 'default' => true],
                        'posts' => ['type' => 'boolean', 'default' => false],
                        'post_metrics' => ['type' => 'boolean', 'default' => false],
                        'ig_account_metrics' => ['type' => 'boolean', 'default' => true],
                        'ig_account_media' => ['type' => 'boolean', 'default' => true],
                        'ig_account_media_metrics' => ['type' => 'boolean', 'default' => true],
                        'lost_access' => ['type' => 'boolean', 'default' => false],
                        'ig_hostname' => ['type' => 'string', 'default' => null],
                        'created_time' => ['type' => 'string', 'default' => null],
                        'ig_created_time' => ['type' => 'string', 'default' => null],
                        'data' => ['type' => 'object'],
                        'ig_data' => ['type' => 'object', 'default' => []]
                    ]
                ]),
            ],
        ];
    }
}
