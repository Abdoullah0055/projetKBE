<?php
$title = 'Reponse - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css'];
$bodyClass = 'enigmes-page reponse-page';

require_once __DIR__ . '/includes/enigmes_request.php';
require_once __DIR__ . '/AlgosBD.php';

$context = resolve_enigme_request('reponse.php');

function build_reward_label(array $riddle): string
{
    $gold = (int) ($riddle['reward_gold'] ?? 0);
    $silver = (int) ($riddle['reward_silver'] ?? 0);
    $bronze = (int) ($riddle['reward_bronze'] ?? 0);

    if ($gold > 0) {
        return $gold . ' piece' . ($gold > 1 ? 's' : '') . ' d\'or';
    }

    if ($silver > 0) {
        return $silver . ' piece' . ($silver > 1 ? 's' : '') . ' d\'argent';
    }

    if ($bronze > 0) {
        return $bronze . ' piece' . ($bronze > 1 ? 's' : '') . ' de bronze';
    }

    return '0 recompense';
}

if (isset($_GET['abandon']) && (string) $_GET['abandon'] === '1') {
    $abandonHp = get_user_hp($_SESSION['user']['id']);
    set_enigme_result([
        'is_correct' => false,
        'source' => $context['source'],
        'roadmap_node_id' => $context['roadmap_node_id'],
        'query' => $context['query'],
        'reward_label' => '',
        'hp_loss' => 0,
        'current_hp' => $abandonHp['current'],
        'max_hp' => $abandonHp['max'],
        'streak_bonus' => false,
        'promoted_to_mage' => false,
        'difficulty' => $context['riddle']['difficulty'] ?? 'Facile',
        'abandoned' => true,
    ]);
    header('Location: resultat.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choiceIndex = filter_input(INPUT_POST, 'choice_index', FILTER_VALIDATE_INT);

    if ($choiceIndex === false || $choiceIndex === null || $choiceIndex < 0 || $choiceIndex > 3) {
        set_enigmes_flash_dialogues([
            [
                'text' => 'Choisis une reponse parmi les quatre propositions.',
                'frame' => 'assets/img/Magicien/furieux.png',
            ],
            [
                'text' => 'Allez, je te renvoie.',
                'frame' => 'assets/img/Magicien/mage8.png',
            ],
        ]);
        header('Location: ' . build_enigmes_page_url('enigme.php', $context['query']));
        exit;
    }

    $result = verify_enigme_choice((int) $context['riddle']['id'], $choiceIndex);

    if (!$result['is_correct']) {
        record_riddle_attempt($_SESSION['user']['id'], (int)$context['riddle']['id'], $result['chosen_text'] ?? '', false);
        increment_riddle_stats($_SESSION['user']['id'], false);

        $hp_loss = match($context['riddle']['difficulty']) {
            'Facile' => 3,
            'Moyenne' => 6,
            'Difficile' => 10,
            default => 3,
        };
        deduct_hp($_SESSION['user']['id'], $hp_loss);
        $hp_data = get_user_hp($_SESSION['user']['id']);
        $_SESSION['user']['hp'] = $hp_data['current'];
        $_SESSION['user']['max_hp'] = $hp_data['max'];

        set_enigme_result([
            'is_correct' => false,
            'source' => $context['source'],
            'roadmap_node_id' => $context['roadmap_node_id'],
            'query' => $context['query'],
            'reward_label' => '',
            'hp_loss' => $hp_loss,
            'current_hp' => $hp_data['current'],
            'max_hp' => $hp_data['max'],
            'streak_bonus' => false,
            'promoted_to_mage' => false,
            'difficulty' => $context['riddle']['difficulty'],
            'abandoned' => false,
        ]);
        header('Location: resultat.php');
        exit;
    } else {
        $riddleId = (int)$context['riddle']['id'];
        $chosenText = $result['chosen_text'] ?? '';
        $isMagic = is_riddle_magic_category($riddleId);

        record_riddle_attempt($_SESSION['user']['id'], $riddleId, $chosenText, true);
        increment_riddle_stats($_SESSION['user']['id'], true, $isMagic);

        $gold = (int)($context['riddle']['reward_gold'] ?? 0);
        $silver = (int)($context['riddle']['reward_silver'] ?? 0);
        $bronze = (int)($context['riddle']['reward_bronze'] ?? 0);
        credit_user_currency($_SESSION['user']['id'], $gold, $silver, $bronze);
        $_SESSION['user']['gold'] += $gold;
        $_SESSION['user']['silver'] += $silver;
        $_SESSION['user']['bronze'] += $bronze;

        $promotedToMage = check_and_promote_mage($_SESSION['user']['id']);
        if ($promotedToMage) {
            $_SESSION['user']['role'] = 'Mage';
        }

        if ($context['source'] === 'roadmap' && $context['roadmap_node_id'] !== null) {
            mark_enigme_completed($context['roadmap_node_id']);
        }

        $streakBonus = credit_streak_bonus($_SESSION['user']['id']);
        if ($streakBonus) {
            $_SESSION['user']['gold'] += 100;
        }

        $hp_data = get_user_hp($_SESSION['user']['id']);

        set_enigme_result([
            'is_correct' => true,
            'source' => $context['source'],
            'roadmap_node_id' => $context['roadmap_node_id'],
            'query' => $context['query'],
            'reward_label' => build_reward_label($context['riddle']),
            'hp_loss' => 0,
            'current_hp' => $hp_data['current'],
            'max_hp' => $hp_data['max'],
            'streak_bonus' => $streakBonus,
            'promoted_to_mage' => $promotedToMage,
            'difficulty' => $context['riddle']['difficulty'],
            'abandoned' => false,
        ]);
        header('Location: resultat.php');
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
    'Ah... je savais bien que tu aurais besoin d\'un indice. Cette enigme semblait deja trop grande pour toi.',
    $hintText,
    'J\'espere que cela suffira a eclairer ton esprit, jeune vagabond.',
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
<?php include __DIR__ . '/includes/header.php'; ?>

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
                <div class="enigmes-choices" id="enigmesChoices">
                <?php foreach ($context['choices'] as $i => $choice): ?>
                    <button type="submit" name="choice_index" value="<?= $i ?>" class="enigmes-choice-btn"><?= htmlspecialchars($choice, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
                </div>
            </form>
        </div>
    </section>
</main>

<script src="assets/js/enigmes.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>
