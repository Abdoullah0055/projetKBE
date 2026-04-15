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
        'weapon', 'weapons', 'arme', 'armes' => 'weapon',
        'armor', 'armour', 'armors', 'armours', 'armure', 'armures' => 'armor',
        'potion', 'potions' => 'potion',
        'magicspell', 'magic spell', 'magic spells', 'sort', 'sorts' => 'magicspell',
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

    .product-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(215px, 1fr));
        gap: 18px;
    }

    .product-list .item-row {
        position: relative;
        isolation: isolate;
        aspect-ratio: 4 / 5;
        min-height: 290px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 14px;
        padding: 14px;
        border-radius: 14px;
        overflow: hidden;
        cursor: pointer;
        background: rgba(14, 16, 20, 0.84);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 14px 26px rgba(0, 0, 0, 0.32);
        transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease, background 0.28s ease;
    }

    .product-list .item-row::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(135deg, var(--rarity-tint, rgba(43, 85, 61, 0.4)) 0%, rgba(0, 0, 0, 0) 42%);
        pointer-events: none;
        z-index: -1;
    }

    .product-list .item-row:hover {
        transform: translateY(-6px);
        background: rgba(18, 21, 26, 0.92);
        border-color: rgba(255, 255, 255, 0.24);
        box-shadow: 0 18px 30px rgba(0, 0, 0, 0.44);
    }

    .product-list .item-row.rarity-commun {
        --rarity-tint: rgba(43, 92, 63, 0.42);
    }

    .product-list .item-row.rarity-rare {
        --rarity-tint: rgba(38, 69, 112, 0.42);
    }

    .product-list .item-row.rarity-epique {
        --rarity-tint: rgba(83, 62, 112, 0.46);
    }

    .product-list .item-row.rarity-legendaire {
        --rarity-tint: rgba(118, 98, 50, 0.44);
    }

    .product-list .item-row.rarity-mythique {
        --rarity-tint: rgba(201, 210, 222, 0.34);
    }

    .product-list .item-row.rarity-mythique::after {
        content: "";
        position: absolute;
        top: -36%;
        left: -56%;
        width: 74%;
        height: 212%;
        background: linear-gradient(115deg, rgba(255, 255, 255, 0) 0%, rgba(239, 243, 250, 0.16) 48%, rgba(255, 255, 255, 0) 100%);
        transform: rotate(8deg);
        opacity: 0;
        mix-blend-mode: screen;
        pointer-events: none;
        animation: mythic-sheen 6.8s ease-in-out infinite;
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
        gap: 8px;
    }

    .item-rarity-pill,
    .item-stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 0.68rem;
        letter-spacing: 0.6px;
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
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .item-card-media .item-icon {
        font-size: clamp(2.6rem, 5vw, 3.7rem);
        width: auto;
    }

    .item-info {
        margin-top: 2px;
    }

    .item-info h3 {
        margin: 0;
        font-size: 1.02rem;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .item-price-line {
        margin-top: 8px;
    }

    .item-price {
        margin: 0;
        color: #d9c176;
        font-weight: 700;
        font-size: 1.08rem;
    }

    .item-rating {
        margin-top: 7px;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .item-rating .rating-stars i {
        font-size: 0.78rem;
    }

    .item-rating small {
        color: var(--text-silver);
        font-size: 0.75rem;
    }

    .product-list .item-row.item-out-of-stock {
        opacity: 0.72;
        filter: saturate(0.65);
    }

    .product-list .item-row.item-out-of-stock:hover {
        transform: translateY(-2px);
    }

    #no-results-message {
        margin-top: 24px;
    }

    @media (max-width: 767px) {
        .product-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .product-list .item-row {
            min-height: 240px;
            padding: 12px;
        }

        .item-rarity-pill,
        .item-stock-pill {
            font-size: 0.64rem;
            padding: 4px 8px;
        }

        .item-info h3 {
            font-size: 0.94rem;
        }

        .item-price {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 460px) {
        .product-list {
            grid-template-columns: 1fr;
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
            </div>

            <div class="sidebar-bottom-actions">
                <div class="cta-box">
                    <div class="hide-text">
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">
                            <?= $user['isConnected'] ? "Essais énigmes : <b style='color:var(--accent)'>5 / 5</b>" : "Besoin d'or ?" ?>
                        </p>
                        <a href="#" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
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
                    item.style.display = "";
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