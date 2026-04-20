<?php
require_once __DIR__ . '/AlgosBD.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = get_pdo();

if (!$pdo) {
    die("Erreur critique : Impossible de se connecter Ã  la base de donnÃ©es.");
}

// --- TON CODE PHP DE SESSION ET TRAITEMENT (GARDÃ‰ TEL QUEL) ---
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage'),
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    $user = [
        'isConnected' => false,
        'alias' => '',
        'isMage' => false,
        'balance' => [
            'gold' => 0,
            'silver' => 0,
            'bronze' => 0
        ]
    ];
}

$error = "";
$success = "";

if (isset($_GET['account_deleted']) && $_GET['account_deleted'] === '1') {
    $success = "Votre compte a bien ete supprime.";
}

function flushProcedureResults(PDOStatement $stmt): void
{
    do {
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    } while ($stmt->nextRowset());

    $stmt->closeCursor();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias'] ?? '');
    $password = $_POST['password'] ?? '';
    $mode = $_POST['mode'] ?? 'login';

    if ($mode === 'register') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("CALL sp_RegisterUser(?, ?)");
            $stmt->execute([$alias, $hashedPassword]);
            $success = "Compte forge avec succes ! Tu peux maintenant te connecter.";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        try {
            $stmt = $pdo->prepare("CALL sp_GetUserByAlias(?)");
            $stmt->execute([$alias]);
            $foundUser = $stmt->fetch(PDO::FETCH_ASSOC);
            flushProcedureResults($stmt);

            if ($foundUser && password_verify($password, $foundUser['Password'])) {
                $_SESSION['user'] = [
                    'id' => (int)$foundUser['UserId'],
                    'alias' => $foundUser['Alias'],
                    'role' => $foundUser['Role'],
                    'gold' => (int)$foundUser['Gold'],
                    'silver' => (int)$foundUser['Silver'],
                    'bronze' => (int)$foundUser['Bronze']
                ];
                header("Location: index.php");
                exit();
            }

            $error = "Alias ou mot de passe incorrect.";
        } catch (PDOException $e) {
            $error = "Erreur systÃ¨me : " . $e->getMessage();
        }
    }
}

$title = "L'Arsenal - Sanctuaire d'AccÃ¨s";
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<link rel="stylesheet" href="assets/css/login.css">

<main class="auth-page">
    <div class="auth-container fade-in" id="auth-card">
        <div class="auth-header">
            <div style="font-size: 3rem; margin-bottom: 10px;">ðŸ—ï¸</div>
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
            <input type="hidden" name="mode" id="auth-mode" value="login">

            <div class="form-group">
                <label for="alias" id="label-alias">Alias de l'Aventurier</label>
                <input type="text" id="alias" name="alias" placeholder="Ex: Slayer99" required minlength="3">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required minlength="6">
            </div>

            <div class="form-group" id="confirm-group" style="display: none;">
                <label for="confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="confirm-password" name="confirm_password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                <div id="confirm-error" style="display:none; color: var(--error); font-size: 0.8rem; margin-top:5px;">
                    Les mots de passe ne correspondent pas.
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">Se connecter</button>
        </form>

        <div class="switch-mode">
            <span id="switch-text">Nouveau ici ?</span>
            <a href="#" id="switch-link">CrÃ©er un compte</a>
        </div>
    </div>
</main>
<script src="assets/js/auth.js"></script>
<?php include __DIR__ . '/templates/end.php'; ?>

