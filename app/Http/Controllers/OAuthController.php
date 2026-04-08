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

/** @package App\Http\Controllers */
class OAuthController extends Controller
{
    /**
     * Redirect the user to the Provider authentication page.
     */
    public function redirect($tenantId, string $provider)
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        $driver->with(['state' => 'tenant_' . Filament::getTenant()?->id]);

        $scopes = match ($provider) {
            'facebook' => config('services.facebook.scopes', []),
            'google' => config('services.google.scopes', []),
            default => [],
        };

        if (is_array($scopes) && count($scopes) > 0) {
            $driver->scopes($scopes);
        }

        return $driver->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function callback($tenantId, string $provider, Request $request)
    {
        try {
            /** @var \Laravel\Socialite\Two\User $socialiteUser */
            $socialiteUser = Socialite::driver($provider)->user();
            $tenant = Filament::getTenant();

            if (!$tenant && $tenantId) {
                $tenant = Project::find($tenantId);
            }

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

            $token = $socialiteUser->token;

            // --- Facebook Long-Lived Token Exchange & ID Storage ---
            if ($provider === 'facebook') {
                try {
                    $fbAuth = new FacebookGraphAuth();
                    $exchangeResponse = $fbAuth->getLongLivedUserAccessToken(
                        config('services.facebook.client_id'),
                        config('services.facebook.client_secret'),
                        $token
                    );
                    
                    if (!empty($exchangeResponse['access_token'])) {
                        $token = $exchangeResponse['access_token'];
                    }
                } catch (\Exception $e) {
                    Log::warning("Facebook Long-Lived Exchange Failed: " . $e->getMessage() . ". Using short-lived token instead.");
                }

                $tenant->update([
                    'facebook_user_token' => $token,
                    'facebook_user_id' => $socialiteUser->id,
                ]);
            } elseif ($provider === 'google') {
                $tenant->update([
                    'google_refresh_token' => $socialiteUser->refreshToken,
                    'google_user_id' => $socialiteUser->id,
                ]);
            }

            return redirect()->to('/app/' . ($tenant->subdomain ?? '') . '/sync-settings');

        } catch (\Exception $e) {
            Log::error("OAuth Callback Failed for {$provider}: " . $e->getMessage());
            return redirect()->to('/login')->with('error', 'Connection failed.');
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
