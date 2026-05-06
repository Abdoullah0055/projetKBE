<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/item_heal_helpers.php';
require_once __DIR__ . '/../includes/business_logic.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecte']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item invalide']);
    exit;
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT inv.InventoryId, inv.Quantity, inv.ItemId, i.Name, i.ItemTypeId, i.IsActive, t.Name AS ItemTypeName
         FROM Inventory inv
         JOIN Items i ON i.ItemId = inv.ItemId
         JOIN ItemTypes t ON t.ItemTypeId = i.ItemTypeId
         WHERE inv.UserId = :userId AND inv.ItemId = :itemId
         FOR UPDATE"
    );
    $stmt->execute(['userId' => $userId, 'itemId' => $itemId]);
    $invItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invItem) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item non trouve dans l\'inventaire']);
        exit;
    }

    if (!(bool) $invItem['isactive']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item non actif']);
        exit;
    }

    $itemTypeName = mb_strtolower((string) ($invItem['itemtypename'] ?? ''), 'UTF-8');
    if ($itemTypeName !== 'potion' && $itemTypeName !== 'magicspell') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Seules les potions et les sorts peuvent etre utilises']);
        exit;
    }

    $healAmount = get_item_heal_amount($itemId);
    if ($healAmount <= 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cet item ne peut pas soigner.']);
        exit;
    }

    $userStmt = $pdo->prepare('SELECT CurrentHP, MaxHP FROM Users WHERE UserId = ? FOR UPDATE');
    $userStmt->execute([$userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);

    $currentHp = (int) ($userRow['currenthp'] ?? 0);
    $maxHp = (int) ($userRow['maxhp'] ?? 100);

    if ($currentHp >= $maxHp) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vos PV sont deja au maximum']);
        exit;
    }

    if ((int) $invItem['quantity'] > 1) {
        $updStmt = $pdo->prepare('UPDATE Inventory SET Quantity = Quantity - 1 WHERE UserId = ? AND ItemId = ?');
        $updStmt->execute([$userId, $itemId]);
    } else {
        $delStmt = $pdo->prepare('DELETE FROM Inventory WHERE UserId = ? AND ItemId = ?');
        $delStmt->execute([$userId, $itemId]);
    }

    $healing = compute_new_hp($currentHp, $maxHp, $healAmount);

    $healStmt = $pdo->prepare('UPDATE Users SET CurrentHP = ? WHERE UserId = ?');
    $healStmt->execute([(int) $healing['new_hp'], $userId]);

    $pdo->commit();

    $_SESSION['user']['hp'] = (int) $healing['new_hp'];
    $_SESSION['user']['max_hp'] = $maxHp;

    $itemConsumed = ((int) $invItem['quantity'] <= 1);

    echo json_encode([
        'success' => true,
        'message' => 'Utilise ! Vous regagnez ' . (int) $healing['effective_heal'] . ' PV',
        'new_hp' => (int) $healing['new_hp'],
        'max_hp' => $maxHp,
        'heal_amount' => $healAmount,
        'effective_heal' => (int) $healing['effective_heal'],
        'wasted_heal' => (int) $healing['wasted_heal'],
        'item_consumed' => $itemConsumed,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'utilisation']);
}
