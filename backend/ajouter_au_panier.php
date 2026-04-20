<?php

require_once __DIR__ . '/../AlgosBD.php';
session_start();

// On prÃ©pare une rÃ©ponse par dÃ©faut
$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];

// 1. VÃ©rification de la connexion
if (!isset($_SESSION['user'])) {
    $response['message'] = "Connecte-toi pour remplir ta besace.";
    handle_response($response, "../login.php");
}

// 2. RÃ©cupÃ©ration et assainissement des donnÃ©es POST
$userId   = $_SESSION['user']['id'] ?? null;
$itemId   = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if (!$userId) {
    $response['message'] = "Session utilisateur invalide.";
    handle_response($response, "../login.php");
}

if ($itemId <= 0 || $quantity <= 0) {
    $response['message'] = "QuantitÃ© ou item invalide.";
    handle_response($response, "../index.php");
}

// 3. Appel de la fonction de la base de donnÃ©es (dÃ©finie dans AlgosBD.php)
$success = add_to_cart($userId, $itemId, $quantity);

if ($success) {
    $response['success'] = true;
    $response['message'] = "L'objet a rejoint ton inventaire !";
    $_SESSION['alerte'] = ['type' => 'succes', 'message' => $response['message']];
} else {
    $response['message'] = "Impossible d'ajouter l'objet (vÃ©rifiez les stocks).";
    $_SESSION['alerte'] = ['type' => 'erreur', 'message' => $response['message']];
}

// 4. Envoi de la rÃ©ponse (AJAX ou Redirection)
handle_response($response, "../details.php?id=$itemId");

/**
 * Fonction utilitaire pour gÃ©rer la double compatibilitÃ© (JS et standard)
 */
function handle_response($data, $redirectUrl)
{
    // On dÃ©tecte si c'est une requÃªte Fetch/AJAX
    // Note : On vÃ©rifie aussi si l'en-tÃªte Content-Type est JSON ou si c'est un appel fetch standard
    if (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
        (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
    ) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        // Sinon, on redirige normalement
        header("Location: $redirectUrl");
    }
    exit();
}


