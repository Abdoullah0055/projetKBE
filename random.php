<?php
$title = 'Choix Aleatoires - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css', 'assets/css/random.css'];
$randomCategories = [
    ['id' => 1, 'label' => 'Magie', 'image' => 'magie.png', 'modifier' => 'magie'],
    ['id' => 2, 'label' => 'Potions', 'image' => 'potion.png', 'modifier' => 'potion'],
    ['id' => 3, 'label' => 'Armes', 'image' => 'arme.png', 'modifier' => 'arme'],
    ['id' => 4, 'label' => 'Armures', 'image' => 'armure.png', 'modifier' => 'armure'],
    ['id' => 5, 'label' => 'Autres', 'image' => 'autres.png', 'modifier' => 'autres'],
];
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<script>
    document.body.classList.add('random-page');
</script>

<main class="random-main">
    <section class="random-stage is-intro-active random-stage--pages-focus" aria-label="Selection magique aleatoire">
        <div class="random-stage-bg random-stage-bg--intro" aria-hidden="true"></div>
        <div class="random-stage-bg random-stage-bg--ready" aria-hidden="true"></div>
        <div class="random-stage-tint" aria-hidden="true"></div>

        <div class="random-scene">
            <a
                class="enigmes-back"
                href="roadmap.php"
                aria-label="Retour">
                <img
                    class="enigmes-back-image"
                    src="assets/img/Magicien/retour.png"
                    alt="">
            </a>

            <div class="random-title" id="randomChoiceFrame">
                <img
                    class="random-title__frame"
                    src="assets/img/Magicien/choix.png"
                    alt="">
                <label class="random-visually-hidden" for="random-difficulty">Choisir une difficulte</label>
                <div class="random-difficulty-select-wrap">
                    <select class="random-difficulty-select" id="random-difficulty" name="difficulty">
                        <option value="Facile" selected>Facile</option>
                        <option value="Moyenne">Moyenne</option>
                        <option value="Difficile">Difficile</option>
                    </select>
                </div>
            </div>

            <div class="random-category-gallery" role="group" aria-label="Domaines mystiques">
                <?php foreach ($randomCategories as $category): ?>
                    <button
                        class="random-category-card random-category-card--<?= htmlspecialchars($category['modifier'], ENT_QUOTES, 'UTF-8') ?>"
                        type="button"
                        data-category-id="<?= (int) $category['id'] ?>"
                        aria-label="<?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?>">
                        <img
                            class="random-category-card__image"
                            src="assets/img/Magicien/<?= htmlspecialchars($category['image'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="random-fog enigmes-fog" id="randomFog" aria-hidden="true"></div>

        <div class="random-mage-overlay" id="randomMageOverlay" aria-hidden="false">
            <div class="random-mage-backdrop" aria-hidden="true"></div>

            <div class="random-mage-panel" role="dialog" aria-modal="true" aria-label="Presentation du mage aleatoire">
                <div class="random-mage-dock">
                    <button class="random-mage-figure" id="randomMageButton" type="button" aria-label="Continuer le message du mage">
                        <img
                            id="randomMageImage"
                            class="random-mage-image"
                            src="assets/img/Magicien/fade.png"
                            alt="Mage en train de parler">
                    </button>

                    <div class="random-mage-dialog">
                        <p class="random-mage-text" id="randomMageText"></p>
                        <p class="random-mage-hint" id="randomMageHint">Cliquez sur le mage pour continuer.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="/assets/js/random.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>


