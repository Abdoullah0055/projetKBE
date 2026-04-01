<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function profile_csrf_token(): string
{
    if (empty($_SESSION['profile_csrf']) || !is_string($_SESSION['profile_csrf'])) {
        $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['profile_csrf'];
}

function profile_is_valid_csrf(?string $token): bool
{
    if (!isset($_SESSION['profile_csrf']) || !is_string($_SESSION['profile_csrf'])) {
        return false;
    }

    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($_SESSION['profile_csrf'], $token);
}

function profile_set_flash(string $type, string $message): void
{
    $_SESSION['profile_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function profile_take_flash(): ?array
{
    if (!isset($_SESSION['profile_flash']) || !is_array($_SESSION['profile_flash'])) {
        return null;
    }

    $flash = $_SESSION['profile_flash'];
    unset($_SESSION['profile_flash']);

    return $flash;
}

function profile_nullable_trimmed(?string $value, int $maxLen): ?string
{
    $clean = trim((string)$value);
    if ($clean === '') {
        return null;
    }

    if (mb_strlen($clean, 'UTF-8') > $maxLen) {
        return null;
    }

    return $clean;
}
