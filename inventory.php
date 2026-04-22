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
    'id' => $_SESSION['user']['id'],
    'alias' => $_SESSION['user']['alias'],
    'isMage' => ($_SESSION['user']['role'] === 'Mage'),
    'balance' => [
        'gold' => $_SESSION['user']['gold'],
        'silver' => $_SESSION['user']['silver'],
        'bronze' => $_SESSION['user']['bronze']
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

// Requête enrichie pour récupérer les items avec propriétés et rareté
try {
    $stmt = $pdo->prepare(
        "SELECT
            inv.InventoryId AS inventory_id,
            inv.ItemId AS item_id,
            inv.Quantity AS quantity,
            i.Name AS item_name,
            i.ImageUrl AS ImageUrl,
            i.Description AS item_description,
            i.PriceGold AS item_price_gold,
            i.PriceSilver AS item_price_silver,
            i.PriceBronze AS item_price_bronze,
            i.Rarity AS item_rarity,
            t.Name AS item_type,
            IFNULL(rating_agg.rating, 0) AS rating,
            IFNULL(rating_agg.review_count, 0) AS review_count,
            CASE WHEN user_review.review_id IS NULL THEN 0 ELSE 1 END AS is_rated_by_user,
            wp.DamageMin AS weapon_damage_min,
            wp.DamageMax AS weapon_damage_max,
            wp.Durability AS weapon_durability,
            wp.RequiredLevel AS weapon_required_level,
            wp.AttackSpeed AS weapon_attack_speed,
            ap.Defense AS armor_defense,
            ap.Durability AS armor_durability,
            ap.RequiredLevel AS armor_required_level,
            pp.EffectType AS potion_effect_type,
            pp.EffectValue AS potion_effect_value,
            pp.DurationSeconds AS potion_duration,
            mp.SpellDamage AS spell_damage,
            mp.ManaCost AS spell_mana_cost,
            mp.ElementType AS spell_element,
            mp.RequiredLevel AS spell_required_level,
            mp.CooldownSeconds AS spell_cooldown
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
        LEFT JOIN WeaponProperties wp ON wp.ItemId = inv.ItemId AND t.Name = 'Weapon'
        LEFT JOIN ArmorProperties ap ON ap.ItemId = inv.ItemId AND t.Name = 'Armor'
        LEFT JOIN PotionProperties pp ON pp.ItemId = inv.ItemId AND t.Name = 'Potion'
        LEFT JOIN MagicSpellProperties mp ON mp.ItemId = inv.ItemId AND t.Name = 'MagicSpell'
        WHERE inv.UserId = ?
        ORDER BY inv.InventoryId DESC"
    );
    $stmt->execute([$user['id'], $user['id']]);
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $inventoryError = "Impossible de charger votre inventaire pour le moment.";
}

// Items en attente d'évaluation (pour la bulle de notification)
if ($inventoryError === '') {
    try {
        $reviewStmt = $pdo->prepare(
            "SELECT
                inv.ItemId AS item_id,
                inv.Quantity AS quantity_owned,
                i.Name AS item_name,
                i.ImageUrl AS ImageUrl,
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
            GROUP BY inv.ItemId, inv.Quantity, i.Name, i.ImageUrl, t.Name
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
        <?php if (is_array($reviewFlash) && !empty($reviewFlash['message'])): ?>
            <?php $flashType = ($reviewFlash['type'] ?? '') === 'success' ? 'success-state' : 'error-state'; ?>
            <div class="inventory-state <?= $flashType ?>">
                <span><?= htmlspecialchars((string) $reviewFlash['message']) ?></span>
            </div>
        <?php endif; ?>

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
            <!-- Layout Elden Ring: Grille gauche + Détail droite -->
            <div class="elden-ring-layout">
                <!-- Colonne gauche: Grille d'items -->
                <div class="items-grid-column">
                    <div class="items-grid" id="items-grid">
                        <?php foreach ($inventoryItems as $index => $entry): ?>
                            <?php
                            $itemName = $entry['item_name'] ?? ('Item #' . $entry['item_id']);
                            $itemType = $entry['item_type'] ?? 'Inconnu';
                            $isSelected = $index === 0 ? 'selected' : '';
                            $itemImagePath = getItemImagePathForItem($entry);
                            
                            // Préparer les données pour JavaScript
                            $itemData = htmlspecialchars(json_encode([
                                'id' => $entry['item_id'],
                                'name' => $itemName,
                                'type' => $itemType,
                                'rarity' => $entry['item_rarity'] ?? 'Commun',
                                'description' => $entry['item_description'] ?: "Aucune description disponible.",
                                'quantity' => (int)$entry['quantity'],
                                'priceGold' => (int)($entry['item_price_gold'] ?? 0),
                                'priceSilver' => (int)($entry['item_price_silver'] ?? 0),
                                'priceBronze' => (int)($entry['item_price_bronze'] ?? 0),
                                'rating' => (float)($entry['rating'] ?? 0),
                                'reviewCount' => (int)($entry['review_count'] ?? 0),
                                'isRatedByUser' => (bool)$entry['is_rated_by_user'],
                                'icon' => getItemImage($itemType),
                                'imageUrl' => $itemImagePath,
                                // Propriétés spécifiques
                                'weapon' => $itemType === 'Weapon' ? [
                                    'damageMin' => (int)($entry['weapon_damage_min'] ?? 0),
                                    'damageMax' => (int)($entry['weapon_damage_max'] ?? 0),
                                    'durability' => (int)($entry['weapon_durability'] ?? 0),
                                    'requiredLevel' => (int)($entry['weapon_required_level'] ?? 0),
                                    'attackSpeed' => (float)($entry['weapon_attack_speed'] ?? 0)
                                ] : null,
                                'armor' => $itemType === 'Armor' ? [
                                    'defense' => (int)($entry['armor_defense'] ?? 0),
                                    'durability' => (int)($entry['armor_durability'] ?? 0),
                                    'requiredLevel' => (int)($entry['armor_required_level'] ?? 0)
                                ] : null,
                                'potion' => $itemType === 'Potion' ? [
                                    'effectType' => $entry['potion_effect_type'] ?? '',
                                    'effectValue' => (int)($entry['potion_effect_value'] ?? 0),
                                    'duration' => $entry['potion_duration'] ? (int)$entry['potion_duration'] : null
                                ] : null,
                                'spell' => $itemType === 'MagicSpell' ? [
                                    'damage' => (int)($entry['spell_damage'] ?? 0),
                                    'manaCost' => (int)($entry['spell_mana_cost'] ?? 0),
                                    'element' => $entry['spell_element'] ?? '',
                                    'requiredLevel' => (int)($entry['spell_required_level'] ?? 0),
                                    'cooldown' => (int)($entry['spell_cooldown'] ?? 0)
                                ] : null
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="item-slot <?= $isSelected ?>" 
                                 data-item-index="<?= $index ?>"
                                 data-item-data="<?= $itemData ?>">
                                <div class="item-slot-icon">
                                    <?php if ($itemImagePath !== null): ?>
                                        <img
                                            class="item-slot-image"
                                            src="<?= htmlspecialchars($itemImagePath, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                    <?php else: ?>
                                        <?= getItemImage($itemType) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="item-slot-name"><?= htmlspecialchars($itemName) ?></div>
                                <div class="item-slot-qty">x<?= (int)$entry['quantity'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Colonne droite: Panneau de détails -->
                <div class="detail-panel-column">
                    <div class="detail-panel" id="detail-panel">
                        <div class="detail-placeholder">
                            <p>Sélectionnez un item pour voir ses détails</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bulle de notification pour les évaluations -->
        <?php if (!empty($pendingReviewItems)): ?>
            <div class="review-notification-bubble" id="review-notification-bubble">
                <div class="review-bubble-icon">
                    <i class="fa-solid fa-star"></i>
                </div>
                <span class="review-bubble-count"><?= count($pendingReviewItems) ?></span>
            </div>

            <!-- Panneau flottant d'évaluation -->
            <div class="review-floating-panel" id="review-floating-panel" aria-hidden="true">
                <div class="review-panel-header">
                    <h3>Items à évaluer</h3>
                    <button class="review-panel-close" id="review-panel-close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="review-panel-content">
                    <?php foreach ($pendingReviewItems as $reviewItem): ?>
                        <?php
                        $reviewItemId = (int) $reviewItem['item_id'];
                        $reviewItemType = (string) ($reviewItem['item_type'] ?? 'Inconnu');
                        $reviewItemName = (string) ($reviewItem['item_name'] ?? ('Item #' . $reviewItemId));
                        $reviewItemImagePath = getItemImagePathForItem($reviewItem);
                        $ratingInputId = 'rating-input-' . $reviewItemId;
                        $ratingPreviewId = 'rating-preview-' . $reviewItemId;
                        ?>

                        <article class="pending-review-card" data-pending-item-id="<?= $reviewItemId ?>">
                            <div class="pending-review-item-meta">
                                <div class="pending-review-thumb" aria-hidden="true">
                                    <?php if ($reviewItemImagePath !== null): ?>
                                        <img
                                            class="pending-review-image"
                                            src="<?= htmlspecialchars($reviewItemImagePath, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="">
                                    <?php else: ?>
                                        <?= getItemImage($reviewItemType) ?>
                                    <?php endif; ?>
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
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== GESTION DE LA SÉLECTION D'ITEMS ==========
    const itemSlots = document.querySelectorAll('.item-slot');
    const detailPanel = document.getElementById('detail-panel');
    
    if (itemSlots.length > 0 && detailPanel) {
        // Sélectionner automatiquement le premier item au chargement
        const firstItem = itemSlots[0];
        selectItem(firstItem);
        
        // Ajouter les événements de clic sur chaque slot
        itemSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                // Retirer la sélection des autres
                itemSlots.forEach(s => s.classList.remove('selected'));
                // Sélectionner celui-ci
                this.classList.add('selected');
                // Mettre à jour le panneau de détails
                selectItem(this);
            });
        });
    }
    
    function selectItem(slot) {
        const itemData = JSON.parse(slot.dataset.itemData);
        renderDetailPanel(itemData);
    }
    
    function renderDetailPanel(item) {
        const rarityClass = getRarityClass(item.rarity);
        const rarityLabel = formatRarityLabel(item.rarity);
        const statusClass = item.isRatedByUser ? 'is-rated' : 'is-unrated';
        const detailVisual = item.imageUrl
            ? `<img class="detail-image-large" src="${item.imageUrl}" alt="">`
            : item.icon;
        const statusLabel = item.isRatedByUser ? 'Évalué' : 'Non évalué';
        
        // Générer les propriétés spécifiques
        let specificPropsHtml = '';
        
        if (item.weapon) {
            specificPropsHtml = `
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Dégâts</span>
                    <span class="detail-stat-value">${item.weapon.damageMin} - ${item.weapon.damageMax}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Durabilité</span>
                    <span class="detail-stat-value">${item.weapon.durability}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Niveau requis</span>
                    <span class="detail-stat-value">${item.weapon.requiredLevel}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Vitesse d'attaque</span>
                    <span class="detail-stat-value">${item.weapon.attackSpeed.toFixed(2)}</span>
                </div>
            `;
        } else if (item.armor) {
            specificPropsHtml = `
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Défense</span>
                    <span class="detail-stat-value">${item.armor.defense}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Durabilité</span>
                    <span class="detail-stat-value">${item.armor.durability}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Niveau requis</span>
                    <span class="detail-stat-value">${item.armor.requiredLevel}</span>
                </div>
            `;
        } else if (item.potion) {
            const durationText = item.potion.duration ? `<div class="detail-stat-row"><span class="detail-stat-label">Durée</span><span class="detail-stat-value">${item.potion.duration} sec</span></div>` : '';
            specificPropsHtml = `
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Effet</span>
                    <span class="detail-stat-value">${item.potion.effectType}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Valeur</span>
                    <span class="detail-stat-value">${item.potion.effectValue}</span>
                </div>
                ${durationText}
            `;
        } else if (item.spell) {
            specificPropsHtml = `
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Dégâts magiques</span>
                    <span class="detail-stat-value">${item.spell.damage}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Coût en mana</span>
                    <span class="detail-stat-value">${item.spell.manaCost}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Élément</span>
                    <span class="detail-stat-value">${item.spell.element}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Niveau requis</span>
                    <span class="detail-stat-value">${item.spell.requiredLevel}</span>
                </div>
                <div class="detail-stat-row">
                    <span class="detail-stat-label">Recharge</span>
                    <span class="detail-stat-value">${item.spell.cooldown} sec</span>
                </div>
            `;
        }
        
        const html = `
            <div class="detail-header">
                <div class="detail-icon-large">${detailVisual}</div>
                <h3 class="detail-title ${rarityClass}">${item.name}</h3>
                <div class="detail-meta">
                    <span class="detail-type">${item.type}</span>
                    <span class="detail-rarity ${rarityClass}">${rarityLabel}</span>
                </div>
            </div>
            
            <div class="detail-description">
                <p>${item.description}</p>
            </div>
            
            <div class="detail-stats-section">
                <h4 class="detail-section-title">Propriétés</h4>
                <div class="detail-stats-list">
                    ${specificPropsHtml}
                </div>
            </div>
            
            <div class="detail-price-section">
                <h4 class="detail-section-title">Prix</h4>
                <div class="detail-price-row">
                    <span class="detail-price-gold">${item.priceGold} <i class="fa-solid fa-coins" style="color:var(--gold)"></i></span>
                    <span class="detail-price-silver">${item.priceSilver} <i class="fa-solid fa-coins" style="color:var(--text-silver)"></i></span>
                    <span class="detail-price-bronze">${item.priceBronze} <i class="fa-solid fa-coins" style="color:#CD7F32"></i></span>
                </div>
            </div>
            
            <div class="detail-footer">
                <div class="detail-rating">
                    ${renderStars(item.rating)}
                    <span class="detail-rating-value">${item.rating.toFixed(1)}/5 (${item.reviewCount} évaluations)</span>
                </div>
                <div class="detail-status ${statusClass}">${statusLabel}</div>
            </div>
        `;
        
        detailPanel.innerHTML = html;
    }
    
    function getRarityClass(rarity) {
        const normalized = rarity.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        switch (normalized) {
            case 'commun': return 'rarity-commun';
            case 'rare': return 'rarity-rare';
            case 'epique': return 'rarity-epique';
            case 'legendaire': return 'rarity-legendaire';
            case 'mythique': return 'rarity-mythique';
            default: return 'rarity-commun';
        }
    }
    
    function formatRarityLabel(rarity) {
        const normalized = rarity.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        switch (normalized) {
            case 'commun': return 'Commun';
            case 'rare': return 'Rare';
            case 'epique': return 'Épique';
            case 'legendaire': return 'Légendaire';
            case 'mythique': return 'Mythique';
            default: return rarity;
        }
    }
    
    function renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalf = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalf ? 1 : 0);
        
        let html = '<span class="detail-stars">';
        for (let i = 0; i < fullStars; i++) {
            html += '<i class="fa-solid fa-star"></i>';
        }
        if (hasHalf) {
            html += '<i class="fa-solid fa-star-half-stroke"></i>';
        }
        for (let i = 0; i < emptyStars; i++) {
            html += '<i class="fa-regular fa-star"></i>';
        }
        html += '</span>';
        return html;
    }
    
    // ========== GESTION DU PANNEAU D'ÉVALUATION FLOTTANT ==========
    const reviewBubble = document.getElementById('review-notification-bubble');
    const reviewPanel = document.getElementById('review-floating-panel');
    const reviewClose = document.getElementById('review-panel-close');
    
    if (reviewBubble && reviewPanel) {
        // Ouvrir le panneau au clic sur la bulle
        reviewBubble.addEventListener('click', function() {
            reviewPanel.classList.add('visible');
            reviewPanel.setAttribute('aria-hidden', 'false');
        });
        
        // Fermer le panneau
        if (reviewClose) {
            reviewClose.addEventListener('click', function() {
                reviewPanel.classList.remove('visible');
                reviewPanel.setAttribute('aria-hidden', 'true');
            });
        }
        
        // Fermer en cliquant en dehors
        document.addEventListener('click', function(e) {
            if (reviewPanel.classList.contains('visible') && 
                !reviewPanel.contains(e.target) && 
                !reviewBubble.contains(e.target)) {
                reviewPanel.classList.remove('visible');
                reviewPanel.setAttribute('aria-hidden', 'true');
            }
        });
    }
    
    // ========== GESTION DU RATING PICKER ==========
    document.querySelectorAll('.rating-picker').forEach(function(picker) {
        const inputId = picker.dataset.inputId;
        const previewId = picker.dataset.previewId;
        const input = inputId ? document.getElementById(inputId) : null;
        const preview = previewId ? document.getElementById(previewId) : null;

        if (!input || !preview) return;

        updatePickerUI(picker, input, preview, input.value || '5');

        picker.addEventListener('click', function(event) {
            const button = event.target.closest('.rating-step-btn');
            if (!button) return;
            updatePickerUI(picker, input, preview, button.dataset.value || '5');
        });
    });

    function clampRatingValue(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) return 1;
        const clamped = Math.max(1, Math.min(5, numeric));
        return Math.round(clamped * 2) / 2;
    }

    function renderStarsMarkup(value) {
        const rating = clampRatingValue(value);
        const full = Math.floor(rating);
        const hasHalf = rating - full >= 0.5;
        const empty = 5 - full - (hasHalf ? 1 : 0);
        const parts = ['<span class="rating-stars" aria-hidden="true">'];

        for (let i = 0; i < full; i++) {
            parts.push('<i class="fa-solid fa-star"></i>');
        }
        if (hasHalf) {
            parts.push('<i class="fa-solid fa-star-half-stroke"></i>');
        }
        for (let i = 0; i < empty; i++) {
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

        preview.innerHTML = renderStarsMarkup(normalized) + '<span class="rating-value-inline">' + normalized.toFixed(1) + '/5</span>';
    }
    
    // ========== GESTION DES FORMULAIRES D'ÉVALUATION ==========
    document.querySelectorAll('.pending-review-form').forEach(function(form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            const card = form.closest('.pending-review-card');
            const messageBox = card ? card.querySelector('.pending-review-message') : null;
            const submitButton = form.querySelector('.btn-submit-rating');
            
            if (!card || !messageBox || !submitButton) return;

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

                // Mettre à jour le compteur de la bulle
                const bubbleCount = document.querySelector('.review-bubble-count');
                const remainingCards = document.querySelectorAll('.pending-review-card');
                
                setTimeout(function() {
                    card.remove();
                    
                    const newCount = document.querySelectorAll('.pending-review-card').length;
                    if (bubbleCount) {
                        bubbleCount.textContent = newCount;
                    }
                    
                    if (newCount === 0) {
                        const bubble = document.getElementById('review-notification-bubble');
                        const panel = document.getElementById('review-floating-panel');
                        if (bubble) bubble.remove();
                        if (panel) panel.remove();
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
