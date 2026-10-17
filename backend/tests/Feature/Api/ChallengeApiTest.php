<?php

namespace Tests\Feature\Api;

use App\Enums\AchievementRule;
use App\Enums\AttemptType;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Achievement;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\DailyXp;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Herausforderung (E-045): 10 Fragen aus gelernten Themen für 25 Brainpower, 5 EP je richtiger Antwort, Bonus und Trophäe,
 * ohne Tagesregel und Booster, höchstens 2 am Tag, bis Mitternacht in Wien fortsetzbar. Dazu ihr Stand im Hub.
 */
class ChallengeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $pos;

    private Subject $dbi;

    private Topic $schleifen;

    private Topic $sql;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));

        $this->pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren'], 'order_index' => 1]);
        $this->dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'Datenbanken'], 'order_index' => 2]);
        $this->user = $this->student();
        $this->schleifen = $this->topic($this->pos, 'schleifen');
        $this->sql = $this->topic($this->dbi, 'sql');
        $this->learned($this->schleifen);
        $this->learned($this->sql);
    }

    public function test_start_kostet_brainpower_und_zieht_zehn_fragen_aus_gelernten_themen(): void
    {
        // Nicht im Pool: ungelerntes Thema, Level zu niedrig, Fach nicht im Lehrplan
        $this->topic($this->pos, 'arrays');
        $this->learned($this->topic($this->pos, 'rekursion', ['level_required' => 50]));
        $this->learned($this->topic(Subject::create(['slug' => 'syp', 'name' => ['de' => 'Systemplanung']]), 'scrum'));

        $response = $this->start()->assertCreated()
            ->assertJsonCount(10, 'data.questions')
            ->assertJsonPath('data.brainpower.current', 75)
            ->assertJsonPath('data.answered', [])
            ->assertJsonPath('data.questions.0.xp_reward', 5)
            ->assertJsonMissingPath('data.questions.0.options.0.is_correct');

        $questions = collect($response->json('data.questions'));
        $topics = Question::whereIn('id', $questions->pluck('id'))->pluck('topic_id')->unique()->values();
        $this->assertEqualsCanonicalizing([$this->schleifen->id, $this->sql->id], $topics->all());
        // Abwechselnd aus den Fächern
        $this->assertNotSame($questions[0]['subject']['slug'], $questions[1]['subject']['slug']);
        $this->assertContains($questions[0]['subject']['name'], ['Programmieren', 'Datenbanken']);

        $attempt = QuizAttempt::where('attempt_type', AttemptType::Challenge)->firstOrFail();
        $this->assertSame(AttemptType::Challenge, $attempt->attempt_type);
        $this->assertNull($attempt->topic_id);
        $this->assertSame(25, $attempt->brainpower_spent);
        $this->assertSame($questions->pluck('id')->all(), $attempt->question_ids);
        $this->assertSame(75, $this->user->fresh()->brainpower_current);
    }

    public function test_zu_wenige_gelernte_fragen_sperren_den_start_ohne_kosten(): void
    {
        $user = $this->student();
        $this->learned($this->schleifen, $user);

        $this->start($user)->assertUnprocessable()->assertJsonValidationErrors(['challenge' => 'Schließe zuerst ein paar Themen ab.']);

        $this->assertSame(100, $user->fresh()->brainpower_current);
        $this->assertSame(0, QuizAttempt::where('attempt_type', 'challenge')->count());
    }

    public function test_zu_wenig_brainpower(): void
    {
        $this->user->forceFill(['brainpower_current' => 20])->save();

        $this->start()->assertUnprocessable()->assertJsonValidationErrors(['challenge']);

        $this->assertSame(20, $this->user->fresh()->brainpower_current);
        $this->assertSame(0, QuizAttempt::where('attempt_type', 'challenge')->count());
    }

    public function test_eine_offene_zur_zeit_und_hoechstens_zwei_am_tag(): void
    {
        $first = $this->start()->assertCreated()->json('data');
        $this->start()->assertConflict()->assertJsonPath('message', 'Du hast noch eine offene Herausforderung.');

        $this->play($first, correct: 1, total: 1);
        $second = $this->start()->assertCreated()->json('data');
        $this->play($second, correct: 1, total: 1);

        $this->start()->assertConflict()->assertJsonPath('message', 'Für heute hast du alle Herausforderungen gespielt. Morgen wieder.');
        $this->assertSame(50, $this->user->fresh()->brainpower_current);
    }

    public function test_nach_mitternacht_abgelaufen_und_kein_hindernis_mehr(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-16 23:50', 'Europe/Vienna'));
        $challenge = $this->start()->assertCreated()->json('data');
        $this->answer($challenge['id'], $challenge['questions'][0]['id'], true)->assertOk();

        $this->travelTo(CarbonImmutable::parse('2026-10-17 00:10', 'Europe/Vienna'));

        $this->getJson('/api/v1/challenges/current')->assertOk()->assertJsonPath('data', null);
        $this->answer($challenge['id'], $challenge['questions'][1]['id'], true)
            ->assertConflict()->assertJsonPath('message', 'Diese Herausforderung ist abgelaufen.');
        $this->postJson("/api/v1/attempts/{$challenge['id']}/finish")->assertConflict();
        $this->start()->assertCreated();
        $this->assertSame(0, (int) $this->user->stats()->value('total_xp'));
    }

    public function test_fortsetzen_zeigt_dieselben_fragen_und_den_stand(): void
    {
        $challenge = $this->start()->json('data');
        $this->answer($challenge['id'], $challenge['questions'][0]['id'], true);
        $this->answer($challenge['id'], $challenge['questions'][1]['id'], false);

        $this->getJson('/api/v1/challenges/current')->assertOk()
            ->assertJsonPath('data.id', $challenge['id'])
            ->assertJsonPath('data.questions', $challenge['questions'])
            ->assertJsonPath('data.answered', [
                ['question_id' => $challenge['questions'][0]['id'], 'correct' => true],
                ['question_id' => $challenge['questions'][1]['id'], 'correct' => false],
            ]);

        $this->postJson("/api/v1/attempts/{$challenge['id']}/finish")->assertOk();
        $this->getJson('/api/v1/challenges/current')->assertJsonPath('data', null);
    }

    public function test_antwort_bringt_feste_ep_ohne_booster_und_ohne_tagesregel(): void
    {
        $this->user->forceFill(['booster_multiplier' => 2.0, 'booster_ends_at' => now()->addMinutes(30)])->save();
        $challenge = $this->start()->json('data');
        $question = $challenge['questions'][0]['id'];
        $this->earnedTodayInLesson($question);

        $this->answer($challenge['id'], $question, true)->assertOk()
            ->assertJsonPath('data.xp', 5)
            ->assertJsonPath('data.multiplier', 1);
        $this->postJson("/api/v1/attempts/{$challenge['id']}/finish")->assertOk()
            ->assertJsonPath('data.xp_earned', 5)
            ->assertJsonPath('data.xp_from_booster', 0);
    }

    public function test_frage_ausserhalb_der_herausforderung_wird_abgelehnt(): void
    {
        $challenge = $this->start()->json('data');
        $outside = Question::whereNotIn('id', collect($challenge['questions'])->pluck('id'))->firstOrFail();

        $this->answer($challenge['id'], $outside->id, true)->assertUnprocessable()->assertJsonValidationErrors(['question_id']);
    }

    public function test_alle_richtig_bringt_bonus_50_und_eine_trophaee(): void
    {
        $this->play($this->start()->json('data'), correct: 10)
            ->assertJsonPath('data.xp_earned', 100)
            ->assertJsonPath('data.xp_bonus', 50)
            ->assertJsonPath('data.trophies_earned', 1)
            ->assertJsonPath('data.correct_count', 10)
            ->assertJsonPath('data.total_questions', 10);

        $stats = $this->user->stats()->first();
        $this->assertSame(100, $stats->total_xp);
        $this->assertSame(1, $stats->trophies);
    }

    public function test_acht_richtig_bringt_bonus_20_ohne_trophaee(): void
    {
        $this->play($this->start()->json('data'), correct: 8)
            ->assertJsonPath('data.xp_earned', 60)
            ->assertJsonPath('data.xp_bonus', 20)
            ->assertJsonPath('data.trophies_earned', 0);
    }

    public function test_sieben_richtig_ohne_bonus(): void
    {
        $this->play($this->start()->json('data'), correct: 7)
            ->assertJsonPath('data.xp_earned', 35)
            ->assertJsonPath('data.xp_bonus', 0);
    }

    public function test_abschluss_zaehlt_als_lerntag_und_bringt_den_erfolg(): void
    {
        Achievement::factory()->rule(AchievementRule::ChallengesFinished)->trophies(1)->create(['slug' => 'first-challenge']);

        $this->play($this->start()->json('data'), correct: 10)
            ->assertJsonPath('data.streak.today', true)
            ->assertJsonPath('data.achievements_unlocked.0.slug', 'first-challenge')
            ->assertJsonPath('data.trophies_earned', 2);
    }

    public function test_herausforderung_sperrt_keine_fragen_in_lerneinheiten(): void
    {
        $challenge = $this->start()->json('data');
        $this->play($challenge, correct: 10);
        $question = Question::findOrFail($challenge['questions'][0]['id']);

        $this->getJson('/api/v1/shop')->assertJsonPath('data.xp_left_today', true);
        $lesson = $this->postJson("/api/v1/topics/{$question->topic_id}/attempts")->assertCreated()
            ->assertJsonMissingPath('data.questions.0.subject')
            ->json('data.id');
        $this->answer($lesson, $question->id, true)->assertJsonPath('data.xp', 2);
    }

    public function test_fach_level_zaehlt_die_antworten_aber_nicht_den_bonus(): void
    {
        $this->play($this->start()->json('data'), correct: 10);

        $xp = QuestionAnswer::xpBySubject($this->user->id);
        $this->assertSame(50, $xp->sum());
        $this->assertSame(100, $this->user->stats()->first()->total_xp);
    }

    public function test_hub_zeigt_den_stand_der_herausforderung(): void
    {
        $this->actingAs($this->user)->getJson('/api/v1/hub')->assertOk()
            ->assertJsonPath('data.challenge', ['cost' => 25, 'questions' => 10, 'left_today' => 2, 'open' => null, 'locked' => false]);

        $challenge = $this->start()->json('data');
        $this->answer($challenge['id'], $challenge['questions'][0]['id'], true);
        $this->getJson('/api/v1/hub')
            ->assertJsonPath('data.challenge.left_today', 1)
            ->assertJsonPath('data.challenge.open', ['id' => $challenge['id'], 'answered' => 1, 'total' => 10]);

        $this->postJson("/api/v1/attempts/{$challenge['id']}/finish");
        $this->play($this->start()->json('data'), correct: 1, total: 1);
        $this->getJson('/api/v1/hub')
            ->assertJsonPath('data.challenge.left_today', 0)
            ->assertJsonPath('data.challenge.open', null);
    }

    public function test_hub_ohne_genug_gelernte_fragen_gesperrt(): void
    {
        $this->actingAs($this->student())->getJson('/api/v1/hub')->assertJsonPath('data.challenge.locked', true);
    }

    public function test_hub_nimmt_das_fach_der_letzten_lerneinheit_nicht_der_herausforderung(): void
    {
        $this->topic($this->pos, 'arrays', ['order_index' => 2]);
        $this->topic($this->dbi, 'joins', ['order_index' => 2]);
        $this->learned($this->sql, at: now()->subHour());
        $challenge = $this->start()->json('data');
        $this->play($challenge, correct: 1, total: 1);

        $finishedAt = QuizAttempt::findOrFail($challenge['id'])->finished_at->toIso8601String();
        $this->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'joins')
            ->assertJsonPath('data.last_study_at', $finishedAt);
    }

    private function student(): User
    {
        $class = SchoolClass::factory()->withSubjects($this->pos, $this->dbi)->create();

        return User::factory()->inClass($class)->brainpower(100)->create();
    }

    /** Veröffentlichtes Thema mit 6 Fragen (je eine richtige und eine falsche Antwort). */
    private function topic(Subject $subject, string $slug, array $extra = []): Topic
    {
        $topic = $subject->topics()->create(['slug' => $slug, 'title' => ['de' => $slug], 'status' => ContentStatus::Published, ...$extra]);
        foreach (range(1, 6) as $i) {
            $question = $topic->questions()->create([
                'question_text' => ['de' => "{$slug} {$i}"], 'type' => QuestionType::SingleChoice,
                'status' => ContentStatus::Published, 'xp_reward' => 2, 'order_index' => $i,
            ]);
            $question->options()->create(['option_text' => ['de' => 'richtig'], 'is_correct' => true, 'order_index' => 0]);
            $question->options()->create(['option_text' => ['de' => 'falsch'], 'is_correct' => false, 'order_index' => 1]);
        }

        return $topic;
    }

    private function learned(Topic $topic, ?User $user = null, ?CarbonInterface $at = null): void
    {
        QuizAttempt::create(['user_id' => ($user ?? $this->user)->id, 'topic_id' => $topic->id, 'finished_at' => $at ?? now()->subDay()]);
    }

    private function earnedTodayInLesson(string $questionId): void
    {
        $question = Question::findOrFail($questionId);
        $lesson = QuizAttempt::create(['user_id' => $this->user->id, 'topic_id' => $question->topic_id, 'finished_at' => now()]);
        QuestionAnswer::create([
            'quiz_attempt_id' => $lesson->id, 'user_id' => $this->user->id, 'question_id' => $questionId,
            'selected_option_ids' => [], 'is_correct' => true, 'xp_awarded' => 2, 'xp_date' => DailyXp::today(),
        ]);
    }

    private function start(?User $user = null): TestResponse
    {
        if ($user) {
            $this->flushSession();
        }

        return $this->actingAs($user ?? $this->user)->postJson('/api/v1/challenges');
    }

    private function answer(string $attemptId, string $questionId, bool $correct): TestResponse
    {
        $option = Question::findOrFail($questionId)->options()->where('is_correct', $correct)->value('id');

        return $this->postJson("/api/v1/attempts/{$attemptId}/answers", ['question_id' => $questionId, 'option_ids' => [$option]]);
    }

    /** Beantwortet die ersten $total Fragen, davon die ersten $correct richtig, und schließt ab. */
    private function play(array $challenge, int $correct, int $total = 10): TestResponse
    {
        foreach (array_slice($challenge['questions'], 0, $total) as $i => $question) {
            $this->answer($challenge['id'], $question['id'], $i < $correct)->assertOk();
        }

        return $this->postJson("/api/v1/attempts/{$challenge['id']}/finish")->assertOk();
    }
}
