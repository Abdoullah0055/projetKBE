<?php
require_once __DIR__ . '/../AlgosBD.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecte']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$userId = (int)$_SESSION['user']['id'];

if ($itemId > 0) {
    $success = remove_from_cart($userId, $itemId);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Item supprime du panier.' : 'Item introuvable dans le panier.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => "ID d'item invalide"]);
}
