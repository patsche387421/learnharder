<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Art der Frage. Muss zu questions_type_check passen. Lückentext kommt später mit eigenem Konzept.
 */
enum QuestionType: string implements HasLabel
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';

    public function getLabel(): string
    {
        return match ($this) {
            self::SingleChoice => 'Eine richtige Antwort',
            self::MultipleChoice => 'Mehrere richtige Antworten',
        };
    }
}
