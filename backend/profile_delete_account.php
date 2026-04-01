<?php
require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/profile_utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit();
}

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../login.php');
    exit();
}

if (!profile_is_valid_csrf($_POST['csrf_token'] ?? null)) {
    profile_set_flash('error', "Session invalide. Rechargez la page puis recommencez.");
    header('Location: ../profile.php');
    exit();
}

$confirmationText = trim((string)($_POST['confirmation_text'] ?? ''));
$checkboxConfirmed = isset($_POST['confirm_delete_account']) && $_POST['confirm_delete_account'] === '1';
$password = (string)($_POST['password'] ?? '');

if ($confirmationText !== 'SUPPRIMER MON COMPTE' || !$checkboxConfirmed || $password === '') {
    profile_set_flash('error', "Confirmation explicite invalide. Suppression du compte annulee.");
    header('Location: ../profile.php');
    exit();
}

$userId = (int)$_SESSION['user']['id'];
$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $passwordStmt = $pdo->prepare("SELECT Password FROM Users WHERE UserId = :userId LIMIT 1 FOR UPDATE");
    $passwordStmt->execute([':userId' => $userId]);
    $passwordHash = (string)$passwordStmt->fetchColumn();

    if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
        $pdo->rollBack();
        profile_set_flash('error', "Mot de passe incorrect. Suppression du compte annulee.");
        header('Location: ../profile.php');
        exit();
    }

    $deleteCartStmt = $pdo->prepare("DELETE FROM Carts WHERE UserId = :userId");
    $deleteCartStmt->execute([':userId' => $userId]);

    $deleteOrderStmt = $pdo->prepare("DELETE FROM Orders WHERE UserId = :userId");
    $deleteOrderStmt->execute([':userId' => $userId]);

    $deleteUserStmt = $pdo->prepare("DELETE FROM Users WHERE UserId = :userId");
    $deleteUserStmt->execute([':userId' => $userId]);

    if ($deleteUserStmt->rowCount() <= 0) {
        $pdo->rollBack();
        profile_set_flash('error', "Impossible de supprimer ce compte.");
        header('Location: ../profile.php');
        exit();
    }

    $pdo->commit();

    session_unset();
    session_destroy();

    header('Location: ../login.php?account_deleted=1');
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    profile_set_flash('error', "Erreur lors de la suppression du compte.");
    header('Location: ../profile.php');
    exit();
}
