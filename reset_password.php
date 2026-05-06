<?php

require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/token_utils.php';

$pdo = get_pdo();
$error = '';
$success = '';
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($token === '') {
    $error = 'Lien invalide ou incomplet.';
}

function find_active_password_reset(PDO $pdo, string $token): ?array
{
    if ($token === '') {
        return null;
    }

    $hash = hash_reset_token($token);
    $stmt = $pdo->prepare(
        "SELECT pr.ResetId, pr.UserId, pr.ExpiresAt, pr.UsedAt
         FROM PasswordResets pr
         WHERE pr.TokenHash = :token_hash
           AND pr.UsedAt IS NULL
         ORDER BY pr.ResetId DESC
         LIMIT 1"
    );
    $stmt->execute([':token_hash' => $hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    if (strtotime((string) $row['expiresat']) < time()) {
        return null;
    }

    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (mb_strlen($newPassword, '8bit') < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caracteres.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'La confirmation du mot de passe ne correspond pas.';
    } else {
        try {
            $reset = find_active_password_reset($pdo, $token);
            if (!$reset) {
                $error = 'Ce lien est invalide ou expire.';
            } else {
                $pdo->beginTransaction();

                $updateUser = $pdo->prepare('UPDATE Users SET Password = :password WHERE UserId = :user_id');
                $updateUser->execute([
                    ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    ':user_id' => (int) $reset['userid'],
                ]);

                $markToken = $pdo->prepare('UPDATE PasswordResets SET UsedAt = NOW() WHERE ResetId = :reset_id');
                $markToken->execute([':reset_id' => (int) $reset['resetid']]);

                $expireAll = $pdo->prepare('UPDATE PasswordResets SET UsedAt = NOW() WHERE UserId = :user_id AND UsedAt IS NULL');
                $expireAll->execute([':user_id' => (int) $reset['userid']]);

                $pdo->commit();
                $success = 'Mot de passe mis a jour. Vous pouvez maintenant vous connecter.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Impossible de reinitialiser le mot de passe maintenant.';
        }
    }
}

if ($error === '' && $success === '' && $token !== '') {
    $reset = find_active_password_reset($pdo, $token);
    if (!$reset) {
        $error = 'Ce lien est invalide ou expire.';
    }
}

$title = 'Nouveau mot de passe - L\'Arsenal';
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
            <div style="font-size: 3rem; margin-bottom: 10px;"><i class="fa-solid fa-key"></i></div>
            <h2>Nouveau mot de passe</h2>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($success) ?></div>
            <div class="switch-mode"><a href="login.php">Retour a la connexion</a></div>
        <?php elseif ($error === ''): ?>
            <form method="post" action="reset_password.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input id="new_password" type="password" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input id="confirm_password" type="password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn-primary">Enregistrer</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/templates/end.php'; ?>
