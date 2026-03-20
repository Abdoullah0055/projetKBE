<?php
require_once __DIR__ . '/AlgosBD.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'id' => $_SESSION['user']['id'],
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage'),
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        ci.ItemId AS id,
        i.Name AS nom,
        i.PriceGold AS prix,
        ci.Quantity AS quantite,
        i.Stock AS stock_max
    FROM CartItems ci
    JOIN Carts c ON ci.CartId = c.CartId
    JOIN Items i ON ci.ItemId = i.ItemId
    WHERE c.UserId = ?
");
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

foreach ($cartItems as &$item) {
    $item['image'] = '📦';
}
unset($item);

$totalGeneral = 0;
foreach ($cartItems as $item) {
    $totalGeneral += ($item['prix'] * $item['quantite']);
}

$title = "L'Arsenal - Ma Besace";
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<link rel="stylesheet" href="css/panier.css">
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="cart-page">
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart-box">
            <h2>Votre besace est vide...</h2>
            <p>Allez remplir votre équipement avant l'aventure !</p>
            <a href="index.php" class="btn-return">Retour à l'échoppe</a>
        </div>
    <?php else: ?>
        <div class="cart-title-box">
            <h2>Contenu de votre besace</h2>
        </div>

        <?php foreach ($cartItems as $item): ?>
            <div class="cart-row">
                <button class="btn-corbeille" title="Retirer l'objet">corbeille</button>

                <div class="item-image-box">
                    <?= $item['image'] ?>
                </div>

                <div class="item-name-box">
                    <?= $item['nom'] ?>
                </div>

                <div class="qty-controls">
                    <button class="btn-qty">+</button>
                    <div class="qty-val"><?= $item['quantite'] ?></div>
                    <button class="btn-qty">-</button>
                </div>

                <div class="item-total-box">
                    <?= number_format($item['prix'] * $item['quantite'], 0) ?> GP
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php if (!empty($cartItems)): ?>
    <div class="cart-footer-actions">
        <a href="index.php" class="btn-return">retour page principale</a>

        <div class="total-summary">
            TOTAL : <?= number_format($totalGeneral, 0) ?> GP
        </div>

        <button class="btn-confirm" onclick="alert('Transaction validée par le marchand !')">
            confirmer l'achat
        </button>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>