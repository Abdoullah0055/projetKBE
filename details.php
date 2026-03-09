<?php
// Simulation de la session (À remplacer par session_start() et vos données réelles)
$user = [
    'isConnected' => true, // Changez à false pour tester le mode visiteur
    'alias' => "Slayer99",
    'isMage' => true,
    'balance' => ['gold' => 12, 'silver' => 50, 'bronze' => 80]
];

// Données de l'item (Simulées) 
$item = [
    'id' => 1,
    'nom' => "Lame de l'Exilé",
    'prix' => 1200,
    'description' => "Forgée dans les tréfonds d'un volcan oublié, cette lame vibre d'une énergie sombre. Elle n'est pas seulement une arme, mais une extension de la volonté de son porteur. Les gravures sur le plat de la lame brillent d'un éclat bleuté à l'approche du danger.",
    'image' => "⚔️",
    'stock' => 3,
    'nb_avis' => 12
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Arsenal - <?= $item['nom'] ?></title>
    <style>
        /* 1. VARIABLES & RESET (Identiques à l'index) */
        :root {
            --bg-dark: #46494C;
            --bg-sidebar: #4C5C68;
            --accent: #1985A1;
            --text-light: #DCDCDD;
            --text-silver: #C5C3C6;
            --gold: #F1C40F;
            --header-height: 70px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* 2. HEADER DYNAMIQUE (Copié de l'index) */
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
            transition: 0.2s;
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

        /* 3. STRUCTURE DÉTAILS */
        main {
            flex: 1;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 40px;
        }

        .visual-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .item-card-main {
            background: var(--bg-sidebar);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Style "Nuage" pour éléments connectés */
        .cloud-info {
            background: rgba(0, 0, 0, 0.2);
            border: 2px dashed var(--accent);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }

        .info-column {
            display: flex;
            flex-direction: column;
        }

        .item-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .item-price {
            font-size: 1.8rem;
            color: var(--gold);
            font-weight: bold;
        }

        .stock-indicator {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 12px;
            background: rgba(25, 133, 161, 0.2);
            border: 1px solid var(--accent);
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--accent);
            font-weight: bold;
        }

        .comments-box {
            background: var(--bg-sidebar);
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid var(--accent);
        }

        .action-bar {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
        }

        .btn-add {
            background: var(--accent);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
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
            <div class="logo-circle"></div>
            <h1 style="font-size: 1.5rem; margin:0;">L'Arsenal</h1>
        </div>

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
                <button style="background:#c0392b;">Déconnexion</button>
            <?php else: ?>
                <button style="background: transparent; border: 1px solid var(--accent); color: var(--accent);">S'inscrire</button>
                <button>Connexion</button>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <div class="visual-column">
            <div class="item-card-main"><?= $item['image'] ?></div>

            <?php if ($user['isConnected']): ?>
                <div class="cloud-info">
                    <div style="color: var(--gold); font-size: 1.3rem; margin-bottom: 5px;">★ ★ ★ ★ ☆</div>
                    <div style="font-size: 0.9rem;"><?= $item['nb_avis'] ?> aventuriers (US-42)</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-column">
            <div class="item-header">
                <div>
                    <h2 style="margin:0; font-size: 2.2rem;"><?= $item['nom'] ?></h2>
                    <?php if ($user['isConnected']): ?>
                        <div class="stock-indicator">En stock : <?= $item['stock'] ?> (US-18)</div>
                    <?php endif; ?>
                </div>
                <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>
            </div>

            <div style="margin-bottom: 30px;">
                <h3 style="color: var(--accent); font-size: 1rem; text-transform: uppercase;">Propriétés (US-14)</h3>
                <p style="color: var(--text-silver); line-height: 1.7;"><?= $item['description'] ?></p>
            </div>

            <?php if ($user['isConnected']): ?>
                <div class="comments-box">
                    <h4 style="margin: 0 0 10px 0; font-size: 0.9rem; color: var(--accent);">Dernier avis (US-24)</h4>
                    <p style="margin: 0; font-style: italic; font-size: 0.95rem;">"Une qualité de forge exceptionnelle."</p>
                </div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="index.php" style="color: var(--text-silver); text-decoration:none;">← Retour au catalogue</a>
                <button class="btn-add">Ajouter au panier (US-15)</button>
            </div>
        </div>
    </main>

    <footer>
        L'Arsenal de Sombre-Donjon © 2026 | Aventuriers en ligne : 124
    </footer>

</body>

</html>