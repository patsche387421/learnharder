<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Booster;
use App\Support\Brainpower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shop (E-037): Brainpower, laufender Booster und die Angebote aus config/gamification.php.
 * Die App kennt keine Preise selbst, damit eine Änderung nach der Abstimmung mit den Lehrkräften nur hier passiert.
 */
class ShopController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        Brainpower::refill($user);

        $boosters = collect(config('gamification.boosters'))
            ->map(fn (array $booster, string $kind) => ['kind' => $kind, ...$booster])
            ->values();

        return response()->json(['data' => [
            'brainpower' => $user->brainpower(),
            'booster' => Booster::current($user),
            'boosters' => $boosters,
        ]]);
    }
}
