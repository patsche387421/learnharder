<?php

namespace Tests\Feature\Api;

use App\Enums\AchievementRule;
use App\Models\Achievement;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/achievements (Konzept Spielwelt §6): Raster im Profil, geheime ohne Inhalt, Nachprüfung beim Laden (E-042).
 */
class AchievementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));
    }

    public function test_ohne_login_401(): void
    {
        $this->getJson('/api/v1/achievements')->assertUnauthorized();
    }

    public function test_raster_in_katalog_reihenfolge_mit_erreichten_und_offenen(): void
    {
        $user = User::factory()->streak(3, today: true)->create();
        $woche = Achievement::factory()->rule(AchievementRule::Streak, 7)->trophies(2)
            ->create(['title' => ['de' => 'Eine Woche'], 'description' => ['de' => '7 Tage in Folge lernen'], 'icon_name' => 'streak', 'sort' => 20]);
        $drei = Achievement::factory()->rule(AchievementRule::Streak, 3)->trophies(1)
            ->create(['title' => ['de' => 'Drei Tage in Folge'], 'icon_name' => 'streak', 'sort' => 10]);

        $this->actingAs($user)->getJson('/api/v1/achievements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $drei->id)
            ->assertJsonPath('data.0.unlocked_at', now()->toIso8601String())
            ->assertJsonPath('data.0.level', 3)
            ->assertJsonPath('data.1', [
                'id' => $woche->id, 'secret' => false, 'title' => 'Eine Woche', 'description' => '7 Tage in Folge lernen',
                'icon_name' => 'streak', 'subject' => null, 'level' => 7, 'trophies' => 2, 'unlocked_at' => null,
            ]);
    }

    public function test_laden_vergibt_nachtraeglich_ohne_lerneinheit(): void
    {
        $user = User::factory()->streak(1, longest: 7)->create();
        Achievement::factory()->rule(AchievementRule::Streak, 7)->trophies(2)->create();

        $this->actingAs($user)->getJson('/api/v1/achievements')->assertOk();
        $this->getJson('/api/v1/achievements')->assertOk();

        $unlock = $user->achievements()->first()->pivot;
        $this->assertNull($unlock->quiz_attempt_id);
        $this->assertSame(2, $unlock->trophies_awarded);
        $this->getJson('/api/v1/me')->assertJsonPath('data.trophies', 2);
    }

    public function test_geheimer_erfolg_ohne_inhalt_bis_er_erreicht_ist(): void
    {
        $user = User::factory()->create();
        $secret = Achievement::factory()->rule(AchievementRule::Streak, 3)->secret()->trophies(2)
            ->create(['title' => ['de' => 'Allrounder'], 'description' => ['de' => 'In 3 Fächern lernen']]);

        $this->actingAs($user)->getJson('/api/v1/achievements')
            ->assertJsonPath('data.0', [
                'id' => $secret->id, 'secret' => true, 'title' => null, 'description' => null,
                'icon_name' => null, 'subject' => null, 'level' => null, 'trophies' => null, 'unlocked_at' => null,
            ])
            ->assertDontSee('Allrounder');

        $user->achievements()->attach($secret, ['trophies_awarded' => 2]);

        $this->getJson('/api/v1/achievements')
            ->assertJsonPath('data.0.title', 'Allrounder')
            ->assertJsonPath('data.0.description', 'In 3 Fächern lernen')
            ->assertJsonPath('data.0.trophies', 2);
    }

    public function test_deaktivierte_nur_fuer_wer_sie_schon_hat(): void
    {
        $hat = User::factory()->create();
        $aus = Achievement::factory()->inactive()->create();
        $hat->achievements()->attach($aus);

        $this->actingAs($hat)->getJson('/api/v1/achievements')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $aus->id);

        $this->flushSession();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/achievements')->assertJsonCount(0, 'data');
    }

    public function test_fach_und_trophaeen_wie_vergeben(): void
    {
        $user = User::factory()->create();
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $fach = Achievement::factory()->subjectLevel(10, $pos)->trophies(3)->create();
        $user->achievements()->attach($fach, ['trophies_awarded' => 3]);
        $fach->update(['trophy_reward' => 9]);

        $this->actingAs($user)->getJson('/api/v1/achievements')
            ->assertJsonPath('data.0.subject', 'pos')
            ->assertJsonPath('data.0.trophies', 3);
    }
}
