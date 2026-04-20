<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/page.php';

// 1. VRAIE GESTION DE LA SESSION (US-02)
if (isset($_SESSION['user'])) {
    $user = [
        'isConnected' => true,
        'alias' => $_SESSION['user']['alias'],
        'isMage' => ($_SESSION['user']['role'] === 'Mage'),
        'balance' => [
            'gold' => $_SESSION['user']['gold'],
            'silver' => $_SESSION['user']['silver'],
            'bronze' => $_SESSION['user']['bronze']
        ]
    ];
} else {
    $user = [
        'isConnected' => false,
        'alias' => '',
        'isMage' => false,
        'balance' => [
            'gold' => 0,
            'silver' => 0,
            'bronze' => 0
        ]
    ];
}

function getItemImage($type)
{
    switch (strtolower(trim($type))) {
        case 'weapon':
            return '⚔️';

        case 'armor':
            return '🛡️';

        case 'potion':
            return '🧪';

        case 'magicspell':
            return '✨';

        default:
            return '❓';
    }
}

function normalizeItemImageKey(string $value): string
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    $normalized = str_replace(['’', '\'', '`', '´'], ' ', $normalized);
    $normalized = strtr($normalized, [
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'î' => 'i',
        'ï' => 'i',
        'ô' => 'o',
        'ö' => 'o',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'œ' => 'oe',
    ]);

    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        if ($ascii !== false) {
            $normalized = $ascii;
        }
    }

    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

    return trim($normalized, '_');
}

function getItemImagePath(string $itemName): ?string
{
    $imageByItem = [
        'arc_de_brume_lune' => 'arc_de_brume_lune.png',
        'breuvage_du_sang_froid' => 'breuvage_du_sang_froid.png',
        'cuirasse_du_bastion_gris' => 'cuirasse_du_bastion_gris.png',
        'elixir_de_aube_claire' => 'elixir_de_aube_claire.png',
        'elixir_de_l_aube_claire' => 'elixir_de_aube_claire.png',
        'lame_du_corbeau_noir' => 'lame_du_corbeau_noir.png',
        'marteau_des_ancetres' => 'marteau_des_ancetres.png',
        'tempete_des_sept_eclairs' => 'grimoire_tempete_des_septs_eclairs.png',
        'tempete_des_septs_eclairs' => 'grimoire_tempete_des_septs_eclairs.png',
        'voile_acier_sacre' => 'voile_acier_sacre.png',
        'voile_d_acier_sacre' => 'voile_acier_sacre.png',
    ];

    $key = normalizeItemImageKey($itemName);

    if (!isset($imageByItem[$key])) {
        return null;
    }

    $path = 'assets/images/items_enigme/' . $imageByItem[$key];

    return is_file(dirname(__DIR__) . '/' . $path) ? $path : null;
}

function normalizeRarityValue(string $rarity): string
{
    $normalized = mb_strtolower(trim($rarity), 'UTF-8');

    return match ($normalized) {
        'commun' => 'commun',
        'rare' => 'rare',
        'epique', 'épique' => 'epique',
        'legendaire', 'légendaire' => 'legendaire',
        'mythique' => 'mythique',
        default => 'commun',
    };
}

function formatRarityLabel(string $rarity): string
{
    return match (normalizeRarityValue($rarity)) {
        'commun' => 'Commun',
        'rare' => 'Rare',
        'epique' => 'Épique',
        'legendaire' => 'Légendaire',
        'mythique' => 'Mythique',
        default => 'Commun',
    };
}

function getRarityClass(string $rarity): string
{
    return 'rarity-' . normalizeRarityValue($rarity);
}

function clampRating(float $rating): float
{
    if (!is_finite($rating)) {
        return 0.0;
    }

    if ($rating < 0) {
        return 0.0;
    }

    if ($rating > 5) {
        return 5.0;
    }

    return $rating;
}

function roundRatingToHalf(float $rating): float
{
    $normalized = clampRating($rating);
    return round($normalized * 2) / 2;
}

function formatRatingValue(float $rating): string
{
    return number_format(clampRating($rating), 1, '.', '');
}

function renderRatingStars(float $rating, string $extraClass = ''): string
{
    $rounded = roundRatingToHalf($rating);
    $fullStars = (int) floor($rounded);
    $hasHalf = (($rounded - $fullStars) >= 0.5) ? 1 : 0;
    $emptyStars = 5 - $fullStars - $hasHalf;

    $classes = trim('rating-stars ' . $extraClass);
    $safeClass = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
    $ratingText = formatRatingValue($rounded);

    $html = '<span class="' . $safeClass . '" aria-label="Note ' . $ratingText . ' sur 5">';

    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fa-solid fa-star" aria-hidden="true"></i>';
    }

    if ($hasHalf) {
        $html .= '<i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>';
    }

    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="fa-regular fa-star" aria-hidden="true"></i>';
    }

    $html .= '</span>';

    return $html;
}

function getItemProperties(PDO $pdo, int $itemId, string $type): array
{
    switch (strtolower(trim($type))) {
        case 'weapon':
            $stmt = $pdo->prepare("SELECT * FROM WeaponProperties WHERE ItemId = ?");
            break;

        case 'armor':
            $stmt = $pdo->prepare("SELECT * FROM ArmorProperties WHERE ItemId = ?");
            break;

        case 'potion':
            $stmt = $pdo->prepare("SELECT * FROM PotionProperties WHERE ItemId = ?");
            break;

        case 'magicspell':
            $stmt = $pdo->prepare("SELECT * FROM MagicSpellProperties WHERE ItemId = ?");
            break;

        default:
            return [];
    }

    $stmt->execute([$itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: [];
}

function renderItemProperties(array $item, array $properties): string
{
    $type = strtolower(trim($item['type']));

    if (empty($properties)) {
        return '<div class="property-empty">Aucune propriété disponible pour cet objet.</div>';
    }

    $html = '<div class="details-properties-grid">';

    switch ($type) {
        case 'weapon':
            $html .= '
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Dégâts</span>
                    <span class="detail-prop-value">' . (int)$properties['DamageMin'] . ' - ' . (int)$properties['DamageMax'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Durabilité</span>
                    <span class="detail-prop-value">' . (int)$properties['Durability'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Niveau requis</span>
                    <span class="detail-prop-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Vitesse d\'attaque</span>
                    <span class="detail-prop-value">' . htmlspecialchars((string)$properties['AttackSpeed']) . '</span>
                </div>
            ';
            break;

        case 'armor':
            $html .= '
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Défense</span>
                    <span class="detail-prop-value">' . (int)$properties['Defense'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Durabilité</span>
                    <span class="detail-prop-value">' . (int)$properties['Durability'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Niveau requis</span>
                    <span class="detail-prop-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
            ';
            break;

        case 'potion':
            $html .= '
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Effet</span>
                    <span class="detail-prop-value">' . htmlspecialchars((string)$properties['EffectType']) . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Valeur</span>
                    <span class="detail-prop-value">' . (int)$properties['EffectValue'] . '</span>
                </div>
            ';

            if (isset($properties['DurationSeconds']) && $properties['DurationSeconds'] !== null) {
                $html .= '
                    <div class="detail-prop-box">
                        <span class="detail-prop-label">Durée</span>
                        <span class="detail-prop-value">' . (int)$properties['DurationSeconds'] . ' sec</span>
                    </div>
                ';
            }
            break;

        case 'magicspell':
            $html .= '
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Dégâts magiques</span>
                    <span class="detail-prop-value">' . (int)$properties['SpellDamage'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Coût en mana</span>
                    <span class="detail-prop-value">' . (int)$properties['ManaCost'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Élément</span>
                    <span class="detail-prop-value">' . htmlspecialchars((string)$properties['ElementType']) . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Niveau requis</span>
                    <span class="detail-prop-value">' . (int)$properties['RequiredLevel'] . '</span>
                </div>
                <div class="detail-prop-box">
                    <span class="detail-prop-label">Recharge</span>
                    <span class="detail-prop-value">' . (int)$properties['CooldownSeconds'] . ' sec</span>
                </div>
            ';
            break;
    }
    $html .= '</div>';

    return $html;
}
