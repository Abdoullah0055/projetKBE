<?php
require_once __DIR__ . '/AlgosBD.php';
$pdo = get_pdo();
$token = $_GET['token'] ?? '';

// Vérifier la validité du token
$stmt = $pdo->prepare("SELECT Email FROM PasswordResets WHERE Token = ? AND ExpiresAt > NOW()");
$stmt->execute([$token]);
$resetReq = $stmt->fetch();

if (!$resetReq) die("Lien invalide ou expiré.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE Users SET Password = ? WHERE Email = ?")->execute([$newPass, $resetReq['Email']]);
    $pdo->prepare("DELETE FROM PasswordResets WHERE Token = ?")->execute([$token]);
    header("Location: login.php?success=1");
}
?>