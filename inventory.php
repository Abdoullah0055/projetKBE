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
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

$inventoryItems = [];
$inventoryError = '';

try {
    $stmt = $pdo->prepare(
        "SELECT
            inv.InventoryId AS inventory_id,
            inv.ItemId AS item_id,
            inv.Quantity AS quantity,
            i.Name AS item_name,
            i.Description AS item_description,
            i.PriceGold AS item_price_gold,
            t.Name AS item_type
         FROM Inventory inv
         LEFT JOIN Items i ON inv.ItemId = i.ItemId
         LEFT JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
         WHERE inv.UserId = ?
         ORDER BY inv.InventoryId DESC"
    );
    $stmt->execute([$user['id']]);
    $inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $inventoryError = "Impossible de charger votre inventaire pour le moment.";
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
        <button id="toggle-btn" onclick="toggleMenu()">
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
                    ?>

                    <div class="inventory-slot"
                        data-item-name="<?= htmlspecialchars($itemName) ?>"
                        data-item-description="<?= htmlspecialchars($itemDescription) ?>"
                        data-item-quantity="<?= (int) $entry['quantity'] ?>"
                        data-item-type="<?= htmlspecialchars($itemType) ?>"
                        data-item-id="<?= (int) $entry['item_id'] ?>"
                        data-item-price="<?= (int) ($entry['item_price_gold'] ?? 0) ?>">

                        <div class="slot-thumb" aria-hidden="true">
                            <span class="slot-icon"><?= getItemImage($itemType) ?></span>
                        </div>

                        <div class="slot-qty-badge">
                            <?= (int) $entry['quantity'] ?>
                        </div>

                        <div class="slot-label"><?= htmlspecialchars($itemName) ?></div>
                    </div>
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
        if (!tooltip || slots.length === 0) return;

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
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
