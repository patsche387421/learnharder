<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Str;

/**
 * Regeln für den Gamer-Tag, gleich im CMS und in der Registrierung (E-025).
 * Er ist der einzige öffentliche Name einer Person, Groß- und Kleinschreibung zählen beim Vergleich nicht.
 */
class GamerTag
{
    /** @return list<mixed> */
    public static function rules(?int $ignoreUserId = null): array
    {
        return [
            'string',
            'min:3',
            'max:20',
            'regex:/^[A-Za-z0-9_-]+$/',
            function (string $attribute, mixed $value, Closure $fail) use ($ignoreUserId) {
                $taken = User::whereRaw('lower(gamer_tag) = ?', [Str::lower((string) $value)])
                    ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
                    ->exists();

                if ($taken) {
                    $fail('Diesen Gamer-Tag gibt es schon.');
                }
            },
        ];
    }
}
