<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page.
     */
    public function redirect(string $provider)
    {
        // We ensure we're targeting the current tenant's scope if possible
        return Socialite::driver($provider)
            ->with(['state' => 'tenant_' . Filament::getTenant()?->id])
            ->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function callback(string $provider, Request $request)
    {
        try {
            $user = Socialite::driver($provider)->user();
            $tenant = Filament::getTenant();

            if (!$tenant) {
                // Fallback: detect tenant from state if needed
                $state = $request->input('state');
                if (str_starts_with($state, 'tenant_')) {
                    $tenantId = str_replace('tenant_', '', $state);
                    $tenant = Project::find($tenantId);
                }
            }

            if (!$tenant) {
                return redirect()->route('filament.app.pages.sync-settings')->with('error', 'Tenant not identified.');
            }

            // Map and store the resulting tokens
            if ($provider === 'facebook') {
                $tenant->update([
                    'facebook_user_token' => $user->token,
                    // Optionally refresh other IDs if GBS needs them
                ]);
            } elseif ($provider === 'google') {
                $tenant->update([
                    'google_refresh_token' => $user->refreshToken,
                    // Google often only sends refresh_token on the first connection
                ]);
            }

            return redirect()->to('/app/' . $tenant->subdomain . '/sync-settings');

        } catch (\Exception $e) {
            Log::error("OAuth Callback Failed for {$provider}: " . $e->getMessage());
            return redirect()->to('/app')->with('error', 'Connection failed.');
        }
    }
}
