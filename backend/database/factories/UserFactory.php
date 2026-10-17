<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserStat;
use App\Support\DailyXp;
use App\Support\Progression;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Testnutzer:innen. Die Bausteine lassen sich kombinieren, z. B.
 * User::factory()->student()->level(100)->streak(6)->brainpower(20)->booster(2.0, minutes: 23)->create().
 * Werte aus user_stats (EP, Streak) schreibt afterCreating, weil sie in einer eigenen Tabelle stehen.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'gamer_tag' => fake()->unique()->bothify('Spieler####??'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /** Rolle student. Ohne Klasse sieht die Person keine Inhalte (E-024). */
    public function student(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Role::findOrCreate('student', 'web')));
    }

    /**
     * Schüler:in mit aktiver Einschreibung in $class. Ohne Angabe eine neue Klasse mit eigener Schule
     * (SchoolClassFactory). Für mehrere Personen in einer Klasse dieselbe Klasse übergeben.
     */
    public function inClass(?SchoolClass $class = null): static
    {
        return $this->student()->afterCreating(function (User $user) use ($class) {
            ($class ?? SchoolClass::factory()->create())->enrollments()->create(['user_id' => $user->id]);
            $user->unsetRelation('activeEnrollment');
        });
    }

    /**
     * Gesamt-EP genau am Anfang von Level $level im Prestige $prestige. Rechnet mit Progression, stimmt also auch,
     * wenn sich die Kurve ändert.
     */
    public function level(int $level, int $prestige = 0): static
    {
        $total = $prestige * Progression::CYCLE_XP + Progression::threshold($level);

        return $this->withStats(['total_xp' => $total]);
    }

    /** Gesamt-EP direkt, z. B. 10 EP vor dem nächsten Prestige: xp(Progression::CYCLE_XP - 10). */
    public function xp(int $total): static
    {
        return $this->withStats(['total_xp' => $total]);
    }

    /**
     * Laufender Streak von $days Tagen (Tag in Wien). Ohne $today zuletzt gestern gelernt, der Streak ist also in Gefahr.
     * Der längste Streak ist mindestens so lang.
     */
    public function streak(int $days, bool $today = false, ?int $longest = null): static
    {
        // Der Tag zählt beim Anlegen, nicht beim Aufruf: Tests stellen die Uhr oft erst danach (travelTo)
        return $this->withStats(function () use ($days, $today, $longest) {
            $last = CarbonImmutable::parse(DailyXp::today());

            return [
                'current_streak' => $days,
                'longest_streak' => max($days, $longest ?? $days),
                'last_study_date' => ($today ? $last : $last->subDay())->toDateString(),
            ];
        });
    }

    /** Brainpower $current, heute schon aufgeladen. Sonst lädt der Server sie beim ersten Lesen sofort auf (E-036). */
    public function brainpower(int $current): static
    {
        return $this->state(fn () => ['brainpower_current' => $current, 'brainpower_refilled_on' => DailyXp::today()]);
    }

    /** Laufender EP-Booster mit Faktor $multiplier, noch $minutes Minuten (E-037). */
    public function booster(float $multiplier, int $minutes): static
    {
        return $this->state(fn () => ['booster_multiplier' => $multiplier, 'booster_ends_at' => now()->addMinutes($minutes)]);
    }

    /**
     * Ergänzt user_stats, mehrere Bausteine schreiben in dieselbe Zeile.
     *
     * @param  array<string, mixed>|Closure(): array<string, mixed>  $values
     */
    private function withStats(array|Closure $values): static
    {
        return $this->afterCreating(function (User $user) use ($values) {
            UserStat::updateOrCreate(['user_id' => $user->id], $values instanceof Closure ? $values() : $values);
            $user->unsetRelation('stats');
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
