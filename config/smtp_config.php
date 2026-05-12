<?php

require_once __DIR__ . '/../includes/env_loader.php';

return [
    'host' => env('SMTP_HOST', 'smtp.gmail.com'),
    'auth' => env('SMTP_AUTH', true),
    'username' => env('SMTP_USERNAME', ''),
    'password' => env('SMTP_PASSWORD', ''),
    'secure' => env('SMTP_SECURE', 'tls'),
    'port' => (int) env('SMTP_PORT', 587),
    'from_email' => env('SMTP_FROM_EMAIL', 'noreply@darquest.com'),
    'from_name' => env('SMTP_FROM_NAME', "L'Arsenal de Sombre-Donjon"),
];