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
    public function __construct(public string $provider, public ?string $type = null)
    {
        $tenant = Filament::getTenant();
        $this->isGlobal = !$tenant;

        $this->isConnected = match ($this->provider) {
            'facebook' => !empty($tenant?->facebook_user_token),
            'google' => !empty($tenant?->google_refresh_token),
            default => false,
        };

        $this->color = $this->isConnected ? 'success' : 'primary';

        $params = ['provider' => $this->provider];
        if ($this->type) {
            $params['type'] = $this->type;
        }

        if ($this->isGlobal) {
            $this->url = route('app.social-login', $params);
            $this->label = "Login with " . ucfirst($this->provider);
            $this->icon = 'heroicon-m-link';
        } else {
            $params['tenant'] = $tenant->id;
            $this->url = route('app.connect', $params);
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
