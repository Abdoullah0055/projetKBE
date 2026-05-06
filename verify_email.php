<?php
require_once __DIR__ . '/AlgosBD.php';

$pdo = get_pdo();

$token = $_GET['token'] ?? '';
$message = "";

if ($token === '') {
    $message = "Lien invalide.";
} else {
    $stmt = $pdo->prepare("
        SELECT Email 
        FROM EmailVerifications 
        WHERE Token = ? 
          AND ExpiresAt > NOW()
          AND VerifiedAt IS NULL
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $message = "Lien expire ou invalide.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE EmailVerifications 
            SET VerifiedAt = NOW()
            WHERE Token = ?
        ");
        $stmt->execute([$token]);

        $message = "Courriel verifie avec succes. Vous pouvez maintenant vous connecter.";
    }
}
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="auth-page">
    <div class="auth-container fade-in">
        <h2>Validation du courriel</h2>
        <p><?= htmlspecialchars($message) ?></p>
        <a href="login.php" class="btn-primary">Retour a la connexion</a>
    </div>
</main>

<?php include __DIR__ . '/templates/end.php'; ?>