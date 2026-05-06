<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/AlgosBD.php';

if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'Admin') {
    header('Location: index.php');
    exit();
}

$pdo = get_pdo();
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    $stmt = $pdo->prepare("
        SELECT *
        FROM FundRequests
        WHERE FundRequestId = ?
          AND Status = 'Pending'
        LIMIT 1
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $error = "Demande introuvable ou deja traitee.";
    } elseif ($action === 'approve') {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE Users
            SET Gold = Gold + ?,
                Silver = Silver + ?,
                Bronze = Bronze + ?,
                FundsGivenCount = FundsGivenCount + 1
            WHERE UserId = ?
        ");
        $stmt->execute([
            $request['GoldRequested'],
            $request['SilverRequested'],
            $request['BronzeRequested'],
            $request['UserId']
        ]);

        $stmt = $pdo->prepare("
            UPDATE FundRequests
            SET Status = 'Approved',
                AdminNote = ?,
                ProcessedAt = NOW(),
                ProcessedBy = ?
            WHERE FundRequestId = ?
        ");
        $stmt->execute([
            $adminNote,
            $_SESSION['user']['id'],
            $requestId
        ]);

        $pdo->commit();
        $success = "Demande acceptee et monnaie ajoutee.";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("
            UPDATE FundRequests
            SET Status = 'Rejected',
                AdminNote = ?,
                ProcessedAt = NOW(),
                ProcessedBy = ?
            WHERE FundRequestId = ?
        ");
        $stmt->execute([
            $adminNote,
            $_SESSION['user']['id'],
            $requestId
        ]);

        $success = "Demande refusee.";
    }
}

$stmt = $pdo->query("
    SELECT fr.*, u.Alias
    FROM FundRequests fr
    INNER JOIN Users u ON u.UserId = fr.UserId
    ORDER BY 
        CASE WHEN fr.Status = 'Pending' THEN 0 ELSE 1 END,
        fr.CreatedAt DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/templates/head.php'; ?>

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
        <h2>Demandes de monnaie</h2>

        <?php if ($error): ?>
            <div class="profile-alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="profile-alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <p>Aucune demande de monnaie.</p>
        <?php endif; ?>

        <?php foreach ($requests as $request): ?>
            <div class="profile-card" style="margin-bottom: 15px;">
                <h3><?= htmlspecialchars($request['Alias']) ?></h3>

                <p>
                    Or: <?= (int)$request['GoldRequested'] ?> |
                    Argent: <?= (int)$request['SilverRequested'] ?> |
                    Bronze: <?= (int)$request['BronzeRequested'] ?>
                </p>

                <p>Raison: <?= htmlspecialchars($request['Reason'] ?? '') ?></p>
                <p>Statut: <strong><?= htmlspecialchars($request['Status']) ?></strong></p>

                <?php if ($request['Status'] === 'Pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?= (int)$request['FundRequestId'] ?>">

                        <label>Note admin</label>
                        <textarea name="admin_note"></textarea>

                        <button type="submit" name="action" value="approve" class="btn-primary">
                            Accepter
                        </button>

                        <button type="submit" name="action" value="reject" class="btn-danger">
                            Refuser
                        </button>
                    </form>
                <?php else: ?>
                    <p>Note admin: <?= htmlspecialchars($request['AdminNote'] ?? '') ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>