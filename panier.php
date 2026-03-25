<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

// 1. PROTECTION DE LA PAGE
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Définition de l'utilisateur pour le header
$user = [
    'isConnected' => true,
    'id'          => $_SESSION['user']['id'],
    'alias'       => $_SESSION['user']['alias'],
    'isMage'      => ($_SESSION['user']['role'] === 'Mage'),
    'balance'     => [
        'gold'    => $_SESSION['user']['gold'],
        'silver'  => $_SESSION['user']['silver'],
        'bronze'  => $_SESSION['user']['bronze']
    ]
];

// 2. RÉCUPÉRATION DU THÈME ET DE L'IMAGE DE FOND
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// 3. RÉCUPÉRATION DES ITEMS DU PANIER
$stmt = $pdo->prepare("
    SELECT 
        ci.ItemId AS id,
        i.Name AS nom,
        i.PriceGold AS prix,
        ci.Quantity AS quantite,
        i.Stock AS stock_max,
        t.Name AS type
    FROM CartItems ci
    JOIN Carts c ON ci.CartId = c.CartId
    JOIN Items i ON ci.ItemId = i.ItemId
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    WHERE c.UserId = ?
");
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

$totalGeneral = 0;
foreach ($cartItems as $item) {
    $totalGeneral += ($item['prix'] * $item['quantite']);
}

$title = "L'Arsenal - Ma Besace";

// --- DÉBUT DU RENDU HTML ---

// Inclusion du head (Contient <!DOCTYPE>, <html>, <head> et l'ouverture du <body>)
include __DIR__ . '/templates/head.php';
?>

<style>
    body {
        background-image: url('<?= $bgImage ?>') !important;
        --main-bg: url('<?= $bgImage ?>') !important;
    }
</style>

<link rel="stylesheet" href="assets/css/panier.css">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="cart-page">
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart-box">
            <h2 style="font-size: 2rem; color: var(--accent);">Votre besace est vide...</h2>
            <p style="margin: 20px 0;">Allez remplir votre équipement avant l'aventure !</p>
            <br>
            <a href="index.php" class="btn-confirm" style="text-decoration:none;">Retour à l'échoppe</a>
        </div>
    <?php else: ?>
        <div class="cart-title-box">
            <h2 style="margin:0;">Contenu de votre besace</h2>
        </div>

        <?php foreach ($cartItems as $item): ?>
            <div class="cart-row">
                <button class="btn-corbeille" title="Retirer l'objet">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <div class="item-image-box">
                    <?= getItemImage($item['type']) ?>
                </div>

                <div class="item-name-box">
                    <?= htmlspecialchars($item['nom']) ?>
                </div>

                <div class="qty-controls">
                    <button class="btn-qty">-</button>
                    <div class="qty-val"><?= $item['quantite'] ?></div>
                    <button class="btn-qty">+</button>
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
        <a href="index.php" class="btn-return">
            <i class="fa-solid fa-arrow-left"></i> Continuer mes achats
        </a>

        <div class="total-summary">
            TOTAL : <?= number_format($totalGeneral, 0) ?> GP
        </div>

        <button class="btn-confirm" onclick="alert('L\'échange est conclu ! Vos pièces d\'or ont été prélevées.')">
            Confirmer l'achat
        </button>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>