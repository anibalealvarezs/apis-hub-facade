<?php

namespace App\Domain\ChannelProfiles;

use App\Domain\ChannelProfiles\Contracts\ChannelProfileInterface;

class ChannelProfileRegistry
{
    /** @var ChannelProfileInterface[] */
    protected array $profiles = [];

    /**
     * Register a new channel profile.
     */
    public function register(ChannelProfileInterface $profile): void
    {
        $this->profiles[$profile->getChannelKey()] = $profile;
    }

    /**
     * Get a specific channel profile.
     */
    public function get(string $key): ?ChannelProfileInterface
    {
        return $this->profiles[$key] ?? null;
    }

    /**
     * Get all registered profiles.
     *
     * @return ChannelProfileInterface[]
     */
    public function all(): array
    {
        return $this->profiles;
    }
}
