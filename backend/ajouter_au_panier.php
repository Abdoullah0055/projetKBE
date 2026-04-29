<?php

require_once __DIR__ . '/../AlgosBD.php';
<<<<<<< HEAD
session_start();
=======
require_once __DIR__ . '/../includes/session.php';
>>>>>>> ffeb3514bac80d7341dced7515461cff6a741bfd

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];
$isAjaxRequest = is_ajax_request();

error_log("[ajouter_au_panier] isAjaxRequest=" . ($isAjaxRequest ? 'true' : 'false') . " X-Requested-With=" . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'missing') . " Accept=" . ($_SERVER['HTTP_ACCEPT'] ?? 'missing'));

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

error_log("[ajouter_au_panier] POST: item_id=$itemId, quantity=$quantity, userId=" . ($userId ?? 'null') . ", SESSION_user=" . json_encode($_SESSION['user'] ?? null));

if (!$userId) {
    error_log("[ajouter_au_panier] userId manquant dans la session");
    $response['message'] = "Session utilisateur invalide.";
    handle_response($response, "../login.php", $isAjaxRequest);
}

if ($itemId <= 0 || $quantity <= 0) {
    $response['message'] = "Quantite ou item invalide.";
    handle_response($response, "../index.php", $isAjaxRequest);
}

$success = add_to_cart($userId, $itemId, $quantity);

error_log("[ajouter_au_panier] add_to_cart result: " . ($success ? 'true' : 'false'));

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

function handle_response(array $data, string $redirectUrl, bool $isAjaxRequest): void
{
    if ($isAjaxRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
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

    error_log("[is_ajax_request] isXmlHttpRequest=" . var_export($isXmlHttpRequest, true) . " wantsJson=" . var_export($wantsJson, true));

    return $isXmlHttpRequest || $wantsJson;
}
