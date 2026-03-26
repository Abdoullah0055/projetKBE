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

$user = [
    'isConnected' => true,
    'id'          => $_SESSION['user']['id'],
    'alias'       => $_SESSION['user']['alias'] ?? 'Aventurier',
    'balance'     => [
        'gold'    => $_SESSION['user']['gold'] ?? 0,
        'silver'  => $_SESSION['user']['silver'] ?? 0,
        'bronze'  => $_SESSION['user']['bronze'] ?? 0
    ]
];

// 2. CONFIGURATION VISUELLE
$currentTheme = $_COOKIE['theme'] ?? 'dark';
$bgNum = $_COOKIE['bgNumber'] ?? '5';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// 3. RÉCUPÉRATION DES ITEMS
$stmt = $pdo->prepare("
    SELECT 
        ci.ItemId AS id, i.Name AS nom, i.PriceGold AS prix,
        ci.Quantity AS quantite, i.Stock AS stock_max, t.Name AS type
    FROM CartItems ci
    JOIN Carts c ON ci.CartId = c.CartId
    JOIN Items i ON ci.ItemId = i.ItemId
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    WHERE c.UserId = ?
");
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

$totalGeneral = 0;
$hasStockError = false;
foreach ($cartItems as $item) {
    $totalGeneral += ($item['prix'] * $item['quantite']);
    if ($item['quantite'] > $item['stock_max']) $hasStockError = true;
}

$title = "L'Arsenal - Ma Besace";
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

<div style="display:none;">
    <?php
    $preList = ["assets/img/archer.png", "assets/img/chevalier2.png", "assets/img/kratos.png", "assets/img/mage.png", "assets/img/samurai.png", "assets/img/viking.png", "assets/img/bull.png", "assets/img/dragon_slayer.png", "assets/img/elf.png", "assets/img/sparta.png", "assets/img/sultan.png", "assets/img/orc.png"];
    foreach ($preList as $img) echo "<img src='$img'>";
    ?>
</div>

<div class="page-banner banner-left">
    <div class="banner-scroll" id="leftBanner">
        <div class="banner-face face-front"><img src="assets/img/kratos.png" id="leftImgFront"></div>
        <div class="banner-face face-back"><img src="assets/img/archer.png" id="leftImgBack"></div>
    </div>
</div>

<div class="page-banner banner-right">
    <div class="banner-scroll" id="rightBanner">
        <div class="banner-face face-front"><img src="assets/img/mage.png" id="rightImgFront"></div>
        <div class="banner-face face-back"><img src="assets/img/orc.png" id="rightImgBack"></div>
    </div>
</div>

<main class="cart-page">
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart-box">
            <h2 style="font-size: 2rem; color: var(--accent);">Votre besace est vide...</h2>
            <p>Allez remplir votre équipement avant l'aventure !</p>
            <a href="index.php" class="btn-confirm" style="text-decoration:none; margin-top:20px; display:inline-block;">Retour à l'échoppe</a>
        </div>
    <?php else: ?>
        <div class="cart-title-box">
            <h2 style="margin:0;">Contenu de votre besace</h2>
        </div>

        <?php foreach ($cartItems as $item):
            $isOverstock = $item['quantite'] > $item['stock_max'];
        ?>
            <div class="cart-row <?= $isOverstock ? 'row-overstock' : '' ?>">
                <button class="btn-corbeille" onclick="deleteItemFromCart(<?= $item['id'] ?>, this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <div class="item-image-box"><?= getItemImage($item['type']) ?></div>

                <div class="item-name-box">
                    <?= htmlspecialchars($item['nom']) ?>
                    <div class="stock-alert-wrapper">
                        <?php if ($isOverstock): ?>
                            <div class="stock-alert"><i class="fa-solid fa-triangle-exclamation"></i> Max : <?= $item['stock_max'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="qty-controls" data-item-id="<?= $item['id'] ?>" data-prix="<?= $item['prix'] ?>" data-stock-max="<?= $item['stock_max'] ?>">
                    <button class="btn-qty btn-minus">-</button>
                    <div class="qty-val <?= $isOverstock ? 'text-danger-pulse' : '' ?>"><?= $item['quantite'] ?></div>
                    <button class="btn-qty btn-plus">+</button>
                </div>

                <div class="item-total-box"><?= number_format($item['prix'] * $item['quantite'], 0) ?> GP</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div id="custom-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><i class="fa-solid fa-scroll"></i>
                <h3 id="modal-title">Message</h3>
            </div>
            <div class="modal-body">
                <p id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button id="modal-btn-cancel" class="btn-secondary">Annuler</button>
                <button id="modal-btn-confirm" class="btn-confirm">Confirmer</button>
            </div>
        </div>
    </div>
</main>

<?php if (!empty($cartItems)): ?>
    <div class="cart-footer-actions">
        <a href="index.php" class="btn-return"><i class="fa-solid fa-arrow-left"></i> Continuer mes achats</a>
        <div class="total-summary">TOTAL : <?= number_format($totalGeneral, 0) ?> GP</div>
        <button class="btn-confirm" id="btn-confirm-purchase" <?= $hasStockError ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?> onclick="processPurchase()">
            <?= $hasStockError ? 'Stock insuffisant' : "Confirmer l'achat" ?>
        </button>
    </div>
<?php endif; ?>

<script>
    // --- GESTION DES BANNIÈRES (LOGIQUE 3D RALENTIE) ---
    const leftImages = ["assets/img/archer.png", "assets/img/chevalier2.png", "assets/img/kratos.png", "assets/img/mage.png", "assets/img/samurai.png", "assets/img/viking.png"];
    const rightImages = ["assets/img/bull.png", "assets/img/dragon_slayer.png", "assets/img/elf.png", "assets/img/sparta.png", "assets/img/sultan.png", "assets/img/orc.png"];

    let l_Idx = 2;
    let l_isFlipped = false;
    let r_Idx = 3;
    let r_isFlipped = false;

    function handleFlip(bannerId, imgFrontId, imgBackId, list, currentIndex, isFlipped) {
        const banner = document.getElementById(bannerId);
        const frontImg = document.getElementById(imgFrontId);
        const backImg = document.getElementById(imgBackId);

        let nextIdx = (currentIndex + 1) % list.length;

        // Pré-chargement sur la face opposée
        if (isFlipped) frontImg.src = list[nextIdx];
        else backImg.src = list[nextIdx];

        // Petit délai pour laisser le navigateur initier le rendu de l'image
        setTimeout(() => {
            banner.classList.toggle('is-flipped');
        }, 30);

        return {
            nextIdx,
            nextFlipped: !isFlipped
        };
    }

    document.getElementById('leftBanner').onclick = () => {
        const res = handleFlip('leftBanner', 'leftImgFront', 'leftImgBack', leftImages, l_Idx, l_isFlipped);
        l_Idx = res.nextIdx;
        l_isFlipped = res.nextFlipped;
    };

    document.getElementById('rightBanner').onclick = () => {
        const res = handleFlip('rightBanner', 'rightImgFront', 'rightImgBack', rightImages, r_Idx, r_isFlipped);
        r_Idx = res.nextIdx;
        r_isFlipped = res.nextFlipped;
    };

    // --- GESTION DU PANIER (QUANTITÉS) ---
    function updateCartState() {
        let grandTotal = 0;
        let hasGlobalError = false;
        const confirmBtn = document.getElementById('btn-confirm-purchase');

        document.querySelectorAll('.cart-row').forEach(row => {
            const priceText = row.querySelector('.item-total-box').innerText;
            grandTotal += parseFloat(priceText.replace(/[^0-9.-]+/g, ""));
            if (row.classList.contains('row-overstock')) hasGlobalError = true;
        });

        if (document.querySelector('.total-summary'))
            document.querySelector('.total-summary').innerText = `TOTAL : ${grandTotal.toLocaleString()} GP`;

        if (confirmBtn) {
            confirmBtn.disabled = hasGlobalError;
            confirmBtn.style.opacity = hasGlobalError ? "0.5" : "1";
            confirmBtn.innerText = hasGlobalError ? "Stock insuffisant" : "Confirmer l'achat";
        }
    }

    document.querySelectorAll('.qty-controls').forEach(control => {
        control.addEventListener('click', async (e) => {
            const isPlus = e.target.classList.contains('btn-plus');
            const isMinus = e.target.classList.contains('btn-minus');
            if (!isPlus && !isMinus) return;

            const qtyValDiv = control.querySelector('.qty-val');
            const row = control.closest('.cart-row');
            let newQty = parseInt(qtyValDiv.innerText) + (isPlus ? 1 : -1);
            if (newQty < 0) return;

            const formData = new FormData();
            formData.append('item_id', control.dataset.itemId);
            formData.append('new_qty', newQty);

            const response = await fetch('backend/modifier_quantite.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (newQty === 0) {
                    row.style.transform = "translateX(50px)";
                    row.style.opacity = "0";
                    setTimeout(() => {
                        row.remove();
                        if (!document.querySelector('.cart-row')) location.reload();
                        updateCartState();
                    }, 300);
                } else {
                    qtyValDiv.innerText = newQty;
                    row.querySelector('.item-total-box').innerText = (newQty * control.dataset.prix).toLocaleString() + " GP";
                    const isOver = newQty > control.dataset.stockMax;
                    row.classList.toggle('row-overstock', isOver);
                    qtyValDiv.classList.toggle('text-danger-pulse', isOver);
                    row.querySelector('.stock-alert-wrapper').innerHTML = isOver ? `<div class="stock-alert"><i class="fa-solid fa-triangle-exclamation"></i> Max : ${control.dataset.stockMax}</div>` : "";
                    updateCartState();
                }
            }
        });
    });

    function showCustomConfirm(message, title = "Confirmation") {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-modal');
            document.getElementById('modal-message').innerText = message;
            document.getElementById('modal-title').innerText = title;
            modal.style.display = 'flex';
            document.getElementById('modal-btn-confirm').onclick = () => {
                modal.style.display = 'none';
                resolve(true);
            };
            document.getElementById('modal-btn-cancel').onclick = () => {
                modal.style.display = 'none';
                resolve(false);
            };
        });
    }

    async function deleteItemFromCart(itemId, button) {
        if (await showCustomConfirm("Retirer cet objet de votre besace ?", "Suppression")) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            const response = await fetch('backend/supprimer_item_panier.php', {
                method: 'POST',
                body: formData
            });
            if ((await response.json()).success) {
                const row = button.closest('.cart-row');
                row.style.opacity = "0";
                setTimeout(() => {
                    row.remove();
                    if (!document.querySelector('.cart-row')) location.reload();
                    updateCartState();
                }, 300);
            }
        }
    }

    async function processPurchase() {
        if (await showCustomConfirm("Sceller l'échange et dépenser vos pièces ?", "Transaction")) {
            const btn = document.getElementById('btn-confirm-purchase');
            btn.disabled = true;
            btn.innerText = "Échange conclu !";
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        }
    }
</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>