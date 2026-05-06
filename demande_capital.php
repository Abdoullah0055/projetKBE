<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit();
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise.']);
    exit();
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide.']);
    exit();
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO Demandes (UserId) VALUES (?)");
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Demande d augmentation de capital envoyee a l administration.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossible de soumettre la demande pour le moment.'
    ]);
}
