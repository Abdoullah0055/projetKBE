<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces refuse.']);
    exit;
}

$reviewId = (int) ($_POST['review_id'] ?? 0);
if ($reviewId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Review invalide.']);
    exit;
}

$pdo = get_pdo();

try {
    $stmt = $pdo->prepare(
        'SELECT r.ReviewId, r.ItemId, u.Alias AS user_alias, i.Name AS item_name
         FROM Reviews r
         JOIN Users u ON u.UserId = r.UserId
         JOIN Items i ON i.ItemId = r.ItemId
         WHERE r.ReviewId = :review_id
         LIMIT 1'
    );
    $stmt->execute([':review_id' => $reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Avis introuvable.']);
        exit;
    }

    $delete = $pdo->prepare('DELETE FROM Reviews WHERE ReviewId = :review_id');
    $delete->execute([':review_id' => $reviewId]);

    echo json_encode([
        'success' => true,
        'message' => 'Avis supprime.',
        'reviewId' => $reviewId,
        'itemId' => (int) $review['itemid'],
        'userAlias' => (string) $review['user_alias'],
        'itemName' => (string) $review['item_name'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Suppression impossible pour le moment.']);
}
