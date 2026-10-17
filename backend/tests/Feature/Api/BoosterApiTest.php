<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BoosterApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));

        $this->user = User::factory()->create(['brainpower_current' => 100, 'brainpower_refilled_on' => '2026-10-17']);
    }

    private function buy(string $kind): TestResponse
    {
        return $this->actingAs($this->user)->postJson('/api/v1/boosters', ['kind' => $kind]);
    }

    public function test_ohne_login_gibt_es_keinen_booster(): void
    {
        $this->postJson('/api/v1/boosters', ['kind' => 'x2_30'])->assertUnauthorized();
    }

    public function test_kauf_zieht_brainpower_ab_und_startet_den_booster(): void
    {
        $this->buy('x2_30')
            ->assertCreated()
            ->assertExactJson(['data' => [
                'brainpower' => ['current' => 60, 'max' => 100],
                'booster' => ['multiplier' => 2, 'ends_at' => '2026-10-17T12:30:00+00:00'],
            ]]);

        $this->actingAs($this->user->fresh())->getJson('/api/v1/me')
            ->assertJsonPath('data.brainpower.current', 60)
            ->assertJsonPath('data.booster', ['multiplier' => 2, 'ends_at' => '2026-10-17T12:30:00+00:00']);
    }

    public function test_alle_booster_aus_dem_katalog(): void
    {
        $this->buy('x1_5_30')->assertCreated()->assertJsonPath('data.booster.multiplier', 1.5)->assertJsonPath('data.brainpower.current', 80);

        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:30', 'Europe/Vienna'));
        $this->buy('x2_60')->assertCreated()
            ->assertJsonPath('data.booster.ends_at', '2026-10-17T13:30:00+00:00')
            ->assertJsonPath('data.brainpower.current', 10);
    }

    public function test_unbekannter_booster_wird_abgelehnt(): void
    {
        $this->buy('x10_999')->assertJsonValidationErrors('kind');
        $this->assertSame(100, $this->user->fresh()->brainpower_current);
    }

    public function test_solange_einer_laeuft_kein_zweiter(): void
    {
        $this->buy('x1_5_30')->assertCreated();

        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:29', 'Europe/Vienna'));
        $this->buy('x2_30')->assertStatus(409)->assertJsonPath('message', 'Es läuft schon ein Booster.');
        $this->assertSame(80, $this->user->fresh()->brainpower_current);

        // Ist er abgelaufen, geht der nächste
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:30', 'Europe/Vienna'));
        $this->buy('x2_30')->assertCreated();
        $this->assertSame(40, $this->user->fresh()->brainpower_current);
    }

    public function test_ohne_genug_brainpower_kein_booster(): void
    {
        $this->user->update(['brainpower_current' => 69]);

        $this->buy('x2_60')
            ->assertJsonValidationErrors(['kind' => 'Dafür reicht deine Brainpower gerade nicht. Morgen lädt sie sich wieder auf.']);
        $this->assertSame(69, $this->user->fresh()->brainpower_current);
        $this->assertNull($this->user->fresh()->booster_ends_at);
    }

    public function test_vor_dem_kauf_wird_aufgeladen(): void
    {
        $this->user->update(['brainpower_current' => 10, 'brainpower_refilled_on' => '2026-10-16']);

        $this->buy('x2_30')->assertCreated()->assertJsonPath('data.brainpower.current', 20);
    }

    public function test_shop_liefert_brainpower_laufenden_booster_und_angebote(): void
    {
        $this->actingAs($this->user)->getJson('/api/v1/shop')
            ->assertOk()
            ->assertExactJson(['data' => [
                'brainpower' => ['current' => 100, 'max' => 100],
                'booster' => null,
                'boosters' => [
                    ['kind' => 'x1_5_30', 'multiplier' => 1.5, 'minutes' => 30, 'cost' => 20],
                    ['kind' => 'x2_30', 'multiplier' => 2, 'minutes' => 30, 'cost' => 40],
                    ['kind' => 'x2_60', 'multiplier' => 2, 'minutes' => 60, 'cost' => 70],
                ],
            ]]);

        $this->buy('x2_30');
        $this->actingAs($this->user->fresh())->getJson('/api/v1/shop')
            ->assertJsonPath('data.brainpower.current', 60)
            ->assertJsonPath('data.booster', ['multiplier' => 2, 'ends_at' => '2026-10-17T12:30:00+00:00']);
    }

    public function test_shop_laedt_vorher_auf(): void
    {
        $this->user->update(['brainpower_current' => 10, 'brainpower_refilled_on' => '2026-10-16']);

        $this->actingAs($this->user)->getJson('/api/v1/shop')->assertJsonPath('data.brainpower.current', 60);
    }

    public function test_shop_nur_mit_login(): void
    {
        $this->getJson('/api/v1/shop')->assertUnauthorized();
    }

    public function test_abgelaufener_booster_fehlt_in_me(): void
    {
        $this->buy('x2_30');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:30', 'Europe/Vienna'));

        $this->actingAs($this->user->fresh())->getJson('/api/v1/me')->assertJsonPath('data.booster', null);
    }
}
