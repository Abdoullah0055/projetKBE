<?php

function load_env(?string $path = null): void
{
    $path = $path ?? __DIR__ . '/../.env';

    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        $value = trim($value, '"\' ');

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    $lower = strtolower((string)$value);

    if ($lower === 'true') {
        return true;
    }

    if ($lower === 'false') {
        return false;
    }

    if ($lower === 'null') {
        return null;
    }

    return $value;
}

load_env();
