<?php

require_once __DIR__ . '/session.php';

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


