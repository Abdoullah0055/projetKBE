<?php

declare(strict_types=1);

function normalize_email(?string $email): ?string
{
    if ($email === null) {
        return null;
    }

    $trimmed = trim($email);
    if ($trimmed === '') {
        return null;
    }

    return mb_strtolower($trimmed, 'UTF-8');
}

function validate_email(?string $email, bool $checkDns = true): bool
{
    $normalized = normalize_email($email);

    if ($normalized === null) {
        return false;
    }

    if (mb_strlen($normalized, 'UTF-8') > 190) {
        return false;
    }

    if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $parts = explode('@', $normalized);
    if (count($parts) !== 2) {
        return false;
    }

    $domain = trim($parts[1]);
    if ($domain === '') {
        return false;
    }

    if (!$checkDns) {
        return true;
    }

    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }

    return true;
}
