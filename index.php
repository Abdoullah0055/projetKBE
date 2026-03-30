<?php
// On garde le config de votre ami et on ajoute votre connexion BD test
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once 'AlgosBD.php';

// On s'assure que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

// 1. VRAIE GESTION DE LA SESSION (US-02)
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

// 2. RÉCUPÉRATION DES ITEMS DEPUIS LA BD (PDO)
$stmt = $pdo->query("
    SELECT 
        i.ItemId as id, 
        i.Name as nom, 
        t.Name as type, 
        'Commun' as rarete, 
        i.PriceGold as prix, 
        i.Stock as stock, 
        IFNULL(AVG(r.Rating), 0) as rating, 
        COUNT(r.ReviewId) as reviews
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.IsActive = TRUE
    GROUP BY i.ItemId, i.Name, t.Name, i.PriceGold, i.Stock
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "L'Arsenal - Marché Noir";

// Gestion du thème via Cookie (30 jours)
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";
$iconClass = ($currentTheme === 'dark') ? 'fa-sun' : 'fa-moon';

/*
|--------------------------------------------------------------------------
| Normalisation des types pour le filtre
|--------------------------------------------------------------------------
| Adapte les noms venant de la BD vers des valeurs cohérentes pour le JS
*/
function normalizeItemType(string $type): string
{
    $t = strtolower(trim($type));

    return match ($t) {
        'arme', 'armes', 'weapon', 'weapons' => 'weapon',
        'armure', 'armures', 'armor', 'armors' => 'armor',
        'potion', 'potions' => 'potion',
        'sort', 'sorts', 'magicspell', 'spell', 'spells' => 'magicspell',
        default => $t
    };
}
?>

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

    .wrapper,
    main {
        background: transparent !important;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: white;
        padding: 10px;
        border-radius: 4px;
        outline: none;
    }

    .filter-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .filter-group {
        margin-bottom: 12px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
    }

    #no-results-message {
        display: none;
        margin-top: 20px;
        text-align: center;
        color: #ddd;
        font-weight: bold;
        padding: 18px;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
</style>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
    <aside id="sidebar">
        <button id="toggle-btn" onclick="toggleMenu()">
            <span id="arrow-icon">«</span>
        </button>

        <div class="sidebar-content">
            <div class="show-icon">🔍</div>

            <div class="hide-text">
                <form class="filter-section" onsubmit="return false;">
                    <div class="filter-group">
                        <label for="search-filter">Recherche</label>
                        <input
                            type="text"
                            id="search-filter"
                            class="filter-input"
                            placeholder="Nom de l'item...">
                    </div>

                    <div class="filter-group">
                        <label for="type-filter">Catégorie</label>
                        <select id="type-filter" name="type" class="filter-select">
                            <option value="all">Tous les items</option>
                            <option value="weapon">Armes</option>
                            <option value="armor">Armures</option>
                            <option value="potion">Potions</option>
                            <option value="magicspell">Sorts</option>
                        </select>
                    </div>

                    <button type="button" id="reset-filters"
                        style="width:100%; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">
                        Réinitialiser
                    </button>
                </form>
            </div>

            <div class="cta-box">
                <div class="hide-text">
                    <?php if ($user['isConnected']): ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Essais énigmes : <b style="color:var(--accent)">5 / 5</b></p>
                    <?php else: ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Besoin d'or ?</p>
                    <?php endif; ?>

                    <a href="#"
                        style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">
                        Résoudre des énigmes
                    </a>
                </div>
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
            <?php foreach ($items as $item): ?>
                <?php $normalizedType = normalizeItemType($item['type']); ?>

                <div class="item-row <?= ((int)$item['stock'] === 0) ? 'item-out-of-stock' : '' ?>"
                    data-type="<?= htmlspecialchars($normalizedType) ?>"
                    data-name="<?= htmlspecialchars(strtolower($item['nom'])) ?>"
                    data-stock="<?= (int)$item['stock'] ?>"
                    onclick="window.location.href='<?= BASE_URL ?>/details.php?id=<?= (int)$item['id'] ?>'"
                    style="cursor:pointer;">

                    <div class="item-icon"><?= getItemImage($item['type']) ?></div>

                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <h3><?= htmlspecialchars($item['nom']) ?></h3>
                            <span style="background: rgba(25, 133, 161, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">
                                <?= htmlspecialchars($item['rarete']) ?>
                            </span>
                            <span style="background: rgba(255,255,255,0.08); color: #ddd; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">
                                <?= htmlspecialchars($item['type']) ?>
                            </span>
                        </div>

                        <div style="margin-top: 5px;">
                            <span style="color: var(--gold);">★ ★ ★ ★ ☆</span>
                            <small style="color: var(--text-silver); margin-left: 5px;">
                                (<?= (int)$item['reviews'] ?> aventuriers)
                            </small>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div class="item-price"><?= number_format((int)$item['prix'], 0) ?> GP</div>

                        <?php if ((int)$item['stock'] > 0): ?>
                            <small style="color: #2ECC71; font-weight: bold;">En stock: <?= (int)$item['stock'] ?></small>
                        <?php else: ?>
                            <small style="color: #E74C3C; font-weight: bold;">Rupture de stock</small>
                        <?php endif; ?>
                    </div>

                    <div class="item-action-btns" style="margin-left: 20px;">
                        <?php if ($user['isConnected']): ?>
                            <?php if ((int)$item['stock'] === 0): ?>
                                <button disabled style="background:#444; cursor:not-allowed;">Épuisé</button>
                            <?php elseif ($normalizedType === 'magicspell' && !$user['isMage']): ?>
                                <button disabled title="Niveau Mage requis" style="background:#666; font-size:0.7rem;">
                                    Mage Requis
                                </button>
                            <?php else: ?>
                                <button
                                    onclick="event.stopPropagation(); window.location.href='details.php?id=<?= (int)$item['id'] ?>'"
                                    style="padding: 5px 12px; font-size: 0.8rem; background:var(--accent); color:white; border:none; border-radius:4px; cursor:pointer;">
                                    Acheter
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="details.php?id=<?= (int)$item['id'] ?>"
                                onclick="event.stopPropagation();"
                                style="text-decoration:none; color:var(--accent); font-size:1.5rem;">
                                ➔
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-results-message">
            Aucun item ne correspond à votre recherche.
        </div>

        <div class="pagination">
            <a href="#">&laquo; Précédent</a>
            <span>Page <strong>1</strong> sur 12</span>
            <a href="#">Suivant &raquo;</a>
        </div>
    </main>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const typeFilter = document.getElementById("type-filter");
        const searchFilter = document.getElementById("search-filter");
        const resetBtn = document.getElementById("reset-filters");
        const items = document.querySelectorAll(".product-list .item-row");
        const noResultsMessage = document.getElementById("no-results-message");

        function applyFilters() {
            const selectedType = (typeFilter.value || "all").toLowerCase();
            const searchValue = (searchFilter.value || "").trim().toLowerCase();

            let visibleCount = 0;

            items.forEach(item => {
                const itemType = (item.dataset.type || "").toLowerCase();
                const itemName = (item.dataset.name || "").toLowerCase();

                const matchesType = selectedType === "all" || itemType === selectedType;
                const matchesSearch = searchValue === "" || itemName.includes(searchValue);

                if (matchesType && matchesSearch) {
                    item.style.display = "";
                    visibleCount++;
                } else {
                    item.style.display = "none";
                }
            });

            noResultsMessage.style.display = visibleCount === 0 ? "block" : "none";
        }

        typeFilter.addEventListener("change", applyFilters);
        searchFilter.addEventListener("input", applyFilters);

        resetBtn.addEventListener("click", function () {
            typeFilter.value = "all";
            searchFilter.value = "";
            applyFilters();
        });

        applyFilters();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>