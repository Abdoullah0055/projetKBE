<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../AlgosBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$pdo = get_pdo();

try {
    $stmt = $pdo->prepare("SELECT HP, MaxHP, Gold, Silver, Bronze FROM Users WHERE UserId = :uid");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false]);
        exit;
    }

    echo json_encode([
        'success' => true,
    'hp' => (int)$row['hp'],
    'max_hp' => (int)$row['maxhp'],
    'gold' => (int)$row['gold'],
    'silver' => (int)$row['silver'],
    'bronze' => (int)$row['bronze']
    ]);
} catch (Throwable $e) {
    error_log('header_stats error: ' . $e->getMessage());
    echo json_encode(['success' => false]);
}
