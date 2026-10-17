<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSchoolSeeder;
use Database\Seeders\TestPersonasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Die Testzentrale liest den Stand der Testpersonen über diesen Befehl, ohne dabei etwas zu ändern.
 */
class TestPersonasCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array<string, mixed>> */
    private function personas(): array
    {
        Artisan::call('learnharder:test-personas');

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_listet_den_stand_aller_testpersonen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));
        $this->seed();
        Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->seed(DemoSchoolSeeder::class);
        $this->seed(TestPersonasSeeder::class);

        $personas = collect($this->personas())->keyBy('gamer_tag');

        $this->assertCount(7, $personas);
        $this->assertSame([
            'gamer_tag' => 'Boosterfan', 'email' => 'boosterfan@test.learnharder.app', 'level' => 30, 'prestige' => 0, 'xp' => 1249,
            'streak' => ['current' => 3, 'longest' => 3, 'today' => true], 'brainpower' => ['current' => 60, 'max' => 130],
            'booster' => ['multiplier' => 2, 'minutes' => 25], 'school_class' => '2AHIT', 'deactivated' => false,
        ], $personas['Boosterfan']);
        $this->assertSame(['level' => 100, 'prestige' => 0, 'xp' => 7990], collect($personas['FastPrestige'])->only('level', 'prestige', 'xp')->all());
        $this->assertNull($personas['OhneKlasse']['school_class']);
        $this->assertTrue($personas['Gesperrt']['deactivated']);
    }

    public function test_liest_nur_und_laedt_keine_brainpower_auf(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));
        $user = User::factory()->create(['email' => 'test@test.learnharder.app', 'brainpower_current' => 5, 'brainpower_refilled_on' => '2026-10-10']);

        $this->assertSame(5, $this->personas()[0]['brainpower']['current']);
        $this->assertSame('2026-10-10', $user->fresh()->brainpower_refilled_on->toDateString());
    }

    public function test_nicht_in_produktion(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('learnharder:test-personas')->expectsOutputToContain('nur lokal')->assertFailed();
    }
}
