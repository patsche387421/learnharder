<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * XP-Booster (E-010, Ökonomie-Modell §3.3): mit Brainpower kaufen, höchstens einer gleichzeitig, Zeit in Echtzeit.
 * Am Konto steht nur der laufende Booster. Wie viel er bringt, merkt sich jede Antwort selbst (question_answers.xp_multiplier).
 */
final class Booster
{
    /** Faktor des Boosters, der gerade läuft, sonst 1. */
    public static function multiplier(User $user): float
    {
        return self::running($user) ? (float) $user->booster_multiplier : 1.0;
    }

    /** @return array{multiplier: float, ends_at: string}|null */
    public static function current(User $user): ?array
    {
        return self::running($user)
            ? ['multiplier' => (float) $user->booster_multiplier, 'ends_at' => $user->booster_ends_at->toIso8601String()]
            : null;
    }

    /** EP einer richtigen Antwort mit Faktor, halbe Punkte aufgerundet (3 × 1,5 = 5). */
    public static function apply(int $xp, float $multiplier): int
    {
        return (int) round($xp * $multiplier);
    }

    /**
     * Startet einen Booster aus config('gamification.boosters'). Lädt vorher auf, prüft unter Sperre, ob schon einer läuft
     * und ob die Brainpower reicht. Ein doppelter Tipp kostet deshalb nicht doppelt.
     */
    public static function start(User $user, string $kind): void
    {
        $booster = config("gamification.boosters.{$kind}");
        Brainpower::refill($user);

        DB::transaction(function () use ($user, $booster): void {
            $locked = User::whereKey($user->getKey())->lockForUpdate()
                ->firstOrFail(['id', 'brainpower_current', 'booster_multiplier', 'booster_ends_at']);

            if (self::running($locked)) {
                throw new ConflictHttpException('Es läuft schon ein Booster.');
            }
            if ($locked->brainpower_current < $booster['cost']) {
                throw ValidationException::withMessages(['kind' => 'Dafür reicht deine Brainpower gerade nicht. Morgen lädt sie sich wieder auf.']);
            }

            $locked->forceFill([
                'brainpower_current' => $locked->brainpower_current - $booster['cost'],
                'booster_multiplier' => $booster['multiplier'],
                'booster_ends_at' => now()->addMinutes($booster['minutes']),
            ])->save();
            $user->forceFill($locked->only(['brainpower_current', 'booster_multiplier', 'booster_ends_at']))->syncOriginal();
        });
    }

    private static function running(User $user): bool
    {
        return $user->booster_ends_at !== null && $user->booster_ends_at->isFuture();
    }
}
