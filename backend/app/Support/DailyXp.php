<?php

namespace App\Support;

use App\Models\QuestionAnswer;
use Carbon\CarbonImmutable;

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
}
