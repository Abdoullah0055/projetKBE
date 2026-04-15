<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$pdo = get_pdo();

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

// --- ACTIONS BACKEND ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Ajouter Item
    if ($_POST['action'] === 'add_item') {
        $name = trim($_POST['name']); $desc = trim($_POST['description']);
        $gold = (int)$_POST['gold']; $silver = (int)$_POST['silver']; $bronze = (int)$_POST['bronze'];
        $stock = (int)$_POST['stock']; $typeId = (int)$_POST['type_id'];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $desc, $gold, $silver, $bronze, $stock, $typeId]);
            $newItemId = $pdo->lastInsertId();

            $typeName = strtolower($pdo->query("SELECT Name FROM ItemTypes WHERE ItemTypeId = $typeId")->fetchColumn());
            if ($typeName === 'weapon') $pdo->prepare("INSERT INTO WeaponProperties (ItemId, DamageMin, DamageMax) VALUES (?, 10, 20)")->execute([$newItemId]);
            elseif ($typeName === 'armor') $pdo->prepare("INSERT INTO ArmorProperties (ItemId, Defense) VALUES (?, 15)")->execute([$newItemId]);
            elseif ($typeName === 'potion') $pdo->prepare("INSERT INTO PotionProperties (ItemId, EffectType, EffectValue) VALUES (?, 'Heal', 50)")->execute([$newItemId]);
            elseif ($typeName === 'magicspell') $pdo->prepare("INSERT INTO MagicSpellProperties (ItemId, SpellDamage, ManaCost, ElementType) VALUES (?, 30, 15, 'Magic')")->execute([$newItemId]);

            $pdo->commit();
            $message_alerte = ["type" => "succes", "texte" => "L'artefact '$name' a été ajouté avec succès."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $message_alerte = ["type" => "erreur", "texte" => "Erreur : Ce nom existe peut-être déjà."];
        }
    }

    // 2. Modifier Item
    elseif ($_POST['action'] === 'edit_item') {
        $itemId = (int)$_POST['item_id'];
        $name = trim($_POST['name']); $desc = trim($_POST['description']);
        $gold = (int)$_POST['gold']; $silver = (int)$_POST['silver']; $bronze = (int)$_POST['bronze'];
        $stock = (int)$_POST['stock'];
        
        $stmt = $pdo->prepare("UPDATE Items SET Name=?, Description=?, PriceGold=?, PriceSilver=?, PriceBronze=?, Stock=? WHERE ItemId=?");
        $stmt->execute([$name, $desc, $gold, $silver, $bronze, $stock, $itemId]);
        $message_alerte = ["type" => "succes", "texte" => "L'artefact a été modifié avec succès."];
    }

    // 3. Activer / Désactiver Item
    elseif ($_POST['action'] === 'toggle_item') {
        $itemId = (int)$_POST['item_id'];
        $stmt = $pdo->prepare("UPDATE Items SET IsActive = NOT IsActive WHERE ItemId=?");
        $stmt->execute([$itemId]);
        $message_alerte = ["type" => "succes", "texte" => "Le statut de disponibilité de l'artefact a été mis à jour."];
    }

    // 4. Supprimer Item définitivement
    elseif ($_POST['action'] === 'delete_item') {
        $itemId = (int)$_POST['item_id'];
        try {
            $pdo->prepare("DELETE FROM Items WHERE ItemId = ?")->execute([$itemId]);
            $message_alerte = ["type" => "succes", "texte" => "L'artefact a été détruit définitivement."];
        } catch (Exception $e) {
            $pdo->prepare("UPDATE Items SET IsActive = 0 WHERE ItemId = ?")->execute([$itemId]);
            $message_alerte = ["type" => "succes", "texte" => "L'objet est lié à des achats passés, il a été désactivé (caché) au lieu d'être supprimé."];
        }
    }

    // 5. Ajouter Fonds au Joueur
    elseif ($_POST['action'] === 'add_funds') {
        $targetUserId = (int)$_POST['user_id'];
        $addGold = (int)$_POST['add_gold']; $addSilver = (int)$_POST['add_silver']; $addBronze = (int)$_POST['add_bronze'];
        $pdo->prepare("UPDATE Users SET Gold = Gold + ?, Silver = Silver + ?, Bronze = Bronze + ? WHERE UserId = ?")->execute([$addGold, $addSilver, $addBronze, $targetUserId]);
        $message_alerte = ["type" => "succes", "texte" => "Les fonds du joueur ont été mis à jour."];
    }

    // 6. Bannir / Débannir Joueur
    elseif ($_POST['action'] === 'toggle_ban') {
        $targetUserId = (int)$_POST['user_id'];
        $pdo->prepare("UPDATE Users SET IsBanned = NOT IsBanned WHERE UserId = ?")->execute([$targetUserId]);
        $message_alerte = ["type" => "succes", "texte" => "Le droit de connexion du joueur a été modifié."];
    }

    // 7. Supprimer un Joueur
    elseif ($_POST['action'] === 'delete_user') {
        $targetUserId = (int)$_POST['user_id'];
        try {
            $pdo->beginTransaction();
            // On supprime d'abord les commandes pour ne pas bloquer la suppression du joueur
            $pdo->prepare("DELETE FROM Orders WHERE UserId = ?")->execute([$targetUserId]);
            $pdo->prepare("DELETE FROM Users WHERE UserId = ?")->execute([$targetUserId]);
            $pdo->commit();
            $message_alerte = ["type" => "succes", "texte" => "Le joueur a été supprimé des archives."];
        } catch(Exception $e) {
            $pdo->rollBack();
            $message_alerte = ["type" => "erreur", "texte" => "Erreur inattendue lors de la suppression."];
        }
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$items = $pdo->query("SELECT i.*, t.Name AS TypeName FROM Items i JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId ORDER BY i.ItemId DESC")->fetchAll();
$itemTypes = $pdo->query("SELECT * FROM ItemTypes")->fetchAll();

// On vérifie si la colonne IsBanned existe pour éviter que la page plante si le SQL n'est pas fait
$hasBannedCol = false;
try { $pdo->query("SELECT IsBanned FROM Users LIMIT 1"); $hasBannedCol = true; } catch (Exception $e) {}

$query = "SELECT UserId, Alias, Role, Gold, Silver, Bronze " . ($hasBannedCol ? ", IsBanned" : "") . " FROM Users WHERE Role IN ('Player', 'Mage') ORDER BY Alias ASC";
$players = $pdo->query($query)->fetchAll();

$title = "Administration - L'Arsenal";
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

include __DIR__ . '/templates/head.php';
?>

<style>
    body { background-image: url('<?= $bgImage ?>') !important; }
    .admin-container { display: flex; gap: 20px; margin-top: 20px; }
    .admin-menu { width: 250px; display: flex; flex-direction: column; gap: 10px; }

    .admin-tab-btn {
        background: rgba(25, 133, 161, 0.1); border: 1px solid rgba(25, 133, 161, 0.4); color: white;
        padding: 15px; text-align: left; border-radius: 8px; cursor: pointer; font-weight: bold;
        transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
    }
    .admin-tab-btn:hover, .admin-tab-btn.active {
        background: rgba(25, 133, 161, 0.4); border-color: var(--accent); box-shadow: 0 0 10px rgba(25, 133, 161, 0.3);
    }

    .admin-content { flex: 1; }
    .admin-section { display: none; animation: fadeIn 0.4s ease; }
    .admin-section.active { display: block; }

    .glass-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: rgba(0, 0, 0, 0.4); border-radius: 8px; overflow: hidden; }
    .glass-table th, .glass-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: var(--text-light); }
    .glass-table th { background: rgba(25, 133, 161, 0.2); color: var(--accent); text-transform: uppercase; font-size: 0.85rem; }
    .glass-table tr:hover { background: rgba(255, 255, 255, 0.03); }

    .admin-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .admin-form-group { display: flex; flex-direction: column; gap: 5px; }
    .admin-form-group label { color: var(--accent); font-size: 0.9rem; }
    
    .admin-input {
        background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 10px; color: white; border-radius: 4px; transition: 0.3s;
    }
    .admin-input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 8px rgba(25, 133, 161, 0.3); }
    
    /* FIX DU TEXTE INVISIBLE POUR LES COMBOBOX */
    .admin-input option {
        background-color: #1a1b1e;
        color: white;
    }

    .btn-danger { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; border-radius: 4px; cursor: pointer; transition: 0.3s; }
    .btn-danger:hover { background: #e74c3c; color: white; }

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
                                    <th>Prix (G/S/B)</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>#<?= $it['ItemId'] ?></td>
                                    <td><strong><?= htmlspecialchars($it['Name']) ?></strong><br><small><?= htmlspecialchars($it['TypeName']) ?></small></td>
                                    <td><?= $it['PriceGold'] ?>/<?= $it['PriceSilver'] ?>/<?= $it['PriceBronze'] ?></td>
                                    <td style="color: <?= $it['Stock'] > 0 ? '#2ECC71' : '#E74C3C' ?>;"><?= $it['Stock'] ?></td>
                                    <td><?= $it['IsActive'] ? '<span style="color:#2ECC71;">Actif</span>' : '<span style="color:#E67E22;">Désactivé</span>' ?></td>
                                    <td>
                                        <button type="button" class="btn-outline-custom" style="padding:5px; border-color:var(--accent); color:var(--accent);" title="Modifier"
                                                onclick='openEditModal(<?= $it['ItemId'] ?>, <?= json_encode($it['Name']) ?>, <?= json_encode($it['Description']) ?>, <?= $it['PriceGold'] ?>, <?= $it['PriceSilver'] ?>, <?= $it['PriceBronze'] ?>, <?= $it['Stock'] ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        
                                        <form style="display:inline;" method="POST" action="admin.php">
                                            <input type="hidden" name="action" value="toggle_item">
                                            <input type="hidden" name="item_id" value="<?= $it['ItemId'] ?>">
                                            <button type="submit" class="btn-outline-custom" style="padding:5px; border-color:<?= $it['IsActive'] ? '#E67E22' : '#2ECC71' ?>; color:<?= $it['IsActive'] ? '#E67E22' : '#2ECC71' ?>;" title="<?= $it['IsActive'] ? 'Désactiver' : 'Activer' ?>">
                                                <i class="fa-solid <?= $it['IsActive'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            </button>
                                        </form>

                                        <form style="display:inline;" method="POST" action="admin.php" onsubmit="return confirm('Voulez-vous vraiment détruire définitivement cet objet ?');">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="item_id" value="<?= $it['ItemId'] ?>">
                                            <button type="submit" class="btn-danger" style="padding:5px;" title="Supprimer Définitivement"><i class="fa-solid fa-trash"></i></button>
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
                    
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_funds">
                        <div class="admin-form-grid">
                            <div class="admin-form-group">
                                <label>Sélectionner le joueur</label>
                                <select name="user_id" class="admin-input" required>
                                    <option value="" disabled selected>-- Choisir un joueur --</option>
                                    <?php foreach ($players as $p): ?>
                                        <option value="<?= $p['UserId'] ?>"><?= htmlspecialchars($p['Alias']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="admin-form-group">
                                <label>Or (+)</label>
                                <input type="number" min="0" name="add_gold" class="admin-input" value="0">
                            </div>
                            <div class="admin-form-group">
                                <label>Argent (+)</label>
                                <input type="number" min="0" name="add_silver" class="admin-input" value="0">
                            </div>
                            <div class="admin-form-group">
                                <label>Bronze (+)</label>
                                <input type="number" min="0" name="add_bronze" class="admin-input" value="0">
                            </div>
                        </div>
                        <button type="submit" class="sidebar-inventory-btn" style="width:auto; padding: 10px 20px;">Transférer les fonds</button>
                    </form>

                    <hr style="border-color: rgba(255,255,255,0.1); margin: 30px 0;">

                    <h3><i class="fa-solid fa-address-book"></i> Registre des Aventuriers</h3>
                    <div style="overflow-x:auto;">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Alias</th>
                                    <th>Capital</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $p): ?>
                                <tr>
                                    <td>#<?= $p['UserId'] ?></td>
                                    <td><strong><?= htmlspecialchars($p['Alias']) ?></strong><br><small><?= $p['Role'] ?></small></td>
                                    <td><span style="color: gold;"><?= $p['Gold'] ?></span> / <span style="color: silver;"><?= $p['Silver'] ?></span> / <span style="color: #cd7f32;"><?= $p['Bronze'] ?></span></td>
                                    <td>
                                        <?php if(isset($p['IsBanned']) && $p['IsBanned']): ?>
                                            <span style="color: #E74C3C; font-weight:bold;">Bloqué</span>
                                        <?php else: ?>
                                            <span style="color: #2ECC71;">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form style="display:inline;" method="POST" action="admin.php">
                                            <input type="hidden" name="action" value="toggle_ban">
                                            <input type="hidden" name="user_id" value="<?= $p['UserId'] ?>">
                                            <?php if(isset($p['IsBanned']) && $p['IsBanned']): ?>
                                                <button type="submit" class="btn-outline-custom" style="padding:5px; border-color:#2ECC71; color:#2ECC71;" title="Débloquer l'accès"><i class="fa-solid fa-unlock"></i></button>
                                            <?php else: ?>
                                                <button type="submit" class="btn-outline-custom" style="padding:5px; border-color:#E67E22; color:#E67E22;" title="Bloquer la connexion"><i class="fa-solid fa-ban"></i></button>
                                            <?php endif; ?>
                                        </form>

                                        <form style="display:inline;" method="POST" action="admin.php" onsubmit="return confirm('⚠️ ATTENTION : Voulez-vous détruire ce compte et toutes ses données (Inventaire, Commandes) définitivement ?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $p['UserId'] ?>">
                                            <button type="submit" class="btn-danger" style="padding:5px;" title="Supprimer le joueur"><i class="fa-solid fa-user-slash"></i></button>
                                        </form>
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

<div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-content details-glass-card" style="background:#1a1b1e; padding:30px; border-radius:8px; width:500px; border: 1px solid var(--accent);">
        <h3 style="margin-top:0; color:var(--accent);"><i class="fa-solid fa-hammer"></i> Modifier l'Artefact</h3>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="edit_item">
            <input type="hidden" name="item_id" id="edit_id">
            
            <div class="admin-form-group">
                <label>Nom de l'objet</label>
                <input type="text" name="name" id="edit_name" class="admin-input" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <div class="admin-form-group" style="flex:1;">
                    <label>GP</label><input type="number" min="0" name="gold" id="edit_gold" class="admin-input" required>
                </div>
                <div class="admin-form-group" style="flex:1;">
                    <label>SP</label><input type="number" min="0" name="silver" id="edit_silver" class="admin-input" required>
                </div>
                <div class="admin-form-group" style="flex:1;">
                    <label>BP</label><input type="number" min="0" name="bronze" id="edit_bronze" class="admin-input" required>
                </div>
                <div class="admin-form-group" style="flex:1;">
                    <label>Stock</label><input type="number" min="0" name="stock" id="edit_stock" class="admin-input" required>
                </div>
            </div>
            <div class="admin-form-group" style="margin-top:10px;">
                <label>Description</label>
                <textarea name="description" id="edit_desc" class="admin-input" rows="4" required></textarea>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-outline-custom" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="sidebar-inventory-btn" style="width:auto; padding:8px 15px;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.admin-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    function openEditModal(id, name, desc, gold, silver, bronze, stock) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_desc').value = desc;
        document.getElementById('edit_gold').value = gold;
        document.getElementById('edit_silver').value = silver;
        document.getElementById('edit_bronze').value = bronze;
        document.getElementById('edit_stock').value = stock;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

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