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

// 2. RÉCUPÉRATION DES ITEMS
$itemsPerPage = 25;
$countStmt = $pdo->query("
    SELECT COUNT(*)
    FROM Items i
    WHERE i.IsActive = TRUE
");
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));

$pageFromQuery = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    ['options' => ['default' => 1, 'min_range' => 1]]
);
$currentPage = min((int) ($pageFromQuery ?: 1), $totalPages);
$offset = ($currentPage - 1) * $itemsPerPage;

$stmt = $pdo->prepare("
    SELECT 
        i.ItemId as id, 
        i.Name as nom, 
        t.Name as type, 
        COALESCE(i.Rarity, 'Commun') as rarete,
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
        --catalog-gap: 14px;
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        position: relative;
        z-index: 0;
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

    .wrapper,
    main {
        background: transparent !important;
    }

    .catalog-banner {
        margin-bottom: 18px;
        padding: 14px 18px;
        border-radius: 12px;
        background: rgba(12, 15, 19, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
    }

    .product-list {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 14px !important;
    }

    .product-list .item-row {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: stretch;
        position: relative;
        isolation: isolate;
        width: 100%;
        aspect-ratio: 4 / 5; /* 👈 carré propre */
        max-width: 240px;   /* 👈 limite taille */
        margin: 0 auto;     /* 👈 centre dans la colonne */
        padding: 12px;
        overflow: hidden;
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
        background: linear-gradient(
            135deg,
            var(--rarity-tint-strong, rgba(43, 85, 61, 0.38)) 0%,
            var(--rarity-tint-soft, rgba(43, 85, 61, 0.16)) 52%,
            rgba(0, 0, 0, 0) 88%
        );
        pointer-events: none;
        z-index: -1;
    }

    .product-list .item-row:hover {
        transform: translateY(-4px);
        background: rgba(18, 21, 26, 0.92);
        border-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 12px 22px rgba(0, 0, 0, 0.38);
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
        0%, 76%, 100% {
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
        gap: 6px;
        min-height: 28px;
    }

    .item-rarity-pill,
    .item-stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 0.58rem;
        letter-spacing: 0.38px;
        text-transform: uppercase;
        font-weight: 700;
        border: 1px solid transparent;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        white-space: nowrap;
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
        height: 124px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 8px;
        flex-shrink: 0;
    }

    .item-card-media .item-icon {
        font-size: clamp(2rem, 3vw, 2.8rem);
        width: auto;
        line-height: 1;
    }

    .item-card-media .item-card-image {
        display: block;
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .item-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
        flex: 1 1 auto;
    }

    .item-info h3 {
        margin: 0;
        font-size: 0.95rem;
        line-height: 1.25;
        min-height: 2.5em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
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
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .item-rating .rating-stars i {
        font-size: 0.7rem;
    }

    .item-rating small {
        color: var(--text-silver);
        font-size: 0.68rem;
    }

    .product-list .item-row.item-out-of-stock {
        opacity: 0.7;
        filter: saturate(0.55);
    }

    .product-list .item-row.item-out-of-stock:hover {
        transform: translateY(-2px);
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
        margin: 20px 0;
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

    @media (max-width: 1200px) {
        .product-list {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 800px) {
        .product-list {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 500px) {
        .product-list {
            grid-template-columns: 1fr !important;
        }
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

                    <button
                        type="button"
                        id="reset-filters"
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
                        <span class="btn-label">Inventaire</span>
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
                $itemImagePath = getItemImagePath((string)$item['nom']);
            ?>
                <div
                    class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
                    data-type="<?= htmlspecialchars($normType) ?>"
                    data-name="<?= htmlspecialchars(mb_strtolower($item['nom'], 'UTF-8')) ?>"
                    data-rarity="<?= htmlspecialchars($rarityClass) ?>"
                    onclick="window.location.href='details.php?id=<?= (int)$item['id'] ?>'">

                    <div class="item-card-head">
                        <span class="item-rarity-pill <?= htmlspecialchars($rarityClass) ?>">
                            <?= htmlspecialchars($rarityLabel) ?>
                        </span>

                        <?php if ((int)$item['stock'] === 0): ?>
                            <span class="item-stock-pill">Épuisé</span>
                        <?php endif; ?>
                    </div>

                    <div class="item-card-media">
                        <?php if ($itemImagePath !== null): ?>
                            <img
                                class="item-card-image"
                                src="<?= htmlspecialchars($itemImagePath, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="item-icon"><?= getItemImage($item['type']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="item-info">
                        <h3><?= htmlspecialchars($item['nom']) ?></h3>

                        <div class="item-price-line">
                            <p class="item-price"><?= number_format((float)$item['prix'], 0, ',', ' ') ?> GP</p>
                        </div>

                        <div class="item-rating">
                            <?= renderRatingStars((float)$item['rating']) ?>
                            <small><?= formatRatingValue((float)$item['rating']) ?>/5</small>
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

            items.forEach(item => {
                const matchesType = (selectedType === "all" || item.dataset.type === selectedType);
                const matchesSearch = (searchValue === "" || item.dataset.name.includes(searchValue));

                if (matchesType && matchesSearch) {
                    item.style.display = "";
                    count++;
                } else {
                    item.style.display = "none";
                }
            });

            noResults.style.display = (count === 0) ? "block" : "none";

            if (pagination) {
                pagination.style.display = (count === 0) ? "none" : "flex";
            }
        }

        typeFilter.addEventListener("change", applyFilters);
        searchFilter.addEventListener("input", applyFilters);

        resetBtn.addEventListener("click", () => {
            typeFilter.value = "all";
            searchFilter.value = "";
            applyFilters();
        });
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
