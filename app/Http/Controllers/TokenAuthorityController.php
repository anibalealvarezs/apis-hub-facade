<?php

namespace App\Http\Controllers;

use App\Models\ChannelProfile;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TokenAuthorityController extends Controller
{
    /**
     * Handle token refresh requests from distributed worker nodes.
     */
    public function refresh(Request $request)
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return response()->json(['error' => 'Missing Bearer Token'], 401);
        }

        // Validate the bearer token against the Tenant (Project)
        // Since `public_api_key` and `remote_admin_api_key` are encrypted in the database,
        // we cannot query them via SQL `where()`. We must filter the collection in memory.
        $project = Project::get()->first(function ($p) use ($bearerToken) {
            return $p->public_api_key === $bearerToken || $p->remote_admin_api_key === $bearerToken;
        });

        if (! $project) {
            return response()->json(['error' => 'Invalid Authority Token'], 403);
        }

        $channel = $request->input('channel');
        if (! $channel) {
            return response()->json(['error' => 'Channel is required'], 400);
        }

        // Infer provider from channel (e.g., google_analytics -> google, facebook_marketing -> facebook)
        $provider = 'unknown';
        if (str_starts_with($channel, 'google')) {
            $provider = 'google';
        } elseif (str_starts_with($channel, 'facebook') || str_starts_with($channel, 'meta')) {
            $provider = 'facebook';
        }

        if ($provider === 'unknown') {
            return response()->json(['error' => 'Unsupported channel/provider combination'], 400);
        }

        // Determine which profile to use based on the provider
        $profileIdColumn = "{$provider}_profile_id";
        $profileId = $project->{$profileIdColumn};

        if (! $profileId) {
            return response()->json(['error' => "No {$provider} profile linked to this project"], 404);
        }

        $profile = ChannelProfile::find($profileId);
        if (! $profile) {
            return response()->json(['error' => "Profile not found"], 404);
        }

        try {
            $cacheKey = "profile_{$profile->id}_last_refresher_project_id";
            $lastRefresherProjectId = \Illuminate\Support\Facades\Cache::get($cacheKey);

            // If the current token in the database is still technically unexpired...
            if ($profile->access_token && $profile->expires_at && $profile->expires_at->isFuture()) {
                
                // If a DIFFERENT project is asking, they just need to catch up to the latest token!
                // We return the existing token to them without hitting the provider API.
                if ($lastRefresherProjectId !== $project->id) {
                    return response()->json([
                        'access_token' => $profile->access_token,
                        'expires_at' => $profile->expires_at->toIso8601String(),
                    ]);
                }
                
                // If the SAME project is asking again, it implies the token we previously gave them 
                // is being rejected by the provider, so we MUST allow a fresh API request.
            }

            $newToken = $this->performRefresh($profile);

            // Record that THIS project was the one who caused the fresh token to be issued
            \Illuminate\Support\Facades\Cache::put($cacheKey, $project->id, now()->addDays(7));

            return response()->json([
                'access_token' => $newToken,
                'expires_at' => $profile->expires_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("Token Authority Refresh Failed for Project {$project->id}: " . $e->getMessage());

            $statusCode = 500;
            if (str_contains($e->getMessage(), 'Google API rejected refresh') || str_contains($e->getMessage(), 'Facebook API rejected refresh')) {
                $statusCode = 400;
            }

            return response()->json(['error' => 'Refresh failed: ' . $e->getMessage()], $statusCode);
        }
    }

    /**
     * Actually perform the HTTP request to the Provider to refresh the token.
     */
    protected function performRefresh(ChannelProfile $profile)
    {
        if ($profile->provider === 'google') {
            if (! $profile->refresh_token) {
                throw new \Exception("No refresh token available for Google profile.");
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $profile->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                throw new \Exception("Google API rejected refresh: " . $response->body());
            }

            $data = $response->json();
            $profile->update([
                'access_token' => $data['access_token'],
                'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
            ]);

            return $data['access_token'];

        } elseif ($profile->provider === 'facebook') {
            // Facebook long-lived tokens generally don't "refresh" via a refresh token.
            // But we can try to extend them or we might just return the existing token if it's still valid.
            // The architecture uses 'FB Exchange Token' which gives a 60-day token.
            // If it's expired, the user must re-authenticate manually, but we can attempt to fetch a new one.
            $response = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id') ?? config('services.facebook.app_id'),
                'client_secret' => config('services.facebook.client_secret') ?? config('services.facebook.app_secret'),
                'fb_exchange_token' => $profile->access_token,
            ]);

            if ($response->failed()) {
                throw new \Exception("Facebook API rejected refresh: " . $response->body());
            }

            $data = $response->json();
            $profile->update([
                'access_token' => $data['access_token'],
                'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
            ]);

            return $data['access_token'];
        }

        throw new \Exception("Unsupported provider: {$profile->provider}");
    }
}
