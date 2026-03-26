<?php
require_once __DIR__ . '/../AlgosBD.php';
session_start();

header('Content-Type: application/json');

// Vérification de sécurité
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$itemId = $_POST['item_id'] ?? 0;
$userId = $_SESSION['user']['id'];

if ($itemId > 0) {
    // Appel de ta fonction dans AlgosBD.php
    $success = remove_from_cart($userId, $itemId);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID d\'item invalide']);
}
