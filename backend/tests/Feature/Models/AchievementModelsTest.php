<?php

namespace Tests\Feature\Models;

use App\Enums\AchievementRule;
use App\Models\Achievement;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_jeder_typ_passt_zur_datenbankregel(): void
    {
        // Enum und CHECK-Regel in der Migration dürfen nicht auseinanderlaufen
        foreach (AchievementRule::cases() as $rule) {
            Achievement::factory()->rule($rule, 3)->create();
        }

        $this->assertSame(count(AchievementRule::cases()), Achievement::count());
        $this->assertSame(AchievementRule::Streak, Achievement::firstWhere('rule', 'streak')->rule);
        $this->assertSame('Fach-Level', AchievementRule::SubjectLevel->getLabel());
    }

    public function test_titel_und_bedingung_sind_uebersetzbar_ohne_leere_sprachen(): void
    {
        $achievement = Achievement::factory()->create(['title' => ['de' => 'Eine Woche', 'en' => ''], 'description' => ['de' => '7 Tage in Folge lernen']]);

        $this->assertSame(['de' => 'Eine Woche'], $achievement->fresh()->getTranslations('title'));
        $this->assertSame('7 Tage in Folge lernen', $achievement->fresh()->description);
    }

    public function test_vergabe_merkt_sich_datum_und_trophaeen(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->trophies(3)->create();

        $user->achievements()->attach($achievement, ['trophies_awarded' => 3]);

        $unlocked = $user->achievements()->first();
        $this->assertTrue($unlocked->is($achievement));
        $this->assertInstanceOf(UserAchievement::class, $unlocked->pivot);
        $this->assertSame(3, $unlocked->pivot->trophies_awarded);
        $this->assertNotNull($unlocked->pivot->unlocked_at);
        $this->assertSame(1, $achievement->unlocks()->count());
        $this->assertTrue(UserAchievement::first()->user->is($user));
    }

    public function test_vergebener_erfolg_wird_nicht_geloescht_sondern_bleibt(): void
    {
        $unused = Achievement::factory()->create();
        $used = Achievement::factory()->create();
        User::factory()->create()->achievements()->attach($used);

        $this->assertFalse($used->delete());
        $this->assertModelExists($used);
        $this->assertTrue($unused->delete());
        $this->assertModelMissing($unused);
    }

    public function test_fach_eines_erfolgs_wird_nicht_geloescht(): void
    {
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $achievement = Achievement::factory()->subjectLevel(10, $subject)->create();

        $this->assertTrue($achievement->subject->is($subject));
        $this->assertFalse($subject->delete());
        $this->assertModelExists($subject);
    }
}
