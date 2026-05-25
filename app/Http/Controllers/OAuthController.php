<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectCredential;
use App\Services\DeployerService;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Anibalealvarezs\FacebookGraphApi\FacebookGraphAuth;
use Anibalealvarezs\ApisHubApi\ApisHubApi;

/** @package App\Http\Controllers */
class OAuthController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page (Standard).
     */
    public function redirect(Request $request, string $provider)
    {
        return $this->performRedirect($request, $provider, null, $request->query('type'));
    }

    /**
     * Redirect the user to the Provider authentication page (With Explicit Tenant).
     */
    public function connect(Request $request, $tenant, string $provider)
    {
        return $this->performRedirect($request, $provider, $tenant, $request->query('type'));
    }

    /**
     * Internal redirection logic.
     */
    protected function performRedirect(Request $request, string $provider, $tenantId = null, ?string $type = null)
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        $tenantId = $tenantId ?: Filament::getTenant()?->id;
        $stateParts = ['tenant_' . (is_object($tenantId) ? $tenantId->id : $tenantId)];
        
        if ($type) {
            $stateParts[] = 'type_' . $type;
        }

        $customParameters = ['state' => implode(':', $stateParts)];

        $config = config("services.{$provider}")['channel_scopes'] ?? [];
        $scopes = $config['default'] ?? [];

        if ($type && isset($config[$type])) {
            $scopes = array_merge($scopes, $config[$type]);
        }

        if (!empty($scopes)) {
            $driver->scopes(\Illuminate\Support\Arr::flatten($scopes));
        }

        // For Google/GSC: ensure we get the refresh token and merge with custom state
        if ($provider === 'google') {
            $customParameters['access_type'] = 'offline';
            $customParameters['prompt'] = 'consent';
        }

        $driver->stateless()->with($customParameters);

        return $driver->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function callback(Request $request, string $provider, DeployerService $deployer, $tenantId = null)
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $state = $request->input('state', '');
            $stateData = [];
            foreach (explode(':', $state) as $part) {
                if (str_contains($part, '_')) {
                    list($key, $val) = explode('_', $part, 2);
                    $stateData[$key] = $val;
                }
            }

            $tenantId = $stateData['tenant'] ?? $tenantId;
            $type = $stateData['type'] ?? null;
            $tenant = Filament::getTenant();

            if (!$tenant && $tenantId) {
                $tenant = Project::find($tenantId);
            }

            if (!$tenant) {
                return redirect()->route('filament.app.auth.login')->with('error', 'Tenant not identified from state parameter.');
            }

            /** @var \Laravel\Socialite\Two\User $socialiteUser */
            $socialiteUser = $driver->stateless()->user();

            $token = $socialiteUser->token;
            $refreshToken = $socialiteUser->refreshToken;

            // Calculate scopes based on type
            $config = config("services.{$provider}")['channel_scopes'] ?? [];
            $requestedScopes = array_merge($config['default'] ?? [], $type ? ($config[$type] ?? []) : []);

            // --- Facebook Long-Lived Token Exchange ---
            if ($provider === 'facebook') {
                try {
                    $fbAuth = new FacebookGraphAuth();
                    $exchangeResponse = $fbAuth->getLongLivedUserAccessToken(
                        config('services.facebook')['client_id'] ?? config('services.facebook')['app_id'],
                        config('services.facebook')['client_secret'] ?? config('services.facebook')['app_secret'],
                        $token
                    );
                    
                    if (!empty($exchangeResponse['access_token'])) {
                        $token = $exchangeResponse['access_token'];
                    }
                    
                    $tenant->update(['facebook_user_id' => $socialiteUser->id]);
                } catch (\Exception $e) {
                    Log::warning("Facebook Token Exchange failed: " . $e->getMessage());
                }
            }

            // Sync with ChannelProfile Architecture
            $profile = \App\Models\ChannelProfile::where('user_id', $tenant->user_id)
                ->where('provider', $provider)
                ->where('provider_account_id', $socialiteUser->id)
                ->first();

            $updatePayload = [
                'name' => $socialiteUser->name,
                'email' => $socialiteUser->email,
                'access_token' => $token,
                'expires_at' => property_exists($socialiteUser, 'expiresIn') ? now()->addSeconds($socialiteUser->expiresIn) : null,
            ];

            if (!empty($refreshToken)) {
                $updatePayload['refresh_token'] = $refreshToken;
            } elseif ($profile && $profile->refresh_token) {
                $updatePayload['refresh_token'] = $profile->refresh_token;
            }

            if ($profile) {
                $profile->update($updatePayload);
            } else {
                $profile = \App\Models\ChannelProfile::create(array_merge([
                    'user_id' => $tenant->user_id,
                    'provider' => $provider,
                    'provider_account_id' => $socialiteUser->id,
                ], $updatePayload));
            }

            // Link profile to tenant
            $profileIdColumn = "{$provider}_profile_id";
            $tenant->update([$profileIdColumn => $profile->id]);

            // Atomic Push to Remote Engine via SDK
            $nodeUrl = "https://{$tenant->subdomain}.apis-hub.cloud";
            $sdk = new ApisHubApi($nodeUrl, $tenant->remote_admin_api_key);
            
            $finalRefreshToken = $profile->refresh_token;
            // Since ChannelProfile does not store scopes, we just pass the requested ones downstream
            $newScopes = $requestedScopes;

            $sdk->importCredentials($provider, $token, [
                'user_id' => $socialiteUser->id,
                'email' => $socialiteUser->email,
                'refresh_token' => $finalRefreshToken,
                'scopes' => $newScopes,
            ]);

            // Reactivate workers if they were paused for a safe update
            if (in_array($tenant->health_status, ['stopping_workers', 'ready_for_auth'])) {
                Log::info("Reactivating workers for project {$tenant->id} post OAuth update.");
                
                $deploymentName = 'apis-hub';
                $refreshCmd = "php bin/console app:refresh-instances";
                $startCmd = "docker compose -p {$deploymentName} up -d --build --remove-orphans worker";
                
                try {
                    $deployer->executeCommand($tenant, clone $tenant->server, $refreshCmd);
                    $deployer->executeCommand($tenant, clone $tenant->server, $startCmd);
                    $tenant->update(['health_status' => 'healthy']);
                    Log::info("Workers reactivated successfully for project {$tenant->id}.");
                } catch (\Exception $e) {
                    Log::error("Failed to reactivate workers for project {$tenant->id}: " . $e->getMessage());
                }
            }

            return redirect(url('/app/' . $tenant->subdomain . '/data-sources'))
                ->with('status', ucfirst($provider) . ' account connected and synchronized successfully.');

        } catch (\Exception $e) {
            Log::error("OAuth Callback Failed for {$provider}: " . $e->getMessage(), [
                'exception' => $e,
                'state_received' => $request->input('state'),
            ]);
            
            if (isset($tenant) && $tenant) {
                return redirect(url('/app/' . $tenant->subdomain . '/data-sources'))
                    ->with('error', 'Authentication failed: ' . $e->getMessage());
            }

            return redirect()->route('filament.app.auth.login')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle Facebook Data Deletion/Deauthorization Callback.
     */
    public function handleDeauthorize(Request $request, DeployerService $deployer)
    {
        $signedRequest = $request->input('signed_request');
        if (!$signedRequest) {
            return response()->json(['error' => 'Missing signed_request'], 400);
        }

        try {
            $data = $this->parseSignedRequest($signedRequest);
            $fbUserId = $data['user_id'] ?? null;

            if ($fbUserId) {
                // Find all projects linked to this Facebook User ID
                /** @var ProjectCredential|null $credential */
                $credentials = ProjectCredential::where('provider', 'facebook')
                    ->where('external_user_id', $fbUserId)
                    ->with('project')
                    ->get();

                foreach ($credentials as $credential) {
                    /** @var ProjectCredential $credential */
                    $project = $credential->project;
                    
                    // ATOMICITY: Try to wipe remote via API FIRST
                    // If this fails, we catch and bail to keep consistency
                    $response = $deployer->injectSocialTokens($project, "", "facebook", [
                        'facebook_user_id' => null,
                    ]);

                    if (($response['status'] ?? '') !== 'success') {
                        throw new \Exception("Could not wipe remote node for project {$project->id}. Status: " . ($response['status'] ?? 'unknown'));
                    }

                    // If remote wipe succeeded, delete from Facade
                    $credential->delete();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Facebook application deauthorized successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Facebook Deauthorize Failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Helper to parse and validate Facebook signed_request.
     */
    private function parseSignedRequest($signed_request)
    {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);
        $secret = config('services.facebook.client_secret');

        $sig = $this->base64UrlDecode($encoded_sig);
        $data = json_decode($this->base64UrlDecode($payload), true);

        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            throw new \Exception('Bad Signed JSON signature!');
        }

        return $data;
    }

    private function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
