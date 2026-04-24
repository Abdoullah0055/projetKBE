<?php
ob_start();


require_once 'AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pdo = get_pdo();

// 1. RECUPERATION DU THEME
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// Gestion de l'utilisateur
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    $user = ['isConnected' => false];
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$itemId = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT i.ItemId AS id, i.Name AS nom, i.ImageUrl AS ImageUrl, i.PriceGold AS prix_gold, i.PriceSilver AS prix_silver,
           i.PriceBronze AS prix_bronze, i.Description AS description,
           i.Stock AS stock, t.Name AS type, IFNULL(AVG(r.Rating), 0) AS rating, COUNT(r.ReviewId) AS nb_avis
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.ItemId = ?
    GROUP BY i.ItemId, i.Name, i.ImageUrl, i.PriceGold, i.PriceSilver, i.PriceBronze, i.Description, i.Stock, t.Name
");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: index.php");
    exit();
}

$item['image'] = getItemImage($item['type']);
$itemImagePath = getItemImagePathForItem($item);
$properties = getItemProperties($pdo, (int) $item['id'], $item['type']);
$title = "Details - " . $item['nom'];
$itemRatingText = formatRatingValue((float) $item['rating']);

$detailAlert = null;
if (isset($_SESSION['alerte']) && is_array($_SESSION['alerte'])) {
    $candidate = $_SESSION['alerte'];
    $alertTypeRaw = (string) ($candidate['type'] ?? '');
    $alertType = in_array($alertTypeRaw, ['succes', 'erreur'], true) ? $alertTypeRaw : '';
    $alertMessage = trim((string) ($candidate['message'] ?? ''));

    $isExpectedSource = (($candidate['source'] ?? '') === 'add_to_cart');
    $isSameItem = ((int) ($candidate['item_id'] ?? 0) === $itemId);
    $isFresh = (isset($candidate['ts']) && (time() - (int) $candidate['ts']) <= 30);

    if ($isExpectedSource && $isSameItem && $isFresh && $alertType !== '' && $alertMessage !== '') {
        $detailAlert = [
            'type' => $alertType,
            'message' => $alertMessage,
        ];
    }

    unset($_SESSION['alerte']);
}
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e !important;
        overflow-y: auto !important;
    }
</style>

<link rel="stylesheet" href="assets/css/details.css?v=<?= filemtime(__DIR__ . '/assets/css/details.css') ?>">

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
    <div class="banner-flip" id="leftFlip">
        <div class="banner-scroll banner-clickable" id="leftBanner">
            <img src="assets/img/kratos.png" alt="Deco Gauche" id="leftBannerImg">
        </div>
    </div>
</div>

<div class="page-banner banner-right">
    <div class="banner-flip" id="rightFlip">
        <div class="banner-scroll banner-clickable" id="rightBanner">
            <img src="assets/img/bull.png" alt="Deco Droite" id="rightBannerImg">
        </div>
    </div>
</div>

<?php if ($detailAlert !== null): ?>
    <div class="alert-box <?= $detailAlert['type'] ?>">
        <i class="fa-solid <?= $detailAlert['type'] === 'succes' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($detailAlert['message']) ?>
    </div>
<?php endif; ?>

<div class="details-wrapper">
    <main class="details-glass-card">

        <div class="visual-column">
            <div class="item-display-box" onclick="triggerMagic()">
                <div class="rarity-tag"><?= htmlspecialchars($item['type']) ?></div>

                <div class="floating-wrapper">
                    <?php if ($itemImagePath !== null): ?>
                        <div class="main-icon item-detail-image-frame" id="target-icon">
                            <img
                                class="item-detail-image"
                                src="<?= htmlspecialchars($itemImagePath, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php else: ?>
                        <div class="main-icon" id="target-icon"><?= $item['image'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="glow-shadow"></div>
                <span class="click-hint">Touchez l'artefact</span>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-label">Avis</span>
                    <span class="stat-value details-rating-stars-wrap"><?= renderRatingStars((float) $item['rating'], 'details-rating-stars') ?></span>
                    <span class="stat-sub"><?= $itemRatingText ?>/5 • <?= (int) $item['nb_avis'] ?> avis</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Stock</span>
                    <span class="stat-value <?= ((int) $item['stock'] === 0) ? 'text-danger' : 'text-success' ?>">
                        <?= (int) $item['stock'] ?>
                    </span>
                    <span class="stat-sub">unites</span>
                </div>
            </div>
        </div>

        <div class="info-column">
            <div class="item-title-section">
                <h1><?= htmlspecialchars($item['nom']) ?></h1>
                <div class="price-tag"><?= number_format((int) $item['prix_gold'], 0) ?> <span class="gp">GP</span></div>
                <div class="price-sub-line"><?= (int) $item['prix_silver'] ?> SP • <?= (int) $item['prix_bronze'] ?> BP</div>
            </div>

            <div class="description-section">
                <h3><i class="fa-solid fa-scroll"></i> Lore & Proprietes</h3>
                <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                <?= renderItemProperties($item, $properties) ?>
            </div>

            <div class="spec-grid">
                <div class="spec-item"><span>Categorie</span><strong><?= htmlspecialchars(ucfirst($item['type'])) ?></strong></div>
                <div class="spec-item"><span>Authenticite</span><strong>Certifiee</strong></div>
                <div class="spec-item"><span>Origine</span><strong>Inconnue</strong></div>
            </div>

            <div class="purchase-section">
                <?php if ((int) $item['stock'] > 0): ?>
                    <form action="backend/ajouter_au_panier.php" method="POST" class="purchase-form">

                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">

                        <div class="purchase-controls">
                            <div class="quantity-wrapper">
                                <label for="qty">Quantite :</label>
                                <select name="quantity" id="qty" class="qty-select">
                                    <?php for ($i = 1; $i <= min((int) $item['stock'], 10); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <?php if ((int) $item['stock'] < 5): ?>
                                <div class="urgency-badge">
                                    <i class="fa-solid fa-bolt"></i>
                                    Plus que <?= (int) $item['stock'] ?> restant !
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-buy-large">Ajouter au Panier</button>
                    </form>
                <?php else: ?>
                    <button class="btn-buy-large btn-out" disabled>Stock Epuise</button>
                <?php endif; ?>

                <a href="index.php" class="back-link">Retour au catalogue</a>
            </div>
        </div>
    </main>
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
    const leftFlip = document.getElementById("leftFlip");
    const rightFlip = document.getElementById("rightFlip");

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

    function playLeftTurn() {
        leftFlip.classList.remove("turn-left");
        void leftFlip.offsetWidth;
        leftFlip.classList.add("turn-left");
    }

    function playRightTurn() {
        rightFlip.classList.remove("turn-right");
        void rightFlip.offsetWidth;
        rightFlip.classList.add("turn-right");
    }

    function changeLeftBanner() {
        leftIndex++;
        if (leftIndex >= leftImages.length) {
            leftIndex = 0;
        }

        playLeftTurn();
        playFireEffect();
        setLeftColor(leftColors[leftIndex]);

        setTimeout(() => {
            leftBannerImg.src = leftImages[leftIndex];
        }, 350);
    }

    function changeRightBanner() {
        rightIndex++;
        if (rightIndex >= rightImages.length) {
            rightIndex = 0;
        }

        playRightTurn();
        playElectricEffect();
        setRightColor(rightColors[rightIndex]);

        setTimeout(() => {
            rightBannerImg.src = rightImages[rightIndex];
        }, 350);
    }

    setInterval(() => {
        changeLeftBanner();
        changeRightBanner();
    }, 5000);

    leftBanner.addEventListener("click", () => {
        leftIndex++;
        if (leftIndex >= leftImages.length) {
            leftIndex = 0;
        }

        playLeftTurn();
        playFireEffect();
        setLeftColor(leftColors[leftIndex]);

        setTimeout(() => {
            leftBannerImg.src = leftImages[leftIndex];
        }, 350);
    });

    rightBanner.addEventListener("click", () => {
        rightIndex++;
        if (rightIndex >= rightImages.length) {
            rightIndex = 0;
        }

        playRightTurn();
        playElectricEffect();
        setRightColor(rightColors[rightIndex]);

        setTimeout(() => {
            rightBannerImg.src = rightImages[rightIndex];
        }, 350);
    });

    setLeftColor(leftColors[0]);
    setRightColor(rightColors[0]);

    function triggerMagic() {
        const icon = document.getElementById('target-icon');
        icon.classList.remove('magic-shake');
        void icon.offsetWidth;
        icon.classList.add('magic-shake');
    }

    function showDetailAlert(message, type = 'succes') {
        const oldAlert = document.querySelector('.alert-box');
        if (oldAlert) {
            oldAlert.remove();
        }

        const box = document.createElement('div');
        box.className = `alert-box ${type}`;
        box.innerHTML = `
            <i class="fa-solid ${type === 'succes' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
            ${message}
        `;
        document.body.appendChild(box);

        setTimeout(() => {
            box.remove();
        }, 2600);
    }

    document.querySelector('.purchase-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const isConnected = <?= json_encode($user['isConnected']) ?>;

        if (!isConnected) {
            const itemId = document.querySelector('input[name="item_id"]').value;
            window.location.href = `login.php?return_to=details.php?id=${itemId}`;
            return;
        }

        const form = this;
        const formData = new FormData(form);
        const cartBtn = document.getElementById('cart-btn');
        const targetIcon = document.getElementById('target-icon');

        const clone = targetIcon.cloneNode(true);
        clone.removeAttribute('id');
        clone.classList.add('flying-item');

        const startRect = targetIcon.getBoundingClientRect();
        clone.style.left = startRect.left + 'px';
        clone.style.top = startRect.top + 'px';
        clone.style.width = startRect.width + 'px';
        clone.style.height = startRect.height + 'px';
        document.body.appendChild(clone);

        if (cartBtn) {
            const endRect = cartBtn.getBoundingClientRect();

            requestAnimationFrame(() => {
                clone.style.left = endRect.left + 'px';
                clone.style.top = endRect.top + 'px';
                clone.style.transform = 'scale(0.1) rotate(360deg)';
                clone.style.opacity = '0';
            });

            setTimeout(() => {
                clone.remove();
                cartBtn.classList.add('cart-shake');
                setTimeout(() => cartBtn.classList.remove('cart-shake'), 400);
            }, 800);
        } else {
            setTimeout(() => clone.remove(), 800);
        }

        try {
            const response = await fetch('backend/ajouter_au_panier.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            let data = null;
            try {
                data = await response.json();
            } catch (_error) {
                data = null;
            }

            if (response.ok && data && data.success) {
                showDetailAlert(data.message || "Objet ajoute au panier.", 'succes');
            } else {
                const errorMessage = data && data.message ?
                    data.message :
                    "Echec de l'ajout au panier.";
                showDetailAlert(errorMessage, 'erreur');
            }
        } catch (error) {
            console.error("Erreur:", error);
            showDetailAlert("Erreur reseau pendant l'ajout au panier.", 'erreur');
        }
    });
</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>
