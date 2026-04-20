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


