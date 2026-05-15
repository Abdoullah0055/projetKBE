<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?? "L'Arsenal - Marché Noir" ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<!-- <link rel="stylesheet" href="assets/css/details.css">
<link rel="stylesheet" href="assets/css/login.css">
<link rel="stylesheet" href="assets/css/panier.css"> -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsive.css">
<link rel="stylesheet" href="assets/css/modal.css">
<?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
<?php foreach ($extraStylesheets as $stylesheet): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<?php endif; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/hero.css">
</head>

<?php
$bodyClassAttribute = '';
$bodyDataTheme = htmlspecialchars($_COOKIE['theme'] ?? 'light', ENT_QUOTES, 'UTF-8');

if (!empty($bodyClass)) {
    $bodyClassValue = is_array($bodyClass) ? implode(' ', $bodyClass) : (string) $bodyClass;
    $bodyClassAttribute = ' class="' . htmlspecialchars($bodyClassValue, ENT_QUOTES, 'UTF-8') . '"';
}
?>

<body<?= $bodyClassAttribute ?> data-theme="<?= $bodyDataTheme ?>">
<?php include __DIR__ . '/../includes/modal.php'; ?>
