<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Prüft die Wertelisten, die Postgres für Themen und Fragen durchsetzt.
 */
class ContentRulesTest extends TestCase
{
    use RefreshDatabase;

    private const CHECK_VIOLATION = '23514';

    private string $topicId;

    protected function setUp(): void
    {
        parent::setUp();

        $subjectId = $this->insert('subjects', ['slug' => 'pos', 'name' => json_encode(['de' => 'POS'])]);
        $this->topicId = $this->insert('topics', ['subject_id' => $subjectId, 'slug' => 'schleifen', 'title' => json_encode(['de' => 'Schleifen'])]);
    }

    public function test_neue_inhalte_starten_als_entwurf(): void
    {
        $questionId = $this->question([]);

        $this->assertSame('draft', DB::table('topics')->where('id', $this->topicId)->value('status'));
        $this->assertSame('draft', DB::table('questions')->where('id', $questionId)->value('status'));
    }

    public function test_unbekannter_status_wird_abgelehnt(): void
    {
        $this->assertRejected(fn () => DB::table('topics')->where('id', $this->topicId)->update(['status' => 'live']));
        $this->assertRejected(fn () => $this->question(['status' => 'live']));
    }

    public function test_unbekannter_typ_wird_abgelehnt(): void
    {
        $this->assertRejected(fn () => $this->question(['type' => 'fill_blank']));
    }

    public function test_schwierigkeit_nur_von_1_bis_5(): void
    {
        $this->assertRejected(fn () => $this->question(['difficulty' => 0]));
        $this->assertRejected(fn () => $this->question(['difficulty' => 6]));
    }

    private function question(array $values): string
    {
        return $this->insert('questions', ['topic_id' => $this->topicId, 'question_text' => json_encode(['de' => 'Frage?']), ...$values]);
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
