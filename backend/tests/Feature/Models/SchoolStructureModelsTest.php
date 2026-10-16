<?php

namespace Tests\Feature\Models;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\ClassEnrollment;
use App\Models\Department;
use App\Models\Program;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SchoolStructureModelsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'HTL Mödling']);
        $this->department = $this->school->departments()->create(['code' => 'ET', 'name' => 'Elektrotechnik']);
    }

    public function test_schule_abteilung_und_ausbildungsgang_haengen_zusammen(): void
    {
        $program = $this->program(ProgramForm::HigherTechnical, ProgramUnit::Year, 5);

        $this->assertTrue($this->school->departments->first()->is($this->department));
        $this->assertTrue($this->department->programs->first()->is($program));
        $this->assertTrue($program->department->school->is($this->school));
    }

    public function test_ausbildungsgang_liefert_enums(): void
    {
        $program = $this->program(ProgramForm::College, ProgramUnit::Semester, 4)->fresh();

        $this->assertSame(ProgramForm::College, $program->form);
        $this->assertSame(ProgramUnit::Semester, $program->unit);
        $this->assertSame('Kolleg', $program->form->getLabel());
    }

    public function test_jede_ausbildungsform_passt_zur_datenbankregel(): void
    {
        // Enum und CHECK-Regel in der Migration dürfen nicht auseinanderlaufen
        foreach (ProgramForm::cases() as $form) {
            foreach (ProgramUnit::cases() as $unit) {
                $this->program($form, $unit, 1);
            }
        }

        $this->assertSame(count(ProgramForm::cases()) * count(ProgramUnit::cases()), Program::count());
    }

    public function test_lehrplan_verbindet_fach_und_ausbildungsgang_mit_abschnitten(): void
    {
        $program = $this->program(ProgramForm::HigherTechnical, ProgramUnit::Year, 5);
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);

        $program->subjects()->attach($subject, ['stage_from' => 1, 'stage_to' => 5]);

        $entry = $program->subjects()->first()->pivot;
        $this->assertTrue(str($entry->id)->isUuid());
        $this->assertSame([1, 5], [$entry->stage_from, $entry->stage_to]);
        $this->assertTrue($program->curriculumEntries->first()->subject->is($subject));
    }

    public function test_mail_domain_wird_klein_und_ohne_leerzeichen_gespeichert(): void
    {
        $domain = $this->school->emailDomains()->create(['domain' => '  HTL.Moedling.AT ']);

        $this->assertSame('htl.moedling.at', $domain->fresh()->domain);
        $this->assertFalse($domain->fresh()->allows_registration);
    }

    public function test_schuljahre_sind_nach_beginn_sortiert_und_liefern_daten(): void
    {
        $this->schoolYear('2027/28', '2027-09-06');
        $this->schoolYear('2026/27', '2026-09-07');

        $years = $this->school->schoolYears;

        $this->assertSame(['2026/27', '2027/28'], $years->pluck('name')->all());
        $this->assertInstanceOf(Carbon::class, $years->first()->second_term_starts_on);
    }

    public function test_klasse_haengt_an_ausbildungsgang_schuljahr_und_lehrkraeften(): void
    {
        $program = $this->program(ProgramForm::HigherTechnical, ProgramUnit::Year, 5);
        $year = $this->schoolYear('2026/27', '2026-09-07');
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);

        $class = $this->schoolClass($program, $year, '2AHIT', 2, ['head_teacher_id' => $teacher->id]);
        $assignment = TeachingAssignment::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        $enrollment = $class->enrollments()->create(['user_id' => $student->id]);

        $this->assertTrue($class->program->is($program));
        $this->assertTrue($class->schoolYear->is($year));
        $this->assertTrue($class->headTeacher->is($teacher));
        $this->assertTrue($program->classes->first()->is($class));
        $this->assertTrue($year->classes->first()->is($class));
        $this->assertTrue($class->teachingAssignments->first()->is($assignment));
        $this->assertTrue($assignment->teacher->is($teacher) && $assignment->subject->is($subject));
        // Das Schuljahr der Einschreibung kommt von der Klasse
        $this->assertSame($year->id, $enrollment->school_year_id);
        $this->assertTrue($student->enrollments->first()->schoolClass->is($class));
    }

    public function test_aktive_einschreibung_ist_die_neueste_ohne_austritt(): void
    {
        $program = $this->program(ProgramForm::HigherTechnical, ProgramUnit::Year, 5);
        $student = User::factory()->create();
        $first = $this->schoolClass($program, $this->schoolYear('2025/26', '2025-09-01'), '1AHIT', 1)
            ->enrollments()->create(['user_id' => $student->id, 'enrolled_at' => '2025-09-01']);
        $second = $this->schoolClass($program, $this->schoolYear('2026/27', '2026-09-07'), '2AHIT', 2)
            ->enrollments()->create(['user_id' => $student->id, 'enrolled_at' => '2026-09-07']);

        $this->assertTrue($student->activeEnrollment->is($second));

        $second->update(['left_at' => '2026-10-01']);
        $this->assertTrue($student->fresh()->activeEnrollment->is($first));

        $first->update(['left_at' => '2026-10-01']);
        $this->assertNull($student->fresh()->activeEnrollment);
        $this->assertSame(0, ClassEnrollment::active()->count());
    }

    public function test_abschnitt_zaehlt_im_zweiten_semester_eins_dazu(): void
    {
        $year = $this->schoolYear('2026/27', '2026-09-07'); // 2. Semester ab 2027-02-07
        $yearly = $this->schoolClass($this->program(ProgramForm::HigherTechnical, ProgramUnit::Year, 5), $year, '2AHIT', 2);
        $college = $this->schoolClass($this->program(ProgramForm::College, ProgramUnit::Semester, 4), $year, '3BKIT', 3);

        $this->assertSame(2, $yearly->currentStage(CarbonImmutable::parse('2027-03-01')));
        $this->assertSame(3, $college->currentStage(CarbonImmutable::parse('2027-02-06')));
        $this->assertSame(4, $college->currentStage(CarbonImmutable::parse('2027-02-07')));
    }

    public function test_semesterwechsel_gilt_nach_wiener_zeit(): void
    {
        $year = $this->schoolYear('2026/27', '2026-09-07');
        $college = $this->schoolClass($this->program(ProgramForm::College, ProgramUnit::Semester, 4), $year, '3BKIT', 3);

        $this->travelTo(CarbonImmutable::parse('2027-02-06 22:30', 'UTC')); // 23:30 in Wien
        $this->assertSame(3, $college->currentStage());

        $this->travelTo(CarbonImmutable::parse('2027-02-06 23:30', 'UTC')); // 00:30 in Wien
        $this->assertSame(4, $college->currentStage());
    }

    private function schoolClass(Program $program, SchoolYear $year, string $name, int $stage, array $extra = []): SchoolClass
    {
        return SchoolClass::create(['program_id' => $program->id, 'school_year_id' => $year->id, 'name' => $name, 'stage' => $stage, ...$extra]);
    }

    private function program(ProgramForm $form, ProgramUnit $unit, int $duration): Program
    {
        return $this->department->programs()->create([
            'code' => 'P'.(Program::count() + 1),
            'name' => $form->getLabel(),
            'form' => $form,
            'unit' => $unit,
            'duration' => $duration,
        ]);
    }

    private function schoolYear(string $name, string $startsOn): SchoolYear
    {
        $start = Carbon::parse($startsOn);

        return $this->school->schoolYears()->create([
            'name' => $name,
            'starts_on' => $start,
            'second_term_starts_on' => $start->copy()->addMonths(5),
            'ends_on' => $start->copy()->addMonths(10),
        ]);
    }
}
