<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/AlgosBD.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit();
}

$pdo = get_pdo();
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gold = max(0, (int)($_POST['gold'] ?? 0));
    $silver = max(0, (int)($_POST['silver'] ?? 0));
    $bronze = max(0, (int)($_POST['bronze'] ?? 0));
    $reason = trim($_POST['reason'] ?? '');

    if ($gold === 0 && $silver === 0 && $bronze === 0) {
        $error = "Vous devez demander au moins une monnaie.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO FundRequests 
            (UserId, GoldRequested, SilverRequested, BronzeRequested, Reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user']['id'],
            $gold,
            $silver,
            $bronze,
            $reason
        ]);

        $success = "Votre demande de monnaie a ete envoyee.";
    }
}
?><?php include __DIR__ . '/templates/head.php'; ?>

<?php
$user = [
    'id' => $_SESSION['user']['id'] ?? 0,
    'alias' => $_SESSION['user']['alias'] ?? 'Invité',
    'role' => $_SESSION['user']['role'] ?? 'Guest',
    'balance' => [
        'gold' => $_SESSION['user']['gold'] ?? 0,
        'silver' => $_SESSION['user']['silver'] ?? 0,
        'bronze' => $_SESSION['user']['bronze'] ?? 0,
    ]
];
?>


<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="profile-page">
    <section class="profile-card">
        <h2>Demande d'augmentation de capital</h2>

        <?php if ($error): ?>
            <div class="profile-alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="profile-alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Or demande</label>
            <input type="number" name="gold" min="0" value="0">

            <label>Argent demande</label>
            <input type="number" name="silver" min="0" value="0">

            <label>Bronze demande</label>
            <input type="number" name="bronze" min="0" value="0">

            <label>Raison</label>
            <textarea name="reason" placeholder="Expliquez pourquoi vous voulez cette monnaie..."></textarea>

            <button type="submit" class="btn-primary">Envoyer la demande</button>
        </form>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>