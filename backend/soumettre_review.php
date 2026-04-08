<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';

session_start();

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
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
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
$ratingRaw = str_replace(',', '.', trim((string)($_POST['rating'] ?? '')));

if ($itemId <= 0 || $ratingRaw === '' || !is_numeric($ratingRaw)) {
    review_response([
        'success' => false,
        'message' => 'Donnees de notation invalides.',
    ], '../inventory.php', $isAjax, 422);
}

$ratingAsFloat = (float)$ratingRaw;
$ratingSteps = (int)round($ratingAsFloat * 2);
$normalizedRating = $ratingSteps / 2;

if (
    $ratingSteps < 2
    || $ratingSteps > 10
    || abs(($ratingAsFloat * 2) - $ratingSteps) > 0.001
) {
    review_response([
        'success' => false,
        'message' => 'La note doit etre comprise entre 1 et 5 par pas de 0.5.',
    ], '../inventory.php', $isAjax, 422);
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $ownedStmt = $pdo->prepare(
        "SELECT Quantity
         FROM Inventory
         WHERE UserId = :user_id
           AND ItemId = :item_id
         LIMIT 1
         FOR UPDATE"
    );
    $ownedStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
    ]);

    $ownedRow = $ownedStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ownedRow || (int)$ownedRow['Quantity'] <= 0) {
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
         VALUES (:user_id, :item_id, :rating, NULL)"
    );
    $insertStmt->execute([
        ':user_id' => $userId,
        ':item_id' => $itemId,
        ':rating' => $normalizedRating,
    ]);

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
