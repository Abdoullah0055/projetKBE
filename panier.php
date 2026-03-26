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

// En haut de panier.php, après avoir récupéré $cartItems
$hasStockError = false;
foreach ($cartItems as $item) {
    if ($item['quantite'] > $item['stock_max']) {
        $hasStockError = true;
        break;
    }
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

        <?php foreach ($cartItems as $item):
            // Vérification du stock
            $isOverstock = $item['quantite'] > $item['stock_max'];
        ?>
            <div class="cart-row <?= $isOverstock ? 'row-overstock' : '' ?>">
                <button class="btn-corbeille" title="Retirer l'objet">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <div class="item-image-box">
                    <?= getItemImage($item['type']) ?>
                </div>

                <div class="item-name-box">
                    <?= htmlspecialchars($item['nom']) ?>
                    <div class="stock-alert-wrapper">
                        <?php if ($isOverstock): ?>
                            <div class="stock-alert">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Quantité max : <?= $item['stock_max'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="qty-controls"
                    data-item-id="<?= $item['id'] ?>"
                    data-prix="<?= $item['prix'] ?>"
                    data-stock-max="<?= $item['stock_max'] ?>">
                    <button class="btn-qty btn-minus">-</button>
                    <div class="qty-val <?= $isOverstock ? 'text-danger-pulse' : '' ?>">
                        <?= $item['quantite'] ?>
                    </div>
                    <button class="btn-qty btn-plus">+</button>
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

        <button class="btn-confirm"
            <?= $hasStockError ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>
            onclick="alert('L\'échange est conclu !')">
            <?= $hasStockError ? 'Stock insuffisant' : 'Confirmer l\'achat' ?>
        </button>
    </div>
<?php endif; ?>

<script>
    document.querySelectorAll('.qty-controls').forEach(control => {
        const itemId = control.dataset.itemId;
        const prix = parseFloat(control.dataset.prix);
        const stockMax = parseInt(control.dataset.stockMax);

        const qtyValDiv = control.querySelector('.qty-val');
        const row = control.closest('.cart-row');
        const totalItemBox = row.querySelector('.item-total-box');
        const alertWrapper = row.querySelector('.stock-alert-wrapper');

        control.addEventListener('click', async (e) => {
            const isPlus = e.target.classList.contains('btn-plus');
            const isMinus = e.target.classList.contains('btn-minus');
            if (!isPlus && !isMinus) return;

            let currentQty = parseInt(qtyValDiv.innerText);
            let newQty = isPlus ? currentQty + 1 : currentQty - 1;
            if (newQty < 0) return;

            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('new_qty', newQty);

            try {
                const response = await fetch('backend/modifier_quantite.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    if (newQty === 0) {
                        row.style.transform = "translateX(100px)";
                        row.style.opacity = "0";
                        setTimeout(() => {
                            row.remove();
                            if (document.querySelectorAll('.cart-row').length === 0) location.reload();
                            updateCartState();
                        }, 300);
                    } else {
                        // 1. Mise à jour des chiffres
                        qtyValDiv.innerText = newQty;
                        totalItemBox.innerText = (newQty * prix).toLocaleString() + " GP";

                        // 2. GESTION DYNAMIQUE DE L'ERREUR DE STOCK
                        if (newQty > stockMax) {
                            row.classList.add('row-overstock');
                            qtyValDiv.classList.add('text-danger-pulse');
                            // On ajoute l'alerte si elle n'existe pas déjà
                            alertWrapper.innerHTML = `
                            <div class="stock-alert">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Quantité max : ${stockMax}
                            </div>`;
                        } else {
                            row.classList.remove('row-overstock');
                            qtyValDiv.classList.remove('text-danger-pulse');
                            alertWrapper.innerHTML = ""; // On efface l'erreur instantanément
                        }

                        updateCartState();
                    }
                }
            } catch (error) {
                console.error("Erreur:", error);
            }
        });
    });

    function updateCartState() {
        let grandTotal = 0;
        let hasGlobalError = false;
        const confirmBtn = document.querySelector('.btn-confirm');

        document.querySelectorAll('.cart-row').forEach(row => {
            // Recalcul du Total
            const priceText = row.querySelector('.item-total-box').innerText;
            grandTotal += parseFloat(priceText.replace(/[^0-9.-]+/g, ""));

            // Vérification si au moins une ligne est en erreur
            if (row.classList.contains('row-overstock')) {
                hasGlobalError = true;
            }
        });

        document.querySelector('.total-summary').innerText = `TOTAL : ${grandTotal.toLocaleString()} GP`;

        // Activation/Désactivation du bouton
        if (hasGlobalError) {
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = "0.5";
            confirmBtn.style.cursor = "not-allowed";
            confirmBtn.innerText = "Stock insuffisant";
        } else {
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = "1";
            confirmBtn.style.cursor = "pointer";
            confirmBtn.innerText = "Confirmer l'achat";
        }
    }
</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>