<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Prüft die Regeln, die Postgres selbst durchsetzt (E-013, docs/plans/DATENMODELL_SCHULE.md).
 */
class SchoolStructureTest extends TestCase
{
    use RefreshDatabase;

    private const UNIQUE_VIOLATION = '23505';

    private const FOREIGN_KEY_VIOLATION = '23503';

    private const CHECK_VIOLATION = '23514';

    private const RESTRICT_VIOLATION = '23001';

    private string $schoolId;

    private string $programId;

    private string $yearId;

    private string $subjectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolId = $this->insert('schools', ['name' => 'HTL Mödling']);
        $departmentId = $this->insert('departments', [
            'school_id' => $this->schoolId, 'code' => 'IT', 'name' => 'Informationstechnologie',
        ]);
        $this->programId = $this->insert('programs', [
            'department_id' => $departmentId, 'code' => 'HIT', 'name' => 'Höhere Lehranstalt IT',
            'form' => 'higher_technical', 'unit' => 'year', 'duration' => 5,
        ]);
        $this->yearId = $this->schoolYear('2026/27', '2026-09-07');
        $this->subjectId = $this->insert('subjects', ['slug' => 'pos', 'name' => json_encode(['de' => 'POS'])]);
    }

    public function test_nur_eine_aktive_klasse_pro_schuljahr(): void
    {
        $student = User::factory()->create();
        $this->enroll($student, $this->schoolClass('2AHIT'));

        $this->assertRejected(self::UNIQUE_VIOLATION, fn () => $this->enroll($student, $this->schoolClass('2BHIT')));
    }

    public function test_klassenwechsel_behaelt_den_verlauf(): void
    {
        $student = User::factory()->create();
        $enrollmentId = $this->enroll($student, $this->schoolClass('2AHIT'));
        DB::table('class_enrollments')->where('id', $enrollmentId)->update(['left_at' => now()->addMonth()]);

        $this->enroll($student, $this->schoolClass('2BHIT'));

        $this->assertSame(2, DB::table('class_enrollments')->where('user_id', $student->id)->count());
    }

    public function test_einschreibung_mit_fremdem_schuljahr_wird_abgelehnt(): void
    {
        $student = User::factory()->create();
        $classId = $this->schoolClass('2AHIT');
        $nextYearId = $this->schoolYear('2027/28', '2027-09-06');

        $this->assertRejected(self::FOREIGN_KEY_VIOLATION, fn () => DB::table('class_enrollments')->insert([
            'school_class_id' => $classId, 'school_year_id' => $nextYearId, 'user_id' => $student->id,
        ]));
    }

    public function test_mehrere_lehrkraefte_pro_fach_und_klasse(): void
    {
        $classId = $this->schoolClass('2AHIT');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assign($classId, $first);
        $this->assign($classId, $second);

        $this->assertSame(2, DB::table('teaching_assignments')->where('school_class_id', $classId)->count());
        // Gleiche Zuordnung ohne Semester ist doppelt, auch wenn semester NULL ist
        $this->assertRejected(self::UNIQUE_VIOLATION, fn () => $this->assign($classId, $first));
    }

    public function test_semester_ist_nur_eins_oder_zwei(): void
    {
        $classId = $this->schoolClass('2AHIT');

        $this->assertRejected(self::CHECK_VIOLATION, fn () => $this->assign($classId, User::factory()->create(), 3));
    }

    public function test_lehrplan_prueft_die_abschnitte(): void
    {
        $this->assertRejected(self::CHECK_VIOLATION, fn () => $this->insert('curriculum_entries', [
            'program_id' => $this->programId, 'subject_id' => $this->subjectId, 'stage_from' => 3, 'stage_to' => 2,
        ]));
    }

    public function test_user_loeschen_entfernt_seine_einschreibungen(): void
    {
        $student = User::factory()->create();
        $this->enroll($student, $this->schoolClass('2AHIT'));

        $student->delete();

        $this->assertSame(0, DB::table('class_enrollments')->count());
    }

    public function test_lehrkraft_mit_zuordnung_kann_nicht_geloescht_werden(): void
    {
        $teacher = User::factory()->create();
        $this->assign($this->schoolClass('2AHIT'), $teacher);

        $this->assertRejected(self::RESTRICT_VIOLATION, fn () => $teacher->delete());
    }

    public function test_klassenvorstand_loeschen_leert_nur_den_verweis(): void
    {
        $headTeacher = User::factory()->create();
        $classId = $this->schoolClass('2AHIT', $headTeacher);

        $headTeacher->delete();

        $this->assertNull(DB::table('school_classes')->where('id', $classId)->value('head_teacher_id'));
    }

    public function test_thema_mit_verlauf_kann_nicht_geloescht_werden(): void
    {
        $topicId = $this->insert('topics', [
            'subject_id' => $this->subjectId, 'slug' => 'variablen', 'title' => json_encode(['de' => 'Variablen']),
        ]);
        $this->insert('quiz_attempts', ['user_id' => User::factory()->create()->id, 'topic_id' => $topicId]);

        $this->assertRejected(self::RESTRICT_VIOLATION, fn () => DB::table('topics')->where('id', $topicId)->delete());
    }

    public function test_email_und_gamer_tag_sind_ohne_gross_kleinschreibung_eindeutig(): void
    {
        User::factory()->create(['email' => 'Max@htl.at', 'gamer_tag' => 'CodeNinja']);

        $this->assertRejected(self::UNIQUE_VIOLATION, fn () => User::factory()->create(['email' => 'max@htl.at']));
        $this->assertRejected(self::UNIQUE_VIOLATION, fn () => User::factory()->create(['gamer_tag' => 'codeninja']));
    }

    public function test_mail_domain_ist_ohne_gross_kleinschreibung_eindeutig(): void
    {
        $this->insert('school_email_domains', ['school_id' => $this->schoolId, 'domain' => 'htl.moedling.at']);

        $this->assertRejected(self::UNIQUE_VIOLATION, fn () => $this->insert('school_email_domains', [
            'school_id' => $this->schoolId, 'domain' => 'HTL.Moedling.at',
        ]));
    }

    /**
     * Führt den Aufruf in einem Savepoint aus, damit die Test-Transaktion nach dem Fehler weiterläuft.
     */
    private function assertRejected(string $sqlState, callable $statement): void
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

    private function schoolYear(string $name, string $startsOn): string
    {
        $start = now()->parse($startsOn);

        return $this->insert('school_years', [
            'school_id' => $this->schoolId, 'name' => $name, 'starts_on' => $start,
            'second_term_starts_on' => $start->copy()->addMonths(5), 'ends_on' => $start->copy()->addMonths(10),
        ]);
    }

    private function schoolClass(string $name, ?User $headTeacher = null): string
    {
        return $this->insert('school_classes', [
            'program_id' => $this->programId, 'school_year_id' => $this->yearId, 'name' => $name,
            'stage' => 2, 'head_teacher_id' => $headTeacher?->id,
        ]);
    }

    private function enroll(User $student, string $classId): int
    {
        return DB::table('class_enrollments')->insertGetId([
            'school_class_id' => $classId, 'school_year_id' => $this->yearId, 'user_id' => $student->id,
        ]);
    }

    private function assign(string $classId, User $teacher, ?int $semester = null): void
    {
        $this->insert('teaching_assignments', [
            'school_class_id' => $classId, 'subject_id' => $this->subjectId,
            'teacher_id' => $teacher->id, 'semester' => $semester,
        ]);
    }
}
