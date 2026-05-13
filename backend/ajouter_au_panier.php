<?php

if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/../includes/session.php';

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];
$isAjaxRequest = is_ajax_request();

if ($isAjaxRequest && isset($_SESSION['alerte'])) {
    unset($_SESSION['alerte']);
}

if (!isset($_SESSION['user'])) {
    $response['message'] = "Veuillez vous connecter pour remplir votre besace.";
    handle_response($response, "../login.php", $isAjaxRequest);
}

$userId = $_SESSION['user']['id'] ?? null;
$itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if (!$userId) {
    $response['message'] = "Session utilisateur invalide.";
    handle_response($response, "../login.php", $isAjaxRequest);
}

if ($itemId <= 0 || $quantity <= 0) {
    $response['message'] = "Quantite ou item invalide.";
    handle_response($response, "../index.php", $isAjaxRequest);
}

$pdo = get_pdo();

$typeStmt = $pdo->prepare("SELECT t.Name FROM Items i JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId WHERE i.ItemId = ?");
$typeStmt->execute([$itemId]);
$itemTypeRow = $typeStmt->fetch(PDO::FETCH_ASSOC);
$itemTypeName = $itemTypeRow ? mb_strtolower($itemTypeRow['Name'], 'UTF-8') : '';

$isMage = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Mage';

if ($itemTypeName === 'magicspell' && !$isMage) {
    $response['message'] = "Seuls les mages peuvent acquérir des sorts.";
    handle_response($response, "../details.php?id=$itemId", $isAjaxRequest);
}

$success = add_to_cart($userId, $itemId, $quantity);

if ($success) {
    $response['success'] = true;
    $response['message'] = "L'objet a ete ajoute a votre panier.";

    if (!$isAjaxRequest) {
        $_SESSION['alerte'] = [
            'type' => 'success',
            'message' => $response['message'],
            'source' => 'add_to_cart',
            'item_id' => $itemId,
            'ts' => time(),
        ];
    }
} else {
    $response['message'] = "Impossible d'ajouter l'objet au panier (stock insuffisant ou item indisponible).";

    if (!$isAjaxRequest) {
        $_SESSION['alerte'] = [
            'type' => 'erreur',
            'message' => $response['message'],
            'source' => 'add_to_cart',
            'item_id' => $itemId,
            'ts' => time(),
        ];
    }
}

handle_response($response, "../details.php?id=$itemId", $isAjaxRequest);

function handle_response(array $data, string $redirectUrl, bool $isAjaxRequest): void
{
    if ($isAjaxRequest) {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        header("Location: $redirectUrl");
    }
    exit();
}

function is_ajax_request(): bool
{
    $isXmlHttpRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $acceptHeader = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = strpos($acceptHeader, 'application/json') !== false;

    return $isXmlHttpRequest || $wantsJson;
}
