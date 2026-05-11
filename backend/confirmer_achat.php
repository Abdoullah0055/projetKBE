<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

function respond_json(array $payload): void
{
    echo json_encode($payload);
    exit;
}

function fail_checkout(PDO $pdo, string $message, string $code): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond_json([
        'success' => false,
        'message' => $message,
        'code' => $code,
    ]);
}

if (!isset($_SESSION['user']['id'])) {
    respond_json([
        'success' => false,
        'message' => 'Session utilisateur invalide.',
        'code' => 'session',
    ]);
}

$userId = (int)$_SESSION['user']['id'];
$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare(
    "SELECT UserId, Gold, Silver, Bronze, Role
        FROM Users
        WHERE UserId = :userId
        FOR UPDATE"
    );
    $userStmt->execute([':userId' => $userId]);
    $userRow = $userStmt->fetch();

    if (!$userRow) {
        fail_checkout($pdo, 'Utilisateur introuvable.', 'session');
    }

    $cartStmt = $pdo->prepare(
        "SELECT CartId
         FROM Carts
         WHERE UserId = :userId
         LIMIT 1
         FOR UPDATE"
    );
    $cartStmt->execute([':userId' => $userId]);
    $cartId = (int)$cartStmt->fetchColumn();

    if ($cartId <= 0) {
        fail_checkout($pdo, 'Votre panier est vide.', 'empty_cart');
    }

    $cartItemsStmt = $pdo->prepare(
        "SELECT
            ci.ItemId,
            ci.Quantity,
            i.PriceGold,
            i.PriceSilver,
            i.PriceBronze,
            i.Stock,
            i.IsActive
         FROM CartItems ci
         JOIN Items i ON i.ItemId = ci.ItemId
         WHERE ci.CartId = :cartId
         FOR UPDATE"
    );
    $cartItemsStmt->execute([':cartId' => $cartId]);
    $cartItems = $cartItemsStmt->fetchAll();

if (empty($cartItems)) {
    fail_checkout($pdo, 'Votre panier est vide.', 'empty_cart');
}

$userRole = $userRow['role'] ?? 'Player';
$isMage = ($userRole === 'Mage');

if (!$isMage) {
    $spellTypeStmt = $pdo->prepare(
        "SELECT 1 FROM CartItems ci JOIN Items i ON i.ItemId = ci.ItemId JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId WHERE ci.CartId = :cartId AND t.Name = 'MagicSpell' LIMIT 1"
    );
    $spellTypeStmt->execute([':cartId' => $cartId]);

    if ($spellTypeStmt->fetchColumn()) {
        fail_checkout($pdo, 'Seuls les mages peuvent acheter des sorts.', 'spell_restricted');
    }
}

$totalGold = 0;
    $totalSilver = 0;
    $totalBronze = 0;

    foreach ($cartItems as $item) {
$quantity = (int)$item['quantity'];
    $stock = (int)$item['stock'];
    $isActive = (int)$item['isactive'] === 1;

        if (!$isActive) {
            fail_checkout($pdo, 'Un article de votre panier n\'est plus disponible.', 'inactive_item');
        }

        if ($quantity <= 0 || $quantity > $stock) {
            fail_checkout($pdo, 'Stock insuffisant pour finaliser l\'achat.', 'stock_insufficient');
        }

        $totalGold += (int)$item['pricegold'] * $quantity;
        $totalSilver += (int)$item['pricesilver'] * $quantity;
        $totalBronze += (int)$item['pricebronze'] * $quantity;
    }

$userGold = (int)$userRow['gold'];
$userSilver = (int)$userRow['silver'];
$userBronze = (int)$userRow['bronze'];

$totalInBronze = $totalGold * 100 + $totalSilver * 10 + $totalBronze;
$userInBronze = $userGold * 100 + $userSilver * 10 + $userBronze;

if ($userInBronze < $totalInBronze) {
    fail_checkout($pdo, 'Solde insuffisant pour finaliser l\'achat.', 'balance_insufficient');
}

$remainInBronze = $userInBronze - $totalInBronze;
$newGold = intdiv($remainInBronze, 100);
$remainInBronze = $remainInBronze % 100;
$newSilver = intdiv($remainInBronze, 10);
$newBronze = $remainInBronze % 10;

    $orderStmt = $pdo->prepare(
        "INSERT INTO Orders (UserId, TotalGold, TotalSilver, TotalBronze)
         VALUES (:userId, :totalGold, :totalSilver, :totalBronze)"
    );
    $orderStmt->execute([
        ':userId' => $userId,
        ':totalGold' => $totalGold,
        ':totalSilver' => $totalSilver,
        ':totalBronze' => $totalBronze,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $orderItemStmt = $pdo->prepare(
        "INSERT INTO OrderItems (OrderId, ItemId, Quantity, PriceGold, PriceSilver, PriceBronze)
         VALUES (:orderId, :itemId, :quantity, :priceGold, :priceSilver, :priceBronze)"
    );

    $stockStmt = $pdo->prepare(
        "UPDATE Items
         SET Stock = Stock - :quantity
         WHERE ItemId = :itemId"
    );

    $inventoryStmt = $pdo->prepare(
        "INSERT INTO Inventory (UserId, ItemId, Quantity)
         VALUES (:userId, :itemId, :quantity)
         ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity)"
    );

    foreach ($cartItems as $item) {
        $itemId = (int)$item['itemid'];
        $quantity = (int)$item['quantity'];

        $orderItemStmt->execute([
            ':orderId' => $orderId,
            ':itemId' => $itemId,
            ':quantity' => $quantity,
            ':priceGold' => (int)$item['pricegold'],
            ':priceSilver' => (int)$item['pricesilver'],
            ':priceBronze' => (int)$item['pricebronze'],
        ]);

        $stockStmt->execute([
            ':quantity' => $quantity,
            ':itemId' => $itemId,
        ]);

        $inventoryStmt->execute([
            ':userId' => $userId,
            ':itemId' => $itemId,
            ':quantity' => $quantity,
        ]);
    }

    $clearCartStmt = $pdo->prepare("DELETE FROM CartItems WHERE CartId = :cartId");
    $clearCartStmt->execute([':cartId' => $cartId]);

$debitStmt = $pdo->prepare(
    "UPDATE Users
    SET Gold = :gold, Silver = :silver, Bronze = :bronze
    WHERE UserId = :userId"
);
$debitStmt->execute([
    ':gold' => $newGold,
    ':silver' => $newSilver,
    ':bronze' => $newBronze,
    ':userId' => $userId,
]);

$pdo->commit();

$_SESSION['user']['gold'] = $newGold;
$_SESSION['user']['silver'] = $newSilver;
$_SESSION['user']['bronze'] = $newBronze;

respond_json([
    'success' => true,
    'message' => 'Achat confirme avec succes.',
    'order_id' => $orderId,
    'totals' => [
        'gold' => $totalGold,
        'silver' => $totalSilver,
        'bronze' => $totalBronze,
    ],
    'old_balance' => [
        'gold' => $userGold,
        'silver' => $userSilver,
        'bronze' => $userBronze,
    ],
    'new_balance' => [
        'gold' => $newGold,
        'silver' => $newSilver,
        'bronze' => $newBronze,
    ],
]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond_json([
        'success' => false,
        'message' => 'Erreur interne pendant la transaction.',
        'code' => 'transaction_error',
    ]);
}
