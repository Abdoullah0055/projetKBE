<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Acces refuse.']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    exit;
}

$pdo = get_pdo();

try {
    $stmt = $pdo->prepare("
        SELECT i.Name AS name, t.Name AS type, inv.Quantity AS quantity,
               i.PriceGold AS gold, i.PriceSilver AS silver, i.PriceBronze AS bronze
        FROM Inventory inv
        JOIN Items i ON i.ItemId = inv.ItemId
        JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
        WHERE inv.UserId = :uid
        ORDER BY i.Name ASC
    ");
    $stmt->execute([':uid' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
