<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/validation_utils.php';

if (ob_get_level() === 0) {
    ob_start();
}

function is_ajax_request(): bool
{
    $isXmlHttpRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $acceptHeader = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = strpos($acceptHeader, 'application/json') !== false;

    return $isXmlHttpRequest || $wantsJson;
}

function review_response(array $payload, string $redirectUrl, bool $isAjax, int $status = 200): void
{
    if ($isAjax) {
        if (ob_get_length() > 0) {
            ob_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (ob_get_length() > 0) {
        ob_clean();
    }

    $_SESSION['review_feedback'] = [
        'type' => (($payload['success'] ?? false) === true) ? 'success' : 'error',
        'message' => (string)($payload['message'] ?? ''),
    ];

    header('Location: ' . $redirectUrl);
    exit;
}

$isAjax = is_ajax_request();

if (!isset($_SESSION['user']['id'])) {
    review_response([
        'success' => false,
        'message' => 'Veuillez vous connecter pour noter un item.',
    ], '../login.php', $isAjax, 401);
}

$userId = (int)$_SESSION['user']['id'];
$itemId = (int)($_POST['item_id'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

$normalizedRating = normalize_review_rating((string) ($_POST['rating'] ?? ''));
if ($itemId <= 0 || $normalizedRating === null) {
    review_response([
        'success' => false,
        'message' => 'Donnees de notation invalides.',
    ], '../inventory.php', $isAjax, 422);
}

if ($comment !== '' && mb_strlen($comment, 'UTF-8') > 1000) {
    review_response([
        'success' => false,
        'message' => 'Commentaire trop long (1000 caracteres max).',
    ], '../inventory.php', $isAjax, 422);
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $ownedStmt = $pdo->prepare(
        "SELECT inv.Quantity
         FROM Inventory inv
         WHERE inv.UserId = :user_id
           AND inv.ItemId = :item_id
         LIMIT 1
         FOR UPDATE"
    );
    $ownedStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
    ]);

    $ownedRow = $ownedStmt->fetch();
    $inInventory = $ownedRow && (int)$ownedRow['quantity'] > 0;

    $purchasedStmt = $pdo->prepare(
        "SELECT 1
         FROM OrderItems oi
         JOIN Orders o ON o.OrderId = oi.OrderId
         WHERE o.UserId = :user_id
           AND oi.ItemId = :item_id
         LIMIT 1"
    );
    $purchasedStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
    ]);
    $wasPurchased = $purchasedStmt->fetchColumn() !== false;

    if (!$inInventory && !$wasPurchased) {
        $pdo->rollBack();
        review_response([
            'success' => false,
            'message' => 'Seuls les items achetes peuvent etre notes.',
        ], '../inventory.php', $isAjax, 403);
    }

    $existingStmt = $pdo->prepare(
        "SELECT ReviewId
         FROM Reviews
         WHERE UserId = :user_id
           AND ItemId = :item_id
         LIMIT 1
         FOR UPDATE"
    );
    $existingStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
    ]);

    if ($existingStmt->fetchColumn()) {
        $pdo->rollBack();
        review_response([
            'success' => false,
            'message' => 'Vous avez deja evalue cet item.',
        ], '../inventory.php', $isAjax, 409);
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO Reviews (UserId, ItemId, Rating, Comment)
         VALUES (:user_id, :item_id, :rating, :comment)"
    );
    $insertStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
        ':rating' => $normalizedRating,
        ':comment' => ($comment === '' ? null : $comment),
    ]);

    $newReviewId = (int) $pdo->lastInsertId();

    $aggregateStmt = $pdo->prepare(
        "SELECT
            IFNULL(AVG(Rating), 0) AS rating,
            COUNT(ReviewId) AS review_count
         FROM Reviews
         WHERE ItemId = :item_id"
    );
    $aggregateStmt->execute([
        ':item_id' => $itemId,
    ]);

    $aggregate = $aggregateStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'rating' => 0,
        'review_count' => 0,
    ];

    $pdo->commit();

    review_response([
        'success' => true,
        'message' => 'Merci, votre note a ete enregistree.',
        'rating' => formatRatingValue((float)$aggregate['rating']),
        'reviewId' => $newReviewId,
        'reviewCount' => (int)$aggregate['review_count'],
    ], '../inventory.php', $isAjax);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    review_response([
        'success' => false,
        'message' => 'Impossible d\'enregistrer votre note pour le moment.',
    ], '../inventory.php', $isAjax, 500);
}
