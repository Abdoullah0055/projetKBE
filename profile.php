<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/profile_utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit();
}

$pdo = get_pdo();
$userId = (int)$_SESSION['user']['id'];
$loadError = '';

try {
    $stmt = $pdo->prepare(
        "SELECT UserId, Alias, FullName, Email, AvatarUrl, Role, Gold, Silver, Bronze
         FROM Users
         WHERE UserId = ?
         LIMIT 1"
    );
    $stmt->execute([$userId]);
    $dbUser = $stmt->fetch();

    if (!$dbUser) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }

$_SESSION['user']['alias'] = $dbUser['alias'];
        $_SESSION['user']['role'] = $dbUser['role'];
        $_SESSION['user']['gold'] = (int)$dbUser['gold'];
        $_SESSION['user']['silver'] = (int)$dbUser['silver'];
        $_SESSION['user']['bronze'] = (int)$dbUser['bronze'];
} catch (PDOException $e) {
    $dbUser = [
        'alias' => $_SESSION['user']['alias'] ?? '',
        'fullname' => null,
        'email' => null,
        'avatarurl' => null,
        'role' => $_SESSION['user']['role'] ?? 'Player',
        'gold' => $_SESSION['user']['gold'] ?? 0,
        'silver' => $_SESSION['user']['silver'] ?? 0,
        'bronze' => $_SESSION['user']['bronze'] ?? 0,
    ];
    $loadError = "Impossible de charger toutes les donnees profil actuellement.";
}

$user = [
    'isConnected' => true,
    'id' => $userId,
    'alias' => $dbUser['alias'],
    'isMage' => (($dbUser['role'] ?? 'Player') === 'Mage'),
    'balance' => [
        'gold' => (int)($dbUser['gold'] ?? 0),
        'silver' => (int)($dbUser['silver'] ?? 0),
        'bronze' => (int)($dbUser['bronze'] ?? 0),
    ],
];

$flash = profile_take_flash();
$csrfToken = profile_csrf_token();

$title = "L'Arsenal - Mon Profil";
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

$avatarUrl = trim((string)($dbUser['avatarurl'] ?? ''));
$hasAvatar = $avatarUrl !== '';
$avatarInitial = strtoupper(mb_substr((string)$dbUser['alias'], 0, 1, 'UTF-8'));
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
    }
</style>
<link rel="stylesheet" href="assets/css/profile.css">
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="profile-page">
    <section class="profile-hero">
        <div class="profile-avatar">
            <?php if ($hasAvatar): ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar du joueur" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span class="avatar-fallback" style="display:none;"><?= htmlspecialchars($avatarInitial) ?></span>
            <?php else: ?>
                <span class="avatar-fallback"><?= htmlspecialchars($avatarInitial) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <h1>Gestion du profil</h1>
            <p>Modifiez vos informations personnelles ou supprimez totalement votre compte.</p>
        </div>
    </section>

    <?php if ($loadError !== ''): ?>
        <div class="profile-alert alert-error"><?= htmlspecialchars($loadError) ?></div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="profile-alert <?= ($flash['type'] === 'success') ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <section class="profile-card">
            <h2>Modifier mon profil</h2>
            <form method="POST" action="backend/profile_update.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <label for="alias">Alias</label>
                <input id="alias" name="alias" type="text" required minlength="3" maxlength="30"
                    value="<?= htmlspecialchars((string)($dbUser['alias'] ?? '')) ?>">

                <label for="full_name">Nom complet</label>
                <input id="full_name" name="full_name" type="text" maxlength="80"
                    value="<?= htmlspecialchars((string)($dbUser['fullname'] ?? '')) ?>" placeholder="Optionnel">

                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="190"
                    value="<?= htmlspecialchars((string)($dbUser['email'] ?? '')) ?>" placeholder="Optionnel">

                <label for="avatar_url">Avatar (URL)</label>
                <input id="avatar_url" name="avatar_url" type="url" maxlength="255"
                    value="<?= htmlspecialchars((string)($dbUser['avatarurl'] ?? '')) ?>" placeholder="https://...">

                <h3>Changer le mot de passe</h3>
                <p class="field-help">Remplissez ces champs uniquement si vous voulez changer votre mot de passe.</p>

                <label for="current_password">Mot de passe actuel</label>
                <input id="current_password" name="current_password" type="password" minlength="6">

                <label for="new_password">Nouveau mot de passe</label>
                <input id="new_password" name="new_password" type="password" minlength="6">

                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="6">

                <button type="submit" class="btn-primary">Enregistrer mes modifications</button>
            </form>
        </section>

        <section class="profile-card danger-card">
            <h2>Actions sensibles</h2>
            <p class="danger-intro">Cette action est irreversible. Une confirmation explicite est obligatoire.</p>

            <form method="POST" action="backend/profile_delete_account.php" class="danger-form confirm-form" data-confirm-text="SUPPRIMER MON COMPTE" data-final-confirm="Derniere confirmation: supprimer definitivement ce compte ?">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <h3>Supprimer mon compte</h3>
                <p>Votre compte et son historique seront supprimes definitivement.</p>

                <label for="account_phrase">Ecrivez "SUPPRIMER MON COMPTE"</label>
                <input id="account_phrase" name="confirmation_text" type="text" required autocomplete="off">

                <label for="account_password">Mot de passe actuel</label>
                <input id="account_password" name="password" type="password" minlength="6" required autocomplete="off">

                <label class="check-line">
                    <input type="checkbox" name="confirm_delete_account" value="1" required>
                    Je comprends que cette suppression est definitive.
                </label>

                <button type="submit" class="btn-danger btn-danger-strong" disabled>Supprimer mon compte</button>
            </form>
        </section>
    </div>
</main>

<script src="assets/js/profile.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
