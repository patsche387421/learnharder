<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Art der Bedingung eines Erfolgs, im CMS mit Schwellwert (E-042). Muss zu achievements_rule_check passen.
 */
enum AchievementRule: string implements HasLabel
{
    case LessonsFinished = 'lessons_finished';
    case PerfectLessons = 'perfect_lessons';
    case Streak = 'streak';
    case Level = 'level';
    case SubjectLevel = 'subject_level';
    case ChallengesFinished = 'challenges_finished';
    case CorrectedMistakes = 'corrected_mistakes';
    case SubjectsLearned = 'subjects_learned';

    public function getLabel(): string
    {
        return match ($this) {
            self::LessonsFinished => 'Abgeschlossene Lerneinheiten',
            self::PerfectLessons => 'Fehlerfreie Lerneinheiten (ab 5 Fragen)',
            self::Streak => 'Streak in Tagen (längster)',
            self::Level => 'Level (ab Prestige 1 immer erreicht)',
            self::SubjectLevel => 'Fach-Level',
            self::ChallengesFinished => 'Abgeschlossene Herausforderungen',
            self::CorrectedMistakes => 'Fragen, die früher falsch und später richtig waren',
            self::SubjectsLearned => 'Fächer mit mindestens einer Lerneinheit',
        };
    }
}
