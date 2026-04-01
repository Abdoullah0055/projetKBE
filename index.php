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
        'arme', 'armes'   => 'arme',
        'armure', 'armures' => 'armure',
        'potion', 'potions' => 'potion',
        'sort', 'sorts'     => 'sort',
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
                        <label>Recherche</label>
                        <input type="text" id="search-filter" class="filter-input" placeholder="Nom de l'objet...">
                    </div>

                    <div class="filter-group" style="margin-top:15px;">
                        <label>Catégorie</label>
                        <select id="type-filter" class="filter-select">
                            <option value="all">Tous les items</option>
                            <option value="arme">Armes</option>
                            <option value="armure">Armures</option>
                            <option value="potion">Potions</option>
                            <option value="sort">Sorts</option>
                        </select>
                    </div>

                    <button type="button" id="reset-filters"
                        style="width:100%; margin-top:20px; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">
                        Réinitialiser
                    </button>
                </form>
            </div>

            <div class="cta-box">
                <div class="hide-text">
                    <p style="margin:0 0 8px 0; font-size:0.9rem;">
                        <?= $user['isConnected'] ? "Essais énigmes : <b style='color:var(--accent)'>5 / 5</b>" : "Besoin d'or ?" ?>
                    </p>
                    <a href="#" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
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
            <?php foreach ($items as $item):
                $normType = normalizeItemType($item['type']);
            ?>
                <div class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?>"
                    data-type="<?= $normType ?>"
                    data-name="<?= htmlspecialchars(strtolower($item['nom'])) ?>"
                    onclick="window.location.href='details.php?id=<?= $item['id'] ?>'"
                    style="cursor:pointer;">

                    <div class="item-icon"><?= getItemImage($item['type']) ?></div>

                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h3><?= htmlspecialchars($item['nom']) ?></h3>
                            <span style="background: rgba(25, 133, 161, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;"><?= $item['rarete'] ?></span>
                        </div>

                        <div style="margin-top: 5px;">
                            <span style="color: var(--gold);">★ ★ ★ ★ ☆</span>
                            <small style="color: var(--text-silver); margin-left: 5px;">(<?= $item['reviews'] ?> aventuriers)</small>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>
                        <small style="color: <?= ($item['stock'] > 0) ? '#2ECC71' : '#E74C3C' ?>; font-weight: bold;">
                            <?= ($item['stock'] > 0) ? "En stock: " . $item['stock'] : "Rupture de stock" ?>
                        </small>
                    </div>

                    <div class="item-action-btns" style="margin-left: 20px;">
                        <?php if ($user['isConnected']): ?>
                            <?php if ($item['stock'] == 0): ?>
                                <button disabled style="background:#444; cursor:not-allowed;">Épuisé</button>
                            <?php elseif ($normType == 'sort' && !$user['isMage']): ?>
                                <button disabled title="Niveau Mage requis" style="background:#666; font-size:0.7rem;">Mage Requis</button>
                            <?php else: ?>
                                <button onclick="event.stopPropagation(); window.location.href='details.php?id=<?= $item['id'] ?>'"
                                    style="padding: 5px 12px; font-size: 0.8rem; background:var(--accent); color:white; border:none; border-radius:4px; cursor:pointer;">Acheter</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="details.php?id=<?= $item['id'] ?>" onclick="event.stopPropagation();" style="text-decoration:none; color:var(--accent); font-size:1.5rem;">➔</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-results-message">Aucun objet trouvé dans les archives...</div>

        <!-- <div class="pagination">
            <a href="#">&laquo; Précédent</a>
            <span>Page <strong>1</strong> sur 1</span>
            <a href="#">Suivant &raquo;</a>
        </div> -->
    </main>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const typeFilter = document.getElementById("type-filter");
        const searchFilter = document.getElementById("search-filter");
        const resetBtn = document.getElementById("reset-filters");
        const items = document.querySelectorAll(".item-row");
        const noResults = document.getElementById("no-results-message");

        function applyFilters() {
            const selectedType = typeFilter.value;
            const searchValue = searchFilter.value.toLowerCase().trim();
            let count = 0;

            items.forEach(item => {
                const matchesType = (selectedType === "all" || item.dataset.type === selectedType);
                const matchesSearch = (searchValue === "" || item.dataset.name.includes(searchValue));

                if (matchesType && matchesSearch) {
                    item.style.display = "flex";
                    count++;
                } else {
                    item.style.display = "none";
                }
            });

            noResults.style.display = (count === 0) ? "block" : "none";
        }

        typeFilter.addEventListener("change", applyFilters);
        searchFilter.addEventListener("input", applyFilters);
        resetBtn.addEventListener("click", () => {
            typeFilter.value = "all";
            searchFilter.value = "";
            applyFilters();
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>