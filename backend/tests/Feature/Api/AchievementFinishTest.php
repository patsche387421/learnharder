<?php

namespace Tests\Feature\Api;

use App\Enums\AchievementRule;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Achievement;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

/**
 * Erfolge beim Abschluss einer Lerneinheit (E-042, E-043): Die Antwort nennt die neuen Erfolge und ihre Trophäen,
 * ein wiederholter Abschluss zeigt dasselbe und schreibt nichts doppelt gut.
 */
class AchievementFinishTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private User $user;

    private Topic $schleifen;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        $this->travelTo(CarbonImmutable::parse('2026-10-16 10:00', 'Europe/Vienna'));

        $this->user = User::factory()->create();
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->addToCurriculum($this->enrollStudent($this->user), $pos);
        $this->schleifen = $pos->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen'], 'status' => ContentStatus::Published]);
        $this->question = $this->schleifen->questions()->create([
            'question_text' => ['de' => 'Frage'], 'type' => QuestionType::SingleChoice, 'status' => ContentStatus::Published, 'xp_reward' => 2,
        ]);
        $this->question->options()->create(['option_text' => ['de' => 'richtig'], 'is_correct' => true, 'order_index' => 0]);
        $this->question->options()->create(['option_text' => ['de' => 'falsch'], 'is_correct' => false, 'order_index' => 1]);
    }

    public function test_abschluss_nennt_neue_erfolge_mit_trophaeen(): void
    {
        Achievement::factory()->trophies(1)->create(['slug' => 'first-lesson', 'title' => ['de' => 'Erste Lerneinheit'], 'icon_name' => 'xp', 'sort' => 1]);
        Achievement::factory()->rule(AchievementRule::SubjectsLearned)->trophies(2)->create(['slug' => 'ein-fach', 'sort' => 2]);
        Achievement::factory()->rule(AchievementRule::LessonsFinished, 2)->create(['slug' => 'zwei']);

        $this->lesson()
            ->assertJsonPath('data.achievements_unlocked', [
                ['slug' => 'first-lesson', 'title' => 'Erste Lerneinheit', 'icon_name' => 'xp', 'trophies' => 1],
                ['slug' => 'ein-fach', 'title' => 'Erste Lerneinheit', 'icon_name' => 'achievement', 'trophies' => 2],
            ])
            ->assertJsonPath('data.trophies_earned', 3);

        $this->assertSame(3, $this->user->stats()->first()->trophies);
    }

    public function test_wiederholter_abschluss_zeigt_dasselbe_und_schreibt_nichts_doppelt_gut(): void
    {
        Achievement::factory()->trophies(2)->create(['slug' => 'first-lesson']);
        $attemptId = $this->answered();

        $first = $this->postJson("/api/v1/attempts/{$attemptId}/finish")->assertOk()->json('data');
        $again = $this->postJson("/api/v1/attempts/{$attemptId}/finish")->assertOk()->json('data');

        $this->assertSame($first['achievements_unlocked'], $again['achievements_unlocked']);
        $this->assertSame(2, $again['trophies_earned']);
        $this->assertSame(2, $this->user->stats()->first()->trophies);
        $this->assertSame(1, $this->user->achievements()->count());
    }

    public function test_naechste_lerneinheit_bringt_den_erfolg_nicht_noch_einmal(): void
    {
        Achievement::factory()->create(['slug' => 'first-lesson']);
        $this->lesson();

        $this->lesson()
            ->assertJsonPath('data.achievements_unlocked', [])
            ->assertJsonPath('data.trophies_earned', 0);
    }

    public function test_ohne_erfolge_im_katalog_bleibt_alles_wie_bisher(): void
    {
        $this->lesson()
            ->assertJsonPath('data.achievements_unlocked', [])
            ->assertJsonPath('data.trophies_earned', 0)
            ->assertJsonPath('data.xp_earned', 2);
    }

    private function lesson(): TestResponse
    {
        return $this->postJson("/api/v1/attempts/{$this->answered()}/finish")->assertOk();
    }

    private function answered(): string
    {
        $attemptId = $this->actingAs($this->user)->postJson("/api/v1/topics/{$this->schleifen->id}/attempts")->assertCreated()->json('data.id');
        $right = $this->question->options()->where('is_correct', true)->value('id');
        $this->postJson("/api/v1/attempts/{$attemptId}/answers", ['question_id' => $this->question->id, 'option_ids' => [$right]])->assertOk();

        return $attemptId;
    }
}
