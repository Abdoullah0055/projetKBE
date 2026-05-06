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

$userId = (int) ($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Joueur invalide.']);
    exit;
}

$pdo = get_pdo();

try {
    $stmt = $pdo->prepare(
        "SELECT
            inv.InventoryId,
            inv.ItemId,
            inv.Quantity,
            i.Name,
            i.ItemTypeId,
            t.Name AS ItemTypeName,
            i.PriceGold,
            i.PriceSilver,
            i.PriceBronze,
            i.Rarity,
            wp.DamageMin,
            wp.DamageMax,
            wp.Durability AS WeaponDurability,
            wp.RequiredLevel AS WeaponRequiredLevel,
            wp.AttackSpeed,
            ap.Defense,
            ap.Durability AS ArmorDurability,
            ap.RequiredLevel AS ArmorRequiredLevel,
            pp.EffectType,
            pp.EffectValue,
            pp.DurationSeconds,
            mp.SpellDamage,
            mp.ManaCost,
            mp.ElementType,
            mp.RequiredLevel AS SpellRequiredLevel,
            mp.CooldownSeconds
         FROM Inventory inv
         JOIN Items i ON i.ItemId = inv.ItemId
         JOIN ItemTypes t ON t.ItemTypeId = i.ItemTypeId
         LEFT JOIN WeaponProperties wp ON wp.ItemId = i.ItemId
         LEFT JOIN ArmorProperties ap ON ap.ItemId = i.ItemId
         LEFT JOIN PotionProperties pp ON pp.ItemId = i.ItemId
         LEFT JOIN MagicSpellProperties mp ON mp.ItemId = i.ItemId
         WHERE inv.UserId = :user_id
         ORDER BY inv.InventoryId DESC"
    );
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de recuperer l\'inventaire.']);
}
