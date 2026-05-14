<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/enigmes_progression.php';
require_once __DIR__ . '/debug_helper.php';

const ENIGMES_FLASH_DIALOGUES_KEY = 'enigmes_flash_dialogues';
const ROADMAP_FLASH_DIALOGUES_KEY = 'roadmap_flash_dialogues';

function build_enigmes_page_url(string $page, array $params): string
{
    return $page . '?' . http_build_query($params);
}

function redirect_to_enigmes_source(string $source): void
{
    header('Location: ' . ($source === 'random' ? 'random.php' : 'roadmap.php'));
    exit;
}

function normalize_random_difficulty(?string $difficulty): ?string
{
    $valid = ['Facile', 'Moyenne', 'Difficile'];

    if ($difficulty === 'random' || $difficulty === null || $difficulty === '') {
        return $valid[array_rand($valid)];
    }

    $lower = strtolower(trim($difficulty));
    foreach ($valid as $v) {
        if (strtolower($v) === $lower) {
            return $v;
        }
    }

    return null;
}

function get_hint_fallback_text(): string
{
    return 'Meme les grimoires les mieux gardes taisent parfois leurs secrets. Tu devras eclairer ta route sans aide supplementaire.';
}

function set_enigmes_flash_dialogues(array $dialogues): void
{
    $_SESSION[ENIGMES_FLASH_DIALOGUES_KEY] = $dialogues;
}

function consume_enigmes_flash_dialogues(): array
{
    $dialogues = $_SESSION[ENIGMES_FLASH_DIALOGUES_KEY] ?? [];

    unset($_SESSION[ENIGMES_FLASH_DIALOGUES_KEY]);

    return is_array($dialogues) ? $dialogues : [];
}

function set_roadmap_flash_dialogues(array $dialogues): void
{
    $_SESSION[ROADMAP_FLASH_DIALOGUES_KEY] = $dialogues;
}

function consume_roadmap_flash_dialogues(): array
{
    $dialogues = $_SESSION[ROADMAP_FLASH_DIALOGUES_KEY] ?? [];

    unset($_SESSION[ROADMAP_FLASH_DIALOGUES_KEY]);

    return is_array($dialogues) ? $dialogues : [];
}

const ENIGME_RESULT_SESSION_KEY = 'enigme_result';

function set_enigme_result(array $result): void
{
    $_SESSION[ENIGME_RESULT_SESSION_KEY] = $result;
}

function consume_enigme_result(): ?array
{
    $result = $_SESSION[ENIGME_RESULT_SESSION_KEY] ?? null;
    unset($_SESSION[ENIGME_RESULT_SESSION_KEY]);
    return is_array($result) ? $result : null;
}

function generate_and_store_choices(array $riddle): array
{
    $riddleType = $riddle['riddle_type'] ?? 'MultipleChoice';
    $answerText = get_riddle_answer_text((int) $riddle['id']);

    if ($answerText === null) {
        return [];
    }

    if ($riddleType === 'ShortAnswer') {
        $_SESSION['enigme_choices_' . $riddle['id']] = [
            'riddle_type' => 'ShortAnswer',
            'correct_text' => $answerText,
        ];
        return [];
    }

    if ($riddleType === 'TrueFalse') {
        $correctIsTrue = (mb_strtolower(trim($answerText), 'UTF-8') === 'vrai');
        $choices = $correctIsTrue ? ['Vrai', 'Faux'] : ['Faux', 'Vrai'];
        $correctIndex = $correctIsTrue ? 0 : 1;

        $_SESSION['enigme_choices_' . $riddle['id']] = [
            'correct_index' => $correctIndex,
            'choice_texts' => $choices,
            'riddle_type' => 'TrueFalse',
        ];
        return $choices;
    }

    $choices = [
        $answerText,
        $riddle['wrong_answer1'] ?? '',
        $riddle['wrong_answer2'] ?? '',
        $riddle['wrong_answer3'] ?? '',
    ];

    $correctIndex = 0;
    $keys = array_keys($choices);
    shuffle($keys);
    $shuffled = [];

    foreach ($keys as $newIndex => $oldIndex) {
        $shuffled[$newIndex] = $choices[$oldIndex];
        if ($oldIndex === 0) {
            $correctIndex = $newIndex;
        }
    }

    $_SESSION['enigme_choices_' . $riddle['id']] = [
        'correct_index' => $correctIndex,
        'choice_texts' => $shuffled,
        'riddle_type' => 'MultipleChoice',
    ];

return $shuffled;
}

function verify_enigme_choice(int $riddleId, int $choiceIndex): array
{
    $key = 'enigme_choices_' . $riddleId;
    $data = $_SESSION[$key] ?? null;

    if (!is_array($data) || !isset($data['correct_index'], $data['choice_texts'])) {
        debug_log("[verify_enigme_choice] riddleId=$riddleId choiceIndex=$choiceIndex - NO DATA IN SESSION (key=$key)");
        return ['is_correct' => false, 'chosen_text' => ''];
    }

    $isCorrect = $choiceIndex === (int) $data['correct_index'];
    $chosenText = $data['choice_texts'][$choiceIndex] ?? '';

    debug_log("[verify_enigme_choice] riddleId=$riddleId choiceIndex=$choiceIndex correctIndex={$data['correct_index']} isCorrect=" . ($isCorrect ? 'true' : 'false') . " chosenText=$chosenText");

    unset($_SESSION[$key]);

    return ['is_correct' => $isCorrect, 'chosen_text' => $chosenText];
}

function verify_enigme_short_answer(int $riddleId, string $userAnswer): array
{
    $key = 'enigme_choices_' . $riddleId;
    $data = $_SESSION[$key] ?? null;

    if (!is_array($data) || ($data['riddle_type'] ?? '') !== 'ShortAnswer') {
        return ['is_correct' => false, 'chosen_text' => $userAnswer];
    }

    $correctText = trim((string)($data['correct_text'] ?? ''));
    $normalizedUser = normalize_enigme_answer($userAnswer);
    $normalizedCorrect = normalize_enigme_answer($correctText);

    $isCorrect = ($normalizedUser === $normalizedCorrect);
    unset($_SESSION[$key]);

    return ['is_correct' => $isCorrect, 'chosen_text' => $userAnswer];
}

function resolve_enigme_request(string $currentPage): array
{
    $source = (string) ($_GET['source'] ?? 'roadmap');

    debug_log("[resolve_enigme_request] currentPage=$currentPage source=$source GET=" . json_encode($_GET));

    if (!in_array($source, ['roadmap', 'random'], true)) {
        redirect_to_enigmes_source('roadmap');
    }

    $roadmapNodeId = null;
    $backHref = $source === 'random' ? 'random.php' : 'roadmap.php';
    $query = ['source' => $source];
    $riddle = null;

    if ($source === 'roadmap') {
        $roadmapNodeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        debug_log("[resolve_enigme_request] roadmapNodeId(from INPUT_GET)=" . var_export($roadmapNodeId, true));

        if (!$roadmapNodeId) {
            debug_log("[resolve_enigme_request] REDIRECT - no valid roadmapNodeId");
            redirect_to_enigmes_source('roadmap');
        }

        $roadmapNode = get_roadmap_node_by_id($roadmapNodeId);

        if ($roadmapNode === null || !is_enigme_accessible($roadmapNodeId)) {
            redirect_to_enigmes_source('roadmap');
        }

        $riddle = get_active_riddle_by_id((int) $roadmapNode['riddle_id']);

        if ($riddle === null) {
            redirect_to_enigmes_source('roadmap');
        }

        $query['id'] = $roadmapNodeId;
    } else {
        $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
        $difficulty = normalize_random_difficulty($_GET['difficulty'] ?? null);
        $requestedRiddleId = filter_input(INPUT_GET, 'riddle_id', FILTER_VALIDATE_INT);

        if (!$categoryId || $difficulty === null) {
            redirect_to_enigmes_source('random');
        }

        if ($requestedRiddleId) {
            $riddle = get_active_riddle_by_id($requestedRiddleId);

            if (
                $riddle === null ||
                (int) $riddle['category_id'] !== $categoryId ||
                $riddle['difficulty'] !== $difficulty
            ) {
                redirect_to_enigmes_source('random');
            }
        } else {
            $riddle = get_random_active_riddle($categoryId, $difficulty);

            if ($riddle === null) {
                redirect_to_enigmes_source('random');
            }

            header('Location: ' . build_enigmes_page_url($currentPage, [
                'source' => 'random',
                'riddle_id' => (int) $riddle['id'],
                'category_id' => $categoryId,
                'difficulty' => $difficulty,
            ]));
            exit;
        }

        $query['riddle_id'] = (int) $riddle['id'];
        $query['category_id'] = $categoryId;
        $query['difficulty'] = $difficulty;
    }

    $choices = [];
    if ($currentPage === 'reponse.php' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $choices = generate_and_store_choices($riddle);
    }

    return [
        'source' => $source,
        'roadmap_node_id' => $roadmapNodeId,
        'back_href' => $backHref,
        'query' => $query,
        'riddle' => $riddle,
        'choices' => $choices,
    ];
}


