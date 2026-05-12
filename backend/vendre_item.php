<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecte']);
    exit;
}

$userId = $_SESSION['user']['id'];
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item invalide']);
    exit;
}

$result = sell_inventory_item($userId, $itemId);

if ($result['success']) {
    $_SESSION['user']['gold'] = $result['new_balance']['gold'];
    $_SESSION['user']['silver'] = $result['new_balance']['silver'];
    $_SESSION['user']['bronze'] = $result['new_balance']['bronze'];
}

echo json_encode($result);
