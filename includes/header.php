<header>
    <div class="logo-area">
        <img src="<?= IMG ?>/logo.png" class="logo-circle" alt="Logo">
        <h1>L'Arsenal</h1>
    </div>

    <form class="search-container" action="<?= Page::Products->url() ?>">
        <input type="text" name="search" placeholder="Rechercher une arme, un sort...">
    </form>

    <div class="header-actions">
        <?php if ($user['isConnected']): ?>
            <div class="user-wallet">
                <span title="Or" style="color:var(--gold)"><?= $user['balance']['gold'] ?> G</span>
                <span title="Argent" style="color:var(--text-silver)"><?= $user['balance']['silver'] ?> S</span>
                <span title="Bronze" style="color:#CD7F32"><?= $user['balance']['bronze'] ?> B</span>
            </div>

            <button class="btn-outline-custom">
                <?= $user['alias'] ?><?= $user['isMage'] ? ' <small>(Mage)</small>' : '' ?>
            </button>

            <button class="btn-accent" onclick="window.location.href='panier.php'">
                <i class="fa-solid fa-cart-shopping"></i>
            </button>

            <button class="btn-danger-custom">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        <?php else: ?>
            <button class="btn-outline-custom">S'inscrire</button>
            <button class="btn-accent" onclick="window.location.href='login.php'">Connexion</button>
        <?php endif; ?>
    </div>
</header>