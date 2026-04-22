<?php
require_once __DIR__ . '/config/config.php';
require_once 'AlgosBD.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

// 1. GESTION DE LA SESSION
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage') || $_SESSION['user']['role'] === 'Admin',
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
        'balance' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]
    ];
}

// 2. RÉCUPÉRATION DE TOUS LES ITEMS (pour filtrage côté client)
$stmt = $pdo->prepare("
    SELECT
        i.ItemId as id,
        i.Name as nom,
        t.Name as type,
        i.Rarity as rarete,
        i.PriceGold as prix,
        i.Stock as stock,
        IFNULL(AVG(r.Rating), 0) as rating,
        COUNT(r.ReviewId) as reviews
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.IsActive = TRUE
    GROUP BY i.ItemId, i.Name, t.Name, i.Rarity, i.PriceGold, i.Stock
    ORDER BY i.ItemId ASC
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Plus besoin de pagination côté serveur
$totalPages = 1;
$currentPage = 1;

$title = "L'Arsenal - Marché Noir";

// Gestion du thème
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

/**
 * Normalisation pour le filtrage JS
 */
function normalizeItemType(string $type): string
{
    $t = mb_strtolower(trim($type), 'UTF-8');
    return match ($t) {
        'weapon', 'weapons', 'arme', 'armes' => 'weapon',
        'armor', 'armour', 'armors', 'armours', 'armure', 'armures' => 'armor',
        'potion', 'potions' => 'potion',
        'magicspell', 'magic spell', 'magic spells', 'sort', 'sorts' => 'magicspell',
        default => $t
    };
}

function buildPageUrl(int $targetPage): string
{
    $params = $_GET;
    $params['page'] = max(1, $targetPage);

    return 'index.php?' . http_build_query($params);
}
?>

<style>
:root {
    --main-bg: url('<?= $bgImage ?>');
    --card-base-width: 200px;
    --card-min-width: 180px;
    --card-max-width: 240px;
    --card-height: 280px;
    --catalog-card-gap: clamp(12px, 1.5vw, 20px);
    --cards-per-row: 7;
}

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e;
        position: relative;
        z-index: 0;
        /* On cache la scrollbar comme demandé précédemment */
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    body::-webkit-scrollbar {
        display: none;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: -1;
        pointer-events: none;
    }

/* Styles spécifiques pour le catalogue - écrasent style.css */
main .product-list {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    gap: var(--catalog-card-gap);
    align-items: flex-start;
    justify-content: center;
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
}

/* Styles spécifiques pour les cartes du catalogue - écrasent style.css */
main .product-list .item-row {
    position: relative;
    isolation: isolate;
    width: auto !important;
    min-width: var(--card-min-width);
    max-width: var(--card-max-width);
    height: var(--card-height);
    flex: 1 1 var(--card-base-width);
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    align-items: stretch !important;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: rgba(14, 16, 20, 0.86) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.28);
    transition: transform 0.24s ease, border-color 0.24s ease, box-shadow 0.24s ease, background 0.24s ease;
    box-sizing: border-box;
    backdrop-filter: none !important;
}

/* Classe pour cacher les items filtrés */
main .product-list .item-row.hidden {
    display: none !important;
}

.product-list .item-row::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(135deg, var(--rarity-tint-strong, rgba(43, 85, 61, 0.38)) 0%, var(--rarity-tint-soft, rgba(43, 85, 61, 0.16)) 52%, rgba(0, 0, 0, 0) 88%);
        pointer-events: none;
        z-index: -1;
    }

    .product-list .item-row:hover {
        transform: translateY(-3px);
        background: rgba(18, 21, 26, 0.92);
        border-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 11px 18px rgba(0, 0, 0, 0.38);
    }

    .product-list .item-row.rarity-commun {
        --rarity-tint-strong: rgba(43, 92, 63, 0.42);
        --rarity-tint-soft: rgba(43, 92, 63, 0.18);
    }

    .product-list .item-row.rarity-rare {
        --rarity-tint-strong: rgba(38, 69, 112, 0.42);
        --rarity-tint-soft: rgba(38, 69, 112, 0.18);
    }

    .product-list .item-row.rarity-epique {
        --rarity-tint-strong: rgba(83, 62, 112, 0.44);
        --rarity-tint-soft: rgba(83, 62, 112, 0.2);
    }

    .product-list .item-row.rarity-legendaire {
        --rarity-tint-strong: rgba(118, 98, 50, 0.42);
        --rarity-tint-soft: rgba(118, 98, 50, 0.2);
    }

    .product-list .item-row.rarity-mythique {
        --rarity-tint-strong: rgba(201, 210, 222, 0.34);
        --rarity-tint-soft: rgba(201, 210, 222, 0.16);
    }

    .product-list .item-row.rarity-mythique::after {
        content: "";
        position: absolute;
        top: -30%;
        left: -58%;
        width: 90%;
        height: 220%;
        background: linear-gradient(115deg, rgba(255, 255, 255, 0) 0%, rgba(239, 243, 250, 0.12) 48%, rgba(255, 255, 255, 0) 100%);
        transform: rotate(8deg);
        opacity: 0;
        mix-blend-mode: screen;
        pointer-events: none;
        animation: mythic-sheen 9s ease-in-out infinite;
    }

    @keyframes mythic-sheen {

        0%,
        76%,
        100% {
            opacity: 0;
            transform: translateX(0) rotate(8deg);
        }

        84% {
            opacity: 0.9;
        }

        94% {
            opacity: 0;
            transform: translateX(240%) rotate(8deg);
        }
    }

    .item-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 5px;
    }

    .item-rarity-pill,
    .item-stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 2px 7px;
        font-size: 0.56rem;
        letter-spacing: 0.38px;
        text-transform: uppercase;
        font-weight: 700;
        border: 1px solid transparent;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    .item-rarity-pill.rarity-commun {
        background: rgba(43, 92, 63, 0.24);
        color: #8fd0a6;
        border-color: rgba(143, 208, 166, 0.4);
    }

    .item-rarity-pill.rarity-rare {
        background: rgba(38, 69, 112, 0.24);
        color: #8db0e7;
        border-color: rgba(141, 176, 231, 0.45);
    }

    .item-rarity-pill.rarity-epique {
        background: rgba(83, 62, 112, 0.24);
        color: #baa4d5;
        border-color: rgba(186, 164, 213, 0.45);
    }

    .item-rarity-pill.rarity-legendaire {
        background: rgba(118, 98, 50, 0.24);
        color: #d8c07e;
        border-color: rgba(216, 192, 126, 0.45);
    }

    .item-rarity-pill.rarity-mythique {
        background: rgba(201, 210, 222, 0.24);
        color: #eef2f9;
        border-color: rgba(238, 242, 249, 0.52);
    }

    .item-stock-pill {
        background: rgba(130, 36, 36, 0.22);
        color: #f3b4b4;
        border-color: rgba(243, 180, 180, 0.38);
    }

    .item-card-media {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 6px;
    }

.item-card-media .item-icon {
    font-size: 2.2rem;
    width: auto;
}

.item-info {
    margin-top: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.item-info h3 {
    margin: 0;
    font-size: 0.85rem;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2em;
}

.item-price-line {
    margin-top: 2px;
}

.item-price {
    margin: 0;
    color: #d9c176;
    font-weight: 700;
    font-size: 0.9rem;
}

.item-rating {
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.item-rating .rating-stars i {
    font-size: 0.7rem;
}

.item-rating small {
    color: var(--text-silver);
    font-size: 0.7rem;
}

    .product-list .item-row.item-out-of-stock {
        opacity: 0.7;
        filter: saturate(0.55);
    }

    .product-list .item-row.item-out-of-stock:hover {
        transform: translateY(-1px);
    }

    #no-results-message {
        margin-top: 24px;
    }

    .catalog-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .catalog-pagination .page-link,
    .catalog-pagination .page-current,
    .catalog-pagination .page-ellipsis {
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .catalog-pagination .page-link {
        color: var(--text-light);
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(18, 22, 27, 0.65);
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .catalog-pagination .page-link:hover {
        background: rgba(25, 133, 161, 0.24);
        border-color: rgba(25, 133, 161, 0.62);
    }

    .catalog-pagination .page-current {
        color: #fff;
        border: 1px solid rgba(25, 133, 161, 0.8);
        background: rgba(25, 133, 161, 0.45);
    }

    .catalog-pagination .page-ellipsis {
        color: var(--text-silver);
    }

.catalog-pagination .page-nav {
    min-width: auto;
    padding: 0 12px;
}

/* ========== RESPONSIVE - Flexbox uniquement ========== */

/* Responsive - ajustements pour petits écrans */
@media (max-width: 768px) {
    :root {
        --card-base-width: 180px;
        --card-min-width: 160px;
        --card-max-width: 200px;
        --card-height: 250px;
    }
    
    main .product-list {
        gap: 12px;
        padding: 8px;
    }
    
    .item-card-media .item-icon {
        font-size: 1.9rem;
    }
    
    .item-info h3 {
        font-size: 0.8rem;
    }
    
    .item-rarity-pill,
    .item-stock-pill {
        font-size: 0.55rem;
        padding: 2px 6px;
    }
}

@media (max-width: 480px) {
    :root {
        --card-base-width: 160px;
        --card-min-width: 140px;
        --card-max-width: 180px;
        --card-height: 220px;
    }
    
    main .product-list {
        gap: 10px;
        padding: 6px;
    }
    
    .item-card-media .item-icon {
        font-size: 1.7rem;
    }
    
    .item-info h3 {
        font-size: 0.75rem;
    }
    
    .item-price {
        font-size: 0.8rem;
    }
    
    .catalog-pagination {
        gap: 6px;
    }

    .catalog-pagination .page-link,
    .catalog-pagination .page-current,
    .catalog-pagination .page-ellipsis {
        min-width: 32px;
        height: 32px;
        font-size: 0.75rem;
    }
}

@media (max-width: 360px) {
    :root {
        --card-base-width: 140px;
        --card-min-width: 120px;
        --card-max-width: 160px;
        --card-height: 195px;
    }
    
    main .product-list {
        gap: 8px;
        padding: 5px;
    }
    
    .item-card-media .item-icon {
        font-size: 1.5rem;
    }
}

    /* Styles spécifiques au filtrage */
    .filter-input,
    .filter-select {
        width: 100%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: white;
        padding: 10px;
        border-radius: 4px;
        margin-top: 5px;
    }

    #no-results-message {
        display: none;
        text-align: center;
        color: var(--accent);
        padding: 20px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        margin: 20px;
    }

    .wrapper,
    main {
        background: transparent !important;
    }

    .sidebar-bottom-actions {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .sidebar-inventory-btn {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid rgba(25, 133, 161, 0.45);
        background: rgba(25, 133, 161, 0.18);
        color: var(--text-light);
        text-decoration: none;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        transition: 0.2s ease;
    }

    .sidebar-inventory-btn:hover {
        background: rgba(25, 133, 161, 0.3);
        border-color: var(--accent);
    }

    aside.collapsed .sidebar-inventory-btn {
        padding: 12px 8px;
    }

    aside.collapsed .sidebar-inventory-btn .btn-label {
        display: none;
    }
</style>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
    <aside id="sidebar">
        <button id="toggle-btn" type="button" aria-expanded="true" aria-label="Réduire la sidebar">
            <span id="arrow-icon">«</span>
        </button>

        <div class="sidebar-content">
            <div class="show-icon">🔍</div>

            <div class="hide-text">
                <form class="filter-section" onsubmit="return false;">
                    <div class="filter-group">
                        <label>Recherche</label>
                        <input type="text" id="search-filter" class="filter-input" placeholder="Nom de l'objet...">
                    </div>

                    <div class="filter-group" style="margin-top:15px;">
                        <label>Catégorie</label>
                        <select id="type-filter" class="filter-select">
                            <option value="all">Tous les items</option>
                            <option value="weapon">Armes</option>
                            <option value="armor">Armures</option>
                            <option value="potion">Potions</option>
                            <option value="magicspell">Sorts</option>
                        </select>
                    </div>

                    <button type="button" id="reset-filters"
                        style="width:100%; margin-top:20px; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">
                        Réinitialiser
                    </button>
                </form>

                <a href="roadmap.php" class="enigme-door-button" aria-label="Acceder aux enigmes">
                    <span class="enigme-door-button__frame">
                        <img
                            src="assets/img/doors/opened.png"
                            alt=""
                            class="enigme-door-button__image">
                    </span>
                </a>
            </div>

            <div class="sidebar-bottom-actions">
                <div class="cta-box">
                    <div class="hide-text">
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">
                            <?= $user['isConnected'] ? "Essais énigmes : <b style='color:var(--accent)'>5 / 5</b>" : "Besoin d'or ?" ?>
                        </p>
                        <a href="roadmap.php" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
                    </div>
                </div>

                <?php if ($user['isConnected']): ?>
                    <a href="inventory.php" class="sidebar-inventory-btn" title="Ouvrir mon inventaire">
                        <span aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
                        <span class="btn-label">Inventory</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <main>
        <div class="catalog-banner">
            <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.3rem;">
                <?= $user['isConnected'] ? "Content de vous revoir, " . htmlspecialchars($user['alias']) : "Catalogue des Reliques" ?>
            </h2>
        </div>

        <div class="product-list" id="product-list">
            <?php foreach ($items as $item):
                $normType = normalizeItemType($item['type']);
                $rarityLabel = formatRarityLabel((string)($item['rarete'] ?? 'Commun'));
                $rarityClass = getRarityClass($rarityLabel);
            ?>
                <div class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
                    data-type="<?= $normType ?>"
                    data-name="<?= htmlspecialchars(strtolower($item['nom'])) ?>"
                    data-rarity="<?= htmlspecialchars($rarityClass) ?>"
                    onclick="window.location.href='details.php?id=<?= $item['id'] ?>'"
                    style="cursor:pointer;">

                    <div class="item-card-head">
                        <span class="item-rarity-pill <?= htmlspecialchars($rarityClass) ?>"><?= htmlspecialchars($rarityLabel) ?></span>
                        <?php if ((int)$item['stock'] === 0): ?>
                            <span class="item-stock-pill">Epuise</span>
                        <?php endif; ?>
                    </div>

                    <div class="item-card-media">
                        <div class="item-icon"><?= getItemImage($item['type']) ?></div>
                    </div>

                    <div class="item-info">
                        <h3><?= htmlspecialchars($item['nom']) ?></h3>

                        <div class="item-price-line">
                            <p class="item-price"><?= number_format($item['prix'], 0) ?> GP</p>
                        </div>

                        <div class="item-rating">
                            <?= renderRatingStars((float) $item['rating']) ?>
                            <small>
                                <?= formatRatingValue((float) $item['rating']) ?>/5
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-results-message">Aucun objet trouvé dans les archives...</div>

        <?php if ($totalPages > 1): ?>
            <nav class="catalog-pagination" id="catalog-pagination" aria-label="Pagination des items">
                <?php if ($currentPage > 1): ?>
                    <a class="page-link page-nav" href="<?= htmlspecialchars(buildPageUrl($currentPage - 1)) ?>">&laquo; Prec.</a>
                <?php endif; ?>

                <?php
                $windowStart = max(1, $currentPage - 2);
                $windowEnd = min($totalPages, $currentPage + 2);
                ?>

                <?php if ($windowStart > 1): ?>
                    <a class="page-link" href="<?= htmlspecialchars(buildPageUrl(1)) ?>">1</a>
                    <?php if ($windowStart > 2): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($page = $windowStart; $page <= $windowEnd; $page++): ?>
                    <?php if ($page === $currentPage): ?>
                        <span class="page-current"><?= $page ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?= htmlspecialchars(buildPageUrl($page)) ?>"><?= $page ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($windowEnd < $totalPages): ?>
                    <?php if ($windowEnd < ($totalPages - 1)): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                    <a class="page-link" href="<?= htmlspecialchars(buildPageUrl($totalPages)) ?>"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="page-link page-nav" href="<?= htmlspecialchars(buildPageUrl($currentPage + 1)) ?>">Suiv. &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const typeFilter = document.getElementById("type-filter");
    const searchFilter = document.getElementById("search-filter");
    const resetBtn = document.getElementById("reset-filters");
    const items = document.querySelectorAll(".item-row");
    const noResults = document.getElementById("no-results-message");
    const pagination = document.getElementById("catalog-pagination");
    
    // --- GESTION RESPONSIVE DU SIDEBAR ---
    const sidebar = document.getElementById('sidebar');
    const productList = document.getElementById('product-list');

    function applyFilters() {
        const selectedType = typeFilter.value;
        const searchValue = searchFilter.value.toLowerCase().trim();
        let count = 0;
        let hiddenCount = 0;

        console.log("=== FILTRAGE ===");
        console.log("Type sélectionné:", selectedType);
        console.log("Recherche:", searchValue);

        items.forEach((item, index) => {
            // Debug: afficher les valeurs des premiers items
            if (index < 3) {
                console.log(`Item ${index}:`, {
                    name: item.dataset.name,
                    type: item.dataset.type,
                    display: item.style.display
                });
            }

            const matchesType = (selectedType === "all" || item.dataset.type === selectedType);
            const matchesSearch = (searchValue === "" || item.dataset.name.includes(searchValue));

            const shouldShow = matchesType && matchesSearch;

            if (shouldShow) {
                item.classList.remove('hidden');
                count++;
            } else {
                item.classList.add('hidden');
                hiddenCount++;
            }
        });

        console.log("Résultats:", { visible: count, hidden: hiddenCount, total: items.length });

        noResults.style.display = (count === 0) ? "block" : "none";

        if (pagination) {
            pagination.style.display = (count === 0) ? "none" : "flex";
        }

        // Réappliquer les styles de taille après le filtrage
        updateCardsForSidebar();
    }

    // Vérifier que les éléments existent
    if (!typeFilter || !searchFilter || !resetBtn) {
        console.error("Filtres non trouvés:", { typeFilter, searchFilter, resetBtn });
        return;
    }

    console.log("Filtres initialisés:", {
        itemsCount: items.length,
        typeFilter: typeFilter.id,
        searchFilter: searchFilter.id
    });

    typeFilter.addEventListener("change", function() {
        console.log("Type filtre changé:", this.value);
        applyFilters();
    });
    
    searchFilter.addEventListener("input", function() {
        console.log("Recherche:", this.value);
        applyFilters();
    });
    
    resetBtn.addEventListener("click", () => {
        console.log("Reset des filtres");
        typeFilter.value = "all";
        searchFilter.value = "";
        applyFilters();
    });

// Appliquer les filtres au chargement pour initialiser correctement
    applyFilters();

    function updateCardsForSidebar() {
        if (!sidebar || !productList) return;

        const sidebarWidth = sidebar.classList.contains('collapsed') ? 80 : 280;
        const mainWidth = window.innerWidth - sidebarWidth - 40;
        const gap = parseFloat(getComputedStyle(productList).gap) || 16;
        const cardMinWidth = 180;
        const cardMaxWidth = 240;

        // Calculer l'espace disponible
        const availableSpace = mainWidth;
        const maxCardsPerRow = Math.floor((availableSpace + gap) / (cardMinWidth + gap));
        const cardsPerRow = Math.min(maxCardsPerRow, 7);

        // Calculer la taille des cartes
        const totalGapSpace = (cardsPerRow - 1) * gap;
        const cardWidth = Math.min(
            Math.max(cardMinWidth, (availableSpace - totalGapSpace) / cardsPerRow),
            cardMaxWidth
        );

        // Appliquer UNIQUEMENT aux cartes visibles
        document.querySelectorAll('.item-row').forEach(card => {
            if (card.style.display !== 'none') {
                card.style.flex = `0 0 ${cardWidth}px`;
                card.style.maxWidth = `${cardWidth}px`;
            }
        });
    }
    
    // Observer les changements du sidebar
    if (sidebar) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    // Attendre la fin de la transition
                    setTimeout(updateCardsForSidebar, 300);
                }
            });
        });
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }
    
    // Mettre à jour sur redimensionnement
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(updateCardsForSidebar, 100);
    });
    
    // Initialiser
    setTimeout(updateCardsForSidebar, 100);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
