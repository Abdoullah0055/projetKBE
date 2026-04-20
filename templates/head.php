<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? "L'Arsenal - MarchÃ© Noir" ?></title>
    <!-- <link rel="stylesheet" href="assets/css/details.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/panier.css"> -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
        <?php foreach ($extraStylesheets as $stylesheet): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>


