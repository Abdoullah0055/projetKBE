<?php
$headerTheme = $_COOKIE['theme'] ?? 'light';
$headerIconClass = ($headerTheme === 'dark') ? 'fa-sun' : 'fa-moon';
$isAdminUser = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
$currentPageName = basename($_SERVER['PHP_SELF'] ?? '');
$isCatalogPage = ($currentPageName === 'index.php');
?>

<header>
    <div class="logo-area">
        <a href="index.php"> <img src="assets/img/logo.png" class="logo-circle" alt="Logo">
        </a>
        <h1>L'Arsenal</h1>
    </div>

    <!-- Desktop search (hidden on mobile) -->
    <form class="search-container" id="header-search-form" action="inventory.php" method="get" role="search" novalidate>
        <input
            type="text"
            name="search"
            id="header-search-input"
            placeholder="Rechercher une arme, un sort..."
            autocomplete="off"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="header-search-suggestions-list">
        <div class="search-suggestions" id="header-search-suggestions" hidden>
            <ul id="header-search-suggestions-list" class="search-suggestion-list"></ul>
        </div>
    </form>

<div class="header-actions<?= $user['isConnected'] ? ' is-authenticated' : '' ?>">
    <?php if ($user['isConnected']): ?>
    <?php if ($user['isMage']): ?>
    <span class="mage-badge" title="Mage"><i class="fa-solid fa-hat-wizard"></i></span>
    <?php endif; ?>
    <div class="user-hp-bar">
      <span class="hp-icon">❤️</span>
      <div class="hp-bar-track"><div class="hp-bar-fill" style="width:<?= $user['max_hp'] > 0 ? round($user['hp'] / $user['max_hp'] * 100) : 100 ?>%"></div></div>
      <span class="hp-value"><?= $user['hp'] ?? 100 ?>/<?= $user['max_hp'] ?? 100 ?></span>
    </div>
    <div class="user-wallet">
      <span title="Or" style="color:var(--gold)"><?= $user['balance']['gold'] ?> G</span>
      <span title="Argent" style="color:var(--text-silver)"><?= $user['balance']['silver'] ?> S</span>
      <span title="Bronze" style="color:#CD7F32"><?= $user['balance']['bronze'] ?> B</span>
    </div>
    <?php endif; ?>
    <button id="theme-toggle" class="btn-outline-custom" title="Changer le mode">
      <i id="theme-icon" class="fa-solid <?= htmlspecialchars($iconClass ?? $headerIconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
    </button>

    <?php if ($user['isConnected']): ?>
    <button class="btn-outline-custom" title="Mon profil" onclick="window.location.href='profile.php'">
      <i class="fa-solid fa-user-gear"></i>
    </button>
    <?php if ($isAdminUser): ?>
    <button class="btn-outline-custom admin-header-link" title="Administration" onclick="window.location.href='admin.php'">
      <i class="fa-solid fa-crown"></i>
    </button>
    <?php endif; ?>
    <button id="cart-btn" class="btn-accent" onclick="window.location.href='panier.php'">
      <i class="fa-solid fa-cart-shopping"></i>
    </button>
    <button class="btn-danger-custom" onclick="window.location.href='logout.php'">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
    <?php else: ?>
    <button class="btn-outline-custom" onclick="window.location.href='login.php?mode=register'">Inscription</button>
    <button class="btn-accent" onclick="window.location.href='login.php'">Connexion</button>
    <?php endif; ?>

        <!-- Mobile menu trigger (hamburger) -->
        <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-label="Menu" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<!-- Mobile Drawer Menu -->
<div id="mobile-drawer-overlay" class="mobile-drawer-overlay" role="presentation"></div>
<nav id="mobile-drawer" class="mobile-drawer" role="navigation" aria-label="Menu">
    <div class="mobile-drawer-content">
        <!-- Search in drawer -->
        <form class="mobile-drawer-search" id="drawer-search-form" action="inventory.php" method="get" role="search" novalidate>
            <input
                type="text"
                name="search"
                id="drawer-search-input"
                placeholder="Rechercher..."
                aria-label="Recherche dans le menu"
                autocomplete="off"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="drawer-search-suggestions-list">
            <div class="search-suggestions mobile-search-suggestions" id="drawer-search-suggestions" hidden>
                <ul id="drawer-search-suggestions-list" class="search-suggestion-list"></ul>
            </div>
        </form>

        <?php if ($isCatalogPage): ?>
        <section class="mobile-drawer-filters" aria-label="Filtres du catalogue">
            <h3 class="mobile-drawer-section-title">Filtres du Catalogue</h3>
            <form class="mobile-filter-form" onsubmit="return false;">
                <div class="mobile-filter-group">
                    <label for="mobile-search-filter">Recherche</label>
                    <input type="text" id="mobile-search-filter" class="filter-input" placeholder="Nom de l'objet...">
                </div>

                <div class="mobile-filter-group">
                    <label for="mobile-type-filter">Categorie</label>
                    <select id="mobile-type-filter" class="filter-select">
                        <option value="all">Tous les items</option>
                        <option value="weapon">Armes</option>
                        <option value="armor">Armures</option>
                        <option value="potion">Potions</option>
                        <option value="magicspell">Sorts</option>
                    </select>
                </div>

                <div class="mobile-filter-group">
                    <label for="mobile-sort-filter">Trier par</label>
                    <select id="mobile-sort-filter" class="filter-select">
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

                <div class="mobile-filter-group">
                    <label for="mobile-rarity-filter">Rarrete</label>
                    <select id="mobile-rarity-filter" class="filter-select">
                        <option value="all">Toutes</option>
                        <option value="rarity-commun">Commun</option>
                        <option value="rarity-rare">Rare</option>
                        <option value="rarity-epique">Epique</option>
                        <option value="rarity-legendaire">Legendaire</option>
                        <option value="rarity-mythique">Mythique</option>
                    </select>
                </div>

                <div class="mobile-filter-group">
                    <label for="mobile-price-filter">Prix max (or)</label>
                    <input type="number" id="mobile-price-filter" class="filter-input" placeholder="Ex: 50" min="0" step="1">
                </div>

                <button type="button" id="mobile-reset-filters" class="drawer-action mobile-reset-filters-btn">
                    <i class="fa-solid fa-rotate-left"></i> Reinitialiser les filtres
                </button>
            </form>
        </section>
        <?php endif; ?>

        <!-- Wallet info in drawer (mobile only) -->
    <?php if ($user['isConnected']): ?>
    <?php if ($user['isMage']): ?>
    <span class="mage-badge" title="Mage"><i class="fa-solid fa-hat-wizard"></i></span>
    <?php endif; ?>
    <div class="user-hp-bar">
      <span class="hp-icon">❤️</span>
      <div class="hp-bar-track"><div class="hp-bar-fill" style="width:<?= $user['max_hp'] > 0 ? round($user['hp'] / $user['max_hp'] * 100) : 100 ?>%"></div></div>
      <span class="hp-value"><?= $user['hp'] ?? 100 ?>/<?= $user['max_hp'] ?? 100 ?></span>
    </div>
    <div class="user-wallet" style="display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px;">
      <span title="Or" style="color:var(--gold)"><?= $user['balance']['gold'] ?> G</span>
      <span title="Argent" style="color:var(--text-silver)"><?= $user['balance']['silver'] ?> S</span>
      <span title="Bronze" style="color:#CD7F32"><?= $user['balance']['bronze'] ?> B</span>
    </div>
    <?php endif; ?>

        <!-- Main actions -->
        <div class="mobile-drawer-actions">
            <?php if ($user['isConnected']): ?>
                <button onclick="window.location.href='roadmap.php'" class="drawer-action">
                    <i class="fa-solid fa-door-open"></i> Enigmes
                </button>
                <button onclick="window.location.href='inventory.php'" class="drawer-action">
                    <i class="fa-solid fa-box-open"></i> Inventaire
                </button>
                <button type="button" id="capital-request-drawer-btn" class="drawer-action">
                    <i class="fa-solid fa-sack-dollar"></i> Demande capital
                </button>
                <button onclick="window.location.href='profile.php'" class="drawer-action">
                    <i class="fa-solid fa-user-gear"></i> Mon Profil
                </button>
                <?php if ($isAdminUser): ?>
                    <button onclick="window.location.href='admin.php'" class="drawer-action">
                        <i class="fa-solid fa-crown"></i> Administration
                    </button>
                <?php endif; ?>
                <button onclick="window.location.href='panier.php'" class="drawer-action">
                    <i class="fa-solid fa-cart-shopping"></i> Panier
                </button>
                <button onclick="window.location.href='logout.php'" class="drawer-action">
                    <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                </button>
            <?php else: ?>
                <button onclick="window.location.href='login.php?mode=register'" class="drawer-action">
                    <i class="fa-solid fa-user-plus"></i> Inscription
                </button>
                <button onclick="window.location.href='login.php'" class="drawer-action">
                    <i class="fa-solid fa-sign-in-alt"></i> Connexion
                </button>
            <?php endif; ?>
        </div>
 </div>
 </nav>

 <?php if ($user['isConnected']): ?>
 <script>
 (function() {
   function refreshHeaderStats() {
     fetch('backend/header_stats.php', { credentials: 'same-origin' })
       .then(function(r) { return r.json(); })
       .then(function(data) {
         if (!data.success) return;
         var pct = data.max_hp > 0 ? Math.round(data.hp / data.max_hp * 100) : 100;
         var hpVal = data.hp + '/' + data.max_hp;
         document.querySelectorAll('.user-hp-bar .hp-bar-fill').forEach(function(el) { el.style.width = pct + '%'; });
         document.querySelectorAll('.user-hp-bar .hp-value').forEach(function(el) { el.textContent = hpVal; });
         document.querySelectorAll('.user-wallet').forEach(function(el) {
           el.querySelector('span[title="Or"]').textContent = data.gold + ' G';
           el.querySelector('span[title="Argent"]').textContent = data.silver + ' S';
           el.querySelector('span[title="Bronze"]').textContent = data.bronze + ' B';
         });
       })
       .catch(function() {});
   }
   setInterval(refreshHeaderStats, 15000);
 })();
 </script>
 <?php endif; ?>
