<?php
// 1. SIMULATION DE LA SESSION (À remplacer plus tard par session_start())
// Changez 'isConnected' à false pour tester le mode visiteur
$user = [
    'isConnected' => true,
    'alias' => "Slayer99",
    'isMage' => true,
    'balance' => [
        'gold' => 12,
        'silver' => 50,
        'bronze' => 80
    ]
];

// 2. SIMULATION DES ITEMS (Pour rendre la boucle propre)
$items = [
    ['id' => 1, 'nom' => "Lame de l'Exilé", 'type' => 'arme', 'rarete' => 'Rare', 'prix' => 1200, 'stock' => 3, 'rating' => 4, 'reviews' => 12],
    ['id' => 2, 'nom' => "Potion de Hâte", 'type' => 'potion', 'rarete' => 'Commun', 'prix' => 150, 'stock' => 0, 'rating' => 2, 'reviews' => 45],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Arsenal - Marché Noir</title>
    <style>
        /* --- Tes styles restent identiques --- */
        :root {
            --bg-dark: #46494C;
            --bg-sidebar: #4C5C68;
            --accent: #1985A1;
            --text-light: #DCDCDD;
            --text-silver: #C5C3C6;
            --gold: #F1C40F;
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
            --header-height: 70px;
            --transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        header {
            height: var(--header-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            background-color: rgba(0, 0, 0, 0.5);
            border-bottom: 2px solid var(--accent);
            flex-shrink: 0;
            z-index: 1000;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--text-silver);
            border: 2px solid var(--accent);
        }

        .search-container {
            flex: 0 1 400px;
            margin: 0 20px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 18px;
            border-radius: 25px;
            border: 1px solid var(--accent);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            outline: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .header-actions button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }

        .user-wallet {
            display: flex;
            gap: 15px;
            margin-right: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        aside {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            transition: width var(--transition);
            position: relative;
            border-right: 1px solid rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        aside.collapsed {
            width: var(--sidebar-collapsed);
        }

        #toggle-btn {
            position: absolute;
            right: -22px;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: var(--accent);
            color: white;
            border: 4px solid var(--bg-dark);
            cursor: pointer;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .filter-group {
            margin-bottom: 25px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 10px;
            border-radius: 5px;
        }

        .cta-box {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--accent);
        }

        .collapsed .hide-text {
            display: none;
        }

        .show-icon {
            display: none;
            font-size: 1.6rem;
            text-align: center;
            margin-bottom: 30px;
        }

        .collapsed .show-icon {
            display: block;
        }

        main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .catalog-banner {
            background: linear-gradient(90deg, var(--accent), transparent);
            padding: 20px 30px;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        .item-row {
            background: rgba(255, 255, 255, 0.03);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            transition: 0.3s;
        }

        .item-row:hover {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.06);
        }

        .item-price {
            font-weight: bold;
            color: var(--accent);
            font-size: 1.3rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        footer {
            height: 50px;
            background: #232527;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top: 1px solid var(--accent);
            font-size: 0.8rem;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo-area">
            <img src="img/logo.png" class="logo-circle">
            <h1>L'Arsenal</h1>
        </div>

        <form class="search-container">
            <input type="text" placeholder="Rechercher une arme, un sort...">
        </form>

        <div class="header-actions">
            <?php if ($user['isConnected']): ?>
                <div class="user-wallet">
                    <span title="Or" style="color:var(--gold)"><?= $user['balance']['gold'] ?> G</span>
                    <span title="Argent" style="color:var(--text-silver)"><?= $user['balance']['silver'] ?> S</span>
                    <span title="Bronze" style="color:#CD7F32"><?= $user['balance']['bronze'] ?> B</span>
                </div>
                <button style="background:transparent; border:1px solid var(--accent); color:var(--accent);">
                    <?= $user['alias'] ?> <?= $user['isMage'] ? ' <small>(Mage)</small>' : '' ?>
                </button>
                <button onclick="window.location.href='panier.php'">Panier (0)</button>
                <button style="background:#c0392b; margin-left:10px;">Déconnexion</button>
            <?php else: ?>
                <button style="background: transparent; border: 1px solid var(--accent); color: var(--accent);">S'inscrire</button>
                <button onclick="window.location.href='login.php'">Connexion</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="wrapper">
        <aside id="sidebar">
            <button id="toggle-btn" onclick="toggleMenu()">
                <span id="arrow-icon">«</span>
            </button>

            <div class="sidebar-content">
                <div class="show-icon">🔍</div>
                <div class="hide-text">
                    <form class="filter-section">
                        <div class="filter-group">
                            <label>Catégorie</label>
                            <select name="type">
                                <option value="all">Tous les items</option>
                                <option value="arme">Armes</option>
                                <option value="armure">Armures</option>
                                <option value="potion">Potions</option>
                                <option value="sort">Sorts</option>
                            </select>
                        </div>
                        <button type="submit" style="width:100%; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">Filtrer</button>
                    </form>
                </div>

                <div class="cta-box">
                    <div class="hide-text">
                        <?php if ($user['isConnected']): ?>
                            <p style="margin:0 0 8px 0; font-size:0.9rem;">Essais énigmes : <b style="color:var(--accent)">5 / 5</b></p>
                        <?php else: ?>
                            <p style="margin:0 0 8px 0; font-size:0.9rem;">Besoin d'or ?</p>
                        <?php endif; ?>
                        <a href="#" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
                    </div>
                </div>
            </div>
        </aside>

        <main>
            <div class="catalog-banner">
                <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.3rem;">
                    <?= $user['isConnected'] ? "Content de vous revoir, " . $user['alias'] : "Catalogue des Reliques" ?>
                </h2>
            </div>

            <div class="product-list">
                <?php foreach ($items as $item): ?>
                    <div class="item-row" <?= ($item['stock'] == 0) ? 'style="opacity: 0.7;"' : '' ?>>
                        <div class="item-info">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h3><?= $item['nom'] ?></h3>
                                <span style="background: rgba(25, 133, 161, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;"><?= $item['rarete'] ?></span>
                            </div>
                            <div style="margin-top: 5px;">
                                <span style="color: var(--gold);">★ ★ ★ ★ ☆</span>
                                <small style="color: var(--text-silver); margin-left: 5px;">(<?= $item['reviews'] ?> aventuriers)</small>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>
                            <?php if ($item['stock'] > 0): ?>
                                <small style="color: #2ECC71; font-weight: bold;">En stock: <?= $item['stock'] ?></small>
                            <?php else: ?>
                                <small style="color: #E74C3C; font-weight: bold;">Rupture de stock</small>
                            <?php endif; ?>
                        </div>

                        <div class="item-action-btns" style="margin-left: 20px;">
                            <?php if ($user['isConnected']): ?>
                                <?php if ($item['stock'] == 0): ?>
                                    <button disabled style="background:#444; cursor:not-allowed;">Épuisé</button>
                                <?php elseif ($item['type'] == 'sort' && !$user['isMage']): ?>
                                    <button disabled title="Niveau Mage requis" style="background:#666; font-size:0.7rem;">Mage Requis</button>
                                <?php else: ?>
                                    <button onclick="window.location.href='details.php?id=<?= $item['id'] ?>'" style="padding: 5px 12px; font-size: 0.8rem; background:var(--accent); color:white; border:none; border-radius:4px; cursor:pointer;">Acheter</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="details.php?id=<?= $item['id'] ?>" style="text-decoration:none; color:var(--accent); font-size:1.5rem;">➔</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination">
                <a href="#">&laquo; Précédent</a>
                <span>Page <strong>1</strong> sur 12</span>
                <a href="#">Suivant &raquo;</a>
            </div>
        </main>
    </div>

    <footer>
        L'Arsenal de Sombre-Donjon © 2026 | Aventuriers connectés : 124
    </footer>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const arrow = document.getElementById('arrow-icon');
            sidebar.classList.toggle('collapsed');
            arrow.innerHTML = sidebar.classList.contains('collapsed') ? '»' : '«';
        }
    </script>
</body>

</html>