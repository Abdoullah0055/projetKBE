<?php

function rate_limiter_check(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ipHash = hash('sha256', $ip);
    $storageKey = "ratelimit_{$key}_{$ipHash}";

    $storagePath = sys_get_temp_dir() . '/darquest_' . $storageKey . '.json';
    $now = time();

    $attempts = [];
    if (is_file($storagePath)) {
        $content = @file_get_contents($storagePath);
        if ($content !== false) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $attempts = $decoded;
            }
        }
    }

    $attempts = array_values(array_filter(
        $attempts,
        function (int $timestamp) use ($now, $windowSeconds): bool {
            return ($now - $timestamp) < $windowSeconds;
        }
    ));

    if (count($attempts) >= $maxAttempts) {
        $oldest = $attempts[0] ?? $now;
        $_SESSION['rate_limit_retry_after'] = $windowSeconds - ($now - $oldest);
        return false;
    }

    $attempts[] = $now;
    @file_put_contents($storagePath, json_encode($attempts), LOCK_EX);

    return true;
}

function rate_limiter_reset(string $key): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ipHash = hash('sha256', $ip);
    $storageKey = "ratelimit_{$key}_{$ipHash}";
    $storagePath = sys_get_temp_dir() . '/darquest_' . $storageKey . '.json';

    if (is_file($storagePath)) {
        @unlink($storagePath);
    }

    unset($_SESSION['rate_limit_retry_after']);
}

function rate_limiter_retry_after(): int
{
    return max(0, (int)($_SESSION['rate_limit_retry_after'] ?? 0));
}
