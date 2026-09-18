<?php

namespace App\Services\Analytics;

class ChannelBreakdownRegistry
{
    /**
     * Get available breakdown and filtering dimensions for a given channel and optional dependency/scope.
     *
     * @param string $channel
     * @param string|null $dependency
     * @return array<string, array{label: string, type: string, operators: array<string>}>
     */
    public static function getBreakdownsForChannel(string $channel, ?string $dependency = null): array
    {
        $defaultOperators = ['in', 'not_in', 'eq', 'neq', 'like', 'not_like', 'is_null', 'is_not_null'];

        return match ($channel) {
            'facebook_marketing' => [
                'channeledCampaign' => [
                    'label' => __('Campaign'),
                    'type' => 'relational',
                    'operators' => $defaultOperators,
                ],
                'adGroup' => [
                    'label' => __('Ad Set'),
                    'type' => 'relational',
                    'operators' => $defaultOperators,
                ],
                'ad' => [
                    'label' => __('Ad'),
                    'type' => 'relational',
                    'operators' => $defaultOperators,
                ],
                'dimensions.age' => [
                    'label' => __('Age Bracket'),
                    'type' => 'dimension',
                    'operators' => $defaultOperators,
                ],
                'dimensions.gender' => [
                    'label' => __('Gender'),
                    'type' => 'dimension',
                    'operators' => $defaultOperators,
                ],
            ],

            'google_analytics' => self::getGa4Breakdowns($dependency, $defaultOperators),
            'google_search_console' => self::getGscBreakdowns($dependency, $defaultOperators),
            'facebook_organic' => self::getFacebookOrganicBreakdowns($dependency, $defaultOperators),

            'shopify' => [
                'dimensions.order_type' => [
                    'label' => __('Order Type'),
                    'type' => 'dimension',
                    'operators' => $defaultOperators,
                ],
                'dimensions.gateway' => [
                    'label' => __('Payment Gateway'),
                    'type' => 'dimension',
                    'operators' => $defaultOperators,
                ],
            ],

            default => [],
        };
    }

    private static function getGscBreakdowns(?string $dependency, array $defaultOperators): array
    {
        $dep = $dependency ?? 'non-searchAppearance';

        if ($dep === 'searchAppearance' || $dep === 'search_appearance') {
            return [
                'dimensions.searchAppearance' => [
                    'label' => __('Search Appearance'),
                    'type' => 'dimension',
                    'operators' => $defaultOperators,
                ],
            ];
        }

        return [
            'query' => [
                'label' => __('Query / Keyword'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.page' => [
                'label' => __('Landing Page'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'country' => [
                'label' => __('Country'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'device' => [
                'label' => __('Device'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
        ];
    }

    private static function getGa4Breakdowns(?string $dependency, array $defaultOperators): array
    {
        $dep = $dependency ?? 'traffic_matrix';

        $traffic = [
            'channeledCampaign' => [
                'label' => __('Campaign'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'channeledAdGroup' => [
                'label' => __('Ad Group'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'dimensions.sessionDefaultChannelGroup' => [
                'label' => __('Default Channel Group'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.sessionSourceMedium' => [
                'label' => __('Source / Medium'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.landing_page' => [
                'label' => __('Landing Page'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'country' => [
                'label' => __('Country'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'device' => [
                'label' => __('Device'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
        ];

        $acquisition = [
            'channeledCampaign' => [
                'label' => __('Campaign (First User)'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'channeledAdGroup' => [
                'label' => __('Ad Group (First User)'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'dimensions.firstUserDefaultChannelGroup' => [
                'label' => __('Default Channel Group (First User)'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.firstUserSourceMedium' => [
                'label' => __('Source / Medium (First User)'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
        ];

        $event = [
            'event' => [
                'label' => __('Event Name'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
        ];

        $touchpoint = [
            'channeledCampaign' => [
                'label' => __('Campaign (Touchpoint)'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'channeledAdGroup' => [
                'label' => __('Ad Group (Touchpoint)'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
            'channeledAd' => [
                'label' => __('Ad (Touchpoint)'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
        ];

        return match ($dep) {
            'acquisition_matrix' => $acquisition,
            'event_matrix' => $event,
            'ad_touchpoint_matrix' => $touchpoint,
            default => $traffic,
        };
    }

    private static function getFacebookOrganicBreakdowns(?string $dependency, array $defaultOperators): array
    {
        $instagramBreakdowns = [
            'dimensions.contact_button_type' => [
                'label' => __('Contact Button Type'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.follow_type' => [
                'label' => __('Follow Type'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'dimensions.media_product_type' => [
                'label' => __('Media Product Type'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'post' => [
                'label' => __('Post / Media ID'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
        ];

        $facebookPageBreakdowns = [
            'dimensions.reaction_type' => [
                'label' => __('Reaction Type'),
                'type' => 'dimension',
                'operators' => $defaultOperators,
            ],
            'post' => [
                'label' => __('Post / Media ID'),
                'type' => 'relational',
                'operators' => $defaultOperators,
            ],
        ];

        if ($dependency === 'instagram_account' || $dependency === 'ig_post' || $dependency === 'instagram') {
            return $instagramBreakdowns;
        }

        if ($dependency === 'facebook_page' || $dependency === 'fb_pages' || $dependency === 'facebook') {
            return $facebookPageBreakdowns;
        }

        // If no dependency specified or combined, merge them gracefully
        return array_merge($instagramBreakdowns, $facebookPageBreakdowns);
    }

    /**
     * Check if a dimension key is valid for a given channel.
     */
    public static function isValidDimension(string $channel, string $dimension, ?string $dependency = null): bool
    {
        $breakdowns = self::getBreakdownsForChannel($channel, $dependency);
        return isset($breakdowns[$dimension]);
    }

    /**
     * Get label for a dimension.
     */
    public static function getDimensionLabel(string $channel, string $dimension, ?string $dependency = null): string
    {
        $breakdowns = self::getBreakdownsForChannel($channel, $dependency);
        return $breakdowns[$dimension]['label'] ?? $dimension;
    }
}
