<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\DailyXp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Was die Vue-App über den eingeloggten Nutzer erfährt. Bewusst ohne E-Mail und Klarnamen.
 * Level, Rang und Prestige rechnet der Server aus den Gesamt-EP (User::progression), den Streak aus den Lerntagen (UserStat).
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
            'streak' => [
                'current' => $this->stats?->currentStreak(DailyXp::today()) ?? 0,
                'longest' => $this->stats->longest_streak ?? 0,
            ],
            'brainpower' => [
                'current' => $this->brainpower_current,
                'max' => $this->brainpower_max,
            ],
        ];
    }
}
