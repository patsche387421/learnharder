<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserStat;
use App\Support\Brainpower;
use App\Support\Progression;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BrainpowerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));

        $this->user = User::factory()->create(['brainpower_current' => 30, 'brainpower_refilled_on' => '2026-10-17']);
    }

    /** @return array{current: int, max: int} */
    private function brainpower(): array
    {
        return $this->actingAs($this->user->fresh())->getJson('/api/v1/me')->assertOk()->json('data.brainpower');
    }

    /** Gesamt-EP für Level $level im Zyklus $prestige (Ökonomie-Modell §2) */
    private static function xpFor(int $level, int $prestige = 0): int
    {
        return $prestige * Progression::CYCLE_XP + (int) round(($level - 1) ** 1.5 * 8);
    }

    /** @return array<string, array{int, int, int}> */
    public static function maxima(): array
    {
        return [
            'Level 1' => [1, 0, 100],
            'Level 4' => [4, 0, 100],
            'Level 5' => [5, 0, 105],
            'Level 25' => [25, 0, 125],
            'Level 99' => [99, 0, 195],
            'Level 100' => [100, 0, 200],
            'Prestige 1, Level 1' => [1, 1, 200],
            'Prestige 3, Level 40' => [40, 3, 200],
        ];
    }

    #[DataProvider('maxima')]
    public function test_maximum_waechst_mit_dem_level_und_bleibt_ab_prestige_oben(int $level, int $prestige, int $max): void
    {
        $progression = Progression::fromTotalXp(self::xpFor($level, $prestige));
        $this->assertSame([$level, $prestige], [$progression->level, $progression->prestige]);

        $this->assertSame($max, Brainpower::max($progression));
    }

    public function test_aufladen_mit_streak_bonus_hoechstens_30(): void
    {
        $this->assertSame(50, Brainpower::dailyRefill(0));
        $this->assertSame(56, Brainpower::dailyRefill(3));
        $this->assertSame(64, Brainpower::dailyRefill(7));
        $this->assertSame(80, Brainpower::dailyRefill(15));
        $this->assertSame(80, Brainpower::dailyRefill(40));
    }

    public function test_me_liefert_stand_und_maximum_aus_dem_level(): void
    {
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => self::xpFor(25)]);

        $this->assertSame(['current' => 30, 'max' => 125], $this->brainpower());
    }

    public function test_am_selben_tag_wird_nicht_aufgeladen(): void
    {
        $this->assertSame(['current' => 30, 'max' => 100], $this->brainpower());
        $this->travelTo(CarbonImmutable::parse('2026-10-17 23:59', 'Europe/Vienna'));
        $this->assertSame(['current' => 30, 'max' => 100], $this->brainpower());
    }

    public function test_um_mitternacht_in_wien_wird_einmal_aufgeladen(): void
    {
        // 00:01 in Wien ist noch der 17. in UTC
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:01', 'Europe/Vienna'));
        $this->assertSame(['current' => 80, 'max' => 100], $this->brainpower());
        $this->assertSame(['current' => 80, 'max' => 100], $this->brainpower());
        $this->assertSame('2026-10-18', $this->user->fresh()->brainpower_refilled_on->toDateString());
    }

    public function test_verpasste_tage_zaehlen_mit_bis_zum_maximum(): void
    {
        $this->user->update(['brainpower_current' => 0]);
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => self::xpFor(50)]);

        $this->travelTo(CarbonImmutable::parse('2026-10-19 08:00', 'Europe/Vienna'));
        $this->assertSame(['current' => 100, 'max' => 150], $this->brainpower());

        $this->travelTo(CarbonImmutable::parse('2026-10-25 08:00', 'Europe/Vienna'));
        $this->assertSame(['current' => 150, 'max' => 150], $this->brainpower());
    }

    public function test_der_laufende_streak_bringt_den_bonus(): void
    {
        UserStat::create(['user_id' => $this->user->id, 'current_streak' => 7, 'longest_streak' => 7, 'last_study_date' => '2026-10-17']);

        $this->travelTo(CarbonImmutable::parse('2026-10-18 08:00', 'Europe/Vienna'));

        $this->assertSame(['current' => 94, 'max' => 100], $this->brainpower());
    }

    public function test_ohne_bisheriges_aufladen_ist_der_akku_voll(): void
    {
        $this->user->update(['brainpower_current' => 10, 'brainpower_refilled_on' => null]);
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => self::xpFor(25)]);

        $this->assertSame(['current' => 125, 'max' => 125], $this->brainpower());
        $this->assertSame('2026-10-17', $this->user->fresh()->brainpower_refilled_on->toDateString());
    }

    public function test_login_laedt_auch_auf(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 08:00', 'Europe/Vienna'));

        $this->postJson('/api/v1/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.brainpower', ['current' => 80, 'max' => 100]);
    }

    public function test_stand_ueber_dem_maximum_wird_gekappt(): void
    {
        $this->user->update(['brainpower_current' => 300]);

        $this->assertSame(['current' => 100, 'max' => 100], $this->brainpower());
    }
}
