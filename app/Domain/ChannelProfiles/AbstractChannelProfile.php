<?php

namespace App\Domain\ChannelProfiles;

use App\Domain\ChannelProfiles\Contracts\ChannelProfileInterface;

abstract class AbstractChannelProfile implements ChannelProfileInterface
{
    /**
     * Helper to define a user-configurable schema field.
     */
    protected function configurableField(string $type, $default, array $options = null, array $extra = []): array
    {
        $field = [
            'type' => $type,
            'default' => $default,
            'user_configurable' => true,
        ];

        if ($options !== null) {
            $field['options'] = $options;
        }

        return array_merge($field, $extra);
    }

    /**
     * Helper to define a fixed system field that the user cannot edit.
     */
    protected function systemField(string $type, $default, array $extra = []): array
    {
        return array_merge([
            'type' => $type,
            'default' => $default,
            'user_configurable' => false,
        ], $extra);
    }
}
