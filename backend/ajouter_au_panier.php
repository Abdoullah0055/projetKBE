<?php

require_once __DIR__ . '/../AlgosBD.php';
session_start();

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];
$isAjaxRequest = is_ajax_request();

if ($isAjaxRequest && isset($_SESSION['alerte'])) {
    unset($_SESSION['alerte']);
}

if (!isset($_SESSION['user'])) {
    $response['message'] = "Veuillez vous connecter pour remplir votre besace.";
    handle_response($response, "../login.php", $isAjaxRequest);
}

$userId   = $_SESSION['user']['id'] ?? null;
$itemId   = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if (!$userId) {
    $response['message'] = "Session utilisateur invalide.";
    handle_response($response, "../login.php", $isAjaxRequest);
}

if ($itemId <= 0 || $quantity <= 0) {
    $response['message'] = "Quantite ou item invalide.";
    handle_response($response, "../index.php", $isAjaxRequest);
}

$success = add_to_cart($userId, $itemId, $quantity);

if ($success) {
    $response['success'] = true;
    $response['message'] = "L'objet a ete ajoute a votre panier.";

    if (!$isAjaxRequest) {
        $_SESSION['alerte'] = [
            'type' => 'succes',
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

function is_ajax_request(): bool
{
    $isXmlHttpRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $acceptHeader = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = strpos($acceptHeader, 'application/json') !== false;

    return $isXmlHttpRequest || $wantsJson;
}

function handle_response(array $data, string $redirectUrl, bool $isAjaxRequest): void
{
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        header("Location: $redirectUrl");
    }
    exit();
}
