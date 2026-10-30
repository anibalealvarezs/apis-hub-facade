<?php

namespace App\Services;

/** Only recognized progress lines become user-facing state; raw output stays private. */
class UpgradeProgress
{
    private string $buffer = '';

    public const LABELS = [
        'preparing' => 'Preparing the upgrade',
        'launching' => 'Starting the upgrade',
        'waiting' => 'Waiting for other infrastructure changes to finish',
        'building' => 'Preparing the new version',
        'database' => 'Upgrading the metrics database',
        'staging' => 'Preparing cached metrics',
        'duplicates' => 'Looking for duplicate records',
        'merging' => 'Organizing cached metrics',
        'finalizing' => 'Finalizing the metrics database',
        'restarting' => 'Starting updated services',
        'insights' => 'Updating insights',
        'verifying' => 'Checking the updated application',
        'interrupted' => 'The upgrade needs attention before it can continue',
        'reconnecting' => 'Reconnecting to check upgrade progress',
    ];

    public function consume(string $chunk, callable $update): void
    {
        $this->buffer .= $chunk;
        while (($end = strpos($this->buffer, "\n")) !== false) {
            $line = substr($this->buffer, 0, $end);
            $this->buffer = substr($this->buffer, $end + 1);
            $line = preg_replace('/\x1b\[[0-9;]*m/', '', trim($line));
            $stage = null;
            if (preg_match('/^__UPGRADE_STAGE__:([a-z]+)$/', $line, $match) && isset(self::LABELS[$match[1]])) $stage = $match[1];
            elseif (str_starts_with($line, 'GSC Web backfill: staged ')) $stage = 'staging';
            elseif (str_starts_with($line, 'GSC Web backfill: reconciling ')) $stage = 'duplicates';
            elseif (str_starts_with($line, 'GSC Web backfill: merged ')) $stage = 'merging';
            elseif (str_starts_with($line, 'GSC Web backfill: updating ')) $stage = 'finalizing';
            if ($stage) $update($stage);
        }
        // Bound memory even if a build tool emits an enormous unterminated line.
        if (strlen($this->buffer) > 16384) $this->buffer = substr($this->buffer, -16384);
    }
}
