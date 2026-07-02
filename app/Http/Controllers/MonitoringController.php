<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitoringController extends Controller
{
    /**
     * Heartbeat Receiver: Catch diagnostic payloads from remote apis-hub nodes.
     */
    public function heartbeat(Request $request): Response
    {
        $token = $request->header('X-Monitoring-Token');

        if (!$token) {
            return response('No monitoring token provided', 400);
        }

        $project = Project::where('monitoring_token', $token)->first();

        if (!$project) {
            return response('Invalid monitoring token', 403);
        }

        // Update project health data
        // Store the incoming diagnostic payload
        $project->update([
            'health_metrics'        => $request->only(['channels', 'catalog', 'db', 'redis', 'system', 'status_summary']),
            'last_heartbeat_at'     => now(),
            'health_status'         => 'online',
            // A heartbeat means the node is alive and responsive — clear the "sync in progress" marker
            // so the UI stops showing the sync-in-progress banner.
            'last_sync_started_at'  => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat received for ' . $project->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Auth Failed Receiver: Catch permanent authentication errors from APIs Hub.
     */
    public function authFailed(Request $request): Response
    {
        $token = $request->header('X-Monitoring-Token');

        if (!$token) {
            return response('No monitoring token provided', 400);
        }

        $project = Project::where('monitoring_token', $token)->first();

        if (!$project) {
            return response('Invalid monitoring token', 403);
        }

        $channel = $request->input('channel');
        if (!$channel) {
            return response('Channel not provided', 400);
        }

        // We do NOT nullify the profile ID on the project as this alters project configuration and breaks billing.
        // Instead, we nullify the access_token and refresh_token on the ChannelProfile itself.
        // For Google, the Token Authority handles this during refresh, but for Facebook long-lived tokens
        // that are revoked mid-lifecycle (e.g. password change), the Token Authority never gets the invalid_grant.
        // Therefore, we must manually disconnect the profile here.
        $provider = 'unknown';
        if (str_starts_with($channel, 'google')) {
            $provider = 'google';
        } elseif (str_starts_with($channel, 'facebook') || str_starts_with($channel, 'meta')) {
            $provider = 'facebook';
        }

        if ($provider !== 'unknown') {
            $profileIdColumn = "{$provider}_profile_id";
            $profileId = $project->{$profileIdColumn};

            if ($profileId) {
                $profile = \App\Models\ChannelProfile::find($profileId);
                if ($profile && $profile->access_token !== null) {
                    $profile->update([
                        'access_token' => null,
                        'refresh_token' => null,
                    ]);

                    // Notify the true owner
                    $owner = $project->trueOwner;
                    if ($owner) {
                        $owner->notify(new \App\Notifications\IntegrationDisconnectedNotification($project, $profile->provider));
                    }
                    
                    \Illuminate\Support\Facades\Log::warning("Permanent auth failure reported by worker for channel $channel on project {$project->name}. Disconnected {$provider} profile.");
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Cleared connection for channel $channel on project " . $project->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
