<?php
$title = 'Enigme - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css'];
$bodyClass = 'enigmes-page';

require_once __DIR__ . '/includes/enigmes_request.php';

$context = resolve_enigme_request('enigme.php');
$nextUrl = build_enigmes_page_url('reponse.php', $context['query']);
$flashDialogues = consume_enigmes_flash_dialogues();

if ($flashDialogues !== []) {
    $mainDialogues = $flashDialogues;
} else {
    $mainDialogues = [
        [
            'text' => 'Te voila dans le portail du savoir. Le but du jeu est de repondre correctement a une question qui te sera posee.',
            'frame' => 'assets/img/Magicien/mage1.png',
        ],
        [
            'text' => 'Les reponses peuvent se trouver directement sur le site, dans les descriptions d’objets, ou concerner des evenements historiques de notre monde.',
            'frame' => 'assets/img/Magicien/mage2.png',
        ],
        [
            'text' => 'Dans la page de reponse, le bouton abandonner sera a ta portee, mais prends garde : le choisir te fera perdre un essai. Dans le message suivant se trouvera l’enigme a resoudre.',
            'frame' => 'assets/img/Magicien/mage3.png',
        ],
        [
            'text' => (string) $context['riddle']['question_text'],
            'frame' => 'assets/img/Magicien/mage_question.png',
        ],
        [
            'text' => 'Il est maintenant a toi de jouer, jeune vagabond. Je te souhaite la meilleure des chances.',
            'frame' => 'assets/img/Magicien/mage8.png',
        ],
    ];
}

$mainDialoguesJson = htmlspecialchars(
    json_encode($mainDialogues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="enigmes-main">
    <section
        class="enigmes-stage is-intro-active"
        aria-label="Zone des enigmes du magicien"
        data-main-dialogues="<?= $mainDialoguesJson ?>"
        data-next-url="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>">
        <a
            class="enigmes-back"
            href="<?= htmlspecialchars($context['back_href'], ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Retour">
            <img
                class="enigmes-back-image"
                src="assets/img/Magicien/retour.png"
                alt="">
        </a>

        <div class="enigmes-fog" id="enigmesFog" aria-hidden="true"></div>

        <div class="enigmes-intro" id="enigmesIntro">
            <div class="enigmes-intro-dock">
                <button class="enigmes-mage" id="enigmesMage" type="button" aria-label="Continuer le dialogue du mage">
                    <img
                        id="enigmesMageImage"
                        class="enigmes-mage-image"
                        src="assets/img/Magicien/mage1.png"
                        alt="Mage en train de parler">
                </button>

                <div class="enigmes-dialog" role="status" aria-live="polite" aria-atomic="true">
                    <p class="enigmes-dialog-text" id="enigmesDialogText"></p>
                    <p class="enigmes-dialog-hint" id="enigmesDialogHint">Cliquez sur le mage pour continuer.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="assets/js/enigme_intro.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>

