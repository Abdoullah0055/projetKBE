<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SÉCURITÉ : Vérification stricte du rôle Admin (US-38)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$pdo = get_pdo();

// Initialisation de l'utilisateur pour le header
$user = [
    'isConnected' => true,
    'id'          => $_SESSION['user']['id'],
    'alias'       => $_SESSION['user']['alias'],
    'role'        => $_SESSION['user']['role'],
    'isMage'      => false,
    'balance'     => [
        'gold'    => $_SESSION['user']['gold'],
        'silver'  => $_SESSION['user']['silver'],
        'bronze'  => $_SESSION['user']['bronze']
    ]
];

$message_alerte = null;

// 2. TRAITEMENT DES FORMULAIRES (Backend)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // US-11 : Ajouter un item
    if ($_POST['action'] === 'add_item') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $gold = (int)$_POST['gold'];
        $silver = (int)$_POST['silver'];
        $bronze = (int)$_POST['bronze'];
        $stock = (int)$_POST['stock'];
        $typeId = (int)$_POST['type_id'];
        $isActive = 1;

        try {
            $pdo->beginTransaction();
            // Insertion dans Items
            $stmt = $pdo->prepare("INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $gold, $silver, $bronze, $stock, $typeId, $isActive]);
            $newItemId = $pdo->lastInsertId();

            // Insertion de propriétés par défaut pour éviter les bugs dans details.php
            $typeNameQuery = $pdo->prepare("SELECT Name FROM ItemTypes WHERE ItemTypeId = ?");
            $typeNameQuery->execute([$typeId]);
            $typeName = strtolower($typeNameQuery->fetchColumn());

            if ($typeName === 'weapon') {
                $pdo->prepare("INSERT INTO WeaponProperties (ItemId, DamageMin, DamageMax) VALUES (?, 10, 20)")->execute([$newItemId]);
            } elseif ($typeName === 'armor') {
                $pdo->prepare("INSERT INTO ArmorProperties (ItemId, Defense) VALUES (?, 15)")->execute([$newItemId]);
            } elseif ($typeName === 'potion') {
                $pdo->prepare("INSERT INTO PotionProperties (ItemId, EffectType, EffectValue) VALUES (?, 'Heal', 50)")->execute([$newItemId]);
            } elseif ($typeName === 'magicspell') {
                $pdo->prepare("INSERT INTO MagicSpellProperties (ItemId, SpellDamage, ManaCost, ElementType) VALUES (?, 30, 15, 'Magic')")->execute([$newItemId]);
            }

            $pdo->commit();
            $message_alerte = ["type" => "succes", "texte" => "L'artefact '$name' a été ajouté avec succès."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $message_alerte = ["type" => "erreur", "texte" => "Erreur lors de l'ajout : Ce nom existe peut-être déjà."];
        }
    }

    // US-13 : Supprimer un item
    elseif ($_POST['action'] === 'delete_item') {
        $itemId = (int)$_POST['item_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM Items WHERE ItemId = ?");
            $stmt->execute([$itemId]);
            $message_alerte = ["type" => "succes", "texte" => "L'artefact a été détruit et retiré du marché."];
        } catch (Exception $e) {
            // S'il est dans une commande ou un inventaire, on le désactive au lieu de le supprimer
            $stmt = $pdo->prepare("UPDATE Items SET IsActive = 0 WHERE ItemId = ?");
            $stmt->execute([$itemId]);
            $message_alerte = ["type" => "succes", "texte" => "L'objet est lié à des transactions passées. Il a été masqué du catalogue."];
        }
    }

    // US-32 : Ajouter du capital à un joueur
    elseif ($_POST['action'] === 'add_funds') {
        $targetUserId = (int)$_POST['user_id'];
        $addGold = (int)$_POST['add_gold'];
        $addSilver = (int)$_POST['add_silver'];
        $addBronze = (int)$_POST['add_bronze'];

        try {
            $stmt = $pdo->prepare("UPDATE Users SET Gold = Gold + ?, Silver = Silver + ?, Bronze = Bronze + ? WHERE UserId = ?");
            $stmt->execute([$addGold, $addSilver, $addBronze, $targetUserId]);
            $message_alerte = ["type" => "succes", "texte" => "Les fonds du joueur ont été mis à jour."];
        } catch (Exception $e) {
            $message_alerte = ["type" => "erreur", "texte" => "Erreur lors de l'ajout des fonds."];
        }
    }
}

// 3. RÉCUPÉRATION DES DONNÉES
$items = $pdo->query("SELECT i.*, t.Name AS TypeName FROM Items i JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId ORDER BY i.ItemId DESC")->fetchAll();
$itemTypes = $pdo->query("SELECT * FROM ItemTypes")->fetchAll();
$players = $pdo->query("SELECT UserId, Alias, Role, Gold, Silver, Bronze FROM Users WHERE Role IN ('Player', 'Mage') ORDER BY Alias ASC")->fetchAll();

$title = "Administration - L'Arsenal";
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

include __DIR__ . '/templates/head.php';
?>

<style>
    body { background-image: url('<?= $bgImage ?>') !important; }
    
    .admin-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }

    .admin-menu {
        width: 250px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .admin-tab-btn {
        background: rgba(25, 133, 161, 0.1);
        border: 1px solid rgba(25, 133, 161, 0.4);
        color: white;
        padding: 15px;
        text-align: left;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .admin-tab-btn:hover, .admin-tab-btn.active {
        background: rgba(25, 133, 161, 0.4);
        border-color: var(--accent);
        box-shadow: 0 0 10px rgba(25, 133, 161, 0.3);
    }

    .admin-content { flex: 1; }
    .admin-section { display: none; animation: fadeIn 0.4s ease; }
    .admin-section.active { display: block; }

    /* Tables Glassmorphism */
    .glass-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 8px;
        overflow: hidden;
    }
    .glass-table th, .glass-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: var(--text-light);
    }
    .glass-table th {
        background: rgba(25, 133, 161, 0.2);
        color: var(--accent);
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .glass-table tr:hover { background: rgba(255, 255, 255, 0.03); }

    /* Forms */
    .admin-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .admin-form-group { display: flex; flex-direction: column; gap: 5px; }
    .admin-form-group label { color: var(--accent); font-size: 0.9rem; }
    .admin-input {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 10px;
        color: white;
        border-radius: 4px;
        transition: 0.3s;
    }
    .admin-input:focus {
        border-color: var(--accent);
        outline: none;
        box-shadow: 0 0 8px rgba(25, 133, 161, 0.3);
    }
    
    .btn-danger {
        background: rgba(231, 76, 60, 0.2);
        color: #e74c3c;
        border: 1px solid #e74c3c;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-danger:hover {
        background: #e74c3c;
        color: white;
    }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
    <main style="width: 100%; max-width: 1200px; margin: 0 auto;">
        
        <div class="catalog-banner">
            <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.5rem;">
                <i class="fa-solid fa-crown"></i> Panneau de Commandement
            </h2>
        </div>

        <?php if ($message_alerte): ?>
            <div class="alert-box <?= $message_alerte['type'] ?>" style="margin-top:20px;">
                <i class="fa-solid <?= $message_alerte['type'] === 'succes' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message_alerte['texte']) ?>
            </div>
        <?php endif; ?>

        <div class="admin-container">
            <div class="admin-menu">
                <button class="admin-tab-btn active" onclick="switchTab('items')"><i class="fa-solid fa-box-open"></i> Catalogue Items</button>
                <button class="admin-tab-btn" onclick="switchTab('users')"><i class="fa-solid fa-users"></i> Registre Joueurs</button>
            </div>

            <div class="admin-content">
                
                <div id="tab-items" class="admin-section active details-glass-card">
                    <h3><i class="fa-solid fa-plus-circle"></i> Forger un nouvel artefact</h3>
                    
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_item">
                        <div class="admin-form-grid">
                            <div class="admin-form-group">
                                <label>Nom de l'objet</label>
                                <input type="text" name="name" class="admin-input" required>
                            </div>
                            <div class="admin-form-group">
                                <label>Catégorie</label>
                                <select name="type_id" class="admin-input" required>
                                    <?php foreach ($itemTypes as $type): ?>
                                        <option value="<?= $type['ItemTypeId'] ?>"><?= htmlspecialchars($type['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>Prix Or (GP)</label>
                                <input type="number" min="0" name="gold" class="admin-input" value="0" required>
                            </div>
                            <div class="admin-form-group">
                                <label>Prix Argent (SP)</label>
                                <input type="number" min="0" name="silver" class="admin-input" value="0" required>
                            </div>
                            <div class="admin-form-group">
                                <label>Prix Bronze (BP)</label>
                                <input type="number" min="0" name="bronze" class="admin-input" value="0" required>
                            </div>
                            <div class="admin-form-group">
                                <label>Stock initial</label>
                                <input type="number" min="1" name="stock" class="admin-input" value="1" required>
                            </div>
                        </div>
                        <div class="admin-form-group" style="margin-bottom: 15px;">
                            <label>Description du Lore</label>
                            <textarea name="description" class="admin-input" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="sidebar-inventory-btn" style="width:auto; padding: 10px 20px;">Forger l'objet</button>
                    </form>

                    <hr style="border-color: rgba(255,255,255,0.1); margin: 30px 0;">

                    <h3><i class="fa-solid fa-list"></i> Inventaire du Marché Noir</h3>
                    <div style="overflow-x:auto;">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Prix (G/S/B)</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>#<?= $it['ItemId'] ?></td>
                                    <td><strong><?= htmlspecialchars($it['Name']) ?></strong></td>
                                    <td><?= htmlspecialchars($it['TypeName']) ?></td>
                                    <td><?= $it['PriceGold'] ?>/<?= $it['PriceSilver'] ?>/<?= $it['PriceBronze'] ?></td>
                                    <td style="color: <?= $it['Stock'] > 0 ? '#2ECC71' : '#E74C3C' ?>;"><?= $it['Stock'] ?></td>
                                    <td><?= $it['IsActive'] ? '<span style="color:#2ECC71;">Actif</span>' : '<span style="color:#E74C3C;">Inactif</span>' ?></td>
                                    <td>
                                        <form method="POST" action="admin.php" onsubmit="return confirm('Voulez-vous vraiment retirer cet objet ?');">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="item_id" value="<?= $it['ItemId'] ?>">
                                            <button type="submit" class="btn-danger" title="Supprimer/Masquer"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-users" class="admin-section details-glass-card">
                    <h3><i class="fa-solid fa-coins"></i> Renflouer un Joueur</h3>
                    <p style="font-size: 0.9rem; color: var(--text-silver); margin-bottom: 15px;">Récompensez les aventuriers en leur octroyant des fonds directement dans leur bourse.</p>
                    
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_funds">
                        <div class="admin-form-grid">
                            <div class="admin-form-group">
                                <label>Sélectionner le joueur</label>
                                <select name="user_id" class="admin-input" required>
                                    <option value="" disabled selected>-- Choisir un joueur --</option>
                                    <?php foreach ($players as $p): ?>
                                        <option value="<?= $p['UserId'] ?>"><?= htmlspecialchars($p['Alias']) ?> (<?= $p['Role'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>Or à ajouter (+)</label>
                                <input type="number" min="0" name="add_gold" class="admin-input" value="0">
                            </div>
                            <div class="admin-form-group">
                                <label>Argent à ajouter (+)</label>
                                <input type="number" min="0" name="add_silver" class="admin-input" value="0">
                            </div>
                            <div class="admin-form-group">
                                <label>Bronze à ajouter (+)</label>
                                <input type="number" min="0" name="add_bronze" class="admin-input" value="0">
                            </div>
                        </div>
                        <button type="submit" class="sidebar-inventory-btn" style="width:auto; padding: 10px 20px;"><i class="fa-solid fa-hand-holding-dollar"></i> Transférer les fonds</button>
                    </form>

                    <hr style="border-color: rgba(255,255,255,0.1); margin: 30px 0;">

                    <h3><i class="fa-solid fa-address-book"></i> Registre des Aventuriers</h3>
                    <div style="overflow-x:auto;">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Alias</th>
                                    <th>Classe</th>
                                    <th>Capital (GP / SP / BP)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $p): ?>
                                <tr>
                                    <td>#<?= $p['UserId'] ?></td>
                                    <td><strong><?= htmlspecialchars($p['Alias']) ?></strong></td>
                                    <td><?= $p['Role'] === 'Mage' ? '<span style="color:#9b59b6;"><i class="fa-solid fa-hat-wizard"></i> Mage</span>' : 'Joueur' ?></td>
                                    <td>
                                        <span style="color: gold;"><?= $p['Gold'] ?></span> / 
                                        <span style="color: silver;"><?= $p['Silver'] ?></span> / 
                                        <span style="color: #cd7f32;"><?= $p['Bronze'] ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
    // Script pour naviguer entre les onglets
    function switchTab(tabId) {
        // Retirer la classe active de tous les boutons et sections
        document.querySelectorAll('.admin-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
        
        // Activer le bouton cliqué et la section correspondante
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // Auto-suppression des alertes après 4 secondes
    setTimeout(() => {
        const alertBox = document.querySelector('.alert-box');
        if (alertBox) {
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 500);
        }
    }, 4000);
</script>

<?php 
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php'; 
?>