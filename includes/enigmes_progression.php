<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ENIGMES_SESSION_KEY = 'enigmes_progression';
const ENIGMES_MOCK_UNLOCKED_ID = 4;
const ENIGMES_MOCK_APPLY_ONCE = true;

function get_enigmes_catalogue(): array
{
    return [
        1 => [
            'id' => 1,
            'riddle_id' => 1,
            'name' => 'Chevalier',
            'roadmap_name' => 'Aldric',
            'difficulty_label' => 'Ecuyer',
            'image' => 'assets/img/Visages/chevalier_face.png',
            'gray_image' => 'assets/img/Visages/chevalier_face_gris.png',
            'position_top' => '26%',
            'position_left' => '16%',
        ],
        2 => [
            'id' => 2,
            'riddle_id' => 2,
            'name' => 'Elfe',
            'roadmap_name' => 'Lysandra',
            'difficulty_label' => 'Ecuyer',
            'image' => 'assets/img/Visages/elf_face.png',
            'gray_image' => 'assets/img/Visages/elf_face_gris.png',
            'position_top' => '26%',
            'position_left' => '38%',
        ],
        3 => [
            'id' => 3,
            'riddle_id' => 3,
            'name' => 'Samourai',
            'roadmap_name' => 'Takeda',
            'difficulty_label' => 'Chevalier',
            'image' => 'assets/img/Visages/samurai_face.png',
            'gray_image' => 'assets/img/Visages/samurai_face_gris.png',
            'position_top' => '26%',
            'position_left' => '60%',
        ],
        4 => [
            'id' => 4,
            'riddle_id' => 4,
            'name' => 'Spartiate',
            'roadmap_name' => 'Dorian',
            'difficulty_label' => 'Chevalier',
            'image' => 'assets/img/Visages/spartan_face.png',
            'gray_image' => 'assets/img/Visages/spartan_face_gris.png',
            'position_top' => '26%',
            'position_left' => '82%',
        ],
        5 => [
            'id' => 5,
            'riddle_id' => 8,
            'name' => 'Sultan',
            'roadmap_name' => 'Suleiman',
            'difficulty_label' => 'Senechal',
            'image' => 'assets/img/Visages/sultan_face.png',
            'gray_image' => 'assets/img/Visages/sultan_face_gris.png',
            'position_top' => '78%',
            'position_left' => '16%',
        ],
        6 => [
            'id' => 6,
            'riddle_id' => 6,
            'name' => 'Minotaure',
            'roadmap_name' => 'Asterion',
            'difficulty_label' => 'Senechal',
            'image' => 'assets/img/Visages/minotaur_face.png',
            'gray_image' => 'assets/img/Visages/minotaur_face_gris.png',
            'position_top' => '78%',
            'position_left' => '38%',
        ],
        7 => [
            'id' => 7,
            'riddle_id' => 7,
            'name' => 'Orc',
            'roadmap_name' => 'Ragor',
            'difficulty_label' => 'Dragon Noir',
            'image' => 'assets/img/Visages/orc_face.png',
            'gray_image' => 'assets/img/Visages/orc_face_gris.png',
            'position_top' => '78%',
            'position_left' => '60%',
        ],
        8 => [
            'id' => 8,
            'riddle_id' => 5,
            'name' => 'Dragon',
            'roadmap_name' => 'Vaelgor',
            'difficulty_label' => 'Dragon Noir',
            'image' => 'assets/img/Visages/dragon_face.png',
            'gray_image' => 'assets/img/Visages/dragon_face_gris.png',
            'position_top' => '78%',
            'position_left' => '82%',
        ],
    ];
}

function ensure_enigmes_progression(): void
{
    $catalogue = get_enigmes_catalogue();

    if (!isset($_SESSION[ENIGMES_SESSION_KEY]) || !is_array($_SESSION[ENIGMES_SESSION_KEY])) {
        $_SESSION[ENIGMES_SESSION_KEY] = [
            'completed' => [],
        ];
    }

    if (ENIGMES_MOCK_APPLY_ONCE && empty($_SESSION[ENIGMES_SESSION_KEY]['mock_applied'])) {
        $mockUnlockedId = max(1, min(ENIGMES_MOCK_UNLOCKED_ID, count($catalogue)));
        $mockCompleted = $mockUnlockedId > 1 ? range(1, $mockUnlockedId - 1) : [];

        $_SESSION[ENIGMES_SESSION_KEY]['completed'] = $mockCompleted;
        $_SESSION[ENIGMES_SESSION_KEY]['mock_applied'] = true;
    }

    $completed = $_SESSION[ENIGMES_SESSION_KEY]['completed'] ?? [];

    if (!is_array($completed)) {
        $completed = [];
    }

    $validIds = array_map('intval', array_keys($catalogue));
    $completed = array_values(array_intersect($validIds, array_map('intval', $completed)));
    sort($completed);

    $_SESSION[ENIGMES_SESSION_KEY]['completed'] = array_values(array_unique($completed));
}

function get_roadmap_node_by_id(int $enigmeId): ?array
{
    $catalogue = get_enigmes_catalogue();

    return $catalogue[$enigmeId] ?? null;
}

function get_enigme_by_id(int $enigmeId): ?array
{
    return get_roadmap_node_by_id($enigmeId);
}

function get_completed_enigmes(): array
{
    ensure_enigmes_progression();

    return $_SESSION[ENIGMES_SESSION_KEY]['completed'];
}

function is_enigme_completed(int $enigmeId): bool
{
    return in_array($enigmeId, get_completed_enigmes(), true);
}

function get_enigmes_states(): array
{
    $catalogue = get_enigmes_catalogue();
    $completedLookup = array_fill_keys(get_completed_enigmes(), true);
    $states = [];
    $nextUnlockedFound = false;

    foreach ($catalogue as $enigmeId => $enigme) {
        if (isset($completedLookup[$enigmeId])) {
            $states[$enigmeId] = 'completed';
            continue;
        }

        if (!$nextUnlockedFound) {
            $states[$enigmeId] = 'unlocked';
            $nextUnlockedFound = true;
            continue;
        }

        $states[$enigmeId] = 'blocked';
    }

    return $states;
}

function is_enigme_accessible(int $enigmeId): bool
{
    $states = get_enigmes_states();

    return ($states[$enigmeId] ?? null) === 'unlocked';
}

function mark_enigme_completed(int $enigmeId): void
{
    if (get_roadmap_node_by_id($enigmeId) === null) {
        return;
    }

    ensure_enigmes_progression();

    if (!is_enigme_completed($enigmeId)) {
        $_SESSION[ENIGMES_SESSION_KEY]['completed'][] = $enigmeId;
        sort($_SESSION[ENIGMES_SESSION_KEY]['completed']);
    }
}

function normalize_enigme_answer(string $answer): string
{
    $answer = trim($answer);
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $answer);

    if ($transliterated !== false) {
        $answer = $transliterated;
    }

    $answer = strtolower($answer);
    $answer = preg_replace('/[^\p{L}\p{N}\s]/u', '', $answer) ?? '';
    $answer = preg_replace('/\s+/u', ' ', $answer) ?? '';

    return trim($answer);
}


