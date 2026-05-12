<?php

require_once __DIR__ . '/session.php';

define('CSRF_POOL_SIZE', 10);
define('CSRF_TTL', 1800);

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    $now = time();

    $_SESSION['csrf_tokens'] = array_filter(
        $_SESSION['csrf_tokens'],
        function (array $meta) use ($now): bool {
            return ($now - ($meta['ts'] ?? 0)) < CSRF_TTL;
        }
    );

    while (count($_SESSION['csrf_tokens']) >= CSRF_POOL_SIZE) {
        array_shift($_SESSION['csrf_tokens']);
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = ['ts' => $now];

    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate(): bool
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!is_string($token) || $token === '') {
        return false;
    }

    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }

    $now = time();
    $meta = $_SESSION['csrf_tokens'][$token];

    unset($_SESSION['csrf_tokens'][$token]);

    if (($now - ($meta['ts'] ?? 0)) > CSRF_TTL) {
        return false;
    }

    return true;
}

function csrf_meta_tag(): string
{
    if (!isset($_SESSION['user']['id'])) {
        return '';
    }

    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
