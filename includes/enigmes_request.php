<?php

require_once __DIR__ . '/../AlgosBD.php';
require_once __DIR__ . '/enigmes_progression.php';

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
    $allowedDifficulties = ['Facile', 'Moyenne', 'Difficile'];

    if ($difficulty === null) {
        return null;
    }

    return in_array($difficulty, $allowedDifficulties, true) ? $difficulty : null;
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

function resolve_enigme_request(string $currentPage): array
{
    $source = (string) ($_GET['source'] ?? 'roadmap');

    if (!in_array($source, ['roadmap', 'random'], true)) {
        redirect_to_enigmes_source('roadmap');
    }

    $roadmapNodeId = null;
    $backHref = $source === 'random' ? 'random.php' : 'roadmap.php';
    $query = ['source' => $source];
    $riddle = null;

    if ($source === 'roadmap') {
        $roadmapNodeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$roadmapNodeId) {
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

    return [
        'source' => $source,
        'roadmap_node_id' => $roadmapNodeId,
        'back_href' => $backHref,
        'query' => $query,
        'riddle' => $riddle,
    ];
}


