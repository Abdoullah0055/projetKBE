<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/includes/token_utils.php';
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    // Vérifier si l'email existe
    $stmt = $pdo->prepare("SELECT Alias FROM Users WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $token = generate_reset_token();
        save_reset_token($pdo, $email, $token);
        // Simulation d'envoi (en mode dev)
        echo "Lien de réinitialisation : reset_password.php?token=" . $token;
    }
    $message = "Si cet email existe, un lien a été envoyé.";
}
?>