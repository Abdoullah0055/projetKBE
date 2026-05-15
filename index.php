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
        --catalog-gap: 16px;
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1c1e;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        position: relative;
        z-index: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    body::-webkit-scrollbar { display: none; }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(26, 28, 30, 0.7);
        z-index: -1;
        pointer-events: none;
    }

    .wrapper, main {
        background: transparent !important;
        min-height: 0;
    }

    aside { overflow-y: auto; }
    .sidebar-content { overflow-y: auto; }

    .product-list {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
        gap: 16px !important;
        grid-auto-rows: minmax(280px, auto);
    }

    .product-list .item-row {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: stretch;
        position: relative;
        isolation: isolate;
        width: 100%;
        min-height: 280px;
        padding: 14px;
    }

    main .product-list .item-row.hidden {
        display: none !important;
    }



    .catalog-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid var(--border-light);
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
        color: var(--text-primary);
        border: 1px solid var(--border-light);
        background: var(--bg-surface);
        transition: var(--transition);
    }

    .catalog-pagination .page-link:hover {
        background: var(--accent-soft);
        border-color: var(--border-accent);
    }

    .catalog-pagination .page-current {
        color: var(--gold);
        border: 1px solid var(--border-accent);
        background: var(--accent-soft);
    }

    .catalog-pagination .page-ellipsis {
        color: var(--text-muted);
    }

    .catalog-pagination .page-nav { min-width: auto; padding: 0 12px; }

    #no-results-message {
        display: none;
        text-align: center;
        color: var(--accent);
        padding: 20px;
        background: var(--bg-surface);
        border-radius: var(--radius-md);
        margin: 20px 0;
        border: 1px solid var(--border-light);
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

    aside.collapsed .sidebar-inventory-btn { padding: 12px 8px; overflow: hidden; }
    aside.collapsed .sidebar-inventory-btn .btn-label { display: none !important; }

    /* Scroll reveal */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .reveal-on-scroll.revealed {
        opacity: 1;
        transform: translateY(0);
    }
    .item-row:nth-child(1) { transition-delay: 0.02s; }
    .item-row:nth-child(2) { transition-delay: 0.04s; }
    .item-row:nth-child(3) { transition-delay: 0.06s; }
    .item-row:nth-child(4) { transition-delay: 0.08s; }
    .item-row:nth-child(5) { transition-delay: 0.10s; }
    .item-row:nth-child(6) { transition-delay: 0.12s; }
    .item-row:nth-child(7) { transition-delay: 0.14s; }
    .item-row:nth-child(8) { transition-delay: 0.16s; }
    .item-row:nth-child(9) { transition-delay: 0.18s; }
    .item-row:nth-child(10) { transition-delay: 0.20s; }
    .item-row:nth-child(11) { transition-delay: 0.22s; }
    .item-row:nth-child(12) { transition-delay: 0.24s; }
    .item-row:nth-child(13) { transition-delay: 0.26s; }
    .item-row:nth-child(14) { transition-delay: 0.28s; }
    .item-row:nth-child(15) { transition-delay: 0.30s; }
    .item-row:nth-child(16) { transition-delay: 0.32s; }
    .item-row:nth-child(17) { transition-delay: 0.34s; }
    .item-row:nth-child(18) { transition-delay: 0.36s; }
    .item-row:nth-child(19) { transition-delay: 0.38s; }
    .item-row:nth-child(20) { transition-delay: 0.40s; }
    .item-row:nth-child(21) { transition-delay: 0.42s; }
    .item-row:nth-child(22) { transition-delay: 0.44s; }
    .item-row:nth-child(23) { transition-delay: 0.46s; }
    .item-row:nth-child(24) { transition-delay: 0.48s; }
    .item-row:nth-child(25) { transition-delay: 0.50s; }
</style>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<button id="toggle-btn" type="button" aria-expanded="true" aria-label="Réduire la sidebar">
<span id="arrow-icon">«</span>
</button>
<div class="wrapper">
<aside id="sidebar">
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
class="item-row reveal-on-scroll <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
data-type="<?= htmlspecialchars($normType) ?>"
data-name="<?= htmlspecialchars(mb_strtolower($item['nom'], 'UTF-8')) ?>"
data-rarity="<?= htmlspecialchars($rarityClass) ?>"
data-price="<?= (float)$item['prix'] ?>"
data-rating="<?= (float)$item['rating'] ?>"
data-rarity-order="<?= $rarityOrder ?>"
onclick="window.location.href='details.php?id=<?= (int)$item['id'] ?>'">

                    <div class="item-card-glow"></div>

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

                    <div class="item-card-body">
                        <h3 class="item-name"><?= htmlspecialchars($item['nom']) ?></h3>

                        <div class="item-meta">
                            <span class="item-type"><?= htmlspecialchars(ucfirst($item['type'])) ?></span>
                            <?php if ((int)$item['stock'] === 0): ?>
                                <span class="item-stock-tag">Épuisé</span>
                            <?php endif; ?>
                        </div>

                        <div class="item-footer">
                            <span class="item-price"><i class="fa-solid fa-coins"></i> <?= number_format((float)$item['prix'], 0, ',', ' ') ?> GP</span>
                            <span class="item-rating"><?= renderRatingStars((float)$item['rating']) ?></span>
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

        /* ScrollReveal */
        class ScrollReveal {
          constructor() {
            this.observer = new IntersectionObserver(function(entries) {
              entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                  entry.target.classList.add('revealed');
                  this.unobserve(entry.target);
                }
              });
            }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });
            document.querySelectorAll('.item-row').forEach(function(el) {
              this.observer.observe(el);
            }.bind(this));
          }
        }
        new ScrollReveal();

        /* Hero spotlight */
        (function initHeroSpotlight() {
          var spot = document.querySelector('.hero-spotlight');
          if (!spot) return;
          var items = Array.from(document.querySelectorAll('.item-row'));
          var order = { 'rarity-mythique': 5, 'rarity-legendaire': 4, 'rarity-epique': 3, 'rarity-rare': 2, 'rarity-commun': 1 };
          items.sort(function(a, b) {
            return (order[b.dataset.rarity] || 0) - (order[a.dataset.rarity] || 0);
          });
          items.slice(0, 3).forEach(function(item) {
            var clone = item.cloneNode(true);
            clone.classList.remove('reveal-on-scroll');
            (function(orig) {
              clone.addEventListener('click', function() { orig.click(); });
            })(item);
            spot.appendChild(clone);
          });
        })();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
