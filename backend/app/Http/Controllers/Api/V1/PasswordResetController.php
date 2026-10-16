<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\PasswordCode;
use App\Models\User;
use App\Support\Accounts;
use App\Support\EmailCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * „Passwort vergessen“ mit Code per Mail (E-025). Auch der erste Weg zum Passwort für Konten,
 * die der Admin angelegt hat. Danach endet jede andere Anmeldung (E-027).
 */
class PasswordResetController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $email = $this->email($request);

        // Einen Code gibt es nur für aktive Konten. Die Antwort verrät nicht, ob es das Konto gibt.
        if ($this->activeUser($email)) {
            Mail::to($email)->send(new PasswordCode(EmailCode::issue('password', $email)));
        }

        return response()->json(['message' => 'Wenn es zu dieser Adresse ein Konto gibt, kommt gleich ein Code.'], 202);
    }

    public function reset(Request $request): Response
    {
        $email = $this->email($request);
        $data = $request->validate([
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], attributes: ['password' => 'Passwort']);

        // Erst die Felder prüfen, dann den Code: Ein zu kurzes Passwort kostet keinen Versuch
        $user = EmailCode::check('password', $email, $data['code']) ? $this->activeUser($email) : null;

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'Der Code stimmt nicht oder ist abgelaufen.']);
        }

        $user->forceFill(['password' => $data['password']])->save();
        Accounts::logoutEverywhere($user);

        return response()->noContent();
    }

    private function email(Request $request): string
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        return Str::lower(trim($request->string('email')));
    }

    private function activeUser(string $email): ?User
    {
        return User::whereRaw('lower(email) = ?', [$email])->whereNull('deactivated_at')->first();
    }
}
