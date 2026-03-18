<?php
require_once 'AlgosBD.php'; 
session_start();

$pdo = get_pdo(); 

if (!$pdo) {
    die("Erreur critique : Impossible de se connecter à la base de données.");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = trim($_POST['alias']);
    $password = $_POST['password'];
    $mode = $_POST['mode']; // 'login' ou 'register'

    if ($mode === 'register') {
        // --- US-01 : INSCRIPTION ---
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hachage sécurisé

        try {
            $stmt = $pdo->prepare("CALL sp_RegisterUser(?, ?)");
            $stmt->execute([$alias, $hashedPassword]);
            $success = "Compte forgé avec succès ! Vous pouvez maintenant vous connecter.";
        } catch (PDOException $e) {
            // Si l'alias existe déjà (géré par la BD)
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        // --- US-02 : CONNEXION ---
        try {
            $stmt = $pdo->prepare("CALL sp_GetUserByAlias(?)");
            $stmt->execute([$alias]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                // Stockage en session
                $_SESSION['user'] = [
                    'id' => $user['UserId'],
                    'alias' => $user['Alias'],
                    'role' => $user['Role'],
                    'gold' => $user['Gold'],
                    'silver' => $user['Silver'],
                    'bronze' => $user['Bronze']
                ];
                header("Location: index.php");
                exit();
            } else {
                $error = "Alias ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur système : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Arsenal - Sanctuaire d'Accès</title>
    <style>
        :root {
            --bg-dark: #1a1b1e;
            --card-bg: #25262b;
            --accent: #1985A1;
            --error: #e74c3c;
            --success: #2ecc71;
            --text-light: #DCDCDD;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at center, #2c3e50 0%, #1a1b1e 100%);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-container {
            background: var(--card-bg);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(25, 133, 161, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            margin: 0;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #A6A7AB;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #373A40;
            background: #1A1B1E;
            color: white;
            box-sizing: border-box;
            outline: none;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: var(--accent);
        }

        button.btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s, background 0.3s;
        }

        button.btn-primary:hover {
            background: #146c82;
            transform: translateY(-2px);
        }

        .switch-mode {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .switch-mode a {
            color: var(--accent);
            text-decoration: none;
            font-weight: bold;
        }

        .alert-msg {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        /* Animation de transition */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="auth-container fade-in" id="auth-card">
        <div class="auth-header">
            <div id="logo-placeholder" style="font-size: 3rem; margin-bottom: 10px;">🗝️</div>
            <h2 id="form-title">Connexion</h2>
            <p id="form-subtitle" style="font-size: 0.8rem; color: #5C5F66;">Entrez dans l'Arsenal de Sombre-Donjon</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-msg alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert-msg alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form id="auth-form" method="POST" action="login.php" onsubmit="return validateForm()">
            <input type="hidden" name="mode" id="auth-mode" value="login">

            <div class="form-group">
                <label for="alias">Alias de l'Aventurier</label>
                <input type="text" id="alias" name="alias" placeholder="Ex: Slayer99" required minlength="3">
            </div>

            

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
            </div>

            <div class="form-group" id="confirm-group" style="display: none;">
                <label for="confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="confirm-password" placeholder="••••••••">
                <div id="confirm-error" style="color:var(--error); font-size:0.8rem; display:none; margin-top:5px;">Les mots de passe ne correspondent pas.</div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">Se connecter</button>
        </form>

        <div class="switch-mode">
            <span id="switch-text">Nouveau ici ?</span>
            <a href="#" onclick="toggleMode(event)" id="switch-link">Créer un compte</a>
        </div>
    </div>

    <script>
        let isLoginMode = true;

        function toggleMode(e) {
            e.preventDefault();
            isLoginMode = !isLoginMode;

            const title = document.getElementById('form-title');
            const submitBtn = document.getElementById('submit-btn');
            const confirmGroup = document.getElementById('confirm-group');
            const switchText = document.getElementById('switch-text');
            const switchLink = document.getElementById('switch-link');
            const aliasLabel = document.querySelector('label[for="alias"]');
            const authMode = document.getElementById('auth-mode');

            if (isLoginMode) {
                // Mode Connexion
                title.innerText = "Connexion";
                submitBtn.innerText = "Se connecter";
                confirmGroup.style.display = "none";
                switchText.innerText = "Nouveau ici ?";
                switchLink.innerText = "Créer un compte";
                aliasLabel.innerText = "Alias de l'Aventurier";
                authMode.value = "login";
            } else {
                // Mode Inscription
                title.innerText = "Inscription";
                submitBtn.innerText = "Forger mon compte";
                confirmGroup.style.display = "block";
                switchText.innerText = "Déjà membre ?";
                switchLink.innerText = "Se connecter";
                aliasLabel.innerText = "Choisir un Alias Unique";
                authMode.value = "register";
            }
        }

        // Remplace l'ancienne simulation par une vraie validation avant envoi au serveur
        function validateForm() {
            if (!isLoginMode) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                if (password !== confirmPassword) {
                    document.getElementById('confirm-error').style.display = 'block';
                    return false; // Bloque l'envoi du formulaire
                }
            }
            return true; // Laisse le formulaire s'envoyer vers le PHP
        }
    </script>

</body>

</html>