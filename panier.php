<?php
// 1. SIMULATION DE LA SESSION
$user = [
    'isConnected' => true,
    'alias' => "Slayer99",
    'isMage' => true,
    'balance' => ['gold' => 12, 'silver' => 50, 'bronze' => 80]
];

// 2. CONTENU DU PANIER
$cartItems = [
    [
        'id' => 1,
        'nom' => "Lame de l'Exilé",
        'prix' => 1200,
        'quantite' => 1,
        'stock_max' => 3,
        'image' => "⚔️"
    ],
    [
        'id' => 4,
        'nom' => "Parchemin de Feu",
        'prix' => 500,
        'quantite' => 2,
        'stock_max' => 5,
        'image' => "🔥"
    ],
    [
        'id' => 4,
        'nom' => "Parchemin de Feu",
        'prix' => 500,
        'quantite' => 2,
        'stock_max' => 5,
        'image' => "🔥"
    ],
    [
        'id' => 4,
        'nom' => "Parchemin de Feu",
        'prix' => 500,
        'quantite' => 20,
        'stock_max' => 5,
        'image' => "🔥"
    ]
];

$totalGeneral = 0;
foreach ($cartItems as $item) {
    $totalGeneral += ($item['prix'] * $item['quantite']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Arsenal - Ma Besace</title>
    <style>
        /* 1. VARIABLES & RESET */
        :root {
            --bg-dark: #46494C;
            --bg-sidebar: #4C5C68;
            --accent: #1985A1;
            --accent-hover: #146c82;
            --text-light: #DCDCDD;
            --text-silver: #C5C3C6;
            --gold: #F1C40F;
            --header-height: 70px;
            --action-bar-height: 80px;
            --footer-height: 40px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* 2. HEADER FIXE */
        header {
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            border-bottom: 2px solid var(--accent);
            z-index: 1000;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--text-silver);
            border: 2px solid var(--accent);
        }

        .logo-area h1 {
            font-size: 1.4rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .user-wallet {
            display: flex;
            gap: 15px;
            margin-right: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.4);
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-actions button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
            transition: 0.2s;
        }

        .header-actions button:hover {
            background: var(--accent-hover);
        }

        /* 3. CONTENU PRINCIPAL (SCROLLABLE) */
        main {
            flex: 1;
            margin-top: var(--header-height);
            /* Espace pour le header fixe */
            margin-bottom: calc(var(--action-bar-height) + var(--footer-height));
            /* Espace pour les barres du bas */
            padding: 40px 20px;
            max-width: 1000px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .cart-row {
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            gap: 20px;
            margin-bottom: 15px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .cart-row:hover {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
        }

        .btn-corbeille {
            background: #c0392b;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            text-transform: lowercase;
            font-size: 0.75rem;
            border-radius: 3px;
        }

        .btn-corbeille:hover {
            background: #e74c3c;
        }

        .item-image-box {
            width: 80px;
            height: 80px;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            border: 1px solid #333;
            border-radius: 4px;
        }

        .item-name-box {
            flex: 1;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-silver);
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .btn-qty {
            width: 35px;
            height: 40px;
            background: #eee;
            color: #333;
            border: none;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-qty:hover {
            background: #fff;
        }

        .qty-val {
            width: 45px;
            height: 40px;
            background: #fff;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .item-total-box {
            width: 120px;
            text-align: right;
            font-weight: bold;
            color: var(--gold);
            font-size: 1.1rem;
        }

        /* 4. BARRE D'ACTIONS FIXE (BAS) */
        .cart-footer-actions {
            position: fixed;
            bottom: var(--footer-height);
            left: 0;
            right: 0;
            height: var(--action-bar-height);
            background: #2c2f33;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 50px;
            border-top: 2px solid var(--accent);
            box-shadow: 0 -10px 20px rgba(0, 0, 0, 0.4);
            z-index: 999;
        }

        .btn-return {
            color: white;
            border: 1px solid white;
            padding: 12px 25px;
            text-decoration: none;
            text-transform: lowercase;
            transition: 0.3s;
        }

        .btn-return:hover {
            background: white;
            color: black;
        }

        .total-summary {
            background: white;
            color: black;
            padding: 12px 40px;
            font-weight: 900;
            font-size: 1.3rem;
            border-radius: 4px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .btn-confirm {
            background: var(--accent);
            color: white;
            border: none;
            padding: 15px 50px;
            font-weight: bold;
            cursor: pointer;
            text-transform: lowercase;
            font-size: 1.1rem;
            border-radius: 4px;
            transition: 0.3s;
        }

        .btn-confirm:hover {
            background: #23a0c0;
            transform: translateY(-2px);
        }

        /* 5. FOOTER TOUT EN BAS */
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--footer-height);
            background: #1a1c1e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #666;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo-area">
            <div class="logo-circle"></div>
            <h1>L'Arsenal</h1>
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
                <button onclick="window.location.href='index.php'">Boutique</button>
            <?php else: ?>
                <button onclick="window.location.href='login.php'">Connexion</button>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <?php if (empty($cartItems)): ?>
            <div style="text-align: center; margin-top: 100px;">
                <h2 style="font-size: 2rem; color: var(--text-silver);">Votre besace est vide...</h2>
                <p>Allez remplir votre équipement avant l'aventure !</p>
                <br>
                <a href="index.php" class="btn-return">Retour à l'échoppe</a>
            </div>
        <?php else: ?>
            <h2 style="text-transform: lowercase; margin-bottom: 30px; border-left: 4px solid var(--accent); padding-left: 15px;">
                Contenu de votre besace
            </h2>

            <?php foreach ($cartItems as $item): ?>
                <div class="cart-row">
                    <button class="btn-corbeille" title="Retirer l'objet">corbeille</button>

                    <div class="item-image-box">
                        <?= $item['image'] ?>
                    </div>

                    <div class="item-name-box">
                        <?= $item['nom'] ?>
                    </div>

                    <div class="qty-controls">
                        <button class="btn-qty">+</button>
                        <div class="qty-val"><?= $item['quantite'] ?></div>
                        <button class="btn-qty">-</button>
                    </div>

                    <div class="item-total-box">
                        <?= number_format($item['prix'] * $item['quantite'], 0) ?> GP
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php if (!empty($cartItems)): ?>
        <div class="cart-footer-actions">
            <a href="index.php" class="btn-return">retour page principale</a>

            <div class="total-summary">
                TOTAL : <?= number_format($totalGeneral, 0) ?> GP
            </div>

            <button class="btn-confirm" onclick="alert('Transaction validée par le marchand !')">
                confirmer l'achat
            </button>
        </div>
    <?php endif; ?>

    <footer>
        L'Arsenal de Sombre-Donjon © 2026 | Ma Besace (US-15)
    </footer>

</body>

</html>