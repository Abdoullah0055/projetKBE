<?php
require_once 'AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

function getItemProperties(PDO $pdo, int $itemId, string $type)
{
    switch (strtolower($type)) {
        case 'weapon':
            $stmt = $pdo->prepare("SELECT * FROM WeaponProperties WHERE ItemId = ?");
            break;

        case 'armor':
            $stmt = $pdo->prepare("SELECT * FROM ArmorProperties WHERE ItemId = ?");
            break;

        case 'potion':
            $stmt = $pdo->prepare("SELECT * FROM PotionProperties WHERE ItemId = ?");
            break;

        case 'magicspell':
            $stmt = $pdo->prepare("SELECT * FROM MagicSpellProperties WHERE ItemId = ?");
            break;

        default:
            return [];
    }

    $stmt->execute([$itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: [];
}

function renderItemProperties(array $item, array $properties): string
{
    $type = strtolower($item['type']);

    if (empty($properties)) {
        return '<div class="property-empty">Aucune propriété disponible pour cet objet.</div>';
    }

    $html = '<div class="stats-grid">';

    switch ($type) {
        case 'weapon':
            $html .= '
                <div class="stat-box">
                    <span class="stat-label">Dégâts</span>
                    <span class="stat-value">' . (int)$properties['DamageMin'] . ' - ' . (int)$properties['DamageMax'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Durabilité</span>
                    <span class="stat-value">' . (int)$properties['Durability'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Niveau requis</span>
                    <span class="stat-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Vitesse d\'attaque</span>
                    <span class="stat-value">' . htmlspecialchars((string)$properties['AttackSpeed']) . '</span>
                </div>
            ';
            break;

        case 'armor':
            $html .= '
                <div class="stat-box">
                    <span class="stat-label">Défense</span>
                    <span class="stat-value">' . (int)$properties['Defense'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Durabilité</span>
                    <span class="stat-value">' . (int)$properties['Durability'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Niveau requis</span>
                    <span class="stat-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
            ';
            break;

        case 'potion':
            $html .= '
                <div class="stat-box">
                    <span class="stat-label">Effet</span>
                    <span class="stat-value">' . htmlspecialchars((string)$properties['EffectType']) . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Valeur</span>
                    <span class="stat-value">' . (int)$properties['EffectValue'] . '</span>
                </div>
            ';

            if (!is_null($properties['DurationSeconds'])) {
                $html .= '
                    <div class="stat-box">
                        <span class="stat-label">Durée</span>
                        <span class="stat-value">' . (int)$properties['DurationSeconds'] . ' sec</span>
                    </div>
                ';
            }
            break;

        case 'magicspell':
            $html .= '
                <div class="stat-box">
                    <span class="stat-label">Dégâts magiques</span>
                    <span class="stat-value">' . (int)$properties['SpellDamage'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Coût en mana</span>
                    <span class="stat-value">' . (int)$properties['ManaCost'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Élément</span>
                    <span class="stat-value">' . htmlspecialchars((string)$properties['ElementType']) . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Niveau requis</span>
                    <span class="stat-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Recharge</span>
                    <span class="stat-value">' . (int)$properties['CooldownSeconds'] . ' sec</span>
                </div>
            ';
            break;
    }

    $html .= '</div>';

    return $html;
}

// 1. RÉCUPÉRATION DE L'ITEM
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        i.ItemId as id, 
        i.Name as nom, 
        i.PriceGold as prix_gold,
        i.PriceSilver as prix_silver,
        i.PriceBronze as prix_bronze,
        i.Description as description, 
        t.Name as type,
        i.Stock as stock, 
        COUNT(r.ReviewId) as nb_avis
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.ItemId = ?
    GROUP BY i.ItemId, i.Name, i.PriceGold, i.PriceSilver, i.PriceBronze, i.Description, i.Stock, t.Name
");

$stmt->execute([$_GET['id']]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: index.php");
    exit();
}

$item['image'] = getItemImage($item['type']);
$properties = getItemProperties($pdo, (int)$item['id'], $item['type']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Arsenal - <?= htmlspecialchars($item['nom']) ?></title>
    <style>
        :root {
            --bg-dark: #46494C;
            --bg-sidebar: #4C5C68;
            --accent: #1985A1;
            --text-light: #DCDCDD;
            --text-silver: #C5C3C6;
            --gold: #F1C40F;
            --header-height: 70px;
            --danger: #c0392b;
            --panel-dark: rgba(0, 0, 0, 0.18);
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

        main {
            flex: 1;
            max-width: 1150px;
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
            background: linear-gradient(145deg, #566773, #42505a);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7rem;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.35);
            position: relative;
        }

        .item-type-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(0, 0, 0, 0.35);
            color: var(--accent);
            border: 1px solid var(--accent);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cloud-info,
        .type-panel,
        .comments-box,
        .properties-panel {
            background: var(--panel-dark);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px;
        }

        .cloud-info {
            border: 2px dashed var(--accent);
            text-align: center;
        }

        .type-panel-title,
        .section-title {
            color: var(--accent);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 15px 0;
        }

        .type-panel p {
            margin: 0;
            color: var(--text-silver);
            line-height: 1.6;
        }

        .info-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .item-header {
            background: var(--panel-dark);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .item-name {
            margin: 0;
            font-size: 2.2rem;
        }

        .item-price {
            text-align: right;
            min-width: 160px;
        }

        .price-main {
            font-size: 1.8rem;
            color: var(--gold);
            font-weight: bold;
        }

        .price-secondary {
            font-size: 0.9rem;
            color: var(--text-silver);
            margin-top: 6px;
        }

        .stock-indicator {
            display: inline-block;
            margin-top: 12px;
            padding: 5px 12px;
            background: rgba(25, 133, 161, 0.2);
            border: 1px solid var(--accent);
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--accent);
            font-weight: bold;
        }

        .properties-panel p.description {
            color: var(--text-silver);
            line-height: 1.7;
            margin-top: 0;
            margin-bottom: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            margin-top: 10px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-label {
            font-size: 0.78rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--text-light);
        }

        .property-empty {
            color: #aaa;
            font-style: italic;
        }

        .comments-box {
            border-left: 4px solid var(--accent);
        }

        .action-bar {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding-top: 10px;
        }

        .btn-add {
            background: var(--accent);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn-add:hover,
        .header-actions button:hover {
            opacity: 0.9;
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

        @media (max-width: 900px) {
            main {
                grid-template-columns: 1fr;
            }

            .item-header {
                flex-direction: column;
            }

            .item-price {
                text-align: left;
                min-width: auto;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-add {
                width: 100%;
            }
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
                    <span title="Or" style="color:var(--gold)"><?= (int)$user['balance']['gold'] ?> G</span>
                    <span title="Argent" style="color:var(--text-silver)"><?= (int)$user['balance']['silver'] ?> S</span>
                    <span title="Bronze" style="color:#CD7F32"><?= (int)$user['balance']['bronze'] ?> B</span>
                </div>
                <button style="background:transparent; border:1px solid var(--accent); color:var(--accent);">
                    <?= htmlspecialchars($user['alias']) ?><?= $user['isMage'] ? ' <small>(Mage)</small>' : '' ?>
                </button>
                <button onclick="window.location.href='panier.php'">Panier (0)</button>
                <button style="background:<?= 'var(--danger)' ?>;">Déconnexion</button>
            <?php else: ?>
                <button style="background: transparent; border: 1px solid var(--accent); color: var(--accent);">S'inscrire</button>
                <button>Connexion</button>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <div class="visual-column">
            <div class="item-card-main">
                <div class="item-type-badge"><?= htmlspecialchars($item['type']) ?></div>
                <?= $item['image'] ?>
            </div>

            <div class="type-panel">
                <h3 class="type-panel-title">Classe d'objet</h3>
                <p>
                    Cet objet appartient à la catégorie
                    <strong><?= htmlspecialchars($item['type']) ?></strong>.
                </p>
            </div>

            <?php if ($user['isConnected']): ?>
                <div class="cloud-info">
                    <div style="color: var(--gold); font-size: 1.3rem; margin-bottom: 5px;">★ ★ ★ ★ ☆</div>
                    <div style="font-size: 0.9rem;"><?= (int)$item['nb_avis'] ?> aventuriers ont laissé un avis</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-column">
            <div class="item-header">
                <div>
                    <h2 class="item-name"><?= htmlspecialchars($item['nom']) ?></h2>
                    <div class="stock-indicator">En stock : <?= (int)$item['stock'] ?></div>
                </div>

                <div class="item-price">
                    <div class="price-main"><?= (int)$item['prix_gold'] ?> GP</div>
                    <div class="price-secondary">
                        <?= (int)$item['prix_silver'] ?> SP • <?= (int)$item['prix_bronze'] ?> BP
                    </div>
                </div>
            </div>

            <div class="properties-panel">
                <h3 class="section-title">Description & propriétés</h3>
                <p class="description"><?= htmlspecialchars($item['description']) ?></p>
                <?= renderItemProperties($item, $properties) ?>
            </div>

            <?php if ($user['isConnected']): ?>
                <div class="comments-box">
                    <h4 style="margin: 0 0 10px 0; font-size: 0.9rem; color: var(--accent);">Dernier avis</h4>
                    <p style="margin: 0; font-style: italic; font-size: 0.95rem;">
                        "Une qualité de forge exceptionnelle."
                    </p>
                </div>
            <?php endif; ?>

            <div class="action-bar">
                <a href="index.php" style="color: var(--text-silver); text-decoration:none;">← Retour au catalogue</a>
                <button class="btn-add">Ajouter au panier</button>
            </div>
        </div>
    </main>

    <footer>
        L'Arsenal de Sombre-Donjon © 2026 | Aventuriers en ligne : 124
    </footer>

</body>

</html>