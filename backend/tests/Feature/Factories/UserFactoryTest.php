<?php

namespace Tests\Feature\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Die Bausteine der UserFactory ergeben genau den Zustand, den sie versprechen. Geprüft über /me, so wie die App ihn sieht.
 */
class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));
    }

    private function me(User $user): TestResponse
    {
        return $this->actingAs($user)->getJson('/api/v1/me')->assertOk();
    }

    public function test_ohne_bausteine_level_1_mit_gamer_tag(): void
    {
        $user = User::factory()->create();

        $this->me($user)
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.prestige', 0)
            ->assertJsonPath('data.streak', ['current' => 0, 'longest' => 0, 'today' => false])
            ->assertJsonPath('data.booster', null);
        $this->assertNotEmpty($user->gamer_tag);
        $this->assertFalse($user->hasRole('student'));
    }

    public function test_student_hat_die_rolle(): void
    {
        $this->assertTrue(User::factory()->student()->create()->hasRole('student'));
    }

    public function test_level_beginnt_genau_am_anfang_des_levels(): void
    {
        $this->me(User::factory()->level(25)->create())
            ->assertJsonPath('data.level', 25)
            ->assertJsonPath('data.rank', 'silver')
            ->assertJsonPath('data.prestige', 0)
            ->assertJsonPath('data.xp.total', 941)
            ->assertJsonPath('data.xp.level_start', 941);

        $this->me(User::factory()->level(100)->create())->assertJsonPath('data.level', 100)->assertJsonPath('data.prestige', 0);
    }

    public function test_level_mit_prestige(): void
    {
        $this->me(User::factory()->level(1, prestige: 2)->create())
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.prestige', 2)
            ->assertJsonPath('data.xp.total', 16000);
    }

    public function test_streak_ist_ohne_today_in_gefahr(): void
    {
        $this->me(User::factory()->streak(6)->create())
            ->assertJsonPath('data.streak', ['current' => 6, 'longest' => 6, 'today' => false]);
    }

    public function test_streak_heute_gelernt_und_laengster(): void
    {
        $this->me(User::factory()->streak(3, today: true, longest: 9)->create())
            ->assertJsonPath('data.streak', ['current' => 3, 'longest' => 9, 'today' => true]);
    }

    public function test_brainpower_bleibt_weil_heute_schon_aufgeladen(): void
    {
        $this->me(User::factory()->level(25)->brainpower(20)->create())
            ->assertJsonPath('data.brainpower', ['current' => 20, 'max' => 125]);
    }

    public function test_booster_laeuft_mit_restzeit(): void
    {
        $this->me(User::factory()->booster(2.0, minutes: 23)->create())
            ->assertJsonPath('data.booster', ['multiplier' => 2, 'ends_at' => '2026-10-17T12:23:00+00:00']);
    }

    public function test_bausteine_lassen_sich_kombinieren(): void
    {
        $user = User::factory()->student()->level(100)->streak(6)->brainpower(20)->booster(1.5, minutes: 10)->create();

        $this->assertTrue($user->hasRole('student'));
        $this->me($user)
            ->assertJsonPath('data.level', 100)
            ->assertJsonPath('data.streak.current', 6)
            ->assertJsonPath('data.brainpower.current', 20)
            ->assertJsonPath('data.booster.multiplier', 1.5);
    }
}
