<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regeln, die Postgres für Erfolge durchsetzt (E-041, E-042): Typen, Schwellwerte, Fach nur beim Fach-Level,
 * eine Vergabe je Person, Verlauf bleibt beim Löschen eines Erfolgs, fällt aber mit dem Konto weg.
 */
class AchievementRulesTest extends TestCase
{
    use RefreshDatabase;

    private const CHECK_VIOLATION = '23514';

    private const UNIQUE_VIOLATION = '23505';

    private const RESTRICT_VIOLATION = '23001';

    public function test_alte_tabellen_sind_weg(): void
    {
        $this->assertFalse(Schema::hasTable('trophies'));
        $this->assertFalse(Schema::hasTable('user_trophies'));
        $this->assertTrue(Schema::hasColumn('user_stats', 'trophies'));
    }

    public function test_neuer_erfolg_hat_sinnvolle_standardwerte(): void
    {
        $id = $this->achievement([]);
        $row = DB::table('achievements')->where('id', $id)->first();

        $this->assertSame('achievement', $row->icon_name);
        $this->assertSame(1, $row->threshold);
        $this->assertFalse($row->is_secret);
        $this->assertTrue($row->is_active);
        $this->assertSame(0, $row->trophy_reward);
    }

    public function test_unbekannter_typ_wird_abgelehnt(): void
    {
        $this->assertRejected(fn () => $this->achievement(['rule' => 'night_owl']), self::CHECK_VIOLATION);
    }

    public function test_schwellwert_mindestens_1_und_trophaeen_nicht_negativ(): void
    {
        $this->assertRejected(fn () => $this->achievement(['threshold' => 0]), self::CHECK_VIOLATION);
        $this->assertRejected(fn () => $this->achievement(['trophy_reward' => -1]), self::CHECK_VIOLATION);
    }

    public function test_fach_nur_beim_fach_level(): void
    {
        $subjectId = $this->subject();

        $this->achievement(['rule' => 'subject_level', 'subject_id' => $subjectId]);
        $this->achievement(['rule' => 'subject_level', 'subject_id' => null]);
        $this->assertRejected(fn () => $this->achievement(['rule' => 'streak', 'subject_id' => $subjectId]), self::CHECK_VIOLATION);
    }

    public function test_jeder_erfolg_nur_einmal_je_person(): void
    {
        $user = User::factory()->create();
        $id = $this->achievement([]);
        DB::table('user_achievements')->insert(['user_id' => $user->id, 'achievement_id' => $id]);

        $this->assertRejected(fn () => DB::table('user_achievements')->insert(['user_id' => $user->id, 'achievement_id' => $id]), self::UNIQUE_VIOLATION);
        $this->assertRejected(
            fn () => DB::table('user_achievements')->insert(['user_id' => User::factory()->create()->id, 'achievement_id' => $id, 'trophies_awarded' => -1]),
            self::CHECK_VIOLATION,
        );
    }

    public function test_vergebener_erfolg_und_sein_fach_lassen_sich_nicht_hart_loeschen(): void
    {
        $subjectId = $this->subject();
        $id = $this->achievement(['rule' => 'subject_level', 'subject_id' => $subjectId]);
        DB::table('user_achievements')->insert(['user_id' => User::factory()->create()->id, 'achievement_id' => $id]);

        $this->assertRejected(fn () => DB::table('achievements')->where('id', $id)->delete(), self::RESTRICT_VIOLATION);
        $this->assertRejected(fn () => DB::table('subjects')->where('id', $subjectId)->delete(), self::RESTRICT_VIOLATION);
    }

    public function test_konto_loeschen_entfernt_seine_erfolge(): void
    {
        $user = User::factory()->create();
        $id = $this->achievement([]);
        DB::table('user_achievements')->insert(['user_id' => $user->id, 'achievement_id' => $id]);

        $user->delete();

        $this->assertSame(0, DB::table('user_achievements')->count());
        $this->assertSame(1, DB::table('achievements')->count());
    }

    private function achievement(array $values): string
    {
        return $this->insert('achievements', [
            'slug' => 'erfolg-'.Str::random(8), 'title' => json_encode(['de' => 'Erfolg']), 'description' => json_encode(['de' => 'Bedingung']),
            'rule' => 'lessons_finished', ...$values,
        ]);
    }

    private function subject(): string
    {
        return $this->insert('subjects', ['slug' => 'pos', 'name' => json_encode(['de' => 'POS'])]);
    }

    private function assertRejected(callable $statement, string $sqlState): void
    {
        try {
            DB::transaction($statement);
        } catch (QueryException $e) {
            $this->assertSame($sqlState, $e->getCode(), $e->getMessage());

            return;
        }

        $this->fail("Postgres hätte mit SQLSTATE {$sqlState} ablehnen müssen.");
    }

    private function insert(string $table, array $values): string
    {
        $id = (string) Str::uuid7();
        DB::table($table)->insert(['id' => $id, ...$values]);

        return $id;
    }
}
