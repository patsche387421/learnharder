<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * EP je Frage höchstens einmal pro Kalendertag (E-015). Der Tag gilt in Wien, nicht in UTC.
 */
final class DailyXp
{
    public const TIMEZONE = 'Europe/Vienna';

    public static function today(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->toDateString();
    }

    /**
     * Fragen aus $questionIds, für die die Person heute schon EP bekommen hat.
     *
     * @param  array<int, string>  $questionIds
     * @return array<int, string>
     */
    public static function alreadyEarned(int $userId, array $questionIds): array
    {
        return QuestionAnswer::query()
            ->where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->where('xp_date', self::today())
            ->where('xp_awarded', '>', 0)
            ->pluck('question_id')
            ->all();
    }

    /**
     * Bringt heute noch mindestens eine Frage EP (für den Hinweis im Shop, E-037)? Zählt nur, was die Person spielen darf:
     * veröffentlicht, im Lehrplan ihrer Klasse (Subject::visibleTo), Level reicht. Eine Abfrage mit EXISTS, lädt keine Fragen.
     * Ohne aktive Klasse und ohne Personal-Rolle gibt es nichts zu spielen, dann ohne Abfrage false.
     */
    public static function anyLeft(User $user): bool
    {
        if (! $user->seesAllContent() && ! $user->activeEnrollment?->schoolClass) {
            return false;
        }

        $level = $user->progression()->level;

        return Question::published()
            ->whereHas('topic', fn (Builder $topics) => $topics->published()
                ->where('level_required', '<=', $level)
                ->whereHas('subject', fn (Builder $subjects) => $subjects->visibleTo($user)))
            ->whereNotExists(fn (QueryBuilder $answers) => $answers->from('question_answers')
                ->whereColumn('question_answers.question_id', 'questions.id')
                ->where('question_answers.user_id', $user->id)
                ->where('question_answers.xp_date', self::today())
                ->where('question_answers.xp_awarded', '>', 0))
            ->exists();
    }
}
