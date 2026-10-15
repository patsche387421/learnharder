<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Progression;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Was die Vue-App über den eingeloggten Nutzer erfährt. Bewusst ohne E-Mail und Klarnamen.
 * Level, Rang und Prestige rechnet der Server aus den Gesamt-EP (App\Support\Progression).
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
        $progress = Progression::fromTotalXp($this->stats->total_xp ?? 0);

        return [
            'gamer_tag' => $this->gamer_tag,
            'roles' => $this->getRoleNames(),
            'level' => $progress->level,
            'rank' => $progress->rank,
            'prestige' => $progress->prestige,
            'xp' => [
                'total' => $progress->total,
                'level_start' => $progress->levelStart,
                'next_level' => $progress->nextLevel,
            ],
            'brainpower' => [
                'current' => $this->brainpower_current,
                'max' => $this->brainpower_max,
            ],
        ];
    }
}
