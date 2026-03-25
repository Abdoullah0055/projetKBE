<?php
require_once 'AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pdo = get_pdo();

// 1. RÉCUPÉRATION DU THÈME POUR LA CONTINUITÉ
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// Gestion de l'utilisateur
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage'),
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    $user = ['isConnected' => false, 'alias' => '', 'isMage' => false, 'balance' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]];
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Récupération de l'item
$stmt = $pdo->prepare("
    SELECT i.ItemId AS id, i.Name AS nom, i.PriceGold AS prix, i.Description AS description,
           i.Stock AS stock, t.Name AS type, IFNULL(AVG(r.Rating), 0) AS rating, COUNT(r.ReviewId) AS nb_avis
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.ItemId = ?
    GROUP BY i.ItemId, i.Name, i.PriceGold, i.Description, i.Stock, t.Name
");
$stmt->execute([$_GET['id']]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: index.php");
    exit();
}

// Mapping des icônes
$icons = ['arme' => '⚔️', 'armure' => '🛡️', 'potion' => '🧪', 'sort' => '✨'];
$item['image'] = $icons[strtolower($item['type'])] ?? '❓';

$title = "Détails - " . $item['nom'];
?>

<?php /* 2. INJECTION DU STYLE AVANT LE HEAD POUR ÉCRASER LES CSS EXTERNES */ ?>
<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e !important;
        /* On évite le gris de details.css */
    }
</style>

<?php include __DIR__ . '/templates/head.php'; ?>
<link rel="stylesheet" href="assets/css/details.css">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="details-page">
    <div class="visual-column">
        <div class="item-card-main" style="background: rgba(20, 22, 25, 0.65); backdrop-filter: blur(12px);">
            <?= $item['image'] ?>
        </div>

        <?php if ($user['isConnected']): ?>
            <div class="cloud-info" style="background: rgba(20, 22, 25, 0.65); backdrop-filter: blur(12px);">
                <div class="stars">★ ★ ★ ★ ☆</div>
                <div class="reviews-text"><?= $item['nb_avis'] ?> aventuriers</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="info-column">
        <div class="item-header">
            <div>
                <h2><?= htmlspecialchars($item['nom']) ?></h2>
                <?php if ($item['stock'] > 0): ?>
                    <div class="stock-indicator">En stock : <?= $item['stock'] ?></div>
                <?php else: ?>
                    <div class="stock-indicator stock-empty">Rupture de stock</div>
                <?php endif; ?>
            </div>
            <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>
        </div>

        <div class="item-section">
            <h3>Propriétés</h3>
            <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
        </div>

        <?php if ($user['isConnected']): ?>
            <div class="comments-box" style="background: rgba(20, 22, 25, 0.65); backdrop-filter: blur(12px);">
                <h4>Informations</h4>
                <p>Type : <?= htmlspecialchars($item['type']) ?></p>
                <p>Nombre d'avis : <?= $item['nb_avis'] ?></p>
            </div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="index.php">← Retour au catalogue</a>
            <?php if ($item['stock'] > 0): ?>
                <button class="btn-add">Ajouter au panier</button>
            <?php else: ?>
                <button class="btn-disabled" disabled>Épuisé</button>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>