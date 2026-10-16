<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 6-stellige Codes per Mail (E-025, Plan Schule §4): nur als HMAC im Cache, 10 Minuten gültig, höchstens 5 Versuche.
 * Ein Code gilt für einen Zweck (register, password) und eine E-Mail. Ein neuer Code ersetzt den alten.
 */
class EmailCode
{
    public static function issue(string $purpose, string $email): string
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $ttl = now()->addMinutes(config('accounts.code_minutes'));

        Cache::put(self::key('code', $purpose, $email), self::hash($code), $ttl);
        Cache::put(self::key('attempts', $purpose, $email), 0, $ttl);

        return $code;
    }

    /** Ein richtiger Code ist danach verbraucht. Nach zu vielen Versuchen hilft nur ein neuer Code. */
    public static function check(string $purpose, string $email, string $code): bool
    {
        $hash = Cache::get(self::key('code', $purpose, $email));

        if ($hash === null) {
            return false;
        }

        // Zählt atomar, damit parallele Versuche das Limit nicht umgehen
        if (Cache::increment(self::key('attempts', $purpose, $email)) > config('accounts.code_attempts')) {
            self::forget($purpose, $email);

            return false;
        }

        if (! hash_equals($hash, self::hash($code))) {
            return false;
        }

        self::forget($purpose, $email);

        return true;
    }

    private static function forget(string $purpose, string $email): void
    {
        Cache::forget(self::key('code', $purpose, $email));
        Cache::forget(self::key('attempts', $purpose, $email));
    }

    /** Im Schlüssel steht die E-Mail nur als Hash. */
    private static function key(string $part, string $purpose, string $email): string
    {
        return "email-code:{$part}:{$purpose}:".hash('sha256', Str::lower($email));
    }

    private static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }
}
