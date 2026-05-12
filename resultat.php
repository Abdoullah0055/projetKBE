<?php
require_once __DIR__ . '/includes/session.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$title = 'Resultat - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css'];
$bodyClass = 'enigmes-page resultat-page';

require_once __DIR__ . '/includes/enigmes_request.php';

$result = consume_enigme_result();

if ($result === null) {
    header('Location: roadmap.php');
    exit;
}

$isCorrect = $result['is_correct'] ?? false;
$isAbandoned = $result['abandoned'] ?? false;
$source = $result['source'] ?? 'roadmap';
$roadmapNodeId = $result['roadmap_node_id'] ?? null;
$query = $result['query'] ?? [];
$rewardLabel = $result['reward_label'] ?? '';
$hpLoss = (int)($result['hp_loss'] ?? 0);
$currentHp = (int)($result['current_hp'] ?? 100);
$maxHp = (int)($result['max_hp'] ?? 100);
$streakBonus = $result['streak_bonus'] ?? false;
$promotedToMage = $result['promoted_to_mage'] ?? false;
$difficulty = $result['difficulty'] ?? 'Facile';

$continueHref = $source === 'random' ? 'random.php' : 'roadmap.php';
$retryHref = '';

if (!$isCorrect && !$isAbandoned && $source === 'roadmap' && $roadmapNodeId !== null) {
    $retryHref = build_enigmes_page_url('enigme.php', $query);
}

$dialogues = [];

if ($isAbandoned) {
    $dialogues = [
        [
            'text' => 'Womp womp, jeune vagabond... tu as abandonne cette epreuve. L\'experience t\'aura au moins appris la prudence.',
            'frame' => 'assets/img/Magicien/womp.png',
        ],
    ];
} elseif ($isCorrect) {
    $dialogues = [
        [
            'text' => 'Houra, jeune vagabond ! Tu as su surmonter le defi !',
            'frame' => 'assets/img/Magicien/houra.png',
        ],
    ];

    if ($rewardLabel !== '') {
        $dialogues[] = [
            'text' => 'Ta recompense est ' . $rewardLabel . '. Que cela te serve bien dans ta quete.',
            'frame' => 'assets/img/Magicien/houra.png',
        ];
    }

    if ($streakBonus) {
        $dialogues[] = [
            'text' => 'Trois enigmes difficiles resolues de suite ! Bonus streak : 100 pieces d\'or !',
            'frame' => 'assets/img/Magicien/mage_rigole.png',
        ];
    }

    if ($promotedToMage) {
        $dialogues[] = [
            'text' => 'Par les anciens ! Tu as resolu 3 quetes de magie... Tu es desormais un MAGE !',
            'frame' => 'assets/img/Magicien/houra.png',
        ];
    }

    $dialogues[] = [
        'text' => 'Que la chance continue de te sourire, jeune vagabond.',
        'frame' => 'assets/img/Magicien/mage8.png',
    ];
} else {
    $hpText = $hpLoss > 0 ? " Tu perds {$hpLoss} points de vie." : '';
    $dialogues = [
        [
            'text' => 'Mauvaise reponse !' . $hpText,
            'frame' => 'assets/img/Magicien/furieux.png',
        ],
        [
            'text' => 'Ne te decourage pas, chaque echec te rapproche de la sagesse. L\'important est de perserverer.',
            'frame' => 'assets/img/Magicien/mage8.png',
        ],
    ];
}

$dialoguesJson = htmlspecialchars(
    json_encode($dialogues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);

$hpPercent = $maxHp > 0 ? round(($currentHp / $maxHp) * 100) : 0;
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="enigmes-main">
    <section
    class="enigmes-stage is-intro-active resultat-stage--<?= $isCorrect ? 'success' : 'failure' ?>"
    aria-label="Resultat de l'enigme"
    data-main-dialogues="<?= $dialoguesJson ?>"
    data-continue-href="<?= htmlspecialchars($continueHref, ENT_QUOTES, 'UTF-8') ?>"
    data-retry-href="<?= htmlspecialchars($retryHref, ENT_QUOTES, 'UTF-8') ?>"
    data-is-correct="<?= $isCorrect ? '1' : '0' ?>"
    data-is-abandoned="<?= $isAbandoned ? '1' : '0' ?>">
        <div class="enigmes-fog" id="enigmesFog" aria-hidden="true"></div>

        <div class="enigmes-intro" id="enigmesIntro">
            <div class="enigmes-intro-dock">
                <button class="enigmes-mage" id="enigmesMage" type="button" aria-label="Continuer le dialogue du mage">
                    <img
                    id="enigmesMageImage"
                    class="enigmes-mage-image"
                    src="assets/img/Magicien/<?= $isCorrect ? 'houra' : 'furieux' ?>.png"
                    alt="Mage en train de parler">
                </button>

                <div class="enigmes-dialog" role="status" aria-live="polite" aria-atomic="true">
                    <p class="enigmes-dialog-text" id="enigmesDialogText"></p>
                    <p class="enigmes-dialog-hint" id="enigmesDialogHint">Cliquez sur le mage pour continuer.</p>
                </div>
            </div>
        </div>

        <div class="resultat-summary" id="resultatSummary" hidden aria-hidden="true">
            <div class="resultat-summary-panel">
                <?php if ($isCorrect): ?>
                <div class="resultat-badge resultat-badge--success">
                    <i class="fas fa-trophy"></i>
                    <span>Victoire</span>
                </div>
                <?php else: ?>
                <div class="resultat-badge resultat-badge--failure">
                    <i class="fas fa-skull-crossbones"></i>
                    <span>Echec</span>
                </div>
                <?php endif; ?>

                <div class="resultat-stats">
                    <?php if ($isCorrect && $rewardLabel !== ''): ?>
                    <div class="resultat-stat">
                        <i class="fas fa-coins"></i>
                        <span><?= htmlspecialchars($rewardLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!$isCorrect && $hpLoss > 0): ?>
                    <div class="resultat-stat resultat-stat--hp-loss">
                        <i class="fas fa-heart-crack"></i>
                        <span>-<?= $hpLoss ?> PV</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($streakBonus): ?>
                    <div class="resultat-stat resultat-stat--streak">
                        <i class="fas fa-fire"></i>
                        <span>Streak +100 or</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($promotedToMage): ?>
                    <div class="resultat-stat resultat-stat--mage">
                        <i class="fas fa-hat-wizard"></i>
                        <span>Mage</span>
                    </div>
                    <?php endif; ?>

                    <div class="resultat-hp-bar">
                        <div class="resultat-hp-bar-fill" style="width: <?= $hpPercent ?>%"></div>
                        <span class="resultat-hp-bar-text"><?= $currentHp ?> / <?= $maxHp ?> PV</span>
                    </div>
                </div>

                <div class="resultat-actions">
                    <?php if ($retryHref !== ''): ?>
                    <a class="resultat-btn resultat-btn--retry" href="<?= htmlspecialchars($retryHref, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-redo"></i> Reessayer
                    </a>
                    <?php endif; ?>

                    <?php if (!$isCorrect && $source === 'random'): ?>
                    <a class="resultat-btn resultat-btn--retry" href="random.php">
                        <i class="fas fa-dice"></i> Autre enigme
                    </a>
                    <?php endif; ?>

                    <a class="resultat-btn resultat-btn--continue" href="<?= htmlspecialchars($continueHref, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-arrow-right"></i> Continuer
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="assets/js/resultat.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>
