<?php

namespace App\Http\Resources;

use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Erfolg für das Raster im Profil (Design `Achievement`). unlock: die eigene Vergabe oder null.
 * Geheime, noch nicht erreichte Erfolge gehen ohne Titel, Bedingung, Icon, Stufe und Trophäen hinaus (E-044).
 * level: Stufe für die Zahl an der Kachel, nur bei einem Schwellwert über 1.
 *
 * @mixin Achievement
 */
class AchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserAchievement|null $unlock */
        $unlock = $this->relationLoaded('unlock') ? $this->getRelation('unlock') : null;
        $hidden = $this->is_secret && $unlock === null;

        return [
            'id' => $this->id,
            'secret' => $this->is_secret,
            'title' => $hidden ? null : $this->title,
            'description' => $hidden ? null : $this->description,
            'icon_name' => $hidden ? null : $this->icon_name,
            'subject' => $hidden ? null : $this->subject?->slug, // Fach-Icon beim Fach-Level in einem bestimmten Fach
            'level' => $hidden || $this->threshold <= 1 ? null : $this->threshold,
            'trophies' => $hidden ? null : ($unlock?->trophies_awarded ?? $this->trophy_reward),
            'unlocked_at' => $unlock?->unlocked_at?->toIso8601String(),
        ];
    }
}
