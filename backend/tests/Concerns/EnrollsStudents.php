<?php

namespace Tests\Concerns;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\CurriculumEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Schüler:innen mit Klasse und Lehrplan für API-Tests. Ohne Klasse sehen sie nichts (E-024).
 */
trait EnrollsStudents
{
    /** Eigene Schule, Ausbildungsgang und Schuljahr 2026/27 (2. Semester ab 2027-02-08), Person darin als Schüler:in. */
    protected function enrollStudent(User $user, int $stage = 2, ProgramUnit $unit = ProgramUnit::Year): SchoolClass
    {
        $school = School::create(['name' => 'Test-HTL '.(School::count() + 1)]);
        $program = $school->departments()->create(['code' => 'IT', 'name' => 'Informationstechnologie'])->programs()->create([
            'code' => 'HIT', 'name' => 'Informationstechnologie',
            'form' => $unit === ProgramUnit::Year ? ProgramForm::HigherTechnical : ProgramForm::College,
            'unit' => $unit, 'duration' => 5,
        ]);
        $year = $school->schoolYears()->create(['name' => '2026/27', 'starts_on' => '2026-09-07', 'second_term_starts_on' => '2027-02-08', 'ends_on' => '2027-07-02']);
        $class = SchoolClass::create(['program_id' => $program->id, 'school_year_id' => $year->id, 'name' => $stage.'AHIT', 'stage' => $stage]);

        $user->assignRole(Role::findOrCreate('student', 'web'));
        $class->enrollments()->create(['user_id' => $user->id]);

        return $class;
    }

    /** Fach in den Lehrplan der Klasse, ab Abschnitt $from */
    protected function addToCurriculum(SchoolClass $class, Subject $subject, int $from = 1, int $to = 5): void
    {
        CurriculumEntry::create(['program_id' => $class->program_id, 'subject_id' => $subject->id, 'stage_from' => $from, 'stage_to' => $to]);
    }
}
