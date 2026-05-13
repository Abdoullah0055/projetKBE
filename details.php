<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';

$pdo = get_pdo();

// 1. RECUPERATION DU THEME
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

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

$distributionStmt = $pdo->prepare("
    SELECT ROUND(Rating) AS star_level, COUNT(*) AS count
    FROM Reviews
    WHERE ItemId = ?
    GROUP BY ROUND(Rating)
    ORDER BY ROUND(Rating) DESC
");
$distributionStmt->execute([$itemId]);
$distRows = $distributionStmt->fetchAll(PDO::FETCH_ASSOC);
$starDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$totalReviewsForDist = 0;
foreach ($distRows as $dr) {
    $level = (int)$dr['star_level'];
    if ($level >= 1 && $level <= 5) {
        $starDist[$level] = (int)$dr['count'];
        $totalReviewsForDist += (int)$dr['count'];
    }
}

$detailAlert = null;
if (isset($_SESSION['alerte']) && is_array($_SESSION['alerte'])) {
    $candidate = $_SESSION['alerte'];
    $alertTypeRaw = (string) ($candidate['type'] ?? '');
    $alertType = in_array($alertTypeRaw, ['success', 'erreur'], true) ? $alertTypeRaw : '';
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
        <i class="fa-solid <?= $detailAlert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
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
      <?php if ($totalReviewsForDist > 0): ?>
      <div class="star-distribution">
      <?php for ($s = 5; $s >= 1; $s--):
      $pct = $totalReviewsForDist > 0 ? round(($starDist[$s] / $totalReviewsForDist) * 100) : 0;
      ?>
      <div class="dist-row">
      <span class="dist-label"><?= $s ?> <i class="fa-solid fa-star"></i></span>
      <div class="dist-bar-bg"><div class="dist-bar-fill" style="width: <?= $pct ?>%"></div></div>
      <span class="dist-pct"><?= $pct ?>%</span>
      </div>
      <?php endfor; ?>
      </div>
      <?php endif; ?>
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
  <?php
    $isSpell = (mb_strtolower($item['type'], 'UTF-8') === 'magicspell');
    $isRestrictedMage = $isSpell && !$user['isMage'];
  ?>

  <?php if ($isRestrictedMage): ?>
    <div class="spell-restricted-notice">
      <i class="fa-solid fa-hat-wizard"></i>
      Seuls les mages peuvent acquérir des sorts.
    </div>
    <button class="btn-buy-large btn-spell-restricted" disabled>
      <i class="fa-solid fa-lock"></i> Sort réservé aux Mages
    </button>
  <?php elseif ((int) $item['stock'] > 0): ?>
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

<?php
$reviewsStmt = $pdo->prepare("
    SELECT r.ReviewId, r.UserId, r.Rating, r.Comment, r.CreatedAt, u.Alias
    FROM Reviews r
    JOIN Users u ON u.UserId = r.UserId
    WHERE r.ItemId = ?
    ORDER BY r.CreatedAt DESC
");
$reviewsStmt->execute([$itemId]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="reviews-section" style="margin-top: 30px; max-width: 900px; margin-left: auto; margin-right: auto; padding: 0 20px;">
    <h3 style="color: var(--accent);"><i class="fa-solid fa-comments"></i> Evaluations (<?= count($reviews) ?>)</h3>

    <?php if (empty($reviews)): ?>
    <p style="color: var(--text-silver);">Aucune evaluation pour le moment.</p>
    <?php else: ?>
    <?php foreach ($reviews as $rev): ?>
    <div class="review-card" data-review-id="<?= (int)$rev['reviewid'] ?>" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px 16px; margin-bottom: 10px;">
        <div class="review-header" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <strong><?= htmlspecialchars($rev['alias']) ?></strong>
            <?= renderRatingStars((float)$rev['rating']) ?>
            <span class="rating-value-inline"><?= formatRatingValue((float)$rev['rating']) ?>/5</span>
            <?php if ($user['isConnected'] && (int)$rev['userid'] === $user['id']): ?>
            <button type="button" class="btn-delete-review" data-review-id="<?= (int)$rev['reviewid'] ?>" title="Supprimer mon evaluation" style="background: transparent; border: 1px solid #e74c3c; color: #e74c3c; border-radius: 4px; padding: 3px 8px; cursor: pointer; font-size: 0.75rem; margin-left: auto;">
                <i class="fa-solid fa-trash"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php if (!empty($rev['comment'])): ?>
        <p class="review-comment" style="margin-top: 8px; color: var(--text-light); font-size: 0.9rem;"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-delete-review').forEach(btn => {
    btn.addEventListener('click', async function() {
        const ok = await showCustomConfirm('Supprimer votre evaluation ?', 'Suppression');
    if (!ok) return;
        const reviewId = this.dataset.reviewId;
        const formData = new FormData();
        formData.append('action', 'delete_review');
        formData.append('review_id', reviewId);
        try {
            const resp = await fetch('backend/soumettre_review.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                const card = btn.closest('.review-card');
                if (card) card.remove();
                if (typeof showToast === 'function') showToast(data.message, 'success');
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Erreur lors de la suppression.', 'erreur');
            }
        } catch (e) {
            if (typeof showToast === 'function') showToast('Erreur reseau.', 'erreur');
        }
    });
});
</script>
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
            let rawText = '';
            try {
                rawText = await response.text();
                data = JSON.parse(rawText);
            } catch (_error) {
                data = null;
            }

            if (response.ok && data && data.success) {
                showToast(data.message || "Objet ajoute au panier.", 'success');
            } else {
                const errorMessage = data && data.message ?
                    data.message :
                    "Echec de l'ajout au panier.";
                showToast(errorMessage, 'erreur');
            }
        } catch (error) {
            console.error("Erreur:", error);
            showToast("Erreur reseau pendant l'ajout au panier.", 'erreur');
        }
    });

</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>
