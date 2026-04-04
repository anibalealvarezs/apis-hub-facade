<?php

namespace App\View\Components;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OAuthButtons extends Component
{
    public string $label;
    public string $url;
    public string $icon;
    public string $color;
    public bool $isConnected;
    public bool $isGlobal;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $provider)
    {
        $tenant = Filament::getTenant();
        $this->isGlobal = !$tenant;

        $this->isConnected = match ($this->provider) {
            'facebook' => !empty($tenant?->facebook_user_token),
            'google' => !empty($tenant?->google_refresh_token),
            default => false,
        };

        $this->color = $this->isConnected ? 'success' : 'primary';

        if ($this->isGlobal) {
            $this->url = route('app.social-login', ['provider' => $this->provider]);
            $this->label = "Login with " . ucfirst($this->provider);
            $this->icon = 'heroicon-m-link';
        } else {
            $this->url = route('app.connect', ['tenant' => $tenant->id, 'provider' => $this->provider]);
            $this->label = $this->isConnected ? 'Account Connected' : 'Connect Account';
            $this->icon = $this->isConnected ? 'heroicon-m-check-circle' : 'heroicon-m-plus';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('filament.app.components.oauth-buttons');
    }
}
