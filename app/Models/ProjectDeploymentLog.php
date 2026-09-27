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

        if ($this->status === 'rolled_back') {
            return __('The upgrade was rolled back automatically. The project is running its previous version again.');
        }

        if ($this->status === 'success' || $this->status === 'completed') {
            return __('Deployment completed successfully. Infrastructure is live.');
        }

        // Composer and most build tools print the actionable error *above* a usage or
        // synopsis block, so the last line is frequently just that block. Scan the whole
        // output instead, otherwise a dependency outage is reported as an unknown issue.
        $haystack = strtolower($output);

        $errorPatterns = [
            ['/could not be downloaded|failed to download .* from dist|policy\.ignore-unreachable|source fallback is disabled/i', __('Dependency download failed: the private Composer repository was unreachable.')],
            ['/=== exception ===|stack trace/i', __('A critical internal error interrupted the deployment process.')],
            ['/fatal error/i', __('A critical internal error interrupted the deployment process.')],
            ['/no such container/i', __('Target container not found or stopped.')],
            ['/permission denied|authentication failed/i', __('Authentication failed: Server denied access.')],
            ['/no space left on device/i', __('Server out of storage space.')],
            ['/failed to connect|connection refused|\btimeout\b|bad gateway/i', __('Network error: Unable to reach the remote node.')],
        ];

        foreach ($errorPatterns as [$pattern, $message]) {
            if (preg_match($pattern, $haystack)) {
                return $message;
            }
        }

        // Progress wording is only meaningful while a run is still in flight. On a failed
        // run these phrases appear in the very same logs that contain the real error, so
        // matching them would mask it.
        if (! in_array($this->status, ['failed', 'error'], true)) {
            $progressPatterns = [
                ['/caddyfile formatted/i', __('Infrastructure ready and proxy configured.')],
                ['/cloning into/i', __('Provisioning initial repository...')],
                ['/already up to date/i', __('Infrastructure is already running the latest version.')],
                ['/restarting|docker compose up|container/i', __('Containers are being rebuilt or restarted.')],
            ];

            foreach ($progressPatterns as [$pattern, $message]) {
                if (preg_match($pattern, $haystack)) {
                    return $message;
                }
            }
        }

        $lines = array_values(array_filter(explode("\n", $output)));
        $lastLine = $lines ? (string) end($lines) : '';

        // Clean up bash colors/escapes from the last line
        $lastLine = preg_replace('/\x1b\[[0-9;]*m/', '', $lastLine);

        $rawSummary = trim($lastLine ?: '');

        if ($this->status === 'failed' || $this->status === 'error') {
            return __('Deployment failed due to an unknown issue. (:summary)', ['summary' => $rawSummary]);
        }

        return $rawSummary ?: __('Process is initializing or running in the background...');
    }
}
