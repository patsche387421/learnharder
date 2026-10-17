<?php

namespace Database\Seeders;

use App\Enums\AchievementRule;
use App\Models\Achievement;
use Illuminate\Database\Seeder;

/**
 * Erster Katalog der Erfolge (E-044, Konzept Spielwelt §6). Läuft in jeder Umgebung, auch in Produktion.
 * Legt nur an, was nach slug fehlt: Änderungen aus dem CMS bleiben, gelöschte oder deaktivierte kommen nicht zurück,
 * solange der slug existiert.
 */
class AchievementSeeder extends Seeder
{
    /** slug => [Titel, Bedingung, Typ, Schwellwert, Icon, Trophäen, geheim] */
    private const CATALOG = [
        'first-lesson' => ['Erste Lerneinheit', '1 Lerneinheit abschließen', AchievementRule::LessonsFinished, 1, 'xp', 1, false],
        'lessons-25' => ['Dranbleiben', '25 Lerneinheiten abschließen', AchievementRule::LessonsFinished, 25, 'xp', 3, false],
        'perfect-lesson' => ['Fehlerfrei', 'Eine Lerneinheit ab 5 Fragen ohne Fehler', AchievementRule::PerfectLessons, 1, 'achievement', 2, false],
        'streak-3' => ['Drei Tage in Folge', '3 Tage in Folge lernen', AchievementRule::Streak, 3, 'streak', 1, false],
        'streak-7' => ['Eine Woche', '7 Tage in Folge lernen', AchievementRule::Streak, 7, 'streak', 2, false],
        'streak-30' => ['Ein Monat', '30 Tage in Folge lernen', AchievementRule::Streak, 30, 'streak', 5, false],
        'level-10' => ['Level 10', 'Level 10 erreichen', AchievementRule::Level, 10, 'xp', 2, false],
        'level-25' => ['Silber', 'Level 25 erreichen', AchievementRule::Level, 25, 'xp', 3, false],
        'subject-level-10' => ['Fach-Level 10', 'In einem Fach Level 10', AchievementRule::SubjectLevel, 10, 'achievement', 3, false],
        'first-challenge' => ['Herausforderung angenommen', '1 Herausforderung abschließen', AchievementRule::ChallengesFinished, 1, 'challenge', 1, false],
        'from-mistakes' => ['Aus Fehlern gelernt', 'Eine Frage richtig, die du früher falsch hattest', AchievementRule::CorrectedMistakes, 1, 'achievement', 2, true],
        'allrounder' => ['Allrounder', 'In 3 Fächern lernen', AchievementRule::SubjectsLearned, 3, 'achievement', 2, true],
    ];

    public function run(): void
    {
        $sort = 0;
        foreach (self::CATALOG as $slug => [$title, $description, $rule, $threshold, $icon, $trophies, $secret]) {
            Achievement::firstOrCreate(['slug' => $slug], [
                'title' => ['de' => $title],
                'description' => ['de' => $description],
                'rule' => $rule,
                'threshold' => $threshold,
                'icon_name' => $icon,
                'trophy_reward' => $trophies,
                'is_secret' => $secret,
                'sort' => $sort += 10,
            ]);
        }
    }
}
