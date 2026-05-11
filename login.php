<?php
ob_start(); // Prevents "Headers already sent" errors

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/includes/mailer_utils.php';
require_once __DIR__ . '/includes/email_verification_utils.php';

$pdo = get_pdo();

if (!$pdo) {
    die("Erreur critique : Impossible de se connecter a la base de donnees.");
}

$error = "";
$success = "";
$initialMode = 'login';

if (isset($_GET['mode']) && $_GET['mode'] === 'register') {
    $initialMode = 'register';
}

if (isset($_GET['account_deleted']) && $_GET['account_deleted'] === '1') {
    $success = "Votre compte a bien ete supprime.";
}
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = "Votre mot de passe a ete reinitialise. Vous pouvez maintenant vous connecter.";
}
if (isset($_GET['verify'])) {
    if ($_GET['verify'] === 'ok') {
        $success = "Votre courriel a ete verifie. Vous pouvez maintenant vous connecter.";
    } elseif ($_GET['verify'] === 'expired') {
        $error = "Le lien de verification est invalide ou expire.";
    } elseif ($_GET['verify'] === 'invalid') {
        $error = "Lien de verification invalide.";
    }
}

function flushProcedureResults(PDOStatement $stmt): void
{
    do {
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    } while ($stmt->nextRowset());

    $stmt->closeCursor();
}

function first_available(array $row, array $keys, $default = null)
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }
    }
    return $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias'] ?? '');
    $password = $_POST['password'] ?? '';
    $mode = $_POST['mode'] ?? 'login';
    $initialMode = $mode === 'register' ? 'register' : 'login';

   if ($mode === 'register') {
    require_once __DIR__ . '/includes/email_utils.php';
    $email = normalize_email($_POST['email'] ?? '');
    
    if (!validate_email($email)) {
        $error = "L'adresse courriel est invalide ou le domaine n'existe pas.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("CALL sp_RegisterUser(?, ?, ?)");
            $stmt->execute([$alias, $hashedPassword, $email]);
            flushProcedureResults($stmt);

            $token = generate_email_verification_token();
            upsert_email_verification($pdo, $email, $token);
            $verifyLink = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http")
                . "://" . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF'])
                . "/verify_email.php?token=" . urlencode($token);

            $subject = "Verification de votre compte Darquest";
            $body = "<p>Bienvenue dans l'Arsenal.</p>"
                . "<p>Cliquez pour verifier votre courriel :</p>"
                . "<p><a href=\"{$verifyLink}\">Verifier mon courriel</a></p>"
                . "<p>Ce lien expire dans 24 heures.</p>";

            if (send_darquest_mail($email, $subject, $body)) {
                $success = "Compte forge. Verifiez votre courriel avant de vous connecter.";
            } else {
                $error = "Compte cree, mais l'envoi du courriel de verification a echoue.";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_GetUserByAlias(?)");
            $stmt->execute([$alias]);
            $foundUser = $stmt->fetch();
            flushProcedureResults($stmt);

            $storedHash = (string) first_available($foundUser, ['Password', 'password'], '');

if ($foundUser && $storedHash !== '' && password_verify($password, $storedHash)) {
            $userId = (int) first_available($foundUser, ['UserId', 'userid'], 0);
            $userEmail = (string) first_available($foundUser, ['Email', 'email'], '');
            $isBanned = (int) first_available($foundUser, ['IsBanned', 'isbanned'], 0);

            if ($userEmail === '' && $userId > 0) {
                $emailStmt = $pdo->prepare("SELECT Email FROM Users WHERE UserId = ? LIMIT 1");
                $emailStmt->execute([$userId]);
                $emailRow = $emailStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $userEmail = (string) first_available($emailRow, ['Email', 'email'], '');
            }

            if ($isBanned === 1) {
                $error = "Ce compte est bloque par un administrateur.";
            } elseif (!empty($userEmail) && !is_email_verified($pdo, $userEmail)) {
                $error = "Veuillez verifier votre courriel avant de vous connecter.";
            } else {
            $_SESSION['user'] = [
                'id' => $userId,
                'alias' => (string) first_available($foundUser, ['Alias', 'alias'], ''),
                'role' => (string) first_available($foundUser, ['Role', 'role'], 'Player'),
                'gold' => (int) first_available($foundUser, ['Gold', 'gold'], 0),
                'silver' => (int) first_available($foundUser, ['Silver', 'silver'], 0),
                'bronze' => (int) first_available($foundUser, ['Bronze', 'bronze'], 0)
            ];

$_SESSION['user']['hp'] = (int) first_available($foundUser, ['CurrentHP', 'currenthp'], 100);
                $_SESSION['user']['max_hp'] = (int) first_available($foundUser, ['MaxHP', 'maxhp'], 100);

            if (!array_key_exists('CurrentHP', $foundUser) && !array_key_exists('currenthp', $foundUser)) {
                $hpData = get_user_hp($_SESSION['user']['id']);
                $_SESSION['user']['hp'] = $hpData['current'];
                $_SESSION['user']['max_hp'] = $hpData['max'];
            }

            require_once __DIR__ . '/includes/enigmes_progression.php';
            ensure_enigmes_progression();

            session_regenerate_id(true);
            header("Location: index.php");
                    exit();
                }
            }

            if ($error === "") {
                $error = "Alias ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur systeme : " . $e->getMessage();
        }
    }
}

$title = "L'Arsenal - Sanctuaire d'Acces";
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }
</style>
<link rel="stylesheet" href="assets/css/login.css">

<main class="auth-page">
    <div class="auth-container fade-in" id="auth-card">
        <div class="auth-header">
            <div style="font-size: 3rem; margin-bottom: 10px;"><i class="fa-solid fa-key"></i></div>
            <h2 id="form-title">Connexion</h2>
            <p id="form-subtitle" style="font-size: 0.8rem; color: #5C5F66;">Entrez dans l'Arsenal de Sombre-Donjon</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form id="auth-form" method="POST" action="login.php">
            <input type="hidden" name="mode" id="auth-mode" value="<?= htmlspecialchars($initialMode, ENT_QUOTES) ?>">

            <div class="form-group">
                <label for="alias" id="label-alias">Alias de l'Aventurier</label>
                <input type="text" id="alias" name="alias" placeholder="Ex: Slayer99" required minlength="3">
            </div>

            <div class="form-group" id="email-group" style="display: none;">
    <label for="email">Adresse Courriel</label>
    <input type="email" id="email" name="email" placeholder="aventurier@exemple.com">
</div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
            </div>

            <div class="form-group" id="confirm-group" style="display: none;">
                <label for="confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="confirm-password" name="confirm_password" placeholder="••••••••">
                <div id="confirm-error" style="display:none; color: var(--error); font-size: 0.8rem; margin-top:5px;">
                    Les mots de passe ne correspondent pas.
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">Se connecter</button>
        </form>

        <div class="switch-mode">
            <span id="switch-text">Nouveau ici ?</span>
            <a href="#" id="switch-link">Creer un compte</a>
        </div>
        <div class="switch-mode" style="margin-top: 8px;">
            <a href="forgot_password.php">Mot de passe oublie ?</a>
        </div>
    </div>
</main>
<script src="assets/js/auth.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const initialMode = document.getElementById('auth-mode')?.value;
        if (initialMode === 'register') {
            const switchLink = document.getElementById('switch-link');
            if (switchLink) {
                switchLink.click();
            }
        }
    });
</script>
<?php include __DIR__ . '/templates/end.php'; ?>
