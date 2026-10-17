<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Booster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Was die Vue-App über den eingeloggten Nutzer erfährt. Bewusst ohne E-Mail und Klarnamen.
 * Level, Rang und Prestige rechnet der Server aus den Gesamt-EP (User::progression), den Streak aus den Lerntagen (UserStat),
 * das Brainpower-Maximum aus Level und Prestige (App\Support\Brainpower).
 *
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'gamer_tag' => $this->gamer_tag,
            // Nur die eigene Klasse, für den Hinweis in der App, wenn noch keine Klasse da ist
            'school_class' => ($class = $this->activeEnrollment?->schoolClass) ? ['name' => $class->name] : null,
            'roles' => $this->getRoleNames(),
            ...$this->progression()->toArray(),
            'streak' => $this->streak(),
            'brainpower' => $this->brainpower(),
            'booster' => Booster::current($this->resource),
            'trophies' => $this->stats->trophies ?? 0, // aus Erfolgen und fehlerfreien Herausforderungen (E-046)
        ];
    }
}
