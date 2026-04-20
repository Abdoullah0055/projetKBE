<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function search_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function escape_like_value(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

$query = trim((string)($_GET['q'] ?? ''));
if ($query === '') {
    search_json([
        'success' => true,
        'items' => [],
    ]);
}

$likePattern = '%' . escape_like_value($query) . '%';
$prefixPattern = escape_like_value($query) . '%';

try {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        "SELECT
            i.ItemId AS id,
            i.Name AS item_name,
            t.Name AS item_type,
            i.Rarity AS rarity,
            IFNULL(AVG(r.Rating), 0) AS rating,
            COUNT(r.ReviewId) AS review_count
         FROM Items i
         JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
         LEFT JOIN Reviews r ON r.ItemId = i.ItemId
         WHERE i.IsActive = 1
           AND (i.Name LIKE :name_query ESCAPE '\\\\' OR t.Name LIKE :type_query ESCAPE '\\\\')
            GROUP BY i.ItemId, i.Name, t.Name, i.Rarity
         ORDER BY
            CASE WHEN i.Name LIKE :prefix_query ESCAPE '\\\\' THEN 0 ELSE 1 END,
            i.Name ASC
         LIMIT 8"
    );

    $stmt->execute([
        ':name_query' => $likePattern,
        ':type_query' => $likePattern,
        ':prefix_query' => $prefixPattern,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $ratingValue = (float)($row['rating'] ?? 0);
        $ratingText = formatRatingValue($ratingValue);

        $items[] = [
            'id' => (int)$row['id'],
            'name' => (string)($row['item_name'] ?? ''),
            'type' => (string)($row['item_type'] ?? ''),
            'rarity' => formatRarityLabel((string)($row['rarity'] ?? 'Commun')),
            'image' => getItemImage((string)($row['item_type'] ?? '')),
            'rating' => (float)$ratingText,
            'ratingText' => $ratingText,
            'reviewCount' => (int)($row['review_count'] ?? 0),
            'detailsUrl' => 'details.php?id=' . (int)$row['id'],
        ];
    }

    search_json([
        'success' => true,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    search_json([
        'success' => false,
        'items' => [],
        'message' => 'Recherche indisponible pour le moment.',
    ], 500);
}
