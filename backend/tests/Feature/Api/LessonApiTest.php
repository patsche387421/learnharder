<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserStat;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

class LessonApiTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private User $user;

    private Subject $pos;

    private Topic $schleifen;

    private Question $einfach;

    private Question $mehrfach;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));

        $this->user = User::factory()->create();

        $this->pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->addToCurriculum($this->enrollStudent($this->user), $this->pos);
        $this->schleifen = $this->topic('schleifen');
        $this->einfach = $this->question($this->schleifen, ['richtig' => true, 'falsch' => false], order: 1);
        $this->mehrfach = $this->question($this->schleifen, ['a' => true, 'b' => true, 'c' => false], QuestionType::MultipleChoice, order: 2);
    }

    private function topic(string $slug, array $extra = []): Topic
    {
        return $this->pos->topics()->create(['slug' => $slug, 'title' => ['de' => $slug], 'status' => ContentStatus::Published, ...$extra]);
    }

    /** @param array<string, bool> $options Text => richtig */
    private function question(Topic $topic, array $options, QuestionType $type = QuestionType::SingleChoice, ContentStatus $status = ContentStatus::Published, int $order = 0): Question
    {
        $question = $topic->questions()->create([
            'question_text' => ['de' => 'Frage '.$order], 'explanation' => ['de' => 'Weil.'],
            'type' => $type, 'status' => $status, 'xp_reward' => 2, 'order_index' => $order,
        ]);
        foreach (array_keys($options) as $i => $text) {
            $question->options()->create(['option_text' => ['de' => $text], 'is_correct' => $options[$text], 'order_index' => $i]);
        }

        return $question;
    }

    /** @return array<int, string> */
    private function optionIds(Question $question, string ...$texts): array
    {
        return $question->options->filter(fn ($o) => in_array($o->option_text, $texts, true))->pluck('id')->values()->all();
    }

    private function start(?User $user = null, ?Topic $topic = null): TestResponse
    {
        return $this->actingAs($user ?? $this->user)->postJson('/api/v1/topics/'.($topic ?? $this->schleifen)->id.'/attempts');
    }

    private function answer(string $attemptId, Question $question, string ...$texts): TestResponse
    {
        return $this->postJson("/api/v1/attempts/{$attemptId}/answers", [
            'question_id' => $question->id, 'option_ids' => $this->optionIds($question, ...$texts),
        ]);
    }

    private function finish(string $attemptId): TestResponse
    {
        return $this->postJson("/api/v1/attempts/{$attemptId}/finish");
    }

    /** Ganze Lerneinheit: beide Fragen richtig, abgeschlossen. */
    private function playPerfect(): TestResponse
    {
        $id = $this->start()->json('data.id');
        $this->answer($id, $this->einfach, 'richtig');
        $this->answer($id, $this->mehrfach, 'a', 'b');

        return $this->finish($id);
    }

    private function totalXp(): int
    {
        return UserStat::where('user_id', $this->user->id)->value('total_xp') ?? 0;
    }

    public function test_start_liefert_veroeffentlichte_fragen_ohne_loesung(): void
    {
        $this->question($this->schleifen, ['x' => true], status: ContentStatus::Review, order: 3);

        $response = $this->start()
            ->assertCreated()
            ->assertJsonCount(2, 'data.questions')
            ->assertJsonPath('data.questions.0.id', $this->einfach->id)
            ->assertJsonPath('data.questions.0.text', 'Frage 1')
            ->assertJsonPath('data.questions.0.type', 'single_choice')
            ->assertJsonPath('data.questions.1.type', 'multiple_choice')
            ->assertJsonCount(3, 'data.questions.1.options');

        $this->assertStringNotContainsString('is_correct', $response->getContent());
        $this->assertStringNotContainsString('Weil.', $response->getContent());
        $this->assertDatabaseHas('quiz_attempts', ['id' => $response->json('data.id'), 'total_questions' => 2, 'finished_at' => null]);
    }

    public function test_start_nur_fuer_sichtbare_themen_mit_fragen(): void
    {
        $entwurf = $this->topic('entwurf', ['status' => ContentStatus::Draft]);
        $leer = $this->topic('leer');

        $this->start(topic: $entwurf)->assertNotFound();
        $this->start(topic: $leer)->assertUnprocessable();

        $this->pos->update(['is_active' => false]);
        $this->start()->assertNotFound();
    }

    public function test_gesperrtes_thema_laesst_sich_nicht_starten(): void
    {
        $rekursion = $this->topic('rekursion', ['level_required' => 2]);
        $this->question($rekursion, ['ja' => true]);

        $this->start(topic: $rekursion)->assertForbidden();

        UserStat::create(['user_id' => $this->user->id, 'total_xp' => 8]); // Level 2
        $this->start($this->user->fresh(), $rekursion)->assertCreated();
    }

    public function test_antwort_liefert_ergebnis_erklaerung_und_ep_vorschau(): void
    {
        $id = $this->start()->json('data.id');

        $this->answer($id, $this->einfach, 'richtig')
            ->assertOk()
            ->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.correct_option_ids', $this->optionIds($this->einfach, 'richtig'))
            ->assertJsonPath('data.explanation', 'Weil.')
            ->assertJsonPath('data.xp', 2)
            ->assertJsonPath('data.repeat', false);

        $this->answer($id, $this->mehrfach, 'c')
            ->assertJsonPath('data.correct', false)
            ->assertJsonPath('data.xp', 0);

        $this->assertSame(0, $this->totalXp(), 'EP gibt es erst beim Abschluss');
    }

    public function test_mehrere_richtige_nur_teilweise_gewaehlt_ist_falsch(): void
    {
        $id = $this->start()->json('data.id');

        $this->answer($id, $this->mehrfach, 'a')->assertJsonPath('data.correct', false);
    }

    public function test_bei_einer_richtigen_ist_nur_eine_auswahl_erlaubt(): void
    {
        $id = $this->start()->json('data.id');

        $this->answer($id, $this->einfach, 'richtig', 'falsch')->assertJsonValidationErrors('option_ids');
    }

    public function test_fremde_oder_unveroeffentlichte_fragen_und_optionen_werden_abgelehnt(): void
    {
        $id = $this->start()->json('data.id');
        $andere = $this->question($this->topic('arrays'), ['ja' => true]);
        $entwurf = $this->question($this->schleifen, ['ja' => true], status: ContentStatus::Draft);

        $this->answer($id, $andere, 'ja')->assertJsonValidationErrors('question_id');
        $this->answer($id, $entwurf, 'ja')->assertJsonValidationErrors('question_id');
        $this->postJson("/api/v1/attempts/{$id}/answers", [
            'question_id' => $this->einfach->id, 'option_ids' => $this->optionIds($andere, 'ja'),
        ])->assertJsonValidationErrors('option_ids.0');
    }

    public function test_keine_uuid_gibt_404_oder_422_statt_serverfehler(): void
    {
        $id = $this->start()->json('data.id');

        $this->postJson('/api/v1/topics/abc/attempts')->assertNotFound();
        $this->postJson('/api/v1/attempts/abc/answers', ['question_id' => $this->einfach->id, 'option_ids' => ['x']])->assertNotFound();
        $this->postJson('/api/v1/attempts/abc/finish')->assertNotFound();
        $this->postJson("/api/v1/attempts/{$id}/answers", ['question_id' => 'abc', 'option_ids' => ['abc']])
            ->assertJsonValidationErrors(['question_id', 'option_ids.0']);
    }

    public function test_wiederholung_am_ende_gibt_keine_ep(): void
    {
        $id = $this->start()->json('data.id');

        $this->answer($id, $this->einfach, 'falsch')->assertJsonPath('data.correct', false);
        $this->answer($id, $this->einfach, 'richtig')
            ->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.repeat', true)
            ->assertJsonPath('data.xp', 0);
        $this->answer($id, $this->mehrfach, 'a', 'b');

        $this->finish($id)
            ->assertJsonPath('data.xp_earned', 2)
            ->assertJsonPath('data.correct_count', 1);
        $this->assertSame(1, QuestionAnswer::where('question_id', $this->einfach->id)->count());
    }

    public function test_dieselbe_erste_antwort_nochmal_ist_keine_wiederholung(): void
    {
        $id = $this->start()->json('data.id');

        $this->answer($id, $this->einfach, 'richtig');
        $this->answer($id, $this->einfach, 'richtig')
            ->assertJsonPath('data.repeat', false)
            ->assertJsonPath('data.xp', 2);
    }

    public function test_abschluss_vergibt_ep_und_meldet_level_aufstieg(): void
    {
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => 5]);

        $this->playPerfect()
            ->assertOk()
            ->assertJsonPath('data.xp_earned', 4)
            ->assertJsonPath('data.correct_count', 2)
            ->assertJsonPath('data.total_questions', 2)
            ->assertJsonPath('data.level_up', true)
            ->assertJsonPath('data.before.level', 1)
            ->assertJsonPath('data.progress.level', 2)
            ->assertJsonPath('data.progress.xp', ['total' => 9, 'level_start' => 8, 'next_level' => 23]);

        $this->assertSame(9, $this->totalXp());
        $this->assertDatabaseHas('quiz_attempts', ['user_id' => $this->user->id, 'xp_earned' => 4, 'score_percentage' => 100]);
    }

    public function test_doppelter_abschluss_gibt_ep_nur_einmal(): void
    {
        $id = $this->start()->json('data.id');
        $this->answer($id, $this->einfach, 'richtig');

        $first = $this->finish($id)->assertOk()->json('data');
        $second = $this->finish($id)->assertOk()->json('data');

        $this->assertSame($first, $second);
        $this->assertSame(2, $this->totalXp());
        $this->answer($id, $this->mehrfach, 'a', 'b')->assertStatus(409);
    }

    public function test_abschluss_ohne_antwort_wird_abgelehnt(): void
    {
        $id = $this->start()->json('data.id');

        $this->finish($id)->assertJsonValidationErrors('answers');
        $this->assertNull(QuizAttempt::find($id)->finished_at);
    }

    public function test_dieselbe_frage_am_selben_tag_gibt_keine_ep_mehr(): void
    {
        $this->playPerfect()->assertJsonPath('data.xp_earned', 4);

        $id = $this->start()->json('data.id');
        $this->answer($id, $this->einfach, 'richtig')->assertJsonPath('data.xp', 0);
        $this->finish($id)->assertJsonPath('data.xp_earned', 0);

        $this->assertSame(4, $this->totalXp());
    }

    public function test_am_naechsten_tag_gibt_es_wieder_ep(): void
    {
        $this->playPerfect();

        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));
        $this->playPerfect()->assertJsonPath('data.xp_earned', 4);

        $this->assertSame(8, $this->totalXp());
    }

    public function test_tagesgrenze_gilt_in_wien(): void
    {
        // 23:59 in Wien ist 21:59 UTC, 00:01 in Wien ist schon der nächste Tag (22:01 UTC)
        $this->travelTo(CarbonImmutable::parse('2026-10-16 23:59', 'Europe/Vienna'));
        $this->playPerfect()->assertJsonPath('data.xp_earned', 4);

        $this->travelTo(CarbonImmutable::parse('2026-10-17 00:01', 'Europe/Vienna'));
        $this->playPerfect()->assertJsonPath('data.xp_earned', 4);

        $this->assertSame(['2026-10-16', '2026-10-17'], QuestionAnswer::where('question_id', $this->einfach->id)
            ->orderBy('id')->pluck('xp_date')->map->toDateString()->all());
    }

    /** @return array{current: int, longest: int} */
    private function streak(): array
    {
        // Wie bei einer echten Anfrage frisch laden, sonst hängt noch user_stats von vor der ersten Lerneinheit am Objekt
        return $this->actingAs($this->user->unsetRelation('stats'))->getJson('/api/v1/me')->json('data.streak');
    }

    private function playOn(string $viennaTime): void
    {
        $this->travelTo(CarbonImmutable::parse($viennaTime, 'Europe/Vienna'));
        $this->playPerfect()->assertOk();
    }

    public function test_streak_zaehlt_tage_mit_abgeschlossener_lerneinheit(): void
    {
        $this->assertSame(['current' => 0, 'longest' => 0], $this->streak());

        $this->playOn('2026-10-16 10:00');
        $this->playOn('2026-10-16 18:00');
        $this->assertSame(['current' => 1, 'longest' => 1], $this->streak());

        $this->playOn('2026-10-17 07:00');
        $this->playOn('2026-10-18 21:00');
        $this->assertSame(['current' => 3, 'longest' => 3], $this->streak());
        $this->assertSame('2026-10-18', UserStat::where('user_id', $this->user->id)->first()->last_study_date->toDateString());
    }

    public function test_streak_zaehlt_auch_ohne_ep_und_offene_lerneinheiten_zaehlen_nicht(): void
    {
        $this->playOn('2026-10-16 10:00');

        // Am nächsten Tag nur angefangen: kein Lerntag
        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));
        $this->answer($this->start()->json('data.id'), $this->einfach, 'falsch');
        $this->assertSame(['current' => 1, 'longest' => 1], $this->streak());

        // Falsch beantwortet und abgeschlossen, 0 EP: zählt trotzdem
        $id = $this->start()->json('data.id');
        $this->answer($id, $this->einfach, 'falsch');
        $this->finish($id)->assertJsonPath('data.xp_earned', 0);
        $this->assertSame(['current' => 2, 'longest' => 2], $this->streak());
    }

    public function test_nach_einem_ausgelassenen_tag_beginnt_der_streak_neu(): void
    {
        $this->playOn('2026-10-16 10:00');
        $this->playOn('2026-10-17 10:00');

        // Am Tag danach läuft er noch, erst nach einem ganzen Tag ohne Lerneinheit ist er weg
        $this->travelTo(CarbonImmutable::parse('2026-10-18 23:00', 'Europe/Vienna'));
        $this->assertSame(['current' => 2, 'longest' => 2], $this->streak());
        $this->travelTo(CarbonImmutable::parse('2026-10-19 08:00', 'Europe/Vienna'));
        $this->assertSame(['current' => 0, 'longest' => 2], $this->streak());

        $this->playOn('2026-10-19 08:00');
        $this->assertSame(['current' => 1, 'longest' => 2], $this->streak());
    }

    public function test_streak_tage_gelten_in_wien(): void
    {
        // 23:59 und 00:01 in Wien sind zwei Lerntage, obwohl beides in UTC am 16. liegt
        $this->playOn('2026-10-16 23:59');
        $this->playOn('2026-10-17 00:01');

        $this->assertSame(['current' => 2, 'longest' => 2], $this->streak());
    }

    public function test_doppelter_abschluss_zaehlt_den_tag_nur_einmal(): void
    {
        $id = $this->start()->json('data.id');
        $this->answer($id, $this->einfach, 'richtig');
        $this->finish($id);
        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));
        $this->finish($id);

        $this->assertSame(['current' => 1, 'longest' => 1], $this->streak());
    }

    public function test_zwei_offene_lerneinheiten_mit_derselben_frage_nur_die_erste_bekommt_ep(): void
    {
        $erste = $this->start()->json('data.id');
        $zweite = $this->start()->json('data.id');

        // Beide zeigen vor dem Abschluss +2 EP an, verbindlich ist der Abschluss
        $this->answer($erste, $this->einfach, 'richtig')->assertJsonPath('data.xp', 2);
        $this->answer($zweite, $this->einfach, 'richtig')->assertJsonPath('data.xp', 2);

        $this->finish($erste)->assertJsonPath('data.xp_earned', 2);
        $this->finish($zweite)->assertJsonPath('data.xp_earned', 0);

        $this->assertSame(2, $this->totalXp());
    }

    public function test_fremde_lerneinheit_ist_verboten(): void
    {
        $id = $this->start()->json('data.id');
        $andere = User::factory()->create();

        $this->flushSession();
        $this->actingAs($andere);
        $this->answer($id, $this->einfach, 'richtig')->assertForbidden();
        $this->finish($id)->assertForbidden();
    }

    public function test_auch_admin_darf_keine_fremde_lerneinheit_beantworten(): void
    {
        $id = $this->start()->json('data.id');
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));

        $this->flushSession();
        $this->actingAs($admin);
        $this->answer($id, $this->einfach, 'richtig')->assertForbidden();
        $this->finish($id)->assertForbidden();
    }

    public function test_admin_darf_gesperrte_themen_zum_pruefen_starten(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'admin', 'guard_name' => 'web']));
        $this->schleifen->update(['level_required' => 50]);

        $this->start($admin)->assertCreated();
    }

    public function test_ohne_login_gibt_es_keine_lerneinheit(): void
    {
        $this->postJson('/api/v1/topics/'.$this->schleifen->id.'/attempts')->assertUnauthorized();
    }

    public function test_abgeschlossene_themen_zaehlen_als_erledigt(): void
    {
        $this->question($this->topic('arrays', ['order_index' => 2]), ['ja' => true]);

        $this->playPerfect();

        $this->getJson('/api/v1/subjects')->assertJsonPath('data.0.topics_count', 2)->assertJsonPath('data.0.topics_done', 1);
        $this->getJson('/api/v1/subjects/pos')
            ->assertJsonPath('data.topics.0.slug', 'schleifen')
            ->assertJsonPath('data.topics.0.done', true)
            ->assertJsonPath('data.topics.1.done', false);
    }

    public function test_fach_level_waechst_mit_den_ep_im_fach(): void
    {
        $dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'DBI'], 'order_index' => 2]);
        $this->addToCurriculum($this->user->activeEnrollment->schoolClass, $dbi);
        $sql = $dbi->topics()->create(['slug' => 'sql', 'title' => ['de' => 'SQL'], 'status' => ContentStatus::Published]);
        $this->question($sql, ['ja' => true]);

        $this->playPerfect();
        $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00', 'Europe/Vienna'));
        $this->playPerfect();

        // 8 EP in POS = Fach-Level 2, DBI ohne EP bleibt bei 1
        $this->getJson('/api/v1/subjects')
            ->assertJsonPath('data.0.code', 'pos')
            ->assertJsonPath('data.0.xp', 8)
            ->assertJsonPath('data.0.level', 2)
            ->assertJsonPath('data.1.code', 'dbi')
            ->assertJsonPath('data.1.xp', 0)
            ->assertJsonPath('data.1.level', 1);
        $this->getJson('/api/v1/subjects/pos')->assertJsonPath('data.level', 2);

        // Andere Personen zählen nicht mit
        $other = User::factory()->create();
        $this->addToCurriculum($this->enrollStudent($other), $this->pos);
        $this->flushSession();
        $this->actingAs($other)->getJson('/api/v1/subjects')->assertJsonPath('data.0.xp', 0);
    }

    public function test_loeschen_einer_person_loescht_ihre_antworten(): void
    {
        $this->playPerfect();

        $this->user->delete();

        $this->assertSame(0, QuizAttempt::count());
        $this->assertSame(0, QuestionAnswer::count());
    }

    public function test_beantwortete_frage_laesst_sich_nicht_hart_loeschen(): void
    {
        $this->playPerfect();

        // Das Model verweigert es, die Datenbank sperrt es auch ohne Model (RESTRICT)
        $this->assertFalse($this->einfach->delete());
        $this->assertModelExists($this->einfach);
        $this->expectException(QueryException::class);
        Question::whereKey($this->einfach->id)->delete();
    }
}
