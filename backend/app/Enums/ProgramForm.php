<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Ausbildungsform eines Ausbildungsgangs. Muss zu programs_form_check passen.
 */
enum ProgramForm: string implements HasLabel
{
    case HigherTechnical = 'higher_technical';
    case TechnicalSchool = 'technical_school';
    case AddOnCourse = 'add_on_course';
    case College = 'college';
    case PreparatoryCourse = 'preparatory_course';

    public function getLabel(): string
    {
        return match ($this) {
            self::HigherTechnical => 'Höhere Lehranstalt',
            self::TechnicalSchool => 'Fachschule',
            self::AddOnCourse => 'Aufbaulehrgang',
            self::College => 'Kolleg',
            self::PreparatoryCourse => 'Vorbereitungslehrgang',
        };
    }
}
