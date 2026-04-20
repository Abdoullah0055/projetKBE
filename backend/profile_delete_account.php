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
    $passwordStmt = $pdo->prepare("SELECT Password FROM Users WHERE UserId = :userId LIMIT 1");
    $passwordStmt->execute([':userId' => $userId]);
    $passwordHash = (string)$passwordStmt->fetchColumn();

    if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
        profile_set_flash('error', "Mot de passe incorrect. Suppression du compte annulee.");
        header('Location: ../profile.php');
        exit();
    }

    $deleteStmt = $pdo->prepare("CALL sp_DeleteUserAccount(:userId)");
    $deleteStmt->execute([':userId' => $userId]);

    while ($deleteStmt->nextRowset()) {
        // Flush potential rowsets returned by MySQL procedures.
    }
    $deleteStmt->closeCursor();

    session_unset();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ../index.php');
    exit();
} catch (PDOException $e) {
    $errorCode = (int)($e->errorInfo[1] ?? 0);

    if ($errorCode === 1305) {
        profile_set_flash('error', "Suppression indisponible: procedure SQL manquante (sp_DeleteUserAccount).");
    } else {
        profile_set_flash('error', "Erreur lors de la suppression du compte.");
    }

    header('Location: ../profile.php');
    exit();
}
