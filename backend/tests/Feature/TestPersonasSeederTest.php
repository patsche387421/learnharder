<?php

namespace Tests\Feature;

use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSchoolSeeder;
use Database\Seeders\TestPersonasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Die Testpersonen haben genau den Zustand, für den sie da sind, und ein neuer Lauf setzt sie zurück.
 */
class TestPersonasSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));
    }

    /** Wie in der Entwicklung: Rollen und Demo-Konten, ein Fach statt des Imports, Demo-Schule, Testpersonen. */
    private function seedAll(): void
    {
        $this->seed();
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $pos->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen'], 'status' => 'published']);
        $this->seed(DemoSchoolSeeder::class);
        $this->seed(TestPersonasSeeder::class);
    }

    private function persona(string $tag): User
    {
        return User::where('email', strtolower($tag).'@test.learnharder.app')->sole();
    }

    private function me(string $tag): TestResponse
    {
        $this->flushSession();

        return $this->actingAs($this->persona($tag))->getJson('/api/v1/me');
    }

    public function test_alle_sieben_mit_gamer_tag_ohne_namen_und_mit_passwort(): void
    {
        $this->seedAll();

        $personas = User::where('email', 'like', '%@test.learnharder.app')->orderBy('id')->get();
        $this->assertSame(['Neuling', 'Mittendrin', 'Boosterfan', 'FastPrestige', 'PrestigeProfi', 'OhneKlasse', 'Gesperrt'], $personas->pluck('gamer_tag')->all());
        $this->assertTrue($personas->every(fn (User $user) => $user->name === null && $user->hasRole('student')));

        $this->postJson('/api/v1/login', ['email' => 'neuling@test.learnharder.app', 'password' => 'learnharder123'])->assertOk();
    }

    public function test_zustaende_wie_versprochen(): void
    {
        $this->seedAll();

        $this->me('Neuling')->assertOk()
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.school_class', ['name' => '2AHIT'])
            ->assertJsonPath('data.streak.current', 0)
            ->assertJsonPath('data.brainpower', ['current' => 100, 'max' => 100]);

        $this->me('Mittendrin')->assertOk()
            ->assertJsonPath('data.level', 12)
            ->assertJsonPath('data.streak', ['current' => 6, 'longest' => 6, 'today' => false])
            ->assertJsonPath('data.brainpower.current', 15);

        $this->me('Boosterfan')->assertOk()
            ->assertJsonPath('data.level', 30)
            ->assertJsonPath('data.rank', 'silver')
            ->assertJsonPath('data.streak.today', true)
            ->assertJsonPath('data.booster', ['multiplier' => 2, 'ends_at' => '2026-10-17T12:25:00+00:00']);

        $this->me('FastPrestige')->assertOk()
            ->assertJsonPath('data.level', 100)
            ->assertJsonPath('data.prestige', 0)
            ->assertJsonPath('data.xp.next_level', 8000)
            ->assertJsonPath('data.xp.total', 7990);

        $this->me('PrestigeProfi')->assertOk()
            ->assertJsonPath('data.level', 10)
            ->assertJsonPath('data.prestige', 2)
            ->assertJsonPath('data.brainpower.max', 200)
            ->assertJsonPath('data.streak', ['current' => 40, 'longest' => 55, 'today' => false]);

        $this->me('OhneKlasse')->assertOk()->assertJsonPath('data.school_class', null);

        $this->me('Gesperrt')->assertForbidden()->assertJsonPath('reason', 'account_deactivated');
    }

    public function test_wer_einen_streak_hat_hat_am_letzten_lerntag_eine_lerneinheit_abgeschlossen(): void
    {
        $this->seedAll();

        $hub = function (string $tag) {
            $this->flushSession();

            return $this->actingAs($this->persona($tag))->getJson('/api/v1/hub')->assertOk();
        };

        // Streak seit gestern: gestern 17 Uhr in Wien (gespeichert in UTC). Heute gelernt: gerade eben
        $hub('Mittendrin')->assertJsonPath('data.last_study_at', '2026-10-16T15:00:00+00:00');
        $hub('PrestigeProfi')->assertJsonPath('data.last_study_at', '2026-10-16T15:00:00+00:00');
        $hub('Boosterfan')->assertJsonPath('data.last_study_at', '2026-10-17T12:00:00+00:00');
        $hub('FastPrestige')->assertJsonPath('data.last_study_at', '2026-10-17T12:00:00+00:00');

        // Ohne Streak kein Verlauf, der Hub sieht den ersten Besuch
        $hub('Neuling')->assertJsonPath('data.last_study_at', null);
        $this->assertSame(4, QuizAttempt::whereHas('user', fn ($users) => $users->where('email', 'like', '%@test.learnharder.app'))->count());
        $this->assertTrue(QuizAttempt::all()->every(fn (QuizAttempt $attempt) => $attempt->topic->slug === 'schleifen' && $attempt->xp_earned === 0));
    }

    public function test_neuer_lauf_setzt_zurueck(): void
    {
        $this->seedAll();
        $this->actingAs($this->persona('Mittendrin'))->postJson('/api/v1/boosters', ['kind' => 'x1_5_30'])->assertUnprocessable();
        $this->flushSession();
        $this->actingAs($this->persona('Neuling'))->postJson('/api/v1/boosters', ['kind' => 'x2_30'])->assertCreated();

        $this->seed(TestPersonasSeeder::class);

        $this->assertSame(7, User::where('email', 'like', '%@test.learnharder.app')->count());
        $this->me('Neuling')->assertJsonPath('data.booster', null)->assertJsonPath('data.brainpower.current', 100);
    }

    public function test_ohne_demo_schule_oder_in_produktion_passiert_nichts(): void
    {
        $this->seed();
        $this->artisan('db:seed', ['--class' => TestPersonasSeeder::class])
            ->expectsOutputToContain('zuerst `db:seed`, den Import und den DemoSchoolSeeder')
            ->assertSuccessful();

        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--class' => TestPersonasSeeder::class, '--force' => true])
            ->expectsOutputToContain('nur in local/testing')
            ->assertSuccessful();

        $this->assertSame(0, User::where('email', 'like', '%@test.learnharder.app')->count());
    }
}
