<?php

namespace App\Support;

use App\Exceptions\AccountRuleViolation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Konten sperren, entsperren, löschen und Rollen ändern (E-027).
 * Die Schutzregeln stehen hier und nicht in einer Policy, weil Gate::before Admins alles erlaubt.
 * Alles, was Admin-Rechte nehmen kann, muss über diese Klasse laufen.
 *
 * Der letzte aktive Admin ist dadurch geschützt, dass zwei Regeln zusammenwirken: Ändern darf nur ein aktiver Admin,
 * und niemand ändert sich selbst. Die handelnde Person bleibt also immer als aktiver Admin übrig.
 */
class Accounts
{
    /** Personal: wird gesperrt, nicht gelöscht, und löscht sich nicht selbst. */
    public const STAFF_ROLES = ['admin', 'autor', 'tester', 'teacher'];

    public static function deactivate(User $user, User $by, string $reason): void
    {
        self::guarded($by, function () use ($user, $by, $reason) {
            self::refuseSelf($user, $by, 'Du kannst dich nicht selbst sperren.');

            $user->forceFill(['deactivated_at' => now(), 'deactivated_by' => $by->id, 'deactivation_reason' => $reason])->save();
            self::logoutEverywhere($user);
        });
    }

    public static function reactivate(User $user, User $by): void
    {
        self::guarded($by, fn () => $user->forceFill(['deactivated_at' => null, 'deactivated_by' => null, 'deactivation_reason' => null])->save());
    }

    /** @param  list<string>  $roles */
    public static function syncRoles(User $user, array $roles, User $by): void
    {
        self::guarded($by, function () use ($user, $by, $roles) {
            if ($user->hasRole('admin') && ! in_array('admin', $roles, true)) {
                self::refuseSelf($user, $by, 'Du kannst dir die Admin-Rolle nicht selbst nehmen.');
            }

            $user->syncRoles($roles);
        });
    }

    /** Admin löscht ein Konto, etwa auf Wunsch einer gesperrten Person. Personal wird nur gesperrt. */
    public static function deleteByAdmin(User $user, User $by): void
    {
        self::guarded($by, function () use ($user, $by) {
            self::refuseSelf($user, $by, 'Du kannst dich nicht selbst löschen.');

            if ($user->hasAnyRole(self::STAFF_ROLES)) {
                throw new AccountRuleViolation('Personal wird gesperrt, nicht gelöscht.');
            }

            self::logoutEverywhere($user);
            $user->delete();
        });
    }

    /** Beleidigender Gamer-Tag: neutraler Ersatz, die Person wählt später selbst einen neuen. */
    public static function resetGamerTag(User $user, User $by): string
    {
        return self::guarded($by, function () use ($user) {
            do {
                $tag = 'Spieler-'.random_int(10000, 99999);
            } while (User::whereRaw('lower(gamer_tag) = ?', [Str::lower($tag)])->exists());

            $user->forceFill(['gamer_tag' => $tag])->save();

            return $tag;
        });
    }

    /**
     * Beendet jede Anmeldung: Sessions in der Datenbank und das Remember-Cookie aus „Angemeldet bleiben“ im CMS.
     * API-Tokens gibt es nicht, die App nutzt Sanctum nur mit Session-Cookies.
     */
    public static function logoutEverywhere(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->setRememberToken(Str::random(60));
        $user->save();
    }

    /**
     * Sperrt alle Admin-Zeilen, bevor geprüft wird: Zwei gleichzeitige Änderungen laufen dann nacheinander.
     * Geprüft wird erst danach mit einer neuen Abfrage, damit sie sieht, was die erste Änderung gespeichert hat.
     * Sperren sich zwei Admins gleichzeitig gegenseitig, ist die zweite Person danach selbst gesperrt und wird abgewiesen.
     */
    private static function guarded(User $by, callable $change): mixed
    {
        return DB::transaction(function () use ($by, $change) {
            User::role('admin')->lockForUpdate()->get(['id']);

            if (! User::role('admin')->whereNull('deactivated_at')->whereKey($by->id)->exists()) {
                throw new AccountRuleViolation('Nur aktive Admins dürfen Konten verwalten.');
            }

            return $change();
        });
    }

    private static function refuseSelf(User $user, User $by, string $message): void
    {
        if ($user->is($by)) {
            throw new AccountRuleViolation($message);
        }
    }
}
