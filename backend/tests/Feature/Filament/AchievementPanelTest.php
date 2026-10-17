<?php

namespace Tests\Feature\Filament;

use App\Enums\AchievementRule;
use App\Filament\Resources\AchievementResource;
use App\Filament\Resources\AchievementResource\Pages\CreateAchievement;
use App\Filament\Resources\AchievementResource\Pages\EditAchievement;
use App\Filament\Resources\AchievementResource\Pages\ListAchievements;
use App\Models\Achievement;
use App\Models\Subject;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Erfolge im CMS (E-042): nur Admin, Typ mit Schwellwert, Fach nur beim Fach-Level, vergebene nur deaktivieren.
 */
class AchievementPanelTest extends TestCase
{
    use RefreshDatabase;

    private Achievement $achievement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->achievement = Achievement::factory()->rule(AchievementRule::Streak, 7)
            ->create(['slug' => 'streak-7', 'title' => ['de' => 'Eine Woche'], 'description' => ['de' => '7 Tage in Folge lernen']]);
    }

    public function test_admin_oeffnet_alle_seiten(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (['/admin/achievements', '/admin/achievements/create', "/admin/achievements/{$this->achievement->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin')->assertSeeInOrder(['Schule', 'Spiel', 'Erfolge']);
    }

    public function test_andere_rollen_sehen_die_erfolge_nicht(): void
    {
        foreach (['autor', 'tester', 'teacher'] as $role) {
            $this->actingAs($this->userWithRole($role));

            $this->get('/admin/achievements')->assertForbidden();
            $this->get("/admin/achievements/{$this->achievement->id}/edit")->assertForbidden();
        }
    }

    public function test_admin_legt_fach_level_in_einem_fach_an(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren']]);

        Livewire::test(CreateAchievement::class)
            ->fillForm([
                'title.de' => 'POS-Profi', 'slug' => 'pos-10', 'description.de' => 'In POS Level 10',
                'rule' => AchievementRule::SubjectLevel->value, 'threshold' => 10, 'subject_id' => $pos->id,
                'icon_name' => 'achievement', 'trophy_reward' => 3, 'sort' => 100, 'is_secret' => false, 'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Achievement::firstWhere('slug', 'pos-10');
        $this->assertSame('POS-Profi', $created->title);
        $this->assertSame(AchievementRule::SubjectLevel, $created->rule);
        $this->assertSame($pos->id, $created->subject_id);
        $this->assertSame(3, $created->trophy_reward);
    }

    public function test_fach_feld_nur_beim_fach_level_und_wird_beim_wechsel_geleert(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren']]);
        $fach = Achievement::factory()->subjectLevel(10, $pos)->create();

        Livewire::test(EditAchievement::class, ['record' => $this->achievement->getRouteKey()])
            ->assertFormFieldHidden('subject_id');

        Livewire::test(EditAchievement::class, ['record' => $fach->getRouteKey()])
            ->assertFormFieldVisible('subject_id')
            ->assertFormSet(['subject_id' => $pos->id])
            ->fillForm(['rule' => AchievementRule::Streak->value])
            ->assertFormFieldHidden('subject_id')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($fach->fresh()->subject_id);
        $this->assertSame(AchievementRule::Streak, $fach->fresh()->rule);
    }

    public function test_formular_prueft_schwellwert_trophaeen_und_kuerzel(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(CreateAchievement::class)
            ->fillForm([
                'title.de' => 'Doppelt', 'slug' => 'streak-7', 'description.de' => 'Bedingung',
                'rule' => AchievementRule::Streak->value, 'threshold' => 0, 'trophy_reward' => -1,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique', 'threshold' => 'min', 'trophy_reward' => 'min']);

        $this->assertSame(1, Achievement::count());
    }

    public function test_unbenutzter_erfolg_wird_geloescht_vergebener_bleibt(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $unused = Achievement::factory()->create();
        User::factory()->create()->achievements()->attach($this->achievement);

        Livewire::test(EditAchievement::class, ['record' => $this->achievement->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified(Notification::make()->danger()->title('Nicht gelöscht')->body(AchievementResource::IN_USE_HINT)->persistent());
        Livewire::test(EditAchievement::class, ['record' => $unused->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelExists($this->achievement);
        $this->assertModelMissing($unused);
    }

    public function test_liste_zeigt_titel_typ_und_wie_oft_vergeben(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->achievement->unlocks()->createMany([
            ['user_id' => User::factory()->create()->id],
            ['user_id' => User::factory()->create()->id],
        ]);

        Livewire::test(ListAchievements::class)
            ->assertCanSeeTableRecords([$this->achievement])
            ->assertTableColumnStateSet('title', 'Eine Woche', $this->achievement)
            ->assertTableColumnStateSet('rule', AchievementRule::Streak, $this->achievement)
            ->assertTableColumnStateSet('unlocks_count', 2, $this->achievement)
            ->assertSee('Streak in Tagen (längster)');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
