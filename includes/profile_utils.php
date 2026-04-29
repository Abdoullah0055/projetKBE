<?php

require_once __DIR__ . '/session.php';

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

function get_user_riddle_stats(int $userId): array
{
    $pdo = get_pdo();

    $defaults = [
        'facile_solved' => 0,
        'facile_total' => 0,
        'moyenne_solved' => 0,
        'moyenne_total' => 0,
        'difficile_solved' => 0,
        'difficile_total' => 0,
        'solved_count' => 0,
    ];

    try {
        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Facile' AND ra.IsCorrect = 1 THEN 1 ELSE 0 END), 0) AS facile_solved,
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Facile' THEN 1 ELSE 0 END), 0) AS facile_total,
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Moyenne' AND ra.IsCorrect = 1 THEN 1 ELSE 0 END), 0) AS moyenne_solved,
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Moyenne' THEN 1 ELSE 0 END), 0) AS moyenne_total,
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Difficile' AND ra.IsCorrect = 1 THEN 1 ELSE 0 END), 0) AS difficile_solved,
                COALESCE(SUM(CASE WHEN r.Difficulty = 'Difficile' THEN 1 ELSE 0 END), 0) AS difficile_total,
                COALESCE(SUM(CASE WHEN ra.IsCorrect = 1 THEN 1 ELSE 0 END), 0) AS solved_count
            FROM RiddleAttempts ra
            JOIN Riddles r ON r.RiddleId = ra.RiddleId
            WHERE ra.UserId = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && is_array($row)) {
            return [
                'facile_solved' => (int)($row['facile_solved'] ?? 0),
                'facile_total' => (int)($row['facile_total'] ?? 0),
                'moyenne_solved' => (int)($row['moyenne_solved'] ?? 0),
                'moyenne_total' => (int)($row['moyenne_total'] ?? 0),
                'difficile_solved' => (int)($row['difficile_solved'] ?? 0),
                'difficile_total' => (int)($row['difficile_total'] ?? 0),
                'solved_count' => (int)($row['solved_count'] ?? 0),
            ];
        }
    } catch (Throwable $e) {}

    return $defaults;
}
