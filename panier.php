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
<link rel="stylesheet" href="assets/css/panier.css?v=<?= time() ?>">
<?php include __DIR__ . '/includes/header.php'; ?>

<?php
$leftImages = [
    'archer.png',
    'chevalier2.png',
    'kratos.png',
    'mage.png',
    'samurai.png',
    'viking.png'
];

$rightImages = [
    'bull.png',
    'dragon_slayer.png',
    'elf.png',
    'sparta.png',
    'sultan.png',
    'orc.png'
];
?>


<div class="page-banner banner-left">
    <div class="banner-scroll banner-clickable" id="leftBanner">
        <img src="assets/img/kratos.png" alt="Déco Gauche" id="leftBannerImg">
    </div>
</div>

<div class="page-banner banner-right">
    <div class="banner-scroll banner-clickable" id="rightBanner">
        <img src="assets/img/mage.png" alt="Déco Droite" id="rightBannerImg">
    </div>
</div>

<script>
    const leftImages = [
        "assets/img/archer.png",
        "assets/img/chevalier2.png",
        "assets/img/kratos.png",
        "assets/img/mage.png",
        "assets/img/samurai.png",
        "assets/img/viking.png"
    ];

    const rightImages = [
        "assets/img/bull.png",
        "assets/img/dragon_slayer.png",
        "assets/img/elf.png",
        "assets/img/sparta.png",
        "assets/img/sultan.png",
        "assets/img/orc.png"
    ];

    const leftColors = [
        "#ff5a1f",
        "#ff7b00",
        "#ff2d55",
        "#ffd000",
        "#ff3c00",
        "#ff00a8"
    ];

    const rightColors = [
        "#00cfff",
        "#1e90ff",
        "#7b2cff",
        "#00ffea",
        "#8a7dff",
        "#4dd2ff"
    ];

    let leftIndex = 0;
    let rightIndex = 0;

    const leftBanner = document.getElementById("leftBanner");
    const rightBanner = document.getElementById("rightBanner");
    const leftBannerImg = document.getElementById("leftBannerImg");
    const rightBannerImg = document.getElementById("rightBannerImg");

    function setLeftColor(color) {
        document.documentElement.style.setProperty("--banner-left-color", color);
    }

    function setRightColor(color) {
        document.documentElement.style.setProperty("--banner-right-color", color);
    }

    function playFireEffect() {
        leftBanner.classList.remove("fire-animate");
        void leftBanner.offsetWidth;
        leftBanner.classList.add("fire-animate");
    }

    function playElectricEffect() {
        rightBanner.classList.remove("electric-animate");
        void rightBanner.offsetWidth;
        rightBanner.classList.add("electric-animate");
    }

    leftBanner.addEventListener("click", () => {
        leftIndex++;
        if (leftIndex >= leftImages.length) {
            leftIndex = 0;
        }

        setLeftColor(leftColors[leftIndex]);
        playFireEffect();

        setTimeout(() => {
            leftBannerImg.src = leftImages[leftIndex];
        }, 120);
    });

    rightBanner.addEventListener("click", () => {
        rightIndex++;
        if (rightIndex >= rightImages.length) {
            rightIndex = 0;
        }

        setRightColor(rightColors[rightIndex]);
        playElectricEffect();

        setTimeout(() => {
            rightBannerImg.src = rightImages[rightIndex];
        }, 120);
    });

    setLeftColor(leftColors[0]);
    setRightColor(rightColors[0]);
</script>

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
                <button class="btn-corbeille" title="Retirer l'objet" onclick="deleteItemFromCart(<?= $item['id'] ?>, this)">
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
    <div id="custom-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fa-solid fa-scroll"></i>
                <h3 id="modal-title">Message de l'Arsenal</h3>
            </div>
            <div class="modal-body">
                <p id="modal-message">Voulez-vous vraiment faire cela ?</p>
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
        <a href="index.php" class="btn-return">
            <i class="fa-solid fa-arrow-left"></i> Continuer mes achats
        </a>

        <div class="total-summary">
            TOTAL : <?= number_format($totalGeneral, 0) ?> GP
        </div>

        <button class="btn-confirm"
            id="btn-confirm-purchase"
            <?= $hasStockError ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>
            onclick="processPurchase()">
            <?= $hasStockError ? 'Stock insuffisant' : "Confirmer l'achat" ?>
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

    /**
     * Affiche une modale de confirmation personnalisée
     */
    function showCustomConfirm(message, title = "Confirmation") {
        return new Promise((resolve) => {
            const modal = document.getElementById('custom-modal');
            const msgPara = document.getElementById('modal-message');
            const titleH3 = document.getElementById('modal-title');
            const btnConfirm = document.getElementById('modal-btn-confirm');
            const btnCancel = document.getElementById('modal-btn-cancel');

            msgPara.innerText = message;
            titleH3.innerText = title;
            modal.style.display = 'flex';

            const close = (result) => {
                modal.style.display = 'none';
                btnConfirm.onclick = null;
                btnCancel.onclick = null;
                resolve(result);
            };

            btnConfirm.onclick = () => close(true);
            btnCancel.onclick = () => close(false);

            // Fermer si on clique en dehors de la modale
            modal.onclick = (e) => {
                if (e.target === modal) close(false);
            };
        });
    }

    // MISE À JOUR de ta fonction de suppression
    async function deleteItemFromCart(itemId, button) {
        // ON REMPLACE LE confirm() PAR NOTRE MODALE
        const confirmed = await showCustomConfirm("Voulez-vous retirer cet objet de votre besace ?", "Retirer l'objet");

        if (!confirmed) return;

        // ... reste du code fetch inchangé ...
        const row = button.closest('.cart-row');
        const formData = new FormData();
        formData.append('item_id', itemId);

        try {
            const response = await fetch('backend/supprimer_item_panier.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                row.style.transform = "translateX(-100px)";
                row.style.opacity = "0";
                setTimeout(() => {
                    row.remove();
                    if (document.querySelectorAll('.cart-row').length === 0) location.reload();
                    else updateCartState();
                }, 400);
            }
        } catch (error) {
            console.error(error);
        }
    }
    async function processPurchase() {
        const confirmBtn = document.getElementById('btn-confirm-purchase');

        if (confirmBtn.disabled) return;

        // Utilisation de TA modale personnalisée ici
        const confirmed = await showCustomConfirm(
            "Voulez-vous sceller cet échange et dépenser vos pièces d'or ?",
            "Sceller l'échange"
        );

        if (!confirmed) return;

        confirmBtn.disabled = true;
        confirmBtn.innerText = "Traitement...";

        try {
            // Simulation d'appel backend
            // const response = await fetch('backend/confirmer_achat.php', { method: 'POST' });

            // On remplace aussi l'alert() final par un petit message temporaire sur le bouton
            confirmBtn.innerText = "Échange conclu !";

            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);

        } catch (error) {
            console.error("Erreur transaction:", error);
            confirmBtn.disabled = false;
            confirmBtn.innerText = "Confirmer l'achat";
        }
    }
</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>