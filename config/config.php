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
        'arc_de_brume_lune' => 'assets/images/items_enigme/arc_de_brume_lune.png',
        'breuvage_du_sang_froid' => 'assets/images/items_enigme/breuvage_du_sang_froid.png',
        'cuirasse_du_bastion_gris' => 'assets/images/items_enigme/cuirasse_du_bastion_gris.png',
        'elixir_de_aube_claire' => 'assets/images/items_enigme/elixir_de_aube_claire.png',
        'elixir_de_l_aube_claire' => 'assets/images/items_enigme/elixir_de_aube_claire.png',
        'lame_du_corbeau_noir' => 'assets/images/items_enigme/lame_du_corbeau_noir.png',
        'marteau_des_ancetres' => 'assets/images/items_enigme/marteau_des_ancetres.png',
        'tempete_des_sept_eclairs' => 'assets/images/items_enigme/grimoire_tempete_des_septs_eclairs.png',
        'tempete_des_septs_eclairs' => 'assets/images/items_enigme/grimoire_tempete_des_septs_eclairs.png',
        'voile_acier_sacre' => 'assets/images/items_enigme/voile_acier_sacre.png',
        'voile_d_acier_sacre' => 'assets/images/items_enigme/voile_acier_sacre.png',

        'basic_sword' => 'assets/images/armes/knight_longsword.png',
        'knight_blade' => 'assets/images/armes/knight_longsword.png',
        'golden_sword' => 'assets/images/armes/sultan_scimitar_and_shield.png',
        'war_axe' => 'assets/images/armes/viking_battleaxe.png',
        'hunter_bow' => 'assets/images/armes/elven_knight_bow.png',
        'royal_spear' => 'assets/images/armes/spartan_spear_and_shield.png',
        'dragon_slayer' => 'assets/images/armes/dragon_slayer_longsword.png',

        'leather_armor' => 'assets/images/armure/chest/elf_chest.png',
        'elf_chest' => 'assets/images/armure/chest/elf_chest.png',
        'chainmail' => 'assets/images/armure/chest/elf_chest.png',
        'steel_armor' => 'assets/images/armure/chest/daedra_chest.png',
        'golden_armor' => 'assets/images/armure/chest/daedra_chest.png',
        'daedra_chest' => 'assets/images/armure/chest/daedra_chest.png',
        'paladin_armor' => 'assets/images/armure/chest/elf_chest.png',
        'dragon_scale_armor' => 'assets/images/armure/chest/daedra_chest.png',
        'elf_helmet' => 'assets/images/armure/helmet/elf_helmet.png',
        'daedra_helmet' => 'assets/images/armure/helmet/daedra_helmet.png',
        'elf_legs' => 'assets/images/armure/legs/elf_legs.png',
        'daedra_legs' => 'assets/images/armure/legs/daedra_legs.png',

        'fireball' => 'assets/images/sorts/fireball_tome.png',
    ];

    $key = normalizeItemImageKey($itemName);

    $candidatePaths = [];

    if (isset($imageByItem[$key])) {
        $candidatePaths[] = $imageByItem[$key];
    }

    $candidatePaths[] = 'assets/images/items_enigme/' . $key . '.png';
    $candidatePaths[] = 'assets/images/armes/' . $key . '.png';
    $candidatePaths[] = 'assets/images/armure/chest/' . $key . '.png';
    $candidatePaths[] = 'assets/images/armure/helmet/' . $key . '.png';
    $candidatePaths[] = 'assets/images/armure/legs/' . $key . '.png';
    $candidatePaths[] = 'assets/images/potions/' . $key . '.png';
    $candidatePaths[] = 'assets/images/sorts/' . $key . '.png';

    foreach (array_unique($candidatePaths) as $path) {
        if (is_file(dirname(__DIR__) . '/' . $path)) {
            return $path;
        }
    }

    return null;
}

function getItemImagePathForItem(array $item): ?string
{
    $imageUrl = trim((string)($item['ImageUrl'] ?? $item['image_url'] ?? $item['imageUrl'] ?? ''));

    if ($imageUrl !== '') {
        $type = strtolower(trim((string)($item['type'] ?? $item['item_type'] ?? '')));
        $fileName = basename(str_replace('\\', '/', $imageUrl));
        $fileKey = normalizeItemImageKey(pathinfo($fileName, PATHINFO_FILENAME));

        $imageUrlAliases = [
            'leather_armor' => 'assets/images/armure/chest/elf_chest.png',
            'elf_chest' => 'assets/images/armure/chest/elf_chest.png',
            'golden_armor' => 'assets/images/armure/chest/daedra_chest.png',
            'daedra_chest' => 'assets/images/armure/chest/daedra_chest.png',
            'elf_helm' => 'assets/images/armure/helmet/elf_helmet.png',
            'elf_helmet' => 'assets/images/armure/helmet/elf_helmet.png',
            'daedra_helm' => 'assets/images/armure/helmet/daedra_helmet.png',
            'daedra_helmet' => 'assets/images/armure/helmet/daedra_helmet.png',
            'elf_leg' => 'assets/images/armure/legs/elf_legs.png',
            'elf_legs' => 'assets/images/armure/legs/elf_legs.png',
            'daedra_leg' => 'assets/images/armure/legs/daedra_legs.png',
            'daedra_legs' => 'assets/images/armure/legs/daedra_legs.png',
            'fireball' => 'assets/images/sorts/fireball_tome.png',
        ];

        // Détection plus fiable pour les armures
        if (
            str_contains($fileKey, 'helmet') ||
            str_contains($fileKey, 'helm') ||
            str_contains($fileKey, 'casque')
        ) {
            $path = 'assets/images/armure/helmet/';
        } elseif (
            str_contains($fileKey, 'legs') ||
            str_contains($fileKey, 'leg') ||
            str_contains($fileKey, 'jambe') ||
            str_contains($fileKey, 'jambes')
        ) {
            $path = 'assets/images/armure/legs/';
        } elseif (
            str_contains($fileKey, 'chest') ||
            str_contains($fileKey, 'armor') ||
            str_contains($fileKey, 'armour') ||
            str_contains($fileKey, 'armure') ||
            $type === 'armor' ||
            $type === 'armour' ||
            $type === 'armure'
        ) {
            $path = 'assets/images/armure/chest/';
        } elseif ($type === 'weapon') {
            $path = 'assets/images/armes/';
        } elseif ($type === 'potion') {
            $path = 'assets/images/potions/';
        } elseif ($type === 'magicspell') {
            $path = 'assets/images/sorts/';
        } else {
            $path = 'assets/images/';
        }

        $candidateFileNames = [$fileName];

        if (pathinfo($fileName, PATHINFO_EXTENSION) === '') {
            $candidateFileNames[] = $fileName . '.png';
        }

        $candidatePaths = [
            $imageUrlAliases[$fileKey] ?? '',
            ltrim($imageUrl, '/'),
        ];

        foreach (array_unique($candidateFileNames) as $candidateFileName) {
            $candidatePaths[] = $path . $candidateFileName;
            $candidatePaths[] = 'assets/images/armure/chest/' . $candidateFileName;
            $candidatePaths[] = 'assets/images/armure/helmet/' . $candidateFileName;
            $candidatePaths[] = 'assets/images/armure/legs/' . $candidateFileName;
            $candidatePaths[] = 'assets/images/armes/' . $candidateFileName;
            $candidatePaths[] = 'assets/images/potions/' . $candidateFileName;
            $candidatePaths[] = 'assets/images/sorts/' . $candidateFileName;
        }

        foreach (array_filter(array_unique($candidatePaths)) as $candidatePath) {
            if (str_starts_with($candidatePath, 'assets/images/') && is_file(dirname(__DIR__) . '/' . $candidatePath)) {
                return $candidatePath;
            }
        }
    }

    return getItemImagePath((string)($item['nom'] ?? $item['item_name'] ?? $item['Name'] ?? ''));
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
