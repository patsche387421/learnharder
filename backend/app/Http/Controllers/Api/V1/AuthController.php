<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AccountRuleViolation;
use App\Http\Controllers\Controller;
use App\Http\Resources\MeResource;
use App\Support\Accounts;
use App\Support\Brainpower;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): MeResource
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // Dass ein Konto gesperrt ist, erfährt nur, wer das richtige Passwort kennt (E-027)
        $user = $guard->getLastAttempted();

        if ($user->isDeactivated()) {
            throw ValidationException::withMessages(['email' => __('accounts.deactivated')]);
        }

        $guard->login($user);
        $request->session()->regenerate();

        Brainpower::refill($request->user());

        return new MeResource($request->user());
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): MeResource
    {
        Brainpower::refill($request->user());

        return new MeResource($request->user());
    }

    /** Eigenes Konto löschen, nur mit Passwort (E-027). Danach gibt es nichts mehr von der Person, außer in den Backups bis zu deren Frist. */
    public function destroy(Request $request): Response
    {
        $request->validate(['password' => ['required', 'string']], attributes: ['password' => 'Passwort']);
        $user = $request->user();

        if (! Hash::check($request->string('password'), $user->getAuthPassword())) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        try {
            Accounts::deleteOwn($user);
        } catch (AccountRuleViolation $e) {
            abort(Response::HTTP_FORBIDDEN, $e->getMessage());
        }

        // Nicht logout(): Das erneuert das Remember-Token mit save() und würde das gelöschte Konto neu anlegen
        Auth::guard('web')->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
