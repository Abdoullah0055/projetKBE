<?php
require_once __DIR__ . '/AlgosBD.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

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
    $user = [
        'isConnected' => false,
        'alias' => '',
        'isMage' => false,
        'balance' => [
            'gold' => 0,
            'silver' => 0,
            'bronze' => 0
        ]
    ];
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        i.ItemId AS id,
        i.Name AS nom,
        i.PriceGold AS prix,
        i.Description AS description,
        i.Stock AS stock,
        t.Name AS type,
        IFNULL(AVG(r.Rating), 0) AS rating,
        COUNT(r.ReviewId) AS nb_avis
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

$item['image'] = '⚔️';

$title = "L'Arsenal - " . $item['nom'];
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<link rel="stylesheet" href="css/details.css">
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="details-page">
    <div class="visual-column">
        <div class="item-card-main"><?= $item['image'] ?></div>

        <?php if ($user['isConnected']): ?>
            <div class="cloud-info">
                <div class="stars">★ ★ ★ ★ ☆</div>
                <div class="reviews-text"><?= $item['nb_avis'] ?> aventuriers</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="info-column">
        <div class="item-header">
            <div>
                <h2><?= $item['nom'] ?></h2>

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
            <p><?= $item['description'] ?></p>
        </div>

        <?php if ($user['isConnected']): ?>
            <div class="comments-box">
                <h4>Informations</h4>
                <p>Type : <?= $item['type'] ?></p>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>