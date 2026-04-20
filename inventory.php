<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$pdo = get_pdo();

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

$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

$inventoryItems = [];
$pendingReviewItems = [];
$inventoryError = '';
$reviewFlash = null;

if (isset($_SESSION['review_feedback']) && is_array($_SESSION['review_feedback'])) {
    $reviewFlash = $_SESSION['review_feedback'];
    unset($_SESSION['review_feedback']);
}

try {
    $stmt = $pdo->prepare(
        "SELECT
            inv.InventoryId AS inventory_id,
            inv.ItemId AS item_id,
            inv.Quantity AS quantity,
            i.Name AS item_name,
            i.Description AS item_description,
            i.PriceGold AS item_price_gold,
            t.Name AS item_type,
            IFNULL(rating_agg.rating, 0) AS rating,
            IFNULL(rating_agg.review_count, 0) AS review_count,
            CASE WHEN user_review.review_id IS NULL THEN 0 ELSE 1 END AS is_rated_by_user
         FROM Inventory inv
         LEFT JOIN Items i ON inv.ItemId = i.ItemId
         LEFT JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
         LEFT JOIN (
            SELECT
                ItemId,
                AVG(Rating) AS rating,
                COUNT(ReviewId) AS review_count
            FROM Reviews
            GROUP BY ItemId
         ) rating_agg ON rating_agg.ItemId = inv.ItemId
         LEFT JOIN (
            SELECT
                ItemId,
                MAX(ReviewId) AS review_id
            FROM Reviews
            WHERE UserId = ?
            GROUP BY ItemId
         ) user_review ON user_review.ItemId = inv.ItemId
         WHERE inv.UserId = ?
         ORDER BY inv.InventoryId DESC"
    );
    $stmt->execute([$user['id'], $user['id']]);
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $inventoryError = "Impossible de charger votre inventaire pour le moment.";
}

if ($inventoryError === '') {
    try {
        $reviewStmt = $pdo->prepare(
            "SELECT
                inv.ItemId AS item_id,
                inv.Quantity AS quantity_owned,
                i.Name AS item_name,
                t.Name AS item_type,
                IFNULL(AVG(all_reviews.Rating), 0) AS rating,
                COUNT(all_reviews.ReviewId) AS review_count
             FROM Inventory inv
             JOIN Items i ON inv.ItemId = i.ItemId
             JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
             LEFT JOIN Reviews user_review
                ON user_review.ItemId = inv.ItemId
               AND user_review.UserId = :user_id_for_review
             LEFT JOIN Reviews all_reviews
                ON all_reviews.ItemId = inv.ItemId
             WHERE inv.UserId = :user_id_for_inventory
               AND inv.Quantity > 0
               AND user_review.ReviewId IS NULL
             GROUP BY inv.ItemId, inv.Quantity, i.Name, t.Name
             ORDER BY i.Name ASC"
        );

        $reviewStmt->execute([
            ':user_id_for_review' => $user['id'],
            ':user_id_for_inventory' => $user['id'],
        ]);

        $pendingReviewItems = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pendingReviewItems = [];
    }
}

$title = "L'Arsenal - Inventory";
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e;
        position: relative;
        z-index: 0;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: -1;
        pointer-events: none;
    }
</style>

<link rel="stylesheet" href="assets/css/inventory.css">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
    <aside id="sidebar">
        <button id="toggle-btn" type="button" aria-expanded="true" aria-label="Réduire la sidebar">
            <span id="arrow-icon">«</span>
        </button>

        <div class="sidebar-content">
            <div class="show-icon"><i class="fa-solid fa-box-open"></i></div>

            <div class="hide-text inventory-side-box">
                <h3>Inventory</h3>
                <p>Consultez vos objets lies a votre compte.</p>
            </div>

            <div class="sidebar-bottom-actions">
                <a href="index.php" class="sidebar-nav-btn">
                    <i class="fa-solid fa-store"></i>
                    <span class="btn-label">Retour Boutique</span>
                </a>
            </div>
        </div>
    </aside>

    <main>
        <div class="catalog-banner">
            <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.3rem;">
                Inventory de <?= htmlspecialchars($user['alias']) ?>
            </h2>
        </div>

        <?php if (is_array($reviewFlash) && !empty($reviewFlash['message'])): ?>
            <?php $flashType = ($reviewFlash['type'] ?? '') === 'success' ? 'success-state' : 'error-state'; ?>
            <div class="inventory-state <?= $flashType ?>">
                <span><?= htmlspecialchars((string) $reviewFlash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="inventory-layout<?= !empty($pendingReviewItems) ? ' has-review-panel' : '' ?>">
            <section class="inventory-main-column">
                <div id="inventory-loading" class="inventory-state loading-state">
                    Chargement de votre inventaire...
                </div>

                <?php if (!empty($inventoryError)): ?>
                    <div class="inventory-state error-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($inventoryError) ?></span>
                    </div>
                <?php elseif (empty($inventoryItems)): ?>
                    <div class="inventory-state empty-state">
                        Aucun item trouve dans votre inventaire.
                    </div>
                <?php else: ?>
                    <div class="inventory-grid" id="inventory-list">
                        <?php foreach ($inventoryItems as $entry): ?>
                            <?php
                            $itemName = $entry['item_name'] ?? ('Item #' . $entry['item_id']);
                            $itemDescription = trim((string) ($entry['item_description'] ?? ''));
                            if ($itemDescription === '') {
                                $itemDescription = "Aucune description disponible.";
                            }
                            $itemType = $entry['item_type'] ?? 'Inconnu';
                            $ratingValue = (float) ($entry['rating'] ?? 0);
                            $reviewCount = (int) ($entry['review_count'] ?? 0);
                            $isRatedByUser = ((int) ($entry['is_rated_by_user'] ?? 0)) === 1;
                            $statusClass = $isRatedByUser ? 'is-rated' : 'is-unrated';
                            $statusLabel = $isRatedByUser ? 'Evalue' : 'Non evalue';
                            ?>

                            <article class="inventory-slot"
                                data-item-name="<?= htmlspecialchars($itemName) ?>"
                                data-item-description="<?= htmlspecialchars($itemDescription) ?>"
                                data-item-quantity="<?= (int) $entry['quantity'] ?>"
                                data-item-type="<?= htmlspecialchars($itemType) ?>"
                                data-item-id="<?= (int) $entry['item_id'] ?>"
                                data-item-price="<?= (int) ($entry['item_price_gold'] ?? 0) ?>">

                                <div class="slot-top-row">
                                    <div class="slot-thumb" aria-hidden="true">
                                        <span class="slot-icon"><?= getItemImage($itemType) ?></span>
                                    </div>

                                    <div class="slot-main-info">
                                        <h3 class="slot-label"><?= htmlspecialchars($itemName) ?></h3>
                                        <p class="slot-type">Type: <?= htmlspecialchars($itemType) ?></p>
                                        <p class="slot-owned">Quantite possedee: <?= (int) $entry['quantity'] ?></p>
                                    </div>

                                    <div class="slot-qty-badge">
                                        x<?= (int) $entry['quantity'] ?>
                                    </div>
                                </div>

                                <p class="slot-description-text"><?= htmlspecialchars($itemDescription) ?></p>

                                <div class="slot-rating-line">
                                    <?= renderRatingStars($ratingValue) ?>
                                    <span class="rating-value-inline slot-rating-value"><?= formatRatingValue($ratingValue) ?>/5</span>
                                </div>

                                <div class="slot-stats">
                                    <div class="slot-stat-row">
                                        <span>Prix</span>
                                        <strong><?= (int) ($entry['item_price_gold'] ?? 0) ?> GP</strong>
                                    </div>
                                    <div class="slot-stat-row">
                                        <span>Evaluations</span>
                                        <strong class="slot-review-count"><?= $reviewCount ?></strong>
                                    </div>
                                    <div class="slot-stat-row">
                                        <span>Statut</span>
                                        <strong class="slot-status <?= $statusClass ?>"><?= $statusLabel ?></strong>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div id="inventory-tooltip" class="inventory-tooltip" role="tooltip" aria-hidden="true">
                        <div class="tooltip-title" id="tooltip-title"></div>
                        <div class="tooltip-description" id="tooltip-description"></div>
                        <div class="tooltip-meta">
                            <span id="tooltip-quantity"></span>
                            <span id="tooltip-type"></span>
                            <span id="tooltip-item-id"></span>
                            <span id="tooltip-price"></span>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!empty($pendingReviewItems)): ?>
                <aside class="pending-reviews-panel" id="pending-reviews-panel" aria-label="Items a evaluer">
                    <div class="pending-reviews-header">
                        <h3>Items achetes a evaluer</h3>
                        <p>Attribuez une note de 1 a 5, avec demi-etoiles, pour ameliorer les suggestions du marche.</p>
                    </div>

                    <div class="pending-reviews-list" id="pending-reviews-list">
                        <?php foreach ($pendingReviewItems as $reviewItem): ?>
                            <?php
                            $reviewItemId = (int) $reviewItem['item_id'];
                            $reviewItemType = (string) ($reviewItem['item_type'] ?? 'Inconnu');
                            $reviewItemName = (string) ($reviewItem['item_name'] ?? ('Item #' . $reviewItemId));
                            $ratingInputId = 'rating-input-' . $reviewItemId;
                            $ratingPreviewId = 'rating-preview-' . $reviewItemId;
                            ?>

                            <article class="pending-review-card" data-pending-item-id="<?= $reviewItemId ?>">
                                <div class="pending-review-item-meta">
                                    <div class="pending-review-thumb" aria-hidden="true">
                                        <?= getItemImage($reviewItemType) ?>
                                    </div>

                                    <div>
                                        <h4><?= htmlspecialchars($reviewItemName) ?></h4>
                                        <p>
                                            Type: <?= htmlspecialchars($reviewItemType) ?>
                                            • Quantite: <?= (int) $reviewItem['quantity_owned'] ?>
                                        </p>

                                        <div class="pending-review-current-rating">
                                            <?= renderRatingStars((float) $reviewItem['rating']) ?>
                                            <span class="rating-value-inline">
                                                <?= formatRatingValue((float) $reviewItem['rating']) ?>/5 (<?= (int) $reviewItem['review_count'] ?> avis)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <form class="pending-review-form" action="backend/soumettre_review.php" method="post">
                                    <input type="hidden" name="item_id" value="<?= $reviewItemId ?>">
                                    <input type="hidden" name="rating" id="<?= $ratingInputId ?>" value="5.0">

                                    <div class="rating-picker" data-input-id="<?= $ratingInputId ?>" data-preview-id="<?= $ratingPreviewId ?>">
                                        <?php for ($step = 2; $step <= 10; $step++): ?>
                                            <?php
                                            $stepValue = $step / 2;
                                            $isHalf = ($step % 2) !== 0;
                                            $valueLabel = number_format((float) $stepValue, 1, '.', '');
                                            ?>
                                            <button
                                                type="button"
                                                class="rating-step-btn<?= ($stepValue === 5.0) ? ' is-selected' : '' ?>"
                                                data-value="<?= $valueLabel ?>"
                                                aria-label="Noter <?= $valueLabel ?> sur 5">
                                                <i class="fa-solid <?= $isHalf ? 'fa-star-half-stroke' : 'fa-star' ?>" aria-hidden="true"></i>
                                                <span class="step-value"><?= $valueLabel ?></span>
                                            </button>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="rating-picker-preview" id="<?= $ratingPreviewId ?>">
                                        <?= renderRatingStars(5.0) ?>
                                        <span class="rating-value-inline">5.0/5</span>
                                    </div>

                                    <button type="submit" class="btn-submit-rating">Envoyer ma note</button>
                                </form>

                                <p class="pending-review-message" aria-live="polite"></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadingBox = document.getElementById('inventory-loading');
        if (loadingBox) {
            loadingBox.style.display = 'none';
        }

        const tooltip = document.getElementById('inventory-tooltip');
        const slots = document.querySelectorAll('.inventory-slot');

        if (tooltip && slots.length > 0) {
            const tooltipTitle = document.getElementById('tooltip-title');
            const tooltipDescription = document.getElementById('tooltip-description');
            const tooltipQuantity = document.getElementById('tooltip-quantity');
            const tooltipType = document.getElementById('tooltip-type');
            const tooltipItemId = document.getElementById('tooltip-item-id');
            const tooltipPrice = document.getElementById('tooltip-price');

            function positionTooltip(event) {
                const offsetX = 16;
                const offsetY = 20;
                const maxX = window.innerWidth - tooltip.offsetWidth - 10;
                const maxY = window.innerHeight - tooltip.offsetHeight - 10;
                const nextX = Math.min(event.clientX + offsetX, maxX);
                const nextY = Math.min(event.clientY + offsetY, maxY);

                tooltip.style.left = Math.max(10, nextX) + 'px';
                tooltip.style.top = Math.max(10, nextY) + 'px';
            }

            slots.forEach(function(slot) {
                slot.addEventListener('mouseenter', function(event) {
                    tooltipTitle.textContent = slot.dataset.itemName || 'Objet';
                    tooltipDescription.textContent = slot.dataset.itemDescription || '';
                    tooltipQuantity.textContent = 'Quantite: ' + (slot.dataset.itemQuantity || '0');
                    tooltipType.textContent = 'Type: ' + (slot.dataset.itemType || 'Inconnu');
                    tooltipItemId.textContent = 'ItemId: ' + (slot.dataset.itemId || '-');
                    tooltipPrice.textContent = 'Prix: ' + (slot.dataset.itemPrice || '0') + ' GP';

                    tooltip.classList.add('visible');
                    tooltip.setAttribute('aria-hidden', 'false');
                    positionTooltip(event);
                });

                slot.addEventListener('mousemove', positionTooltip);

                slot.addEventListener('mouseleave', function() {
                    tooltip.classList.remove('visible');
                    tooltip.setAttribute('aria-hidden', 'true');
                });
            });
        }

        const pendingPanel = document.getElementById('pending-reviews-panel');
        const pendingCards = document.querySelectorAll('.pending-review-card');

        if (!pendingPanel || pendingCards.length === 0) {
            return;
        }

        function clampRatingValue(value) {
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return 1;
            }

            const clamped = Math.max(1, Math.min(5, numeric));
            return Math.round(clamped * 2) / 2;
        }

        function renderStarsMarkup(value) {
            const rating = clampRatingValue(value);
            const full = Math.floor(rating);
            const hasHalf = rating - full >= 0.5;
            const empty = 5 - full - (hasHalf ? 1 : 0);
            const parts = ['<span class="rating-stars" aria-hidden="true">'];

            for (let i = 0; i < full; i += 1) {
                parts.push('<i class="fa-solid fa-star"></i>');
            }

            if (hasHalf) {
                parts.push('<i class="fa-solid fa-star-half-stroke"></i>');
            }

            for (let i = 0; i < empty; i += 1) {
                parts.push('<i class="fa-regular fa-star"></i>');
            }

            parts.push('</span>');
            return parts.join('');
        }

        function updatePickerUI(picker, input, preview, value) {
            const normalized = clampRatingValue(value);
            input.value = normalized.toFixed(1);

            const stepButtons = picker.querySelectorAll('.rating-step-btn');
            stepButtons.forEach(function(button) {
                const btnValue = clampRatingValue(button.dataset.value || '1');
                button.classList.toggle('is-selected', btnValue === normalized);
            });

            preview.innerHTML =
                renderStarsMarkup(normalized) +
                '<span class="rating-value-inline">' + normalized.toFixed(1) + '/5</span>';
        }

        document.querySelectorAll('.rating-picker').forEach(function(picker) {
            const inputId = picker.dataset.inputId;
            const previewId = picker.dataset.previewId;
            const input = inputId ? document.getElementById(inputId) : null;
            const preview = previewId ? document.getElementById(previewId) : null;

            if (!input || !preview) {
                return;
            }

            updatePickerUI(picker, input, preview, input.value || '5');

            picker.addEventListener('click', function(event) {
                const button = event.target.closest('.rating-step-btn');
                if (!button) {
                    return;
                }

                updatePickerUI(picker, input, preview, button.dataset.value || '5');
            });
        });

        document.querySelectorAll('.pending-review-form').forEach(function(form) {
            form.addEventListener('submit', async function(event) {
                event.preventDefault();

                const card = form.closest('.pending-review-card');
                const messageBox = card ? card.querySelector('.pending-review-message') : null;
                const submitButton = form.querySelector('.btn-submit-rating');
                if (!card || !messageBox || !submitButton) {
                    return;
                }

                messageBox.textContent = '';
                messageBox.classList.remove('is-success', 'is-error');
                submitButton.disabled = true;
                submitButton.textContent = 'Envoi en cours...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(form)
                    });

                    const responseText = await response.text();
                    let data = null;

                    // Be resilient to accidental PHP warnings around JSON payload.
                    try {
                        data = JSON.parse(responseText);
                    } catch (_error) {
                        const jsonStart = responseText.indexOf('{');
                        const jsonEnd = responseText.lastIndexOf('}');

                        if (jsonStart !== -1 && jsonEnd > jsonStart) {
                            try {
                                data = JSON.parse(responseText.slice(jsonStart, jsonEnd + 1));
                            } catch (_nestedError) {
                                data = null;
                            }
                        }
                    }

                    if (!response.ok || !data || data.success !== true) {
                        const errorMessage = (data && data.message) ? data.message : 'Impossible d\'enregistrer la note.';
                        messageBox.textContent = errorMessage;
                        messageBox.classList.add('is-error');
                        submitButton.disabled = false;
                        submitButton.textContent = 'Envoyer ma note';
                        return;
                    }

                    messageBox.textContent = data.message || 'Merci, votre note a été enregistrée.';
                    messageBox.classList.add('is-success');
                    submitButton.textContent = 'Note enregistrée';

                    const itemIdInput = form.querySelector('input[name="item_id"]');
                    const reviewedItemId = itemIdInput ? String(itemIdInput.value || '').trim() : '';
                    const linkedSlot = reviewedItemId ?
                        document.querySelector('.inventory-slot[data-item-id="' + reviewedItemId + '"]') :
                        null;

                    if (linkedSlot) {
                        const statusNode = linkedSlot.querySelector('.slot-status');
                        const reviewCountNode = linkedSlot.querySelector('.slot-review-count');
                        const ratingValueNode = linkedSlot.querySelector('.slot-rating-value');
                        const ratingLineNode = linkedSlot.querySelector('.slot-rating-line');

                        if (statusNode) {
                            statusNode.textContent = 'Evalue';
                            statusNode.classList.remove('is-unrated');
                            statusNode.classList.add('is-rated');
                        }

                        if (reviewCountNode && Number.isFinite(Number(data.reviewCount))) {
                            reviewCountNode.textContent = String(Number(data.reviewCount));
                        }

                        const parsedRating = Number.parseFloat(String(data.rating || '').replace(',', '.'));
                        if (ratingLineNode && ratingValueNode && Number.isFinite(parsedRating)) {
                            ratingLineNode.innerHTML =
                                renderStarsMarkup(parsedRating) +
                                '<span class="rating-value-inline slot-rating-value">' + parsedRating.toFixed(1) + '/5</span>';
                        }
                    }

                    setTimeout(function() {
                        card.remove();
                        const remainingCards = document.querySelectorAll('.pending-review-card').length;
                        if (remainingCards === 0) {
                            pendingPanel.remove();

                            const inventoryLayout = document.querySelector('.inventory-layout');
                            if (inventoryLayout) {
                                inventoryLayout.classList.remove('has-review-panel');
                            }
                        }
                    }, 900);
                } catch (_error) {
                    messageBox.textContent = 'Erreur réseau, veuillez réessayer.';
                    messageBox.classList.add('is-error');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Envoyer ma note';
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
