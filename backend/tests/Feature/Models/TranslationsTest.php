<?php

namespace Tests\Feature\Models;

use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Leere Sprachfelder landen als NULL in der Datenbank, nicht als {"de":null} (HasTranslationsWithoutEmpty).
 */
class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('de');
        $this->subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
    }

    public function test_leere_beschreibung_wird_null(): void
    {
        $withNull = $this->topic('a', ['de' => null]);
        $withEmptyString = $this->topic('b', ['de' => '']);
        $withScalar = $this->topic('c', '');

        foreach ([$withNull, $withEmptyString, $withScalar] as $topic) {
            $this->assertNull($this->rawDescription($topic));
        }
    }

    public function test_leeren_entfernt_nur_die_eine_sprache(): void
    {
        $topic = $this->topic('a', ['de' => 'Wiederholen', 'en' => 'Repeat']);

        $topic->update(['description' => ['de' => null]]);

        $this->assertSame(['en' => 'Repeat'], json_decode($this->rawDescription($topic), true));
    }

    public function test_text_landet_in_der_aktuellen_sprache(): void
    {
        $topic = $this->topic('a', 'Wiederholen');

        $this->assertSame(['de' => 'Wiederholen'], json_decode($this->rawDescription($topic), true));
    }

    public function test_cms_speichert_leere_beschreibung_als_null(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'autor']));
        $this->actingAs($user);

        Livewire::test(CreateTopic::class)
            ->fillForm(['subject_id' => $this->subject->id, 'slug' => 'schleifen', 'title.de' => 'Schleifen'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull($this->rawDescription(Topic::where('slug', 'schleifen')->sole()));
    }

    private function topic(string $slug, mixed $description): Topic
    {
        return $this->subject->topics()->create(['slug' => $slug, 'title' => ['de' => 'Schleifen'], 'description' => $description]);
    }

    private function rawDescription(Topic $topic): ?string
    {
        return DB::table('topics')->where('id', $topic->id)->value('description');
    }
}
