<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDeploymentLog extends Model
{
    protected $table = 'project_deployment_logs';

    protected $fillable = [
        'project_id',
        'status',
        'output',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getSummaryMessage(): string
    {
        $output = trim($this->output ?? '');
        $lines = array_filter(explode("\n", $output));
        $lastLine = end($lines);
        
        // Clean up bash colors/escapes from the last line
        $lastLine = preg_replace('/\x1b\[[0-9;]*m/', '', $lastLine);
        
        $rawSummary = $lastLine ?: '';
        $summaryLower = strtolower($rawSummary);
        
        if ($this->status === 'success' || $this->status === 'completed') {
            return __('Deployment completed successfully. Infrastructure is live.');
        } elseif (str_contains($summaryLower, '=== exception ===') || str_contains($summaryLower, 'fatal error') || str_contains($summaryLower, 'stack trace')) {
            return __('A critical internal error interrupted the deployment process.');
        } elseif (str_contains($summaryLower, 'caddyfile formatted')) {
            return __('Infrastructure ready and proxy configured.');
        } elseif (str_contains($summaryLower, 'cloning into')) {
            return __('Provisioning initial repository...');
        } elseif (str_contains($summaryLower, 'no such container')) {
            return __('Target container not found or stopped.');
        } elseif (str_contains($summaryLower, 'permission denied') || str_contains($summaryLower, 'authentication failed')) {
            return __('Authentication failed: Server denied access.');
        } elseif (str_contains($summaryLower, 'no space left on device')) {
            return __('Server out of storage space.');
        } elseif (str_contains($summaryLower, 'already up to date')) {
            return __('Infrastructure is already running the latest version.');
        } elseif (str_contains($summaryLower, 'restarting') || str_contains($summaryLower, 'docker compose up') || str_contains($summaryLower, 'container')) {
            return __('Containers are being rebuilt or restarted.');
        } elseif (str_contains($summaryLower, 'failed to connect') || str_contains($summaryLower, 'connection refused') || str_contains($summaryLower, 'timeout')) {
            return __('Network error: Unable to reach the remote node.');
        } elseif ($this->status === 'failed' || $this->status === 'error') {
            return __('Deployment failed due to an unknown issue. (:summary)', ['summary' => $rawSummary]);
        }
        
        return $rawSummary ?: __('Process is initializing or running in the background...');
    }
}
