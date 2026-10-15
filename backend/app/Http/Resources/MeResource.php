<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Was die Vue-App über den eingeloggten Nutzer erfährt. Bewusst ohne E-Mail und Klarnamen.
 * Level und Rang kommen dazu, sobald die Level-Formel festgelegt ist (docs/plans/README.md, Widerspruch 4).
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
            'roles' => $this->getRoleNames(),
            'total_xp' => $this->stats->total_xp ?? 0,
            'prestige' => $this->stats->prestige_level ?? 0,
            'brainpower' => [
                'current' => $this->brainpower_current,
                'max' => $this->brainpower_max,
            ],
        ];
    }
}
