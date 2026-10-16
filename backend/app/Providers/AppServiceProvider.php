<?php

namespace App\Providers;

use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Forms\Components\Field;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin darf alles, die Policies regeln nur die übrigen Rollen. Ausnahme: fremde Lerneinheiten,
        // denn deren EP gehören der Person, die gelernt hat. Gesperrte Themen starten darf Admin weiter.
        Gate::before(function (User $user, string $ability, array $arguments) {
            if (($arguments[0] ?? null) instanceof QuizAttempt) {
                return null;
            }

            return $user->hasRole('admin') ? true : null;
        });

        // Letzter Login in App und CMS, für die Löschfrist nach 12 Monaten (E-028)
        Event::listen(Login::class, fn (Login $event) => $event->user->forceFill(['last_login_at' => now()])->saveQuietly());

        // Höchstens 5 Login-Versuche pro Minute je E-Mail und IP
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Filament schreibt das Label in Fehlermeldungen klein („Das Feld titel …“). Deutsche Nomen bleiben groß.
        Field::configureUsing(fn (Field $field) => $field->validationAttribute(
            fn (Field $component): ?string => is_string($label = $component->getLabel()) ? $label : null,
        ));
    }
}
