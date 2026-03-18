<?php

<<<<<<< HEAD
session_start();
=======
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
>>>>>>> 3216082bbb7b76b230f07975bc80cea55c00d40e

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/page.php';

<<<<<<< HEAD
$user = [
    'isConnected' => true,
    'alias' => "Slayer99",
    'isMage' => true,
    'balance' => [
        'gold' => 12,
        'silver' => 50,
        'bronze' => 80
    ]
];
=======
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
    // Visiteur non connecté
    $user = [
        'isConnected' => false,
        'alias' => '',
        'isMage' => false,
        'balance' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]
    ];
}
?>
>>>>>>> 3216082bbb7b76b230f07975bc80cea55c00d40e
