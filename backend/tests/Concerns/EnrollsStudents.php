<?php

namespace Tests\Concerns;

use App\Enums\ProgramUnit;
use App\Models\CurriculumEntry;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Schüler:innen mit Klasse und Lehrplan für API-Tests. Ohne Klasse sehen sie nichts (E-024).
 * Baut auf SchoolClassFactory auf. Neue Tests nehmen besser gleich User::factory()->inClass().
 */
trait EnrollsStudents
{
    /** Eigene Schule, Ausbildungsgang und Schuljahr 2026/27 (2. Semester ab 2027-02-08), Person darin als Schüler:in. */
    protected function enrollStudent(User $user, int $stage = 2, ProgramUnit $unit = ProgramUnit::Year): SchoolClass
    {
        $classes = SchoolClass::factory()->stage($stage);
        $class = ($unit === ProgramUnit::Semester ? $classes->semester() : $classes)->create();

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
