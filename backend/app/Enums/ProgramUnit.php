<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Worin ein Ausbildungsgang zählt: Jahrgänge oder Semester. Muss zu programs_unit_check passen.
 */
enum ProgramUnit: string implements HasLabel
{
    case Year = 'year';
    case Semester = 'semester';

    public function getLabel(): string
    {
        return match ($this) {
            self::Year => 'Jahrgang',
            self::Semester => 'Semester',
        };
    }
}
