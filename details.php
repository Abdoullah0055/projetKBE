<?php
require_once 'AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pdo = get_pdo();

// 1. RÉCUPÉRATION DU THÈME
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// Gestion de l'utilisateur
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'balance' => ['gold' => $_SESSION['user']['gold'], 'silver' => $_SESSION['user']['silver'], 'bronze' => $_SESSION['user']['bronze']]
    ];
} else {
    $user = ['isConnected' => false];
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT i.ItemId AS id, i.Name AS nom, i.PriceGold AS prix, i.Description AS description,
           i.Stock AS stock, t.Name AS type, IFNULL(AVG(r.Rating), 0) AS rating, COUNT(r.ReviewId) AS nb_avis
    FROM Items i
    JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
    LEFT JOIN Reviews r ON i.ItemId = r.ItemId
    WHERE i.ItemId = ?
    GROUP BY i.ItemId, i.Name, i.PriceGold, i.Description, i.Stock, t.Name
");
$stmt->execute([$_GET['id']]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: index.php");
    exit();
}

$icons = ['arme' => '⚔️', 'armure' => '🛡️', 'potion' => '🧪', 'sort' => '✨'];
$item['image'] = $icons[strtolower($item['type'])] ?? '❓';
$title = "Détails - " . $item['nom'];
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e !important;
        overflow-y: auto !important;
    }
</style>

<link rel="stylesheet" href="assets/css/details.css">

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (isset($_SESSION['alerte'])): ?>
    <div class="alert-box <?= $_SESSION['alerte']['type'] ?>">
        <i class="fa-solid <?= $_SESSION['alerte']['type'] === 'succes' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
        <?= $_SESSION['alerte']['message'] ?>
    </div>
    <?php unset($_SESSION['alerte']); ?>
<?php endif; ?>

<div class="details-wrapper">
    <main class="details-glass-card">

        <div class="visual-column">
            <div class="item-display-box" onclick="triggerMagic()">
                <div class="rarity-tag"><?= htmlspecialchars($item['type']) ?></div>

                <div class="floating-wrapper">
                    <div class="main-icon" id="target-icon"><?= $item['image'] ?></div>
                </div>

                <div class="glow-shadow"></div>
                <span class="click-hint">Touchez l'artefact</span>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-label">Avis</span>
                    <span class="stat-value">★ <?= number_format($item['rating'], 1) ?></span>
                    <span class="stat-sub"><?= $item['nb_avis'] ?> avis</span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Stock</span>
                    <span class="stat-value <?= ($item['stock'] == 0) ? 'text-danger' : 'text-success' ?>">
                        <?= $item['stock'] ?>
                    </span>
                    <span class="stat-sub">unités</span>
                </div>
            </div>
        </div>

        <div class="info-column">
            <div class="item-title-section">
                <h1><?= htmlspecialchars($item['nom']) ?></h1>
                <div class="price-tag"><?= number_format($item['prix'], 0) ?> <span class="gp">GP</span></div>
            </div>

            <div class="description-section">
                <h3><i class="fa-solid fa-scroll"></i> Lore & Propriétés</h3>
                <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
            </div>

            <div class="spec-grid">
                <div class="spec-item"><span>Catégorie</span><strong><?= ucfirst($item['type']) ?></strong></div>
                <div class="spec-item"><span>Authenticité</span><strong>Certifiée</strong></div>
                <div class="spec-item"><span>Origine</span><strong>Inconnue</strong></div>
            </div>

            <div class="purchase-section">
                <?php if ($item['stock'] > 0): ?>
                    <form action="backend/ajouter_au_panier.php" method="POST" class="purchase-form">

                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                        <div class="purchase-controls">
                            <div class="quantity-wrapper">
                                <label for="qty">Quantité :</label>
                                <select name="quantity" id="qty" class="qty-select">
                                    <?php for ($i = 1; $i <= min($item['stock'], 10); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <?php if ($item['stock'] < 5): ?>
                                <div class="urgency-badge">
                                    <i class="fa-solid fa-bolt"></i>
                                    Plus que <?= $item['stock'] ?> restant !
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-buy-large">Ajouter au Panier</button>
                    </form>
                <?php else: ?>
                    <button class="btn-buy-large btn-out" disabled>Stock Épuisé</button>
                <?php endif; ?>

                <a href="index.php" class="back-link">Retour au catalogue</a>
            </div>
        </div>
    </main>
</div>

<script>
    // 1. Animation de secousse au clic sur l'icône
    function triggerMagic() {
        const icon = document.getElementById('target-icon');
        icon.classList.remove('magic-shake');
        void icon.offsetWidth;
        icon.classList.add('magic-shake');
    }

    // 2. Logique "Fly to Cart" et Ajout AJAX
    document.querySelector('.purchase-form')?.addEventListener('submit', async function(e) {
        e.preventDefault(); // Empêche le rechargement de la page

        // --- NOUVEAU : VÉRIFICATION CONNEXION ---
        // On récupère la variable PHP dans le JS
        const isConnected = <?= json_encode($user['isConnected']) ?>;

        if (!isConnected) {
            // On récupère l'ID de l'item actuel pour revenir ici
            const itemId = document.querySelector('input[name="item_id"]').value;
            // On redirige vers login.php avec le paramètre de retour
            window.location.href = `login.php?return_to=details.php?id=${itemId}`;
            return; // On arrête tout le reste
        }

        const form = this;
        const formData = new FormData(form);
        const cartBtn = document.getElementById('cart-btn');
        const targetIcon = document.getElementById('target-icon');

        // --- PARTIE ANIMATION ---
        // Création du clone
        const clone = document.createElement('div');
        clone.innerHTML = targetIcon.innerHTML; // On prend l'émoji/icône
        clone.className = 'flying-item';

        // Position de départ (l'item au centre)
        const startRect = targetIcon.getBoundingClientRect();
        clone.style.left = startRect.left + 'px';
        clone.style.top = startRect.top + 'px';
        document.body.appendChild(clone);

        // Position d'arrivée (le panier dans le header)
        const endRect = cartBtn.getBoundingClientRect();

        // On lance le mouvement au prochain frame
        requestAnimationFrame(() => {
            clone.style.left = endRect.left + 'px';
            clone.style.top = endRect.top + 'px';
            clone.style.transform = 'scale(0.1) rotate(360deg)'; // Réduit et tourne
            clone.style.opacity = '0';
        });

        // Nettoyage après l'animation
        setTimeout(() => {
            clone.remove();
            cartBtn.classList.add('cart-shake');
            setTimeout(() => cartBtn.classList.remove('cart-shake'), 400);
        }, 800);

        // --- PARTIE ENVOI DES DONNÉES ---
        try {
            const response = await fetch('backend/ajouter_au_panier.php', {
                method: 'POST',
                body: formData
            });

            // On recharge juste pour voir l'alerte PHP ou on peut l'afficher en JS
            // Pour rester simple avec ta version 4.2, on simule le succès :
            if (response.ok) {
                console.log("Ajouté avec succès");
                // Optionnel : afficher une petite alerte personnalisée ici
            }
        } catch (error) {
            console.error("Erreur:", error);
        }
    });
</script>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>