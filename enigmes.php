<?php
$title = "Les &Eacute;nigmes - March&eacute; Noir";
$extraStylesheets = ['assets/css/enigmes.css'];

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

$user = [
    'isConnected' => isset($_SESSION['user']),
    'balance' => [
        'gold' => $_SESSION['user']['gold'],
        'silver' => $_SESSION['user']['silver'],
        'bronze' => $_SESSION['user']['bronze']
    ]
];

$iconClass = 'fa-moon';
?>

<?php include __DIR__ . '/templates/head.php'; ?>
<script>
    document.body.classList.add('enigmes-page');
</script>

<main class="enigmes-main">
    <section class="enigmes-stage is-intro-active" aria-label="Zone des &eacute;nigmes du magicien">
        <a class="enigmes-back" href="index.php" aria-label="Retour">
            <img
                class="enigmes-back-image"
                src="assets/img/Magicien/retour.png"
                alt="">
        </a>
        <button class="enigmes-replay-btn" id="enigmesReplayBtn" type="button" aria-label="Revoir l'&eacute;nigme">
            <img
                class="enigmes-replay-icon"
                src="assets/img/Magicien/info.png"
                alt="Ic&ocirc;ne information">
        </button>
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
                    <p class="enigmes-dialog-hint">Cliquez sur le mage pour continuer.</p>
                </div>
            </div>
        </div>

        <div class="enigmes-orb" id="enigmesRiddleArea">
            <form class="enigmes-form" action="#" method="post">
                <input
                    id="enigmesResponseInput"
                    type="text"
                    name="reponse_enigme"
                    placeholder="Entrez votre r&eacute;ponse dans l&rsquo;orbe du magicien"
                    aria-label="R&eacute;ponse &agrave; l'&eacute;nigme"
                    disabled>
            </form>
        </div>
    </section>
</main>
<script src="/assets/js/enigmes.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>
