<?php

namespace Tests\Feature\Policies;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\CurriculumEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolStructurePolicyTest extends TestCase
{
    use RefreshDatabase;

    /** Aktionen, die Filament prüft. Die mit Datensatz bekommen das Model, die anderen die Klasse. */
    private const RECORD_ABILITIES = ['view', 'update', 'replicate', 'delete', 'restore', 'forceDelete'];

    private const CLASS_ABILITIES = ['viewAny', 'create', 'reorder', 'deleteAny', 'restoreAny', 'forceDeleteAny'];

    public static function roles(): array
    {
        $roles = ['admin', 'autor', 'tester', 'teacher', 'student'];

        return array_combine($roles, array_map(fn (string $role) => [$role], $roles));
    }

    #[DataProvider('roles')]
    public function test_nur_admin_darf_die_schulstruktur_pflegen(string $role): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => $role]));
        $expected = $role === 'admin';

        foreach ($this->structureRecords() as $record) {
            foreach (self::RECORD_ABILITIES as $ability) {
                $this->assertAbility($user, $expected, $ability, $record);
            }
            foreach (self::CLASS_ABILITIES as $ability) {
                $this->assertAbility($user, $expected, $ability, $record::class);
            }
        }
    }

    private function assertAbility(User $user, bool $expected, string $ability, Model|string $target): void
    {
        $this->assertSame($expected, $user->can($ability, $target), "{$ability} auf ".class_basename($target));
    }

    /** @return list<Model> */
    private function structureRecords(): array
    {
        $school = School::create(['name' => 'HTL Mödling']);
        $domain = $school->emailDomains()->create(['domain' => 'htl.moedling.at']);
        $year = $school->schoolYears()->create([
            'name' => '2026/27', 'starts_on' => '2026-09-07', 'second_term_starts_on' => '2027-02-15', 'ends_on' => '2027-07-02',
        ]);
        $department = $school->departments()->create(['code' => 'ET', 'name' => 'Elektrotechnik']);
        $program = $department->programs()->create([
            'code' => 'HET', 'name' => 'Höhere Lehranstalt Elektrotechnik',
            'form' => ProgramForm::HigherTechnical, 'unit' => ProgramUnit::Year, 'duration' => 5,
        ]);
        $subject = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']]);
        $entry = CurriculumEntry::create(['program_id' => $program->id, 'subject_id' => $subject->id, 'stage_from' => 1, 'stage_to' => 5]);

        $class = SchoolClass::create(['program_id' => $program->id, 'school_year_id' => $year->id, 'name' => '2AHET', 'stage' => 2]);
        $enrollment = $class->enrollments()->create(['user_id' => User::factory()->create()->id]);
        $assignment = TeachingAssignment::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => User::factory()->create()->id]);

        return [$school, $domain, $year, $department, $program, $entry, $class, $enrollment, $assignment];
    }
}
