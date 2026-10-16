<?php

namespace Tests\Feature\Accounts;

use App\Models\User;
use App\Support\Accounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Wirkung einer Sperre in App und CMS (E-027) und der letzte Login (E-028).
 */
class DeactivatedAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Anfragen kommen wie von der Vue-App, damit Sanctum Session-Cookies nutzt
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_gesperrte_person_kommt_mit_richtigem_passwort_nicht_hinein(): void
    {
        User::factory()->create(['email' => 'lena@htl.at', 'deactivated_at' => now()]);

        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Dein Konto ist gesperrt. Wende dich an deine Lehrkraft.']);

        $this->assertGuest('web');
    }

    public function test_falsches_passwort_verraet_die_sperre_nicht(): void
    {
        User::factory()->create(['email' => 'lena@htl.at', 'deactivated_at' => now()]);

        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'falsch'])
            ->assertJsonValidationErrors(['email' => 'E-Mail oder Passwort stimmt nicht.']);
    }

    public function test_sperre_wirkt_bei_der_naechsten_anfrage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/v1/me')->assertOk();

        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($user->fresh())->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Dein Konto ist gesperrt. Wende dich an deine Lehrkraft.')
            ->assertJsonPath('reason', 'account_deactivated');
        $this->assertGuest('web');
    }

    public function test_login_merkt_sich_den_zeitpunkt(): void
    {
        $this->travelTo('2026-10-16 10:05:00');
        $user = User::factory()->create(['email' => 'lena@htl.at']);

        $this->postJson('/api/v1/login', ['email' => 'lena@htl.at', 'password' => 'password'])->assertOk();

        $this->assertSame('2026-10-16 10:05:00', $user->fresh()->last_login_at->toDateTimeString());
    }

    public function test_gesperrte_autorin_kommt_nicht_mehr_ins_cms(): void
    {
        $autorin = $this->userWithRole('autor');
        $this->actingAs($autorin)->get('/admin')->assertOk();

        $autorin->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($autorin->fresh())->get('/admin')->assertForbidden();
    }

    public function test_sperre_entwertet_das_remember_cookie_im_cms(): void
    {
        $admin = $this->userWithRole('admin');
        $autorin = $this->userWithRole('autor', ['remember_token' => 'alt']);
        $cookie = fn () => [Auth::guard('web')->getRecallerName() => $autorin->id.'|alt|'.$autorin->getAuthPassword()];

        // Vorher meldet das Cookie die Autorin an
        $this->withCookies($cookie())->get('/admin')->assertOk();
        // Wie ein neuer Besuch, aber ohne logout(): logout würde das Remember-Token schon selbst erneuern.
        // forgetGuards, weil der Guard sich sonst merkt, dass er das Cookie schon einmal versucht hat.
        Auth::forgetGuards();
        $this->flushSession();

        Accounts::deactivate($autorin, $admin, 'Kündigung');
        Accounts::reactivate($autorin, $admin);

        // Auch nach dem Entsperren meldet das alte Cookie niemanden mehr an
        $this->withCookies($cookie())->get('/admin')->assertRedirect('/admin/login');
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
