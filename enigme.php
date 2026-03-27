<?php
$title = "L'Arsenal - Marché Noir";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] ??= [
    'alias' => 'Utilisateur',
    'role' => '',
    'gold' => 0,
    'silver' => 0,
    'bronze' => 0
];
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
    html, body {
        margin: 0;
        padding: 0;
    }

    .page-middle-bg {
        width: 100%;
        height: calc(100vh - 160px);
        background: url('assets/img/magicien/respond.png') center center / cover no-repeat;
    }
</style>

<div class="page-middle-bg"></div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>