<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Brainpower (Ökonomie-Modell §3, E-036): Maximum aus Level und Prestige, Aufladen einmal je Kalendertag in Wien.
 * Aufgeladen wird beim ersten Lesen an einem neuen Tag, nicht per Scheduler. Verpasste Tage zählen mit, bis zum Maximum.
 */
final class Brainpower
{
    public static function max(Progression $progression): int
    {
        $config = config('gamification.brainpower');
        if ($progression->prestige > 0) {
            return $config['max_cap'];
        }

        return min($config['max_cap'], $config['max_base'] + intdiv($progression->level, $config['max_step_levels']) * $config['max_step']);
    }

    /** Aufladen an einem Tag: Basis plus Streak-Bonus. */
    public static function dailyRefill(int $streak): int
    {
        $config = config('gamification.brainpower');

        return $config['daily_refill'] + min($streak * $config['streak_bonus_per_day'], $config['streak_bonus_max']);
    }

    /**
     * Lädt auf, wenn seit dem letzten Aufladen ein neuer Tag in Wien begonnen hat. Ohne bisheriges Aufladen
     * (neues Konto, Konten von vor G2) ist der Akku voll. Die Sperre verhindert doppeltes Aufladen bei parallelen Anfragen.
     */
    public static function refill(User $user): void
    {
        $today = DailyXp::today();
        $max = self::max($user->progression());
        $streak = $user->streak()['current'];

        DB::transaction(function () use ($user, $today, $max, $streak): void {
            $locked = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail(['id', 'brainpower_current', 'brainpower_refilled_on']);
            $last = $locked->brainpower_refilled_on?->toDateString();

            if ($last === null) {
                $current = $max;
            } else {
                $days = (int) CarbonImmutable::parse($last)->diffInDays(CarbonImmutable::parse($today));
                if ($days <= 0) {
                    $user->forceFill($locked->only(['brainpower_current', 'brainpower_refilled_on']))->syncOriginal();

                    return;
                }
                $current = min($max, $locked->brainpower_current + $days * self::dailyRefill($streak));
            }

            $locked->forceFill(['brainpower_current' => $current, 'brainpower_refilled_on' => $today])->save();
            $user->forceFill($locked->only(['brainpower_current', 'brainpower_refilled_on']))->syncOriginal();
        });
    }
}
