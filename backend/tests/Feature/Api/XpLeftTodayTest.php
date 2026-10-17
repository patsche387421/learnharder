<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\DailyXp;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

/**
 * Hinweis im Shop (E-037): Bringt heute noch eine Frage EP? Eine Abfrage, nur Inhalte, die die Person spielen darf.
 */
class XpLeftTodayTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private User $user;

    private SchoolClass $class;

    private Subject $pos;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));

        $this->user = User::factory()->create();
        $this->class = $this->enrollStudent($this->user);
        $this->pos = $this->subject('pos');
    }

    private function subject(string $slug, int $from = 1): Subject
    {
        $subject = Subject::create(['slug' => $slug, 'name' => ['de' => $slug]]);
        $this->addToCurriculum($this->class, $subject, $from);

        return $subject;
    }

    private function topic(Subject $subject, string $slug, array $extra = []): Topic
    {
        return $subject->topics()->create(['slug' => $slug, 'title' => ['de' => $slug], 'status' => ContentStatus::Published, ...$extra]);
    }

    private function question(Topic $topic, ContentStatus $status = ContentStatus::Published): Question
    {
        $question = $topic->questions()->create([
            'question_text' => ['de' => 'Frage'], 'explanation' => ['de' => 'Weil.'],
            'type' => QuestionType::SingleChoice, 'status' => $status, 'xp_reward' => 2, 'order_index' => 0,
        ]);
        $question->options()->create(['option_text' => ['de' => 'richtig'], 'is_correct' => true, 'order_index' => 0]);
        $question->options()->create(['option_text' => ['de' => 'falsch'], 'is_correct' => false, 'order_index' => 1]);

        return $question;
    }

    /** Lerneinheit über die API: jede Frage mit $choice beantwortet und abgeschlossen. */
    private function play(Topic $topic, string $choice = 'richtig'): void
    {
        $id = $this->actingAs($this->user)->postJson("/api/v1/topics/{$topic->id}/attempts")->assertCreated()->json('data.id');
        foreach ($topic->questions()->published()->with('options')->get() as $question) {
            $this->postJson("/api/v1/attempts/{$id}/answers", [
                'question_id' => $question->id,
                'option_ids' => [$question->options->first(fn ($o) => $o->option_text === $choice)->id],
            ])->assertOk();
        }
        $this->postJson("/api/v1/attempts/{$id}/finish")->assertOk();
    }

    private function xpLeft(?User $user = null): bool
    {
        return $this->actingAs($user ?? $this->user)->getJson('/api/v1/shop')->assertOk()->json('data.xp_left_today');
    }

    public function test_offene_frage_bringt_noch_ep(): void
    {
        $this->question($this->topic($this->pos, 'schleifen'));

        $this->assertTrue($this->xpLeft());
    }

    public function test_alles_heute_verdient_bringt_keine_ep_mehr_morgen_wieder(): void
    {
        $topic = $this->topic($this->pos, 'schleifen');
        $this->question($topic);
        $this->play($topic);

        $this->assertFalse($this->xpLeft());

        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:01', 'Europe/Vienna'));
        $this->assertTrue($this->xpLeft());
    }

    public function test_falsch_beantwortet_bringt_heute_noch_ep(): void
    {
        $topic = $this->topic($this->pos, 'schleifen');
        $this->question($topic);
        $this->play($topic, 'falsch');

        $this->assertTrue($this->xpLeft());
    }

    public function test_nur_spielbare_inhalte_zaehlen(): void
    {
        $this->question($this->topic($this->pos, 'entwurf', ['status' => ContentStatus::Draft]));
        $this->question($this->topic($this->pos, 'gesperrt', ['level_required' => 2]));
        $this->question($this->topic($this->pos, 'schleifen'), ContentStatus::Draft);
        $inaktiv = $this->subject('alt');
        $this->question($this->topic($inaktiv, 'alt'));
        $inaktiv->update(['is_active' => false]);
        $this->question($this->topic(Subject::create(['slug' => 'medt', 'name' => ['de' => 'MEDT']]), 'fremd'));

        $this->assertFalse($this->xpLeft());
    }

    public function test_fach_aus_einem_spaeteren_abschnitt_zaehlt_nicht(): void
    {
        // Klasse im 2. Abschnitt, Fach erst ab dem 3.
        $this->question($this->topic($this->subject('dbi', from: 3), 'sql'));

        $this->assertFalse($this->xpLeft());
    }

    public function test_ohne_klasse_false_ohne_abfrage_der_fragen(): void
    {
        $ohne = User::factory()->create();
        $ohne->assignRole(Role::findOrCreate('student', 'web'));
        $this->question($this->topic($this->pos, 'schleifen'));

        $questionQueries = 0;
        DB::listen(function (QueryExecuted $query) use (&$questionQueries) {
            $questionQueries += str_contains($query->sql, '"questions"') ? 1 : 0;
        });

        $this->assertFalse(DailyXp::anyLeft($ohne));
        $this->assertSame(0, $questionQueries);
        $this->assertFalse($this->xpLeft($ohne));
    }

    public function test_beendete_einschreibung_zaehlt_wie_ohne_klasse(): void
    {
        $this->question($this->topic($this->pos, 'schleifen'));
        $this->class->enrollments()->where('user_id', $this->user->id)->update(['left_at' => now()]);

        $this->assertFalse($this->xpLeft($this->user->fresh()));
    }

    public function test_personal_ohne_klasse_sieht_alle_inhalte(): void
    {
        $lehrkraft = User::factory()->create();
        $lehrkraft->assignRole(Role::findOrCreate('teacher', 'web'));
        $this->question($this->topic(Subject::create(['slug' => 'medt', 'name' => ['de' => 'MEDT']]), 'fremd'));

        $this->assertTrue($this->xpLeft($lehrkraft));
    }

    public function test_anzahl_der_abfragen_waechst_nicht_mit_den_inhalten(): void
    {
        $this->question($this->topic($this->pos, 'schleifen'));
        $count = function (): int {
            $user = User::find($this->user->id);
            DB::flushQueryLog();
            DB::enableQueryLog();
            DailyXp::anyLeft($user);
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        $wenig = $count();

        foreach (range(1, 5) as $i) {
            $topic = $this->topic($this->subject("fach{$i}"), "thema{$i}");
            foreach (range(1, 4) as $j) {
                $this->question($topic);
            }
        }

        $this->assertSame($wenig, $count());
    }
}
