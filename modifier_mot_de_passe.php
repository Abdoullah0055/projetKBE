<?php
ob_start();

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/AlgosBD.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$pdo = get_pdo();
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caracteres.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT Password FROM Users WHERE UserId = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['Password'])) {
            $error = "Mot de passe actuel incorrect.";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE UserId = ?");
            $stmt->execute([$hash, $_SESSION['user']['id']]);

            $success = "Mot de passe modifie avec succes.";
        }
    }
}
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="auth-page">
    <div class="auth-container fade-in">
        <h2>Modifier le mot de passe</h2>

        <?php if ($error): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn-primary">Modifier</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/templates/end.php'; ?>