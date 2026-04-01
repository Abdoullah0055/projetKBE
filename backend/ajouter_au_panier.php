<?php

require_once __DIR__ . '/../AlgosBD.php';
session_start();

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];

if (!isset($_SESSION['user'])) {
    $response['message'] = "Veuillez vous connecter pour remplir votre besace.";
    handle_response($response, "../login.php");
}

$userId   = $_SESSION['user']['id'] ?? null;
$itemId   = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if (!$userId) {
    $response['message'] = "Session utilisateur invalide.";
    handle_response($response, "../login.php");
}

if ($itemId <= 0 || $quantity <= 0) {
    $response['message'] = "Quantite ou item invalide.";
    handle_response($response, "../index.php");
}

$success = add_to_cart($userId, $itemId, $quantity);

if ($success) {
    $response['success'] = true;
    $response['message'] = "L'objet a ete ajoute a votre panier.";
    $_SESSION['alerte'] = ['type' => 'succes', 'message' => $response['message']];
} else {
    $response['message'] = "Impossible d'ajouter l'objet au panier (stock insuffisant ou item indisponible).";
    $_SESSION['alerte'] = ['type' => 'erreur', 'message' => $response['message']];
}

handle_response($response, "../details.php?id=$itemId");

function handle_response($data, $redirectUrl)
{
    if (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
        (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
    ) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        header("Location: $redirectUrl");
    }
    exit();
}
