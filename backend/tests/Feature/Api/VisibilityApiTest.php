<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\ProgramUnit;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

/**
 * Wer sieht welche Fächer (E-024, Szenarien aus E-013): Schüler:innen den Lehrplan ihrer Klasse, die übrigen Rollen alles.
 */
class VisibilityApiTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private User $student;

    private Subject $pos;

    private Subject $medt;

    private Topic $video;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));

        $this->student = User::factory()->create();
        $this->pos = $this->subject('pos', 1);
        $this->medt = $this->subject('medt', 2);
        $this->video = $this->medt->topics()->sole();
    }

    public function test_ohne_klasse_gibt_es_keine_inhalte(): void
    {
        $this->student->assignRole(Role::findOrCreate('student', 'web'));

        $this->actingAs($this->student);
        $this->getJson('/api/v1/subjects')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/subjects/pos')->assertNotFound();
        $this->getJson('/api/v1/subjects/pos/topics/pos-thema')->assertNotFound();
        $this->postJson('/api/v1/topics/'.$this->pos->topics()->sole()->id.'/attempts')->assertNotFound();
        $this->getJson('/api/v1/me')->assertJsonPath('data.school_class', null);
    }

    public function test_jahrgang_zeigt_den_lehrplan_bis_zum_aktuellen_abschnitt(): void
    {
        $class = $this->enrollStudent($this->student, stage: 2);
        $this->addToCurriculum($class, $this->pos, from: 1, to: 1); // nur im 1. Jahrgang, bleibt zum Wiederholen
        $this->addToCurriculum($class, $this->medt, from: 3);

        $this->actingAs($this->student);
        $this->assertSubjects(['pos']);
        $this->getJson('/api/v1/subjects/medt')->assertNotFound();
        $this->getJson('/api/v1/subjects/medt/topics/medt-thema')->assertNotFound();
        $this->postJson("/api/v1/topics/{$this->video->id}/attempts")->assertNotFound();
        $this->getJson('/api/v1/me')->assertJsonPath('data.school_class.name', '2AHIT');
    }

    public function test_im_zweiten_semester_kommt_der_naechste_abschnitt_dazu(): void
    {
        $class = $this->enrollStudent($this->student, stage: 2, unit: ProgramUnit::Semester);
        $this->addToCurriculum($class, $this->medt, from: 3);

        $this->actingAs($this->student);
        $this->assertSubjects([]);

        $this->travelTo(CarbonImmutable::parse('2027-02-08 07:00', 'Europe/Vienna'));
        $this->assertSubjects(['medt']);
    }

    public function test_in_den_sommerferien_gilt_die_klasse_weiter(): void
    {
        $this->addToCurriculum($this->enrollStudent($this->student), $this->pos);

        $this->travelTo(CarbonImmutable::parse('2027-08-15 10:00', 'Europe/Vienna'));
        $this->actingAs($this->student);
        $this->assertSubjects(['pos']);
    }

    public function test_nach_dem_klassenwechsel_gilt_der_lehrplan_der_neuen_klasse(): void
    {
        $old = $this->enrollStudent($this->student);
        $this->addToCurriculum($old, $this->pos);
        $old->enrollments()->update(['left_at' => now()]);

        $new = $this->enrollStudent($this->student);
        $this->addToCurriculum($new, $this->medt);

        $this->actingAs($this->student);
        $this->assertSubjects(['medt']);
    }

    public function test_ausgetragen_ohne_neue_klasse_sieht_nichts_aber_die_ep_bleiben(): void
    {
        $class = $this->enrollStudent($this->student);
        $this->addToCurriculum($class, $this->pos);
        $this->actingAs($this->student);
        $this->learnPos();
        $this->getJson('/api/v1/subjects')->assertJsonPath('data.0.xp', 2);

        $class->enrollments()->update(['left_at' => now()]);
        // Jede echte Anfrage lädt die Person neu, im Test hängt die alte Klasse sonst noch am Model
        $this->actingAs($this->student->fresh());

        $this->assertSubjects([]);
        $this->getJson('/api/v1/me')
            ->assertJsonPath('data.school_class', null)
            ->assertJsonPath('data.xp.total', 2);
    }

    public function test_wer_inhalte_pflegt_prueft_oder_unterrichtet_sieht_alles(): void
    {
        foreach (['admin', 'autor', 'tester', 'teacher'] as $role) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($role, 'web'));

            $this->flushSession();
            $this->actingAs($user);
            $this->assertSubjects(['pos', 'medt'], $role);
            $this->getJson('/api/v1/subjects/medt/topics/medt-thema')->assertOk();
        }
    }

    public function test_ohne_rolle_wird_gefiltert_wie_bei_schuelern(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertSubjects([]);
    }

    /** @param list<string> $codes */
    private function assertSubjects(array $codes, string $who = 'Schüler:in'): void
    {
        $this->assertSame($codes, $this->getJson('/api/v1/subjects')->assertOk()->json('data.*.code'), $who);
    }

    private function subject(string $slug, int $order): Subject
    {
        $subject = Subject::create(['slug' => $slug, 'name' => ['de' => strtoupper($slug)], 'order_index' => $order]);
        $topic = $subject->topics()->create(['slug' => "{$slug}-thema", 'title' => ['de' => 'Thema'], 'status' => ContentStatus::Published]);
        $question = $topic->questions()->create(['question_text' => ['de' => 'Frage?'], 'status' => ContentStatus::Published]);
        $question->options()->create(['option_text' => ['de' => 'Ja'], 'is_correct' => true]);

        return $subject;
    }

    /** Eine Lerneinheit in POS richtig abschließen: 2 EP */
    private function learnPos(): void
    {
        $question = $this->pos->topics()->sole()->questions()->sole();
        $attempt = $this->postJson("/api/v1/topics/{$question->topic_id}/attempts")->assertCreated()->json('data.id');
        $this->postJson("/api/v1/attempts/{$attempt}/answers", [
            'question_id' => $question->id, 'option_ids' => [$question->options()->sole()->id],
        ])->assertOk();
        $this->postJson("/api/v1/attempts/{$attempt}/finish")->assertOk();
    }
}
