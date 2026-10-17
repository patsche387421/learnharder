<?php

namespace App\Support;

use App\Enums\AchievementRule;
use App\Enums\AttemptType;
use App\Models\Achievement;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Die Werte einer Person, mit denen die Bedingungen verglichen werden. Jeder Wert wird höchstens einmal gerechnet.
 * Nur für Achievements.
 */
final class AchievementValues
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly User $user, private readonly UserStat $stats) {}

    public function reached(Achievement $achievement): bool
    {
        $threshold = $achievement->threshold;

        return match ($achievement->rule) {
            AchievementRule::LessonsFinished => $this->remember('lessons', fn () => $this->finished(AttemptType::Standard)->count()) >= $threshold,
            AchievementRule::PerfectLessons => $this->remember('perfect', fn () => $this->finished(AttemptType::Standard)
                ->where('total_questions', '>=', Achievements::PERFECT_MIN_QUESTIONS)
                ->whereColumn('correct_count', 'total_questions')
                ->count()) >= $threshold,
            AchievementRule::Streak => $this->stats->longest_streak >= $threshold,
            // Das Level beginnt nach einem Prestige wieder bei 1, der Erfolg bleibt erreicht
            AchievementRule::Level => $this->progression()->prestige > 0 || $this->progression()->level >= $threshold,
            AchievementRule::SubjectLevel => $this->subjectLevel($achievement->subject_id) >= $threshold,
            AchievementRule::ChallengesFinished => $this->remember('challenges', fn () => $this->finished(AttemptType::Challenge)->count()) >= $threshold,
            AchievementRule::CorrectedMistakes => $this->remember('corrected', fn () => $this->correctedMistakes()) >= $threshold,
            AchievementRule::SubjectsLearned => $this->remember('subjects', fn () => $this->finished(AttemptType::Standard)
                ->join('topics', 'topics.id', '=', 'quiz_attempts.topic_id')
                ->distinct()
                ->count('topics.subject_id')) >= $threshold,
        };
    }

    private function finished(AttemptType $type): Builder
    {
        return QuizAttempt::finished()->where('user_id', $this->user->id)->where('attempt_type', $type);
    }

    private function progression(): Progression
    {
        return $this->remember('progression', fn () => Progression::fromTotalXp($this->stats->total_xp));
    }

    /** Fach-Level in einem bestimmten Fach oder, ohne Fach, das höchste. */
    private function subjectLevel(?string $subjectId): int
    {
        $xp = $this->remember('subject_xp', fn () => QuestionAnswer::xpBySubject($this->user->id));

        if ($subjectId !== null) {
            return Progression::subjectLevel($xp[$subjectId] ?? 0);
        }

        return $xp->map(fn (int $subjectXp) => Progression::subjectLevel($subjectXp))->max() ?? 0;
    }

    /**
     * Fragen, die in einer Lerneinheit falsch und in einer späteren richtig waren. Gespeichert ist nur die erste Antwort je
     * Lerneinheit, die Wiederholung am Ende zählt deshalb nicht.
     */
    private function correctedMistakes(): int
    {
        return (int) DB::table('question_answers as wrong')
            ->join('question_answers as right', function ($join) {
                $join->on('right.user_id', '=', 'wrong.user_id')
                    ->on('right.question_id', '=', 'wrong.question_id')
                    ->on('right.created_at', '>', 'wrong.created_at');
            })
            ->where('wrong.user_id', $this->user->id)
            ->where('wrong.is_correct', false)
            ->where('right.is_correct', true)
            ->distinct()
            ->count('wrong.question_id');
    }

    private function remember(string $key, callable $value): mixed
    {
        return $this->cache[$key] ??= $value();
    }
}
