<?php
$headerTheme = $_COOKIE['theme'] ?? 'light';
$headerIconClass = ($headerTheme === 'dark') ? 'fa-sun' : 'fa-moon';
$isAdminUser = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
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

<div class="header-actions">
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
