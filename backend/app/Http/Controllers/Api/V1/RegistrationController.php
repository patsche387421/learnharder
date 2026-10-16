<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeResource;
use App\Mail\AlreadyRegistered;
use App\Mail\RegistrationCode;
use App\Models\SchoolEmailDomain;
use App\Models\User;
use App\Support\EmailCode;
use App\Support\GamerTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Registrierung für Schüler:innen ab 14 in drei Schritten (E-025, Plan Schule §4):
 * Code anfordern → Code prüfen (ergibt einen Einmal-Token) → Gamer-Tag, Passwort und Zustimmung.
 */
class RegistrationController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $email = $this->email($request);

        if (! SchoolEmailDomain::where('domain', Str::after($email, '@'))->where('allows_registration', true)->exists()) {
            throw ValidationException::withMessages(['email' => 'Registrieren geht nur mit deiner Schul-Mail.']);
        }

        // Gibt es die Adresse schon, kommt ein Hinweis statt eines Codes. Die Antwort bleibt dieselbe.
        if ($this->existing($email)) {
            Mail::to($email)->send(new AlreadyRegistered);
        } else {
            Mail::to($email)->send(new RegistrationCode(EmailCode::issue('register', $email)));
        }

        return response()->json(['message' => 'Wir haben dir einen Code geschickt. Schau in dein Schulpostfach.'], 202);
    }

    public function verify(Request $request): JsonResponse
    {
        $email = $this->email($request);
        $request->validate(['code' => ['required', 'string']]);

        if (! EmailCode::check('register', $email, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'Der Code stimmt nicht oder ist abgelaufen.']);
        }

        $token = Str::random(64);
        Cache::put($this->tokenKey($token), $email, now()->addMinutes(config('accounts.registration_token_minutes')));

        return response()->json(['token' => $token]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'gamer_tag' => ['required', ...GamerTag::rules()],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_14' => ['accepted'],
            'accepts_terms' => ['accepted'],
        ], [
            'gamer_tag.regex' => 'Nur Buchstaben, Ziffern, _ und -.',
            'is_14.accepted' => 'Bestätige, dass du mindestens 14 bist.',
            'accepts_terms.accepted' => 'Stimm bitte der Datenschutzerklärung und den Nutzungsregeln zu.',
        ], [
            'gamer_tag' => 'Gamer-Tag',
            'password' => 'Passwort',
        ]);

        // Erst nach den übrigen Feldern einlösen: Ist der Gamer-Tag vergeben, bleibt der Token gültig
        $user = DB::transaction(function () use ($data) {
            $email = Cache::pull($this->tokenKey($data['token']));

            if ($email === null) {
                throw ValidationException::withMessages(['token' => 'Die Bestätigung ist abgelaufen. Fang bitte nochmal mit deiner E-Mail an.']);
            }

            if ($this->existing($email)) {
                throw ValidationException::withMessages(['token' => 'Für diese E-Mail gibt es schon ein Konto.']);
            }

            $user = new User;
            $user->forceFill([
                'email' => $email,
                'gamer_tag' => $data['gamer_tag'],
                'password' => $data['password'],
                'email_verified_at' => now(),
                'consented_at' => now(),
                'terms_version' => config('legal.version'),
            ])->save();
            $user->assignRole(Role::findOrCreate('student', 'web'));

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new MeResource($user))->response()->setStatusCode(201);
    }

    private function email(Request $request): string
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        return Str::lower(trim($request->string('email')));
    }

    private function existing(string $email): bool
    {
        return User::whereRaw('lower(email) = ?', [$email])->exists();
    }

    /** Im Cache steht nur der Hash des Tokens. */
    private function tokenKey(string $token): string
    {
        return 'registration-token:'.hash('sha256', $token);
    }
}
