<?php

namespace App\Services\Analytics;

use Illuminate\Support\Str;

final class EntityAttributeLabels
{
    private const FIELDS = [
        'billingevent' => 'Billing Event', 'budget' => 'Budget', 'buyingtype' => 'Buying Type',
        'campaignid' => 'Campaign ID', 'canonicalid' => 'Canonical ID', 'code' => 'Code',
        'createdat' => 'Created At', 'creativeid' => 'Creative ID', 'email' => 'Email',
        'enabled' => 'Enabled', 'hash' => 'Identifier', 'hostname' => 'Domain', 'id' => 'ID',
        'lat' => 'Latitude', 'lng' => 'Longitude', 'name' => 'Name', 'objective' => 'Objective',
        'optimizationgoal' => 'Optimization Goal', 'orderid' => 'Order ID', 'platformid' => 'Platform ID',
        'postid' => 'Post ID', 'productid' => 'Product ID', 'query' => 'Search Query', 'sku' => 'SKU',
        'status' => 'Status', 'storecode' => 'Store Code', 'title' => 'Title', 'type' => 'Type',
        'updatedat' => 'Updated At', 'url' => 'URL', 'zipcode' => 'Postal Code',
    ];

    public static function decorate(array $catalog, string $channel): array
    {
        $relations = ['channeledCampaign' => 'Campaign', 'campaign' => 'Campaign',
            'channeledAdGroup' => $channel === 'facebook_marketing' ? 'Ad Set' : 'Ad Group',
            'channeledAd' => 'Ad', 'channeledAccount' => $channel === 'mailchimp' ? 'Audience' : 'Account',
            'channeledEvent' => 'Event'];
        foreach ($catalog as &$field) {
            $name = (string)($field['field'] ?? '');
            $relation = (string)($field['relation'] ?? '');
            $field['display_label'] = __(self::FIELDS[strtolower(str_replace('_', '', $name))] ?? self::humanize($name));
            $field['label'] = __($relations[$relation] ?? self::humanize(preg_replace('/^channeled(?=[A-Z_])/', '', $relation))).' / '.$field['display_label'];
        }
        unset($field);
        return $catalog;
    }

    private static function humanize(string $name): string
    {
        $words = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $name);
        $words = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $words);
        $words = Str::title(str_replace('_', ' ', $words));
        return preg_replace_callback('/\b(Id|Url|Sku|Iso)\b/', static fn ($match) => strtoupper($match[0]), $words);
    }
}
