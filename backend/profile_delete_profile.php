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
$checkboxConfirmed = isset($_POST['confirm_delete_profile']) && $_POST['confirm_delete_profile'] === '1';

if ($confirmationText !== 'SUPPRIMER MON PROFIL' || !$checkboxConfirmed) {
    profile_set_flash('error', "Confirmation explicite invalide. Suppression du profil annulee.");
    header('Location: ../profile.php');
    exit();
}

$userId = (int)$_SESSION['user']['id'];
$pdo = get_pdo();

try {
    $stmt = $pdo->prepare(
        "UPDATE Users
         SET FullName = NULL,
             Email = NULL,
             AvatarUrl = NULL,
             ProfileIsDeleted = 1,
             ProfileDeletedAt = NOW()
         WHERE UserId = :userId"
    );
    $stmt->execute([':userId' => $userId]);

    profile_set_flash('success', "Votre profil a ete anonymise. Le compte reste actif.");
} catch (PDOException $e) {
    profile_set_flash('error', "Erreur lors de la suppression du profil. Verifiez la migration SQL et reessayez.");
}

header('Location: ../profile.php');
exit();
