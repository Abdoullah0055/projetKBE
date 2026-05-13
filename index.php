<?php
require_once __DIR__ . '/config/config.php';
require_once 'AlgosBD.php';

$pdo = get_pdo();

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
        min-height: 0;
    }

    aside {
        overflow-y: auto;
    }

.sidebar-content {
  overflow-y: auto;
}

.product-list {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
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
    min-height: 260px;
    padding: 12px;
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
        background: linear-gradient(135deg,
                var(--rarity-tint-strong, rgba(43, 85, 61, 0.38)) 0%,
                var(--rarity-tint-soft, rgba(43, 85, 61, 0.16)) 52%,
                rgba(0, 0, 0, 0) 88%);
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

/* ========== RESPONSIVE - moved to responsive.css ========== */

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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

aside.collapsed .sidebar-inventory-btn {
    padding: 12px 8px;
    overflow: hidden;
}

aside.collapsed .sidebar-inventory-btn .btn-label {
    display: none !important;
}
</style>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
<aside id="sidebar">
<button id="toggle-btn" type="button" aria-expanded="true" aria-label="Réduire la sidebar">
<span id="arrow-icon">«</span>
</button>

<?php
$sidebarIcon = '🔍';
$sidebarTitle = '';
$sidebarDesc = '';
$showDoorBtn = true;
include __DIR__ . '/includes/sidebar_filters.php';
?>
</aside>

    <main>
  <div class="catalog-banner">
    <h2>
      <?= $user['isConnected'] ? "Content de vous revoir, " . htmlspecialchars($user['alias']) : "Catalogue des Reliques" ?>
    </h2>
  </div>

        <div class="product-list" id="product-list">
<?php foreach ($items as $item):
$normType = normalizeItemType($item['type']);
$rarityLabel = formatRarityLabel((string)($item['rarete'] ?? 'Commun'));
$rarityClass = getRarityClass($rarityLabel);
$itemImagePath = getItemImagePath((string)$item['nom']);
$rarityOrderMap = ['commun'=>1, 'rare'=>2, 'epique'=>3, 'legendaire'=>4, 'mythique'=>5];
$rarityOrder = $rarityOrderMap[strtolower($rarityLabel)] ?? 1;
?>
<div
class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
data-type="<?= htmlspecialchars($normType) ?>"
data-name="<?= htmlspecialchars(mb_strtolower($item['nom'], 'UTF-8')) ?>"
data-rarity="<?= htmlspecialchars($rarityClass) ?>"
data-price="<?= (float)$item['prix'] ?>"
data-rating="<?= (float)$item['rating'] ?>"
data-rarity-order="<?= $rarityOrder ?>"
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
  const sortFilter = document.getElementById("sort-filter");
  const rarityFilter = document.getElementById("rarity-filter");
  const priceFilter = document.getElementById("price-filter");
  const resetBtn = document.getElementById("reset-filters");
  const mobileTypeFilter = document.getElementById("mobile-type-filter");
  const mobileSearchFilter = document.getElementById("mobile-search-filter");
  const mobileSortFilter = document.getElementById("mobile-sort-filter");
  const mobileRarityFilter = document.getElementById("mobile-rarity-filter");
  const mobilePriceFilter = document.getElementById("mobile-price-filter");
  const mobileResetBtn = document.getElementById("mobile-reset-filters");
  const items = document.querySelectorAll(".item-row");
  const noResults = document.getElementById("no-results-message");
  const pagination = document.getElementById("catalog-pagination");
  const productList = document.getElementById("product-list");

  const sidebar = document.getElementById('sidebar');

  function applyFilters() {
    const selectedType = typeFilter.value;
    const searchValue = searchFilter.value.toLowerCase().trim();
    const selectedRarity = rarityFilter.value;
    const maxPrice = priceFilter.value !== '' ? parseFloat(priceFilter.value) : null;
    let count = 0;

    items.forEach(item => {
      const matchesType = (selectedType === "all" || item.dataset.type === selectedType);
      const matchesSearch = (searchValue === "" || item.dataset.name.includes(searchValue));
      const matchesRarity = (selectedRarity === "all" || item.dataset.rarity === selectedRarity);
      const matchesPrice = (maxPrice === null || parseFloat(item.dataset.price) <= maxPrice);

      if (matchesType && matchesSearch && matchesRarity && matchesPrice) {
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

    sortItems();
  }

  function sortItems() {
    const sortValue = sortFilter.value;
    const parts = sortValue.split('-');
    const sortKey = parts[0];
    const sortDir = parts[1];
    const itemsArray = Array.from(items);

    itemsArray.sort((a, b) => {
      let valA, valB;
      switch (sortKey) {
        case 'name':
          valA = a.dataset.name || '';
          valB = b.dataset.name || '';
          return sortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
        case 'price':
          valA = parseFloat(a.dataset.price) || 0;
          valB = parseFloat(b.dataset.price) || 0;
          return sortDir === 'asc' ? valA - valB : valB - valA;
        case 'rating':
          valA = parseFloat(a.dataset.rating) || 0;
          valB = parseFloat(b.dataset.rating) || 0;
          return sortDir === 'asc' ? valA - valB : valB - valA;
        case 'rarity':
          valA = parseInt(a.dataset.rarityOrder) || 1;
          valB = parseInt(b.dataset.rarityOrder) || 1;
          return sortDir === 'asc' ? valA - valB : valB - valA;
        default:
          return 0;
      }
    });

    itemsArray.forEach(item => productList.appendChild(item));
  }

  typeFilter.addEventListener("change", applyFilters);
  searchFilter.addEventListener("input", applyFilters);
  sortFilter.addEventListener("change", applyFilters);
  rarityFilter.addEventListener("change", applyFilters);
  priceFilter.addEventListener("input", applyFilters);

  resetBtn.addEventListener("click", () => {
    typeFilter.value = "all";
    searchFilter.value = "";
    sortFilter.value = "name-asc";
    rarityFilter.value = "all";
    priceFilter.value = "";
    applyFilters();
  });

  function syncDesktopToMobileFilters() {
    if (mobileTypeFilter) mobileTypeFilter.value = typeFilter.value;
    if (mobileSearchFilter) mobileSearchFilter.value = searchFilter.value;
    if (mobileSortFilter) mobileSortFilter.value = sortFilter.value;
    if (mobileRarityFilter) mobileRarityFilter.value = rarityFilter.value;
    if (mobilePriceFilter) mobilePriceFilter.value = priceFilter.value;
  }

  function bindMobileFilter(mobileEl, desktopEl, eventName) {
    if (!mobileEl || !desktopEl) return;
    mobileEl.addEventListener(eventName, () => {
      desktopEl.value = mobileEl.value;
      applyFilters();
    });
  }

  bindMobileFilter(mobileTypeFilter, typeFilter, "change");
  bindMobileFilter(mobileSearchFilter, searchFilter, "input");
  bindMobileFilter(mobileSortFilter, sortFilter, "change");
  bindMobileFilter(mobileRarityFilter, rarityFilter, "change");
  bindMobileFilter(mobilePriceFilter, priceFilter, "input");

  if (mobileResetBtn) {
    mobileResetBtn.addEventListener("click", () => {
      typeFilter.value = "all";
      searchFilter.value = "";
      sortFilter.value = "name-asc";
      rarityFilter.value = "all";
      priceFilter.value = "";
      applyFilters();
      syncDesktopToMobileFilters();
    });
  }

        async function handleCapitalRequest(button) {
                button.disabled = true;
                try {
                    const response = await fetch("demande_capital.php", {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (_error) {
                        data = null;
                    }

                    if (response.ok && data && data.success) {
                        showToast(data.message || "Demande envoyee.", "succes");
                    } else {
                        showToast((data && data.message) ? data.message : "Echec de la demande.", "erreur");
                    }
                } catch (_error) {
                    showToast("Erreur reseau pendant l'envoi de la demande.", "erreur");
                } finally {
                    button.disabled = false;
                }
        }

        const capitalRequestButtons = [
            document.getElementById("capital-request-sidebar-btn"),
            document.getElementById("capital-request-drawer-btn")
        ];

        capitalRequestButtons.forEach((btn) => {
            if (!btn) return;
            btn.addEventListener("click", () => handleCapitalRequest(btn));
        });

        if (resetBtn) {
            resetBtn.addEventListener("click", syncDesktopToMobileFilters);
        }

        applyFilters();
        syncDesktopToMobileFilters();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
