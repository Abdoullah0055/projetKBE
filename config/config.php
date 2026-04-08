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
    // commentaire
    $html .= '</div>';

    return $html;
}
