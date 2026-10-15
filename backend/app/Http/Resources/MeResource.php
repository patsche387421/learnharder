<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Was die Vue-App über den eingeloggten Nutzer erfährt. Bewusst ohne E-Mail und Klarnamen.
 * Level, Rang und Prestige rechnet der Server aus den Gesamt-EP (User::progression).
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
            ...$this->progression()->toArray(),
            'brainpower' => [
                'current' => $this->brainpower_current,
                'max' => $this->brainpower_max,
            ],
        ];
    }
}
