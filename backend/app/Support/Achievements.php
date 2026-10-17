<?php

namespace App\Support;

use App\Models\Achievement;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Vergibt Erfolge (E-042, E-043) nur auf dem Server: nach jedem Abschluss und beim Laden der Erfolge.
 * Geprüft werden nur aktive Erfolge, die die Person noch nicht hat, und nur die Werte, die sie brauchen.
 * Jeder Erfolg kommt einmal, mit Datum und Trophäen, und wird nie entzogen.
 */
final class Achievements
{
    /** „Fehlerfrei“ zählt erst ab so vielen Fragen in der Lerneinheit. */
    public const PERFECT_MIN_QUESTIONS = 5;

    /**
     * Vergibt alle erreichten Erfolge und schreibt ihre Trophäen gut. $attempt ist die Lerneinheit, deren Abschluss sie gebracht hat.
     * Die Sperre auf user_stats lässt Prüfungen derselben Person nacheinander laufen (in finish() hält der Aufrufer sie schon).
     *
     * @return Collection<int, Achievement> neu erreichte, nach Reihenfolge im Katalog
     */
    public static function check(User $user, ?QuizAttempt $attempt = null): Collection
    {
        UserStat::firstOrCreate(['user_id' => $user->id]);

        return DB::transaction(function () use ($user, $attempt): Collection {
            $stats = UserStat::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $missing = Achievement::where('is_active', true)
                ->whereDoesntHave('unlocks', fn (Builder $unlocks) => $unlocks->where('user_id', $user->id))
                ->orderBy('sort')
                ->get();

            $values = new AchievementValues($user, $stats);
            $unlocked = $missing->filter(fn (Achievement $achievement) => $values->reached($achievement))
                ->filter(fn (Achievement $achievement) => DB::table('user_achievements')->insertOrIgnore([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now(),
                    'trophies_awarded' => $achievement->trophy_reward,
                    'quiz_attempt_id' => $attempt?->id,
                ]) === 1)
                ->values();

            $trophies = $unlocked->sum('trophy_reward');
            if ($trophies > 0) {
                $stats->increment('trophies', $trophies);
            }

            return $unlocked;
        });
    }
}
