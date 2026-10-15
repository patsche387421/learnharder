<?php

namespace App\Support;

use App\Enums\Rank;

/**
 * Level, Rang und Prestige aus den Gesamt-EP (Ökonomie-Modell §2, E-010, E-016).
 * levelStart und nextLevel sind Gesamt-EP: Die EP-Leiste zeigt total − levelStart von nextLevel − levelStart.
 */
final readonly class Progression
{
    public const CYCLE_XP = 8000;

    public const MAX_LEVEL = 100;

    private function __construct(
        public int $total,
        public int $prestige,
        public int $level,
        public Rank $rank,
        public int $levelStart,
        public int $nextLevel,
    ) {}

    public static function fromTotalXp(int $total): self
    {
        $total = max(0, $total);
        $prestige = intdiv($total, self::CYCLE_XP);
        $inCycle = $total % self::CYCLE_XP;

        $level = 1;
        while ($level < self::MAX_LEVEL && self::threshold($level + 1) <= $inCycle) {
            $level++;
        }

        $cycleStart = $prestige * self::CYCLE_XP;
        $next = $level < self::MAX_LEVEL ? self::threshold($level + 1) : self::CYCLE_XP;

        return new self(
            total: $total,
            prestige: $prestige,
            level: $level,
            rank: Rank::forLevel($level),
            levelStart: $cycleStart + self::threshold($level),
            nextLevel: $cycleStart + $next,
        );
    }

    /** EP im Zyklus, ab denen Level n beginnt. */
    public static function threshold(int $level): int
    {
        return (int) round(($level - 1) ** 1.5 * 8);
    }
}
