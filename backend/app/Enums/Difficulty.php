<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Schwierigkeit einer Frage von 1 bis 5. Muss zu questions_difficulty_check passen.
 */
enum Difficulty: int implements HasLabel
{
    case VeryEasy = 1;
    case Easy = 2;
    case Medium = 3;
    case Hard = 4;
    case VeryHard = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::VeryEasy => '1 – sehr leicht',
            self::Easy => '2 – leicht',
            self::Medium => '3 – mittel',
            self::Hard => '4 – schwer',
            self::VeryHard => '5 – sehr schwer',
        };
    }
}
