<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Booster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * XP-Booster kaufen (E-010). Was er kostet und wie lange er läuft, steht in config/gamification.php.
 */
class BoosterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'string', Rule::in(array_keys(config('gamification.boosters')))],
        ]);

        $user = $request->user();
        Booster::start($user, $data['kind']);

        return response()->json(['data' => [
            'brainpower' => $user->brainpower(),
            'booster' => Booster::current($user),
        ]], 201);
    }
}
