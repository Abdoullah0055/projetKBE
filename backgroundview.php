<?php
require_once 'AlgosBD.php';
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. RÃ‰CUPÃ‰RATION DU THÃˆME
$currentTheme = $_COOKIE['theme'] ?? 'light';
$bgNum = $_COOKIE['bgNumber'] ?? '1';
$bgImage = "assets/img/{$currentTheme}theme/{$currentTheme}{$bgNum}.png";

// Gestion de l'utilisateur pour le header
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    $user = ['isConnected' => false];
}

$title = "Vue d'Ambiance - L'Arsenal";
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<style>
    :root {
        --main-bg: url('<?= $bgImage ?>');
    }

    body {
        background-image: var(--main-bg) !important;
        background-color: #1a1b1e !important;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        overflow: hidden;
        /* EmpÃªche le scroll pour garder le focus sur le fond */
    }

    .background-content {
        flex: 1;
        /* Occupe tout l'espace central entre header et footer */
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Message discret si la page semble trop vide */
    .view-hint {
        color: rgba(255, 255, 255, 0.15);
        font-family: 'Segoe UI', sans-serif;
        letter-spacing: 4px;
        text-transform: uppercase;
        font-size: 0.9rem;
        user-select: none;
    }
</style>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="background-content">
    <div class="view-hint">Mode Contemplation</div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/templates/end.php';
?>


