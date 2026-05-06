<?php

declare(strict_types=1);

function generate_reset_token(): string
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

function hash_reset_token(string $token): string
{
    return hash('sha256', $token);
}

function verify_reset_token(string $plainToken, string $storedTokenHash): bool
{
    if ($plainToken === '' || $storedTokenHash === '') {
        return false;
    }

    return hash_equals($storedTokenHash, hash_reset_token($plainToken));
}

function get_token_expiry(int $ttlSeconds = 3600): string
{
    $safeTtl = max(300, $ttlSeconds);
    return date('Y-m-d H:i:s', time() + $safeTtl);
}
