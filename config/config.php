<?php

session_start();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/page.php';

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