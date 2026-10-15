<?php

namespace Tests\Feature\Filament;

use App\Enums\ContentStatus;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\RelationManagers\TheorySectionsRelationManager;
use App\Models\Subject;
use App\Models\TheorySection;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TheorySectionsTest extends TestCase
{
    use RefreshDatabase;

    private Topic $topic;

    private TheorySection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->topic = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']])
            ->topics()->create(['slug' => 'variablen', 'title' => ['de' => 'Variablen'], 'status' => ContentStatus::Published]);
        $this->section = $this->topic->theorySections()->create([
            'title' => ['de' => 'Was ist eine Variable?'], 'content' => ['de' => 'Ein benannter Speicherplatz.'], 'order_index' => 1,
        ]);
    }

    public function test_thema_zeigt_seine_theorie_auf_deutsch(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->get("/admin/topics/{$this->topic->id}/edit")->assertOk()->assertSeeLivewire(TheorySectionsRelationManager::class);
        $this->theory()
            ->assertCanSeeTableRecords([$this->section])
            ->assertTableColumnStateSet('title', 'Was ist eine Variable?', $this->section);
    }

    public function test_autor_legt_abschnitt_an_und_thema_geht_in_pruefung(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->theory()
            ->callTableAction('create', data: ['title.de' => 'Deklaration', 'content.de' => 'Mit Datentyp und Name, z. B. `int alter = 17;`'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Das Thema ist jetzt „In Prüfung“.');

        $section = $this->topic->theorySections()->where('title->de', 'Deklaration')->sole();
        $this->assertSame(2, $section->order_index);
        $this->assertSame('Mit Datentyp und Name, z. B. `int alter = 17;`', $section->getTranslation('content', 'de'));
        $this->assertSame(ContentStatus::Review, $this->topic->fresh()->status);
    }

    public function test_autor_bearbeitet_abschnitt_und_thema_geht_in_pruefung(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->theory()
            ->callTableAction('edit', $this->section, data: ['content.de' => 'Ein benannter Speicherplatz für einen Wert.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Ein benannter Speicherplatz für einen Wert.', $this->section->fresh()->getTranslation('content', 'de'));
        $this->assertSame(ContentStatus::Review, $this->topic->fresh()->status);
    }

    public function test_admin_aendert_theorie_ohne_statuswechsel(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->theory()
            ->callTableAction('edit', $this->section, data: ['content.de' => 'Korrigiert.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(ContentStatus::Published, $this->topic->fresh()->status);
    }

    public function test_nur_admin_loescht_abschnitte(): void
    {
        $this->actingAs($this->userWithRole('autor'));
        $this->theory()->assertTableActionHidden('delete', $this->section);

        $this->actingAs($this->userWithRole('admin'));
        $this->theory()->callTableAction('delete', $this->section);

        $this->assertModelMissing($this->section);
    }

    public function test_titel_und_text_sind_pflicht(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->theory()
            ->callTableAction('create', data: ['title.de' => '', 'content.de' => ''])
            ->assertHasTableActionErrors(['title.de' => 'required', 'content.de' => 'required']);
    }

    private function theory(): mixed
    {
        return Livewire::test(TheorySectionsRelationManager::class, ['ownerRecord' => $this->topic, 'pageClass' => EditTopic::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role]));

        return $user;
    }
}
