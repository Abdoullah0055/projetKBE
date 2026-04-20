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
        'balance' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]
    ];
}

// 2. RÉCUPÉRATION DES ITEMS (Ta requête SQL d'origine)
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
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "L'Arsenal - Marché Noir";

// Gestion du thème
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

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
        --catalog-card-max: clamp(192px, 14.8vw, 232px);
        --catalog-card-gap: 10px;
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

    .product-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--catalog-card-gap);
        align-items: start;
        justify-items: center;
    }

    .product-list .item-row {
        position: relative;
        isolation: isolate;
        aspect-ratio: 4 / 5;
        min-height: 0;
        width: 100%;
        max-width: var(--catalog-card-max);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 7px;
        padding: 9px;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        background: rgba(14, 16, 20, 0.86);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.28);
        transition: transform 0.24s ease, border-color 0.24s ease, box-shadow 0.24s ease, background 0.24s ease;
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
        font-size: clamp(1.8rem, 2.35vw, 2.55rem);
        width: auto;
    }

    .item-info {
        margin-top: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .item-info h3 {
        margin: 0;
        font-size: clamp(0.79rem, 0.96vw, 0.9rem);
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .item-price-line {
        margin-top: 1px;
    }

    .item-price {
        margin: 0;
        color: #d9c176;
        font-weight: 700;
        font-size: clamp(0.77rem, 1.02vw, 0.88rem);
    }

    .item-rating {
        margin-top: 1px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .item-rating .rating-stars i {
        font-size: 0.66rem;
    }

    .item-rating small {
        color: var(--text-silver);
        font-size: 0.63rem;
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

    .product-list .item-row.item-last-single-row-2 {
        grid-column: 1 / -1;
        max-width: var(--catalog-card-max);
        width: 100%;
        justify-self: center;
    }

    .product-list .item-row.item-last-single-row-3 {
        grid-column: 2;
    }

    .product-list .item-row.item-last-single-row-4 {
        grid-column: 2 / span 2;
    }

    .product-list .item-row.item-last-single-row-5 {
        grid-column: 3;
    }

    .product-list .item-row.item-last-single-row-6 {
        grid-column: 3 / span 2;
    }

    .product-list .item-row.item-last-single-row-7 {
        grid-column: 4;
    }

    @media (min-width: 600px) {
        .product-list {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 860px) {
        .product-list {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (min-width: 1160px) {
        .product-list {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }
    }

    @media (min-width: 1500px) {
        .product-list {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 11px;
        }
    }

    @media (min-width: 1840px) {
        .product-list {
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }
    }

    @media (max-width: 859px) {
        .product-list .item-row {
            max-width: 184px;
            padding: 8px;
        }

        .item-rarity-pill,
        .item-stock-pill {
            font-size: 0.55rem;
            padding: 2px 6px;
        }

        .item-info h3 {
            font-size: 0.76rem;
        }

        .item-price {
            font-size: 0.76rem;
        }

        .catalog-pagination {
            gap: 6px;
        }

        .catalog-pagination .page-link,
        .catalog-pagination .page-current,
        .catalog-pagination .page-ellipsis {
            min-width: 30px;
            height: 30px;
            font-size: 0.76rem;
        }
    }

    @media (max-width: 460px) {
        .product-list {
            grid-template-columns: 1fr;
        }

        .product-list .item-row {
            max-width: min(228px, 100%);
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

        function getGridColumns() {
            if (window.matchMedia("(min-width: 1840px)").matches) {
                return 7;
            }

            if (window.matchMedia("(min-width: 1500px)").matches) {
                return 6;
            }

            if (window.matchMedia("(min-width: 1160px)").matches) {
                return 5;
            }

            if (window.matchMedia("(min-width: 860px)").matches) {
                return 4;
            }

            if (window.matchMedia("(min-width: 600px)").matches) {
                return 3;
            }

            if (window.matchMedia("(min-width: 460px)").matches) {
                return 2;
            }

            return 1;
        }

        function refreshLastRowAlignment() {
            items.forEach(item => {
                item.classList.remove(
                    "item-last-single-row-2",
                    "item-last-single-row-3",
                    "item-last-single-row-4",
                    "item-last-single-row-5",
                    "item-last-single-row-6",
                    "item-last-single-row-7"
                );
            });

            const visibleItems = Array.from(items).filter(item => item.style.display !== "none");
            const columns = getGridColumns();

            if (columns <= 1 || visibleItems.length === 0) {
                return;
            }

            if (visibleItems.length % columns === 1) {
                const orphanItem = visibleItems[visibleItems.length - 1];
                orphanItem.classList.add(`item-last-single-row-${columns}`);
            }
        }

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

            refreshLastRowAlignment();
        }

        typeFilter.addEventListener("change", applyFilters);
        searchFilter.addEventListener("input", applyFilters);
        resetBtn.addEventListener("click", () => {
            typeFilter.value = "all";
            searchFilter.value = "";
            applyFilters();
        });

        window.addEventListener("resize", refreshLastRowAlignment);
        refreshLastRowAlignment();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
