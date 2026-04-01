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

$userId = (int)$_SESSION['user']['id'];
$pdo = get_pdo();

$alias = trim((string)($_POST['alias'] ?? ''));
$fullNameRaw = trim((string)($_POST['full_name'] ?? ''));
$emailRaw = trim((string)($_POST['email'] ?? ''));
$avatarRaw = trim((string)($_POST['avatar_url'] ?? ''));

$fullName = ($fullNameRaw === '') ? null : $fullNameRaw;
$email = ($emailRaw === '') ? null : $emailRaw;
$avatarUrl = ($avatarRaw === '') ? null : $avatarRaw;

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$wantsPasswordUpdate = ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '');

if ($alias === '' || mb_strlen($alias, 'UTF-8') < 3 || mb_strlen($alias, 'UTF-8') > 30) {
    profile_set_flash('error', "Alias invalide: 3 a 30 caracteres obligatoires.");
    header('Location: ../profile.php');
    exit();
}

if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $alias)) {
    profile_set_flash('error', "Alias invalide: utilisez uniquement lettres, chiffres, tirets et underscores.");
    header('Location: ../profile.php');
    exit();
}

if ($fullName !== null && mb_strlen($fullName, 'UTF-8') > 80) {
    profile_set_flash('error', "Nom complet trop long (80 caracteres max).");
    header('Location: ../profile.php');
    exit();
}

if ($email !== null && mb_strlen($email, 'UTF-8') > 190) {
    profile_set_flash('error', "Email trop long (190 caracteres max).");
    header('Location: ../profile.php');
    exit();
}

if ($avatarUrl !== null && mb_strlen($avatarUrl, 'UTF-8') > 255) {
    profile_set_flash('error', "Avatar trop long (255 caracteres max).");
    header('Location: ../profile.php');
    exit();
}

if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    profile_set_flash('error', "Email invalide.");
    header('Location: ../profile.php');
    exit();
}

if ($avatarUrl !== null) {
    $isValidHttpUrl = filter_var($avatarUrl, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $avatarUrl);
    $isValidLocalPath = (bool)preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $avatarUrl);

    if (!$isValidHttpUrl && !$isValidLocalPath) {
        profile_set_flash('error', "Avatar invalide: utilisez une URL http/https ou un chemin local simple.");
        header('Location: ../profile.php');
        exit();
    }
}

if ($wantsPasswordUpdate) {
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        profile_set_flash('error', "Pour changer le mot de passe, remplissez les 3 champs mot de passe.");
        header('Location: ../profile.php');
        exit();
    }

    if (mb_strlen($newPassword, '8bit') < 6) {
        profile_set_flash('error', "Le nouveau mot de passe doit faire au moins 6 caracteres.");
        header('Location: ../profile.php');
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        profile_set_flash('error', "La confirmation du nouveau mot de passe ne correspond pas.");
        header('Location: ../profile.php');
        exit();
    }
}

try {
    $pdo->beginTransaction();

    $aliasStmt = $pdo->prepare(
        "SELECT UserId
         FROM Users
         WHERE Alias = :alias AND UserId <> :userId
         LIMIT 1"
    );
    $aliasStmt->execute([
        ':alias' => $alias,
        ':userId' => $userId,
    ]);

    if ($aliasStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        profile_set_flash('error', "Cet alias est deja utilise.");
        header('Location: ../profile.php');
        exit();
    }

    if ($email !== null) {
        $emailStmt = $pdo->prepare(
            "SELECT UserId
             FROM Users
             WHERE Email = :email AND UserId <> :userId
             LIMIT 1"
        );
        $emailStmt->execute([
            ':email' => $email,
            ':userId' => $userId,
        ]);

        if ($emailStmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            profile_set_flash('error', "Cet email est deja utilise.");
            header('Location: ../profile.php');
            exit();
        }
    }

    $passwordHash = null;
    if ($wantsPasswordUpdate) {
        $passStmt = $pdo->prepare("SELECT Password FROM Users WHERE UserId = :userId LIMIT 1");
        $passStmt->execute([':userId' => $userId]);
        $dbPasswordHash = (string)$passStmt->fetchColumn();

        if ($dbPasswordHash === '' || !password_verify($currentPassword, $dbPasswordHash)) {
            $pdo->rollBack();
            profile_set_flash('error', "Le mot de passe actuel est incorrect.");
            header('Location: ../profile.php');
            exit();
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if ($passwordHash !== null) {
        $updateStmt = $pdo->prepare(
            "UPDATE Users
             SET Alias = :alias,
                 FullName = :fullName,
                 Email = :email,
                 AvatarUrl = :avatarUrl,
                 Password = :password,
                 ProfileIsDeleted = 0,
                 ProfileDeletedAt = NULL
             WHERE UserId = :userId"
        );

        $updateStmt->execute([
            ':alias' => $alias,
            ':fullName' => $fullName,
            ':email' => $email,
            ':avatarUrl' => $avatarUrl,
            ':password' => $passwordHash,
            ':userId' => $userId,
        ]);
    } else {
        $updateStmt = $pdo->prepare(
            "UPDATE Users
             SET Alias = :alias,
                 FullName = :fullName,
                 Email = :email,
                 AvatarUrl = :avatarUrl,
                 ProfileIsDeleted = 0,
                 ProfileDeletedAt = NULL
             WHERE UserId = :userId"
        );

        $updateStmt->execute([
            ':alias' => $alias,
            ':fullName' => $fullName,
            ':email' => $email,
            ':avatarUrl' => $avatarUrl,
            ':userId' => $userId,
        ]);
    }

    $pdo->commit();

    $_SESSION['user']['alias'] = $alias;
    profile_set_flash('success', "Profil mis a jour avec succes.");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    profile_set_flash('error', "Erreur lors de la mise a jour du profil. Verifiez la migration SQL et reessayez.");
}

header('Location: ../profile.php');
exit();
