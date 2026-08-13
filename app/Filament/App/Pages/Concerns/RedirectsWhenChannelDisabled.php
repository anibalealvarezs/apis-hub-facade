<?php

namespace App\Filament\App\Pages\Concerns;

use App\Filament\App\Pages\JointDashboard;
use Filament\Facades\Filament;

trait RedirectsWhenChannelDisabled
{
    abstract protected static function getChannelConfigKey(): string;

    public function mountCanAuthorizeAccess(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->can('view_data')) {
            abort(403);
            return;
        }

        if (! static::isChannelEnabled()) {
            $this->redirect(static::getChannelDisabledRedirectUrl());
        }
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        $this->mountCanAuthorizeAccess();
    }

    protected static function isChannelEnabled(): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return false;
        }

        $config = $tenant->sync_config ?? [];

        return ! empty($config[static::getChannelConfigKey()]['enabled']);
    }

    protected static function getChannelDisabledRedirectUrl(): string
    {
        return JointDashboard::getUrl();
    }
}
