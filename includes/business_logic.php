<?php

declare(strict_types=1);

function compute_sell_multiplier(int $itemTypeId, ?string $rarity): float
{
    if ($itemTypeId !== 4) {
        return 0.60;
    }

    $normalized = mb_strtolower(trim((string) $rarity), 'UTF-8');

    return match ($normalized) {
        'commun' => 1.0,
        'rare' => 0.95,
        'epique', 'épique' => 0.90,
        'legendaire', 'légendaire' => 0.90,
        'mythique' => 0.90,
        default => 0.90,
    };
}

function compute_new_hp(int $currentHp, int $maxHp, int $healAmount): array
{
    $safeCurrent = max(0, $currentHp);
    $safeMax = max(0, $maxHp);
    $safeHeal = max(0, $healAmount);

    $target = min($safeCurrent + $safeHeal, $safeMax);
    $effective = max(0, $target - $safeCurrent);
    $wasted = max(0, $safeHeal - $effective);

    return [
        'new_hp' => $target,
        'effective_heal' => $effective,
        'wasted_heal' => $wasted,
    ];
}
