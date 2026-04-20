<?php
$title = 'Roadmap des enigmes - Marche Noir';
$extraStylesheets = ['assets/css/enigmes.css', 'assets/css/roadmap.css'];
$bodyClass = 'enigmes-page roadmap-page';

require_once __DIR__ . '/includes/enigmes_progression.php';
require_once __DIR__ . '/includes/enigmes_request.php';

$enigmes = get_enigmes_catalogue();
$states = get_enigmes_states();
$postRoadmapDialogues = consume_roadmap_flash_dialogues();
$postRoadmapDialoguesJson = htmlspecialchars(
    json_encode($postRoadmapDialogues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
?>

<?php include __DIR__ . '/templates/head.php'; ?>

<main class="roadmap-main">
    <section
        class="roadmap-stage"
        aria-label="Progression des enigmes"
        data-post-dialogues="<?= $postRoadmapDialoguesJson ?>">
        <a
            class="enigmes-back"
            href="index.php"
            aria-label="Retour">
            <img
                class="enigmes-back-image"
                src="assets/img/Magicien/retour.png"
                alt="">
        </a>

        <a
            class="roadmap-random-btn"
            href="random.php"
            aria-label="Acceder aux choix aleatoires">
            <img
                class="roadmap-random-btn-image"
                src="assets/img/Magicien/random.png"
                alt="">
        </a>

        <div class="roadmap-track" aria-label="Carte des enigmes">
            <?php foreach ($enigmes as $enigme): ?>
                <?php
                $state = $states[$enigme['id']] ?? 'blocked';
                $isUnlocked = $state === 'unlocked';
                $tag = $isUnlocked ? 'a' : 'button';
                $imageSource = $state === 'completed' ? $enigme['gray_image'] : $enigme['image'];
                $roadmapName = $enigme['roadmap_name'] ?? $enigme['name'];
                $difficultyLabel = $enigme['difficulty_label'] ?? '';
                $noticeDialogues = [];

                if ($state === 'blocked') {
                    $noticeDialogues = [
                        [
                            'text' => 'Halte, jeune vagabond. Les sceaux de cette enigme ne te reconnaissent pas encore.',
                            'frame' => 'assets/img/Magicien/furieux.png',
                        ],
                        [
                            'text' => 'Mon grimoire l’atteste : accomplis ses predecesseurs et la voie s’ouvrira devant toi.',
                            'frame' => 'assets/img/Magicien/mage_grimoire.png',
                        ],
                    ];
                } elseif ($state === 'completed') {
                    $noticeDialogues = [
                        [
                            'text' => 'Serieusement ? Tu reviens encore ici ? Cette enigme est deja pliee, meme un troll distrait l’aurait remarque.',
                            'frame' => 'assets/img/Magicien/furieux.png',
                        ],
                        [
                            'text' => 'Mon grimoire l’a deja consignee. Retourne sur la roadmap et poursuis ta quete vers de nouveaux 50 gold.',
                            'frame' => 'assets/img/Magicien/mage_grimoire.png',
                        ],
                    ];
                }
                ?>
                <<?= $tag ?>
                    class="roadmap-node roadmap-node--<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>"
                    style="--node-top: <?= htmlspecialchars($enigme['position_top'], ENT_QUOTES, 'UTF-8') ?>; --node-left: <?= htmlspecialchars($enigme['position_left'], ENT_QUOTES, 'UTF-8') ?>;"
                    <?php if ($isUnlocked): ?>
                        href="enigme.php?source=roadmap&amp;id=<?= (int) $enigme['id'] ?>"
                    <?php else: ?>
                        type="button"
                        data-roadmap-dialogues="<?= htmlspecialchars(json_encode($noticeDialogues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                    <?php endif; ?>
                    aria-label="<?= htmlspecialchars($roadmapName, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($difficultyLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="roadmap-node-shell">
                        <img
                            class="roadmap-node-face"
                            src="<?= htmlspecialchars($imageSource, ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($roadmapName, ENT_QUOTES, 'UTF-8') ?>">

                        <?php if ($state === 'blocked'): ?>
                            <img
                                class="roadmap-node-lock"
                                src="assets/img/Visages/lock.png"
                                alt=""
                                aria-hidden="true">
                        <?php endif; ?>
                    </span>

                    <span class="roadmap-node-meta">
                        <span class="roadmap-node-tier"><?= htmlspecialchars($difficultyLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="roadmap-node-name"><?= htmlspecialchars($roadmapName, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>

        <div class="roadmap-mage-overlay" id="roadmapMageOverlay" hidden aria-hidden="true">
            <button class="roadmap-mage-backdrop" id="roadmapMageBackdrop" type="button" aria-label="Fermer le message du mage"></button>

            <div class="roadmap-mage-panel" role="dialog" aria-modal="true" aria-label="Message du mage">
                <div class="roadmap-mage-dock">
                    <button class="roadmap-mage-figure" id="roadmapMageButton" type="button" aria-label="Continuer le message du mage">
                        <img
                            id="roadmapMageImage"
                            class="roadmap-mage-image"
                            src="assets/img/Magicien/mage1.png"
                            alt="Mage en train de parler">
                    </button>

                    <div class="roadmap-mage-dialog">
                        <p class="roadmap-mage-text" id="roadmapMageText"></p>
                        <p class="roadmap-mage-hint" id="roadmapMageHint">Cliquez sur le mage pour continuer.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="/assets/js/roadmap.js" defer></script>
<?php include __DIR__ . '/templates/end.php'; ?>


