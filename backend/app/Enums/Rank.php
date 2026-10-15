<?php

namespace App\Enums;

/**
 * Rang = Farbe des Levels (E-016). Werte passen zu data-rank im Design-System.
 */
enum Rank: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platin = 'platin';

    public static function forLevel(int $level): self
    {
        return match (true) {
            $level >= 75 => self::Platin,
            $level >= 50 => self::Gold,
            $level >= 25 => self::Silver,
            default => self::Bronze,
        };
    }
}
