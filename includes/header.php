<header>
    <div class="logo-area">
        <a href="index.php"> <img src="img/logo.png" class="logo-circle" alt="Logo">
        </a>
        <h1>L'Arsenal</h1>
    </div>

    <!-- Desktop search (hidden on mobile) -->
    <form class="search-container">
        <input type="text" name="search" placeholder="Rechercher une arme, un sort...">
    </form>

    <div class="header-actions">
        <button id="theme-toggle" class="btn-outline-custom" title="Changer le mode">
            <i id="theme-icon" class="fa-solid <?= $iconClass ?? 'fa-moon' ?>"></i>
        </button>

        <?php if ($user['isConnected']): ?>
            <div class="user-wallet">
                <span title="Or" style="color:var(--gold)"><?= $user['balance']['gold'] ?> G</span>
                <span title="Argent" style="color:var(--text-silver)"><?= $user['balance']['silver'] ?> S</span>
                <span title="Bronze" style="color:#CD7F32"><?= $user['balance']['bronze'] ?> B</span>
            </div>

            <button class="btn-outline-custom" title="Mon profil" onclick="window.location.href='profile.php'">
                <i class="fa-solid fa-user-gear"></i>
            </button>
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
        <div class="mobile-drawer-search">
            <input type="text" name="drawer-search" placeholder="Rechercher..." aria-label="Recherche dans le menu">
        </div>

        <!-- Wallet info in drawer (mobile only) -->
        <?php if ($user['isConnected']): ?>
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