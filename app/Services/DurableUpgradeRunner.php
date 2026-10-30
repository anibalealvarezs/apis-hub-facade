<?php

namespace App\Services;

/** Host-side execution survives SSH disconnects and Facade worker restarts. */
class DurableUpgradeRunner
{
    public static function quote(string $value): string
    {
        // The destination shell is POSIX even when Facade tests run on Windows.
        return "'".str_replace("'", "'\"'\"'", $value)."'";
    }

    public static function directory(string $id): string
    {
        if (!preg_match('/^[a-f0-9-]{36}$/D', $id)) throw new \InvalidArgumentException('Invalid upgrade identifier');
        return '/var/www/apis-hub/upgrade-operations/'.$id;
    }

    public static function script(string $directory, string $commands): string
    {
        $dir = self::quote($directory);
        $serverLock = self::quote(dirname($directory).'/server.lock');
        return "#!/usr/bin/env bash\nset -Eeuo pipefail\numask 077\ncd {$dir}\n"
            . "exec 8>run.lock\nflock -n 8 || exit 0\n"
            . "# An uncertain or interrupted run must never replay migrations.\n"
            . "test ! -e started || exit 0\n"
            . "date -u +%s > started\n"
            . 'trap \'code=$?; printf "%s\n" "$code" > result.tmp; mv result.tmp result; rm -f target.env\' EXIT'."\n"
            . "exec >> output.log 2>&1\necho '__UPGRADE_STAGE__:waiting'\n"
            . "exec 9>{$serverLock}\nflock 9\n"
            . "echo '__UPGRADE_STAGE__:preparing'\n(\n{$commands}\n)\n";
    }

    public static function launch(string $directory): string
    {
        $dir = self::quote($directory);
        // All descriptors are detached, including the SSH input stream.
        return "cd {$dir} && (nohup bash runner.sh </dev/null >/dev/null 2>&1 & echo launched)";
    }

    public static function probe(string $directory): string
    {
        $dir = self::quote($directory);
        return "if test ! -f {$dir}/runner.sh; then echo missing; else cd {$dir}; "
            . "if test -f result; then printf 'finished:'; cat result; "
            . "elif test ! -f started; then echo pending; "
            . "elif flock -n run.lock true; then echo interrupted; else echo running; fi; "
            . "tail -c 65536 output.log 2>/dev/null || true; fi";
    }

    public static function decode(string $output): array
    {
        [$header, $log] = array_pad(explode("\n", trim($output), 2), 2, '');
        if (preg_match('/^finished:(\d+)$/D', $header, $match)) {
            return ['status' => (int) $match[1] === 0 ? 'success' : 'error', 'output' => $log];
        }
        return ['status' => 'running', 'remote_state' => in_array($header, ['missing', 'pending', 'running', 'interrupted'], true) ? $header : 'unknown', 'output' => $log];
    }
}
