<?php
require_once __DIR__ . '/AlgosBD.php';

$pdo = get_pdo();
if (!$pdo) {
    die("Erreur base de donnees.");
}

$token = trim($_GET['token'] ?? '');
if ($token === '') {
    header('Location: login.php?verify=invalid');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT Email
     FROM EmailVerifications
     WHERE Token = ?
       AND ExpiresAt > NOW()
     LIMIT 1"
);
$stmt->execute([$token]);
$verification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$verification) {
    header('Location: login.php?verify=expired');
    exit();
}

$update = $pdo->prepare(
    "UPDATE EmailVerifications
     SET VerifiedAt = NOW()
     WHERE Email = ?"
);
$verifiedEmail = $verification['Email'] ?? $verification['email'] ?? null;
if (!$verifiedEmail) {
    header('Location: login.php?verify=invalid');
    exit();
}

$update->execute([$verifiedEmail]);

header('Location: login.php?verify=ok');
exit();
