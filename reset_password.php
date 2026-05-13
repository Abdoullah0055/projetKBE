<?php
require_once __DIR__ . '/AlgosBD.php';

$pdo = get_pdo();
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = '';
$message = '';
$isTokenValid = false;

if ($token === '') {
    $error = "Lien invalide ou expire.";
} else {
    $stmt = $pdo->prepare("SELECT Email FROM PasswordResets WHERE Token = ? AND ExpiresAt > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $resetReq = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (empty($resetReq)) {
        $error = "Lien invalide ou expire.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $isTokenValid = true;
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caracteres.";
        } elseif ($password !== $confirmPassword) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $email = (string)($resetReq['Email'] ?? $resetReq['email'] ?? '');
            if ($email === '') {
                $error = "Lien invalide ou expire.";
            } else {
                $newPass = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE Users SET Password = ? WHERE Email = ?")->execute([$newPass, $email]);
                $pdo->prepare("DELETE FROM PasswordResets WHERE Token = ?")->execute([$token]);
                header("Location: login.php?success=1");
                exit();
            }
        }
    } else {
        $isTokenValid = true;
    }
}

$title = "L'Arsenal - Reinitialisation";
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
            <div style="font-size: 3rem; margin-bottom: 10px;"><i class="fa-solid fa-lock"></i></div>
            <h2>Reinitialiser le mot de passe</h2>
            <p style="font-size: 0.8rem; color: #5C5F66;">Choisissez un nouveau mot de passe securise</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($isTokenValid): ?>
            <form method="POST" action="reset_password.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" name="password" type="password" required minlength="6" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="6" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary">Mettre a jour le mot de passe</button>
            </form>
        <?php endif; ?>

        <div class="switch-mode">
            <a href="login.php">Retour a la connexion</a>
        </div>
    </div>
</main>
<?php include __DIR__ . '/templates/end.php'; ?>
