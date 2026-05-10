<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/session.php';

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
        'hp' => (int)$row['HP'],
        'max_hp' => (int)$row['MaxHP'],
        'gold' => (int)$row['Gold'],
        'silver' => (int)$row['Silver'],
        'bronze' => (int)$row['Bronze']
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false]);
}
