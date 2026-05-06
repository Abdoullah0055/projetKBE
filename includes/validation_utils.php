<?php

declare(strict_types=1);

function validate_alias(string $alias): bool
{
    $trimmed = trim($alias);

    if ($trimmed === '') {
        return false;
    }

    $length = mb_strlen($trimmed, 'UTF-8');
    if ($length < 3 || $length > 30) {
        return false;
    }

    return (bool) preg_match('/^[\p{L}\p{N}_-]+$/u', $trimmed);
}

function validate_review_rating(string|int|float $rating): bool
{
    $raw = is_string($rating) ? str_replace(',', '.', trim($rating)) : (string) $rating;

    if ($raw === '' || !is_numeric($raw)) {
        return false;
    }

    $value = (float) $raw;
    $steps = (int) round($value * 2);

    if ($steps < 2 || $steps > 10) {
        return false;
    }

    return abs(($value * 2) - $steps) <= 0.001;
}

function normalize_review_rating(string|int|float $rating): ?float
{
    if (!validate_review_rating($rating)) {
        return null;
    }

    $raw = is_string($rating) ? str_replace(',', '.', trim($rating)) : (string) $rating;
    $value = (float) $raw;

    return round($value * 2) / 2;
}
