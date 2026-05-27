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
            'health_metrics' => $request->only(['channels', 'catalog', 'db', 'redis', 'system', 'status_summary']),
            'last_heartbeat_at' => now(),
            'health_status' => 'online',
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

        // Clear connection by nullifying profile ID
        $profileIdField = "{$channel}_profile_id";
        
        $updateData = [];

        // Ensure we only nullify if the column exists on the model
        if (\Illuminate\Support\Facades\Schema::hasColumn('projects', $profileIdField)) {
            $updateData[$profileIdField] = null;
        }

        if (!empty($updateData)) {
            $project->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Cleared connection for channel $channel on project " . $project->name,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
