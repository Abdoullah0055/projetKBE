<?php
require_once __DIR__ . '/../AlgosBD.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Non connectÃ©']);
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$itemId = (int)($_POST['item_id'] ?? 0);
$newQty = (int)($_POST['new_qty'] ?? 0);

if ($itemId > 0 && $newQty >= 0) {
    $success = modify_item_quantity_cart($userId, $itemId, $newQty);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Quantite mise a jour.' : 'Mise a jour impossible (stock ou item indisponible).'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'DonnÃ©es invalides']);
}


