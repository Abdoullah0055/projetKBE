<?php

declare(strict_types=1);

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/business_logic.php';

function get_item_heal_amount(int $itemId): int
{
    if ($itemId <= 0) {
        return 0;
    }

    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        "SELECT i.ItemId, i.ItemTypeId, t.Name AS ItemTypeName, p.EffectValue, m.SpellDamage
         FROM Items i
         JOIN ItemTypes t ON t.ItemTypeId = i.ItemTypeId
         LEFT JOIN PotionProperties p ON p.ItemId = i.ItemId
         LEFT JOIN MagicSpellProperties m ON m.ItemId = i.ItemId
         WHERE i.ItemId = :item_id
         LIMIT 1"
    );
    $stmt->execute([':item_id' => $itemId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return 0;
    }

    $type = mb_strtolower((string) ($row['itemtypename'] ?? ''), 'UTF-8');

    if ($type === 'potion') {
        $heal = (int) ($row['effectvalue'] ?? 0);
        return max(1, min($heal, 9999));
    }

    if ($type === 'magicspell') {
        $spellDamage = (int) ($row['spelldamage'] ?? 0);
        $heal = (int) floor($spellDamage / 2);
        return max(3, min($heal, 9999));
    }

    return 0;
}
