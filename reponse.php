<?php
$title = 'Reponse - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css'];
$bodyClass = 'enigmes-page reponse-page';

require_once __DIR__ . '/includes/enigmes_request.php';

$context = resolve_enigme_request('reponse.php');
$responseValue = '';

function build_reward_label(array $riddle): string
{
    $gold = (int) ($riddle['reward_gold'] ?? 0);
    $silver = (int) ($riddle['reward_silver'] ?? 0);
    $bronze = (int) ($riddle['reward_bronze'] ?? 0);

    if ($gold > 0) {
        return $gold . ' gold';
    }

    if ($silver > 0) {
        return $silver . ' argent';
    }

    if ($bronze > 0) {
        return $bronze . ' bronze';
    }

    return '0 recompense';
}

if (isset($_GET['abandon']) && (string) $_GET['abandon'] === '1') {
    set_roadmap_flash_dialogues([
        [
            'text' => 'Womp womp, jeune vagabond... tu n’as pas reussi cette epreuve, et un essai vient de disparaitre dans la brume.',
            'frame' => 'assets/img/Magicien/womp.png',
        ],
    ]);
    header('Location: roadmap.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $responseValue = trim((string) ($_POST['reponse_enigme'] ?? ''));

    if ($responseValue === '') {
        set_enigmes_flash_dialogues([
            [
                'text' => 'Entrez une reponse avant de valider cette enigme.',
                'frame' => 'assets/img/Magicien/furieux.png',
            ],
            [
                'text' => 'Allez, je te renvoie.',
                'frame' => 'assets/img/Magicien/mage8.png',
            ],
        ]);
        header('Location: ' . build_enigmes_page_url('enigme.php', $context['query']));
        exit;
    } elseif (normalize_enigme_answer($responseValue) !== normalize_enigme_answer($context['riddle']['answer_text'])) {
        set_enigmes_flash_dialogues([
            [
                'text' => 'Reponse incorrecte, reessaie.',
                'frame' => 'assets/img/Magicien/furieux.png',
            ],
            [
                'text' => 'Allez, je te renvoie.',
                'frame' => 'assets/img/Magicien/mage8.png',
            ],
        ]);
        header('Location: ' . build_enigmes_page_url('enigme.php', $context['query']));
        exit;
    } else {
        if ($context['source'] === 'roadmap' && $context['roadmap_node_id'] !== null) {
            mark_enigme_completed($context['roadmap_node_id']);
            $rewardLabel = build_reward_label($context['riddle']);
            set_roadmap_flash_dialogues([
                [
                    'text' => 'Houra, jeune vagabond ! Tu as su surmonter le defi. Ta recompense est ' . $rewardLabel . '.',
                    'frame' => 'assets/img/Magicien/houra.png',
                ],
            ]);
            header('Location: roadmap.php');
            exit;
        }

        header('Location: random.php');
        exit;
    }
}

$hintText = trim((string) ($context['riddle']['hint_text'] ?? ''));

if ($hintText === '') {
    $hintText = get_hint_fallback_text();
}

$mainDialoguesJson = htmlspecialchars(
    json_encode([], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
$hintDialogues = [
    'Ah... je savais bien que tu aurais besoin d’un indice. Cette enigme semblait deja trop grande pour toi.',
    $hintText,
    'J’espere que cela suffira a eclairer ton esprit, jeune vagabond.',
];
$hintDialoguesJson = htmlspecialchars(
    json_encode($hintDialogues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
$formAction = build_enigmes_page_url('reponse.php', $context['query']);
$abandonUrl = build_enigmes_page_url('reponse.php', array_merge($context['query'], ['abandon' => 1]));
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="enigmes-main">
    <section
        class="enigmes-stage is-riddle-active is-hint-available"
        aria-label="Zone de reponse du magicien"
        data-main-dialogues="<?= $mainDialoguesJson ?>"
        data-hint-dialogues="<?= $hintDialoguesJson ?>"
        data-start-mode="riddle"
        data-hint-unlock-step="0"
        data-response-url="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
        data-abandon-url="<?= htmlspecialchars($abandonUrl, ENT_QUOTES, 'UTF-8') ?>">
        <a
            class="enigmes-back"
            href="<?= htmlspecialchars($abandonUrl, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Abandonner l enigme">
            <img
                class="enigmes-back-image"
                src="assets/img/Magicien/abandon.png"
                alt="">
        </a>

        <button class="enigmes-replay-btn" id="enigmesHintBtn" type="button" aria-label="Obtenir un indice">
            <img
                class="enigmes-replay-icon"
                src="assets/img/Magicien/info.png"
                alt="Icone information">
        </button>

        <div class="enigmes-fog" id="enigmesFog" aria-hidden="true"></div>

        <div class="enigmes-intro" id="enigmesIntro" aria-hidden="true">
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
            <form class="enigmes-form" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input
                    id="enigmesResponseInput"
                    type="text"
                    name="reponse_enigme"
                    placeholder=""
                    aria-label="Reponse a l enigme"
                    value="<?= htmlspecialchars($responseValue, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off">

                <div class="enigmes-form-actions">
                    <button class="enigmes-submit-btn" type="submit" aria-label="Valider la reponse">
                        <img
                            class="enigmes-submit-image"
                            src="assets/img/Magicien/valider.png"
                            alt="">
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<script src="/assets/js/enigmes.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>


