<?php
$sidebarIcon  = $sidebarIcon  ?? '<i class="fa-solid fa-magnifying-glass"></i>';
$sidebarTitle = $sidebarTitle ?? 'Filtres';
$sidebarDesc  = $sidebarDesc  ?? '';
$showDoorBtn  = $showDoorBtn  ?? true;
$sidebarExtraBottom = $sidebarExtraBottom ?? '';
?>
<div class="sidebar-content">
    <div class="show-icon"><?= $sidebarIcon ?></div>

    <div class="hide-text">
        <?php if ($sidebarTitle || $sidebarDesc): ?>
        <div class="inventory-side-box">
            <?php if ($sidebarTitle): ?><h3><?= htmlspecialchars($sidebarTitle) ?></h3><?php endif; ?>
            <?php if ($sidebarDesc): ?><p><?= htmlspecialchars($sidebarDesc) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="filter-toggle-header open">
            <button type="button" id="filter-toggle-btn">
                <span>Filtres</span>
                <i class="fa-solid fa-chevron-down" id="filter-chevron"></i>
            </button>
        </div>
        <div id="filter-body" class="filter-body open">
            <form class="filter-section" onsubmit="return false;">
                <div class="filter-group">
                    <label>Recherche</label>
                    <input type="text" id="search-filter" class="filter-input" placeholder="Nom de l'objet...">
                </div>

                <div class="filter-group">
                    <label>Catégorie</label>
                    <select id="type-filter" class="filter-select">
                        <option value="all">Tous les items</option>
                        <option value="weapon">Armes</option>
                        <option value="armor">Armures</option>
                        <option value="potion">Potions</option>
                        <option value="magicspell">Sorts</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Trier par</label>
                    <select id="sort-filter" class="filter-select">
                        <option value="name-asc">Nom (A-Z)</option>
                        <option value="name-desc">Nom (Z-A)</option>
                        <option value="price-asc">Prix (croissant)</option>
                        <option value="price-desc">Prix (decroissant)</option>
                        <option value="rating-desc">Note (meilleure)</option>
                        <option value="rating-asc">Note (moins bonne)</option>
                        <option value="rarity-asc">Rarrete (Commun -> Mythique)</option>
                        <option value="rarity-desc">Rarrete (Mythique -> Commun)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Rarrete</label>
                    <select id="rarity-filter" class="filter-select">
                        <option value="all">Toutes</option>
                        <option value="rarity-commun">Commun</option>
                        <option value="rarity-rare">Rare</option>
                        <option value="rarity-epique">Epique</option>
                        <option value="rarity-legendaire">Legendaire</option>
                        <option value="rarity-mythique">Mythique</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Prix max (or)</label>
                    <input type="number" id="price-filter" class="filter-input" placeholder="Ex: 50" min="0" step="1">
                </div>

                <button type="button" id="reset-filters" class="sidebar-btn sidebar-btn--reset">Réinitialiser</button>
            </form>
        </div>

        <?php if ($showDoorBtn): ?>
        <a href="<?= isset($user) && $user['isConnected'] ? 'roadmap.php' : 'login.php' ?>" class="enigme-door-button" aria-label="Acceder aux enigmes">
            <span class="enigme-door-button__frame">
                <img src="assets/img/doors/opened.png" alt="" class="enigme-door-button__image">
            </span>
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-bottom-actions">
        <?php if (isset($user) && $user['isConnected']): ?>
        <button type="button" id="capital-request-sidebar-btn" title="Demander une augmentation de capital" class="sidebar-btn sidebar-btn--capital">
            <i class="fa-solid fa-sack-dollar"></i>
            <span class="btn-label">Demande capital</span>
        </button>
        <button type="button" onclick="location.href='inventory.php'" title="Ouvrir mon inventaire" class="sidebar-btn sidebar-btn--inventory">
            <i class="fa-solid fa-box-open"></i>
            <span class="btn-label">Inventaire</span>
        </button>
        <?php endif; ?>

        <?php if ($sidebarExtraBottom): ?>
            <?= $sidebarExtraBottom ?>
        <?php endif; ?>
    </div>
</div>
