<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/page.php';

// 1. VRAIE GESTION DE LA SESSION (US-02)
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage'),
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    // Visiteur non connectÃ©
    $user = [
        'isConnected' => false,
        'alias' => '',
        'isMage' => false,
        'balance' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]
    ];
}


function getItemImage($type)
{
    switch (strtolower($type)) {
        case 'arme':
            return 'âš”ï¸';
        case 'armure':
            return 'ðŸ›¡ï¸';
        case 'potion':
            return 'ðŸ§ª';
        case 'sort':
            return 'âœ¨';
        default:
            return 'â“';
    }
}
?>

