<?php

namespace App\Domain\ChannelProfiles\Contracts;

interface ChannelProfileInterface
{
    /**
     * Get the unique key identifying this channel profile.
     */
    public function getChannelKey(): string;

    /**
     * Get the human-readable label for this channel.
     */
    public function getLabel(): string;

    /**
     * Get the configuration schema definition for this channel.
     * This defines both user-editable options and fixed system values.
     */
    public function getSchemaDefinition(): array;

    /**
     * Determine if this channel profile is actively connected for a project.
     */
    public function isConnected(\App\Models\Project $project): bool;
}
