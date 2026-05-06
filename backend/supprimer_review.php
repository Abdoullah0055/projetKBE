<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function can_send_json(): bool
{
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

    return $isAjax || str_contains($accept, 'application/json');
}

function end_user_review_delete(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user']['id'])) {
    end_user_review_delete(['success' => false, 'message' => 'Connexion requise.'], 401);
}

$reviewId = (int) ($_POST['review_id'] ?? $_GET['review_id'] ?? 0);
if ($reviewId <= 0) {
    end_user_review_delete(['success' => false, 'message' => 'Review invalide.'], 422);
}

$userId = (int) $_SESSION['user']['id'];
$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $owned = $pdo->prepare(
        'SELECT ReviewId, ItemId
         FROM Reviews
         WHERE ReviewId = :review_id
           AND UserId = :user_id
         LIMIT 1
         FOR UPDATE'
    );
    $owned->execute([
        ':review_id' => $reviewId,
        ':user_id' => $userId,
    ]);
    $review = $owned->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        $pdo->rollBack();
        end_user_review_delete(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres avis.'], 403);
    }

    $itemId = (int) $review['itemid'];

    $delete = $pdo->prepare('DELETE FROM Reviews WHERE ReviewId = :review_id');
    $delete->execute([':review_id' => $reviewId]);

    $agg = $pdo->prepare(
        'SELECT IFNULL(AVG(Rating), 0) AS rating, COUNT(ReviewId) AS review_count
         FROM Reviews
         WHERE ItemId = :item_id'
    );
    $agg->execute([':item_id' => $itemId]);
    $aggregate = $agg->fetch(PDO::FETCH_ASSOC) ?: ['rating' => 0, 'review_count' => 0];

    $pdo->commit();

    end_user_review_delete([
        'success' => true,
        'message' => 'Votre avis a ete retire.',
        'itemId' => $itemId,
        'rating' => formatRatingValue((float) $aggregate['rating']),
        'reviewCount' => (int) $aggregate['review_count'],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    end_user_review_delete(['success' => false, 'message' => 'Impossible de supprimer cet avis actuellement.'], 500);
}
