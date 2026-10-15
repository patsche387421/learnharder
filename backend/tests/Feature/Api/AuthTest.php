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
            'first_name' => 'Lena',
            'last_name' => 'Huber',
            'brainpower_current' => 80,
            'brainpower_max' => 100,
        ]);
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => 450, 'prestige_level' => 1]);
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
                'roles' => [],
                'total_xp' => 450,
                'prestige' => 1,
                'brainpower' => ['current' => 80, 'max' => 100],
            ]]);
    }

    public function test_logout_beendet_die_session(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])->assertOk();

        $this->postJson('/api/v1/logout')->assertNoContent();

        $this->assertGuest('web');
    }
}
