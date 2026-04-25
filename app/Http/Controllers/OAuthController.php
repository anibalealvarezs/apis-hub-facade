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

        $driver->with(['state' => implode(':', $stateParts)]);

        $config = config("services.{$provider}")['scopes'] ?? [];
        $scopes = $config['default'] ?? [];

        if ($type && isset($config[$type])) {
            $scopes = array_merge($scopes, $config[$type]);
        }

        if (!empty($scopes)) {
            $driver->scopes($scopes);
        }

        // For Google/GSC: ensure we get the refresh token
        if ($provider === 'google') {
            $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }

        return $driver->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function callback(Request $request, string $provider, $tenantId = null)
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            /** @var \Laravel\Socialite\Two\User $socialiteUser */
            $socialiteUser = $driver->stateless()->user();
            
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
                return redirect()->route('filament.app.auth.login')->with('error', 'Tenant not identified.');
            }

            $token = $socialiteUser->token;
            $refreshToken = $socialiteUser->refreshToken;

            // Calculate scopes based on type
            $config = config("services.{$provider}")['scopes'] ?? [];
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

            // Sync with Database (Merge scopes if already exists)
            $credential = $tenant->credentials()->where('provider', $provider)->first();
            $existingScopes = $credential?->scopes ?? [];
            $newScopes = array_values(array_unique(array_merge($existingScopes, $requestedScopes)));

            $credential = $tenant->credentials()->updateOrCreate(
                ['provider' => $provider],
                [
                    'token' => $token,
                    'refresh_token' => $refreshToken,
                    'external_user_id' => $socialiteUser->id,
                    'scopes' => $newScopes,
                    'expires_at' => property_exists($socialiteUser, 'expiresIn') ? now()->addSeconds($socialiteUser->expiresIn) : null,
                    'meta' => [
                        'name' => $socialiteUser->name,
                        'email' => $socialiteUser->email,
                        'avatar' => $socialiteUser->avatar,
                    ],
                ]
            );

            // Atomic Push to Remote Engine via SDK
            $nodeUrl = "https://{$tenant->subdomain}.apis-hub.cloud";
            $sdk = new ApisHubApi($nodeUrl, $tenant->remote_admin_api_key);
            $sdk->importCredentials($provider, $token, [
                'user_id' => $socialiteUser->id,
                'email' => $socialiteUser->email,
                'refresh_token' => $refreshToken,
                'scopes' => $newScopes,
            ]);

            return redirect()->route('filament.app.pages.sync-settings', ['tenant' => $tenant->subdomain])
                ->with('status', ucfirst($provider) . ' account connected and synchronized successfully.');

        } catch (\Exception $e) {
            Log::error("OAuth Callback Failed for {$provider}: " . $e->getMessage(), [
                'exception' => $e,
                'state_received' => $request->input('state'),
            ]);
            
            // Si tenemos tenant, volvemos a su panel. Si no, al login general.
            if (isset($tenant) && $tenant) {
                return redirect()->route('filament.app.pages.sync-settings', ['tenant' => $tenant->subdomain])
                    ->with('error', 'Authentication failed: ' . $e->getMessage());
            }

            return redirect()->route('filament.app.auth.login')->with('error', 'Authentication failed.');
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
                    $response = $deployer->injectSocialTokens($project, [
                        'facebook_user_token' => null,
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
