<?php

declare(strict_types=1);

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$itemId = (int) ($_GET['item_id'] ?? 0);
if ($itemId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Item invalide.']);
    exit;
}

$levels = [5.0, 4.5, 4.0, 3.5, 3.0, 2.5, 2.0, 1.5, 1.0];
$distribution = [];
foreach ($levels as $level) {
    $distribution[number_format($level, 1, '.', '')] = 0;
}

$pdo = get_pdo();
$stmt = $pdo->prepare(
    "SELECT Rating, COUNT(*) AS cnt
     FROM Reviews
     WHERE ItemId = :item_id
     GROUP BY Rating"
);
$stmt->execute([':item_id' => $itemId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($rows as $row) {
    $key = number_format((float) $row['rating'], 1, '.', '');
    $count = (int) $row['cnt'];
    if (isset($distribution[$key])) {
        $distribution[$key] = $count;
    }
    $total += $count;
}

$payload = [];
foreach ($distribution as $key => $count) {
    $payload[] = [
        'rating' => (float) $key,
        'count' => $count,
        'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
    ];
}

echo json_encode([
    'success' => true,
    'item_id' => $itemId,
    'total' => $total,
    'distribution' => $payload,
], JSON_UNESCAPED_UNICODE);
