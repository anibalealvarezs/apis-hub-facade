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

        // Do NOT nullify the profile ID as this alters project configuration and breaks billing.
        // The Token Authority already handles marking the profile's access_token as null when the grant is invalid.
        \Illuminate\Support\Facades\Log::warning("Permanent auth failure reported by worker for channel $channel on project {$project->name}. TokenAuthority should have already marked the profile as disconnected.");

        return response()->json([
            'status' => 'success',
            'message' => "Cleared connection for channel $channel on project " . $project->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Alert Triggered Receiver: Receive threshold evaluation results from tenant node.
     */
    public function alertTriggered(Request $request): Response
    {
        $token = $request->header('X-Monitoring-Token');

        if (!$token) {
            return response('No monitoring token provided', 400);
        }

        $project = Project::where('monitoring_token', $token)->first();

        if (!$project) {
            return response('Invalid monitoring token', 403);
        }

        $payload = $request->all();

        app(\App\Services\AlertService::class)->handleTriggeredAlert($project, $payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Alert result logged for ' . $project->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
