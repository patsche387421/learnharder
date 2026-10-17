<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Support\Achievements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Erfolge für das Profil (Konzept Spielwelt §6). Vorher wird nachgeprüft, damit neue Erfolge aus dem CMS
 * auch Konten erreichen, die sie schon erfüllen (E-042). Zu sehen sind alle aktiven und die eigenen deaktivierten.
 */
class AchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Achievements::check($user);

        $unlocks = $user->achievements()->get()->mapWithKeys(fn (Achievement $achievement) => [$achievement->id => $achievement->pivot]);
        $achievements = Achievement::with('subject:id,slug')
            ->where(fn (Builder $query) => $query->where('is_active', true)->orWhereIn('id', $unlocks->keys()))
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get()
            ->each(fn (Achievement $achievement) => $achievement->setRelation('unlock', $unlocks->get($achievement->id)));

        return AchievementResource::collection($achievements)->response();
    }
}
