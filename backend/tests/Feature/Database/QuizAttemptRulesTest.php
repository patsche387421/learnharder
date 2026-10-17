<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regeln, die Postgres für Lerneinheiten durchsetzt (E-045): Eine Lerneinheit braucht ein Thema,
 * eine Herausforderung ihre Fragenliste. Bonus und Trophäen sind nie negativ.
 */
class QuizAttemptRulesTest extends TestCase
{
    use RefreshDatabase;

    private const CHECK_VIOLATION = '23514';

    private int $userId;

    private string $topicId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = User::factory()->create()->id;
        $subjectId = $this->insert('subjects', ['slug' => 'pos', 'name' => json_encode(['de' => 'POS'])]);
        $this->topicId = $this->insert('topics', ['subject_id' => $subjectId, 'slug' => 'schleifen', 'title' => json_encode(['de' => 'Schleifen'])]);
    }

    public function test_lerneinheit_startet_als_standard_mit_null_bonus(): void
    {
        $id = $this->attempt(['topic_id' => $this->topicId]);
        $row = DB::table('quiz_attempts')->where('id', $id)->first();

        $this->assertSame('standard', $row->attempt_type);
        $this->assertSame(0, $row->xp_bonus);
        $this->assertSame(0, $row->trophies_earned);
        $this->assertNull($row->question_ids);
    }

    public function test_lerneinheit_braucht_ein_thema(): void
    {
        $this->assertRejected(fn () => $this->attempt(['topic_id' => null]));
    }

    public function test_herausforderung_ohne_thema_aber_mit_fragenliste(): void
    {
        $this->attempt(['attempt_type' => 'challenge', 'topic_id' => null, 'question_ids' => json_encode([])]);

        $this->assertRejected(fn () => $this->attempt(['attempt_type' => 'challenge', 'topic_id' => null]));
        $this->assertSame(1, DB::table('quiz_attempts')->count());
    }

    public function test_unbekannte_art_wird_abgelehnt(): void
    {
        $this->assertRejected(fn () => $this->attempt(['attempt_type' => 'duel', 'topic_id' => $this->topicId]));
    }

    public function test_bonus_und_trophaeen_nicht_negativ(): void
    {
        $this->assertRejected(fn () => $this->attempt(['topic_id' => $this->topicId, 'xp_bonus' => -1]));
        $this->assertRejected(fn () => $this->attempt(['topic_id' => $this->topicId, 'trophies_earned' => -1]));
    }

    private function attempt(array $values): string
    {
        return $this->insert('quiz_attempts', ['user_id' => $this->userId, ...$values]);
    }

    private function assertRejected(callable $statement): void
    {
        try {
            DB::transaction($statement);
        } catch (QueryException $e) {
            $this->assertSame(self::CHECK_VIOLATION, $e->getCode(), $e->getMessage());

            return;
        }

        $this->fail('Postgres hätte mit SQLSTATE '.self::CHECK_VIOLATION.' ablehnen müssen.');
    }

    private function insert(string $table, array $values): string
    {
        $id = (string) Str::uuid7();
        DB::table($table)->insert(['id' => $id, ...$values]);

        return $id;
    }
}
