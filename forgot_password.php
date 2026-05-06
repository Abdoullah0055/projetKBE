<?php

require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/email_utils.php';
require_once __DIR__ . '/includes/token_utils.php';
require_once __DIR__ . '/includes/mailer_utils.php';

$pdo = get_pdo();
$message = '';
$error = '';
$devToken = '';
$devResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email($_POST['email'] ?? null);

    if (!validate_email($email, false)) {
        $error = 'Adresse courriel invalide.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT UserId, Alias, Email FROM Users WHERE LOWER(TRIM(Email)) = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userRow) {
                $token = generate_reset_token();
                $tokenHash = hash_reset_token($token);

                $smtp = get_smtp_config();
                $ttl = (int) ($smtp['token_ttl_seconds'] ?? 3600);
                $expiresAt = get_token_expiry($ttl);

                $insert = $pdo->prepare(
                    'INSERT INTO PasswordResets (UserId, Email, TokenHash, ExpiresAt)
                     VALUES (:user_id, :email, :token_hash, :expires_at)'
                );
                $insert->execute([
                    ':user_id' => (int) $userRow['userid'],
                    ':email' => (string) $userRow['email'],
                    ':token_hash' => $tokenHash,
                    ':expires_at' => $expiresAt,
                ]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');
                $resetUrl = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . urlencode($token);

                $mailResult = send_password_reset_email((string) $userRow['email'], (string) $userRow['alias'], $resetUrl);

                if (!($mailResult['success'] ?? false)) {
                    $error = (string) ($mailResult['message'] ?? 'Erreur d\'envoi du courriel.');
                }

                if (($smtp['dev_display_token'] ?? false) === true) {
                    $devToken = $token;
                    $devResetLink = $resetUrl;
                }
            }

            if ($error === '') {
                $message = 'Si ce courriel existe, un lien de reinitialisation a ete envoye.';
            }
        } catch (Throwable $e) {
            $error = 'Impossible de traiter la demande pour le moment.';
        }
    }
}

$title = 'Mot de passe oublie - L\'Arsenal';
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<style>
:root { --main-bg: url('<?= $bgImage ?>'); }
</style>
<link rel="stylesheet" href="assets/css/login.css">

<main class="auth-page">
    <div class="auth-container fade-in">
        <div class="auth-header">
            <div style="font-size: 3rem; margin-bottom: 10px;"><i class="fa-solid fa-unlock-keyhole"></i></div>
            <h2>Reinitialiser</h2>
            <p style="font-size: 0.9rem; color: #5C5F66;">Entrez votre courriel pour recevoir un lien.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php">
            <div class="form-group">
                <label for="email">Adresse courriel</label>
                <input id="email" type="email" name="email" required placeholder="exemple@domaine.com">
            </div>
            <button type="submit" class="btn-primary">Envoyer le lien</button>
        </form>

        <?php if ($devToken !== '' && $devResetLink !== ''): ?>
            <div class="alert-msg" style="margin-top: 16px; border-color: rgba(241, 196, 15, 0.45); background: rgba(241, 196, 15, 0.13); color: #ffd975; text-align: left;">
                <strong>Mode developpement</strong><br>
                Token: <code><?= htmlspecialchars($devToken) ?></code><br>
                Lien: <a href="<?= htmlspecialchars($devResetLink) ?>" style="color:#ffecb0; word-break: break-all;"><?= htmlspecialchars($devResetLink) ?></a>
            </div>
        <?php endif; ?>

        <div class="switch-mode">
            <a href="login.php">Retour a la connexion</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/templates/end.php'; ?>
