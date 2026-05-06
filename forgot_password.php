<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/includes/token_utils.php';
require_once __DIR__ . '/includes/mailer_utils.php';
require_once __DIR__ . '/includes/email_utils.php';

$pdo = get_pdo();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email($_POST['email'] ?? '');

    if (!validate_email($email)) {
        $error = "L'adresse courriel est invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT Alias, Email FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if (!empty($user)) {
            $token = generate_reset_token();
            save_reset_token($pdo, $email, $token);

            $resetLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http")
                . "://" . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF'])
                . "/reset_password.php?token=" . urlencode($token);

            $alias = (string)($user['Alias'] ?? $user['alias'] ?? 'Aventurier');
            $subject = "Reinitialisation de votre mot de passe Darquest";
            $body = "<p>Bonjour " . htmlspecialchars($alias, ENT_QUOTES, 'UTF-8') . ",</p>"
                . "<p>Voici votre lien de reinitialisation :</p>"
                . "<p><a href=\"{$resetLink}\">Changer mon mot de passe</a></p>"
                . "<p>Le lien expire dans 1 heure.</p>";

            send_darquest_mail($email, $subject, $body);
        }
        $message = "Si cet email existe, un lien de reinitialisation a ete envoye.";
    }
}

$title = "L'Arsenal - Mot de passe oublie";
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";
?>
<?php include __DIR__ . '/templates/head.php'; ?>
<style>
    :root {
        --main-bg: url('<?= htmlspecialchars($bgImage, ENT_QUOTES, 'UTF-8') ?>');
    }
</style>
<link rel="stylesheet" href="assets/css/login.css">

<main class="auth-page">
    <div class="auth-container fade-in">
        <div class="auth-header">
            <div style="font-size: 3rem; margin-bottom: 10px;"><i class="fa-solid fa-envelope"></i></div>
            <h2>Mot de passe oublie</h2>
            <p style="font-size: 0.8rem; color: #5C5F66;">Recevez un lien de reinitialisation securise</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label for="email">Adresse courriel</label>
                <input id="email" name="email" type="email" required placeholder="aventurier@exemple.com">
            </div>
            <button type="submit" class="btn-primary">Envoyer le lien</button>
        </form>

        <div class="switch-mode">
            <a href="login.php">Retour a la connexion</a>
        </div>
    </div>
</main>
<?php include __DIR__ . '/templates/end.php'; ?>
