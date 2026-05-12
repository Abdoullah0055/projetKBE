<?php
require_once __DIR__ . '/AlgosBD.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit();
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise.']);
    exit();
}

if (!csrf_validate()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de securite invalide.']);
    exit();
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide.']);
    exit();
}

try {
    $pdo = get_pdo();
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM Demandes WHERE UserId = ?");
    $countStmt->execute([$userId]);
    $totalRequests = (int)$countStmt->fetchColumn();

    if ($totalRequests >= 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Limite atteinte: 3 demandes maximum par joueur.'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO Demandes (UserId) VALUES (?)");
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Demande d augmentation de capital envoyee a l administration.'
    ]);
} catch (Throwable $e) {
    $sqlState = '';
    if ($e instanceof PDOException && isset($e->errorInfo[0])) {
        $sqlState = (string) $e->errorInfo[0];
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => ($sqlState === '42S02')
            ? 'La table Demandes est absente. Execute la migration SQL demandes_migration.sql.'
            : 'Impossible de soumettre la demande pour le moment.'
    ]);
}
