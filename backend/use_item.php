<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecte']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate()) {
    echo json_encode(['success' => false, 'message' => 'Token de securite invalide.']);
    exit;
}

$userId = $_SESSION['user']['id'];
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item invalide']);
    exit;
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT inv.InventoryId, inv.Quantity, inv.ItemId, i.Name, i.ItemTypeId, i.IsActive
        FROM Inventory inv
        JOIN Items i ON i.ItemId = inv.ItemId
        WHERE inv.UserId = :userId AND inv.ItemId = :itemId
        FOR UPDATE");
    $stmt->execute(['userId' => $userId, 'itemId' => $itemId]);
    $invItem = $stmt->fetch();

    if (!$invItem) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item non trouve dans l\'inventaire']);
        exit;
    }

    if (!$invItem['isactive']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item non actif']);
        exit;
    }

    $healAmount = 0;
    $itemTypeName = '';

    $typeStmt = $pdo->prepare("SELECT Name FROM ItemTypes WHERE ItemTypeId = ?");
    $typeStmt->execute([$invItem['itemtypeid']]);
    $typeRow = $typeStmt->fetch();
    $itemTypeName = $typeRow ? $typeRow['name'] : '';

    if ($itemTypeName === 'Potion') {
        $propStmt = $pdo->prepare("SELECT EffectValue FROM PotionProperties WHERE ItemId = ?");
        $propStmt->execute([$itemId]);
        $propRow = $propStmt->fetch();
        $healAmount = $propRow ? min((int)$propRow['effectvalue'], 5) : 3;
    } elseif ($itemTypeName === 'MagicSpell') {
        $propStmt = $pdo->prepare("SELECT SpellDamage FROM MagicSpellProperties WHERE ItemId = ?");
        $propStmt->execute([$itemId]);
        $propRow = $propStmt->fetch();
        $healAmount = $propRow ? max((int)floor($propRow['spelldamage'] / 2), 3) : 3;
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Seules les potions et les sorts peuvent etre utilises']);
        exit;
    }

    $userStmt = $pdo->prepare("SELECT CurrentHP, MaxHP FROM Users WHERE UserId = ? FOR UPDATE");
    $userStmt->execute([$userId]);
    $userRow = $userStmt->fetch();

    if ((int)$userRow['currenthp'] >= (int)$userRow['maxhp']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vos PV sont deja au maximum']);
        exit;
    }

    if ((int)$invItem['quantity'] > 1) {
        $updStmt = $pdo->prepare("UPDATE Inventory SET Quantity = Quantity - 1 WHERE UserId = ? AND ItemId = ?");
        $updStmt->execute([$userId, $itemId]);
    } else {
        $delStmt = $pdo->prepare("DELETE FROM Inventory WHERE UserId = ? AND ItemId = ?");
        $delStmt->execute([$userId, $itemId]);
    }

    $newHP = min((int)$userRow['currenthp'] + $healAmount, (int)$userRow['maxhp']);
    $healStmt = $pdo->prepare("UPDATE Users SET CurrentHP = ? WHERE UserId = ?");
    $healStmt->execute([$newHP, $userId]);

    $pdo->commit();

    $_SESSION['user']['hp'] = $newHP;
    $_SESSION['user']['max_hp'] = (int)$userRow['maxhp'];

    $itemConsumed = ((int)$invItem['quantity'] <= 1);

    echo json_encode([
        'success' => true,
        'message' => 'Utilise ! Vous regagnez ' . $healAmount . ' PV',
        'new_hp' => $newHP,
        'max_hp' => (int)$userRow['maxhp'],
        'heal_amount' => $healAmount,
        'item_consumed' => $itemConsumed
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'utilisation']);
}
