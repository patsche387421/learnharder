<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Anfragen kommen wie von der Vue-App, damit Sanctum Session-Cookies nutzt
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->user = User::factory()->create([
            'email' => 'lena@htl.at',
            'gamer_tag' => 'lena42',
            'name' => 'Lena Huber',
            'brainpower_current' => 80,
            'brainpower_refilled_on' => now('Europe/Vienna')->toDateString(),
        ]);
        // 8.450 EP: Prestige 1, im Zyklus 450 EP = Level 15 (ab 419, Level 16 ab 465)
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => 8450]);
    }

    public function test_login_mit_richtigem_passwort(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.gamer_tag', 'lena42');

        $this->assertAuthenticatedAs($this->user, 'web');
    }

    public function test_login_mit_falschem_passwort(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'falsch'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'E-Mail oder Passwort stimmt nicht.']);

        $this->assertGuest('web');
    }

    public function test_login_sperrt_nach_fuenf_fehlversuchen(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'falsch'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])
            ->assertTooManyRequests();
    }

    public function test_me_ohne_login(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_liefert_nur_gamer_tag_und_spielwerte(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertExactJson(['data' => [
                'gamer_tag' => 'lena42',
                'school_class' => null,
                'roles' => [],
                'level' => 15,
                'rank' => 'bronze',
                'prestige' => 1,
                'xp' => ['total' => 8450, 'level_start' => 8419, 'next_level' => 8465],
                'streak' => ['current' => 0, 'longest' => 0],
                'brainpower' => ['current' => 80, 'max' => 200],
            ]]);
    }

    public function test_me_ohne_statistik_startet_bei_level_1(): void
    {
        $neu = User::factory()->create();

        $this->actingAs($neu)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.rank', 'bronze')
            ->assertJsonPath('data.prestige', 0)
            ->assertJsonPath('data.xp', ['total' => 0, 'level_start' => 0, 'next_level' => 8])
            ->assertJsonPath('data.streak', ['current' => 0, 'longest' => 0]);
    }

    public function test_logout_beendet_die_session(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])->assertOk();

        $this->postJson('/api/v1/logout')->assertNoContent();

        $this->assertGuest('web');
    }
}
