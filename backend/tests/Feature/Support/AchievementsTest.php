<?php

namespace Tests\Feature\Support;

use App\Enums\AchievementRule;
use App\Enums\AttemptType;
use App\Models\Achievement;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\Achievements;
use App\Support\Progression;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bedingungen der Erfolge je Typ (E-042) und die Vergabe mit Trophäen (E-043).
 * Lernverlauf entsteht hier direkt in der Datenbank, der Ablauf über die API steht in AchievementFinishTest.
 */
class AchievementsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Topic $schleifen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));
        $this->user = User::factory()->create();
        $this->schleifen = $this->topic('pos', 'schleifen');
    }

    public function test_abgeschlossene_lerneinheiten_ohne_offene_und_ohne_herausforderungen(): void
    {
        $zwei = Achievement::factory()->rule(AchievementRule::LessonsFinished, 2)->create();
        $this->attempt();
        $this->attempt(finished: false);
        $this->attempt(type: AttemptType::Challenge);

        $this->assertNotUnlocked($zwei);

        $this->attempt();
        $this->assertUnlocked($zwei);
    }

    public function test_fehlerfrei_erst_ab_fuenf_fragen_und_alle_richtig(): void
    {
        $fehlerfrei = Achievement::factory()->rule(AchievementRule::PerfectLessons)->create();
        $this->attempt(total: 4, correct: 4);
        $this->attempt(total: 5, correct: 4);

        $this->assertNotUnlocked($fehlerfrei);

        $this->attempt(total: 5, correct: 5);
        $this->assertUnlocked($fehlerfrei);
    }

    public function test_streak_zaehlt_den_laengsten_auch_nachtraeglich(): void
    {
        $user = User::factory()->streak(2, longest: 7)->create();
        $woche = Achievement::factory()->rule(AchievementRule::Streak, 7)->create();
        $monat = Achievement::factory()->rule(AchievementRule::Streak, 30)->create();

        $this->assertEquals([$woche->id], Achievements::check($user)->pluck('id')->all());
        $this->assertFalse($user->achievements()->whereKey($monat->id)->exists());
    }

    public function test_level_gilt_ab_prestige_immer(): void
    {
        $zehn = Achievement::factory()->rule(AchievementRule::Level, 10)->create();
        $silber = Achievement::factory()->rule(AchievementRule::Level, 25)->create();

        $level10 = User::factory()->level(10)->create();
        $this->assertEquals([$zehn->id], Achievements::check($level10)->pluck('id')->all());

        $prestige = User::factory()->level(1, prestige: 1)->create();
        $this->assertEqualsCanonicalizing([$zehn->id, $silber->id], Achievements::check($prestige)->pluck('id')->all());
    }

    public function test_fach_level_in_irgendeinem_oder_einem_bestimmten_fach(): void
    {
        $dbi = $this->topic('dbi', 'sql')->subject;
        $irgendeins = Achievement::factory()->subjectLevel(10)->create();
        $inDbi = Achievement::factory()->subjectLevel(10, $dbi)->create();
        $this->answer($this->attempt(), $this->question($this->schleifen), true, xp: Progression::threshold(10));

        $this->assertEquals([$irgendeins->id], Achievements::check($this->user)->pluck('id')->all());
        $this->assertFalse($this->user->achievements()->whereKey($inDbi->id)->exists());
    }

    public function test_abgeschlossene_herausforderungen(): void
    {
        $erste = Achievement::factory()->rule(AchievementRule::ChallengesFinished)->create();
        $this->attempt();
        $this->attempt(type: AttemptType::Challenge, finished: false);

        $this->assertNotUnlocked($erste);

        $this->attempt(type: AttemptType::Challenge);
        $this->assertUnlocked($erste);
    }

    public function test_aus_fehlern_gelernt_nur_falsch_dann_spaeter_richtig(): void
    {
        $gelernt = Achievement::factory()->rule(AchievementRule::CorrectedMistakes)->secret()->create();
        $erst = $this->question($this->schleifen);
        $dann = $this->question($this->schleifen);

        // Erst richtig, später falsch zählt nicht
        $this->answer($this->attempt(), $dann, true);
        $this->travel(1)->days();
        $this->answer($this->attempt(), $dann, false);
        $this->answer($this->attempt(), $erst, false);
        $this->assertNotUnlocked($gelernt);

        $this->travel(1)->days();
        $this->answer($this->attempt(), $erst, true);
        $this->assertUnlocked($gelernt);
    }

    public function test_faecher_mit_mindestens_einer_lerneinheit(): void
    {
        $allrounder = Achievement::factory()->rule(AchievementRule::SubjectsLearned, 3)->create();
        $this->attempt();
        $this->attempt();
        $this->attempt(topic: $this->topic('dbi', 'sql'));
        $this->attempt(topic: $this->topic('syp', 'scrum'), finished: false);

        $this->assertNotUnlocked($allrounder);

        $this->attempt(topic: Topic::where('slug', 'scrum')->first());
        $this->assertUnlocked($allrounder);
    }

    public function test_vergabe_einmal_mit_trophaeen_und_nur_aktive(): void
    {
        $erste = Achievement::factory()->trophies(2)->create(['sort' => 2]);
        $auch = Achievement::factory()->trophies(3)->create(['sort' => 1]);
        $aus = Achievement::factory()->inactive()->trophies(5)->create();
        $this->attempt();

        $this->assertEquals([$auch->id, $erste->id], Achievements::check($this->user)->pluck('id')->all());
        $this->assertCount(0, Achievements::check($this->user));

        $this->assertSame(5, $this->user->stats()->first()->trophies);
        $this->assertFalse($this->user->achievements()->whereKey($aus->id)->exists());
        // Eine spätere Änderung im CMS verschiebt nichts
        $erste->update(['trophy_reward' => 10]);
        $this->assertSame(2, $this->user->achievements()->whereKey($erste->id)->first()->pivot->trophies_awarded);
    }

    public function test_ohne_fehlende_erfolge_wird_nichts_gerechnet(): void
    {
        $this->attempt();

        $this->assertCount(0, Achievements::check($this->user));
        $this->assertSame(0, $this->user->stats()->first()->trophies);
    }

    private function assertUnlocked(Achievement $achievement): void
    {
        Achievements::check($this->user);
        $this->assertTrue($this->user->achievements()->whereKey($achievement->id)->exists(), "{$achievement->rule->value} fehlt");
    }

    private function assertNotUnlocked(Achievement $achievement): void
    {
        Achievements::check($this->user);
        $this->assertFalse($this->user->achievements()->whereKey($achievement->id)->exists(), "{$achievement->rule->value} zu früh");
    }

    private function topic(string $subject, string $slug): Topic
    {
        return Subject::firstOrCreate(['slug' => $subject], ['name' => ['de' => strtoupper($subject)]])
            ->topics()->create(['slug' => $slug, 'title' => ['de' => $slug]]);
    }

    private function question(Topic $topic): Question
    {
        return $topic->questions()->create(['question_text' => ['de' => 'Frage?']]);
    }

    /** Herausforderungen ohne Thema, mit Fragenliste (E-045). */
    private function attempt(?Topic $topic = null, AttemptType $type = AttemptType::Standard, bool $finished = true, int $total = 1, int $correct = 1): QuizAttempt
    {
        $challenge = $type === AttemptType::Challenge;

        return QuizAttempt::create([
            'user_id' => $this->user->id, 'attempt_type' => $type,
            'topic_id' => $challenge ? null : ($topic ?? $this->schleifen)->id, 'question_ids' => $challenge ? [] : null,
            'total_questions' => $total, 'correct_count' => $correct, 'finished_at' => $finished ? now() : null,
        ]);
    }

    private function answer(QuizAttempt $attempt, Question $question, bool $correct, int $xp = 0): void
    {
        QuestionAnswer::create([
            'quiz_attempt_id' => $attempt->id, 'user_id' => $this->user->id, 'question_id' => $question->id,
            'selected_option_ids' => [], 'is_correct' => $correct, 'xp_awarded' => $xp, 'xp_date' => now()->toDateString(),
        ]);
    }
}
