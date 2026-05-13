<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/AlgosBD.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$title = 'Demande de capital - Marche Noir';
$requestMessage = '';
$requestType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = get_pdo();
        $isLimitReached = false;

        try {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM Demandes WHERE UserId = ?");
            $countStmt->execute([$user['id']]);
            $requestCount = (int) $countStmt->fetchColumn();
            $isLimitReached = $requestCount >= 3;
        } catch (PDOException $ignoredCountError) {
            $isLimitReached = false;
        }

        if ($isLimitReached) {
            $requestMessage = "Limite atteinte: vous avez deja 3 demandes en cours/enregistrees.";
            $requestType = 'error';
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO Demandes (UserId) VALUES (?)");
            $insertStmt->execute([$user['id']]);

            $requestMessage = "Votre demande de capital a bien ete envoyee.";
            $requestType = 'success';
        }
    } catch (Throwable $error) {
        $requestMessage = "Impossible d'envoyer la demande pour le moment. Reessayez plus tard.";
        $requestType = 'error';
    }
}
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="capital-request-main">
    <section class="capital-request-card" aria-label="Demande de capital">
        <h2><i class="fa-solid fa-hand-holding-dollar"></i> Demande de capital</h2>
        <p class="capital-request-description">
            Soumettez une demande pour obtenir un soutien en capital. Un administrateur pourra ensuite traiter votre demande.
        </p>

        <?php if ($requestMessage !== ''): ?>
            <div class="capital-request-feedback <?= $requestType === 'success' ? 'is-success' : 'is-error' ?>">
                <?= htmlspecialchars($requestMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="demande_capital.php" method="post">
            <button type="submit" class="capital-request-submit">
                <i class="fa-solid fa-paper-plane"></i> Envoyer ma demande
            </button>
        </form>

        <div class="capital-request-links">
            <a href="index.php">Retour boutique</a>
            <a href="inventory.php">Voir inventaire</a>
        </div>
    </section>
</main>

<style>
.capital-request-main {
  min-height: calc(100vh - var(--header-height));
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.capital-request-card {
  width: min(560px, 95vw);
  border: 1px solid rgba(25, 133, 161, 0.38);
  border-radius: 12px;
  background: rgba(8, 12, 18, 0.76);
  backdrop-filter: blur(8px);
  padding: 24px;
}

.capital-request-card h2 {
  margin: 0 0 12px;
  color: var(--accent);
}

.capital-request-description {
  margin: 0 0 18px;
  color: var(--text-silver);
}

.capital-request-feedback {
  margin-bottom: 16px;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid transparent;
  font-weight: 600;
}

.capital-request-feedback.is-success {
  color: #b8ffd4;
  border-color: rgba(46, 204, 113, 0.45);
  background: rgba(23, 66, 44, 0.45);
}

.capital-request-feedback.is-error {
  color: #ffc9c9;
  border-color: rgba(231, 76, 60, 0.45);
  background: rgba(86, 26, 26, 0.45);
}

.capital-request-submit {
  width: 100%;
  border: 1px solid rgba(25, 133, 161, 0.55);
  background: rgba(25, 133, 161, 0.24);
  color: var(--text-light);
  border-radius: 8px;
  padding: 12px;
  font-weight: 700;
  cursor: pointer;
}

.capital-request-links {
  margin-top: 14px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 0.9rem;
}

.capital-request-links a {
  color: var(--accent);
  text-decoration: none;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
