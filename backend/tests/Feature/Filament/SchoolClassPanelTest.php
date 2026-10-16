<?php

namespace Tests\Feature\Filament;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Filament\Resources\SchoolClassResource\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClassResource\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClassResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\SchoolClassResource\RelationManagers\TeachingAssignmentsRelationManager;
use App\Models\Program;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolClassPanelTest extends TestCase
{
    use RefreshDatabase;

    private Program $program;

    private SchoolYear $year;

    private SchoolClass $class;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'HTL Mödling']);
        $this->program = $school->departments()->create(['code' => 'IT', 'name' => 'Informationstechnologie'])->programs()->create([
            'code' => 'HIT', 'name' => 'Höhere Lehranstalt Informationstechnologie',
            'form' => ProgramForm::HigherTechnical, 'unit' => ProgramUnit::Year, 'duration' => 5,
        ]);
        $this->year = $this->schoolYear($school, '2026/27');
        $this->class = SchoolClass::create(['program_id' => $this->program->id, 'school_year_id' => $this->year->id, 'name' => '2AHIT', 'stage' => 2]);
        $this->teacher = $this->userWithRole('teacher');
        $this->student = $this->userWithRole('student');

        $this->actingAs($this->userWithRole('admin'));
    }

    public function test_nur_admin_sieht_klassen(): void
    {
        $this->get('/admin/school-classes')->assertOk();
        $this->get('/admin/school-classes/create')->assertOk();
        $this->get("/admin/school-classes/{$this->class->id}/edit")->assertOk();

        foreach (['autor', 'tester'] as $role) {
            $this->actingAs($this->userWithRole($role))->get('/admin/school-classes')->assertForbidden();
        }
    }

    public function test_admin_legt_klasse_an(): void
    {
        $this->createClass(['name' => '3AHIT', 'stage' => 3, 'head_teacher_id' => $this->teacher->id])->assertHasNoFormErrors();

        $class = SchoolClass::where('name', '3AHIT')->sole();
        $this->assertSame(3, $class->stage);
        $this->assertTrue($class->headTeacher->is($this->teacher));
    }

    public function test_schuljahr_einer_anderen_schule_wird_abgelehnt(): void
    {
        $otherYear = $this->schoolYear(School::create(['name' => 'HTL Wien']), '2026/27');

        $this->createClass(['school_year_id' => $otherYear->id])->assertHasFormErrors(['school_year_id']);
    }

    public function test_name_abschnitt_und_klassenvorstand_werden_geprueft(): void
    {
        $this->createClass(['name' => '2AHIT'])->assertHasFormErrors(['name']);
        $this->createClass(['stage' => 6])->assertHasFormErrors(['stage']);
        $this->createClass(['head_teacher_id' => $this->student->id])->assertHasFormErrors(['head_teacher_id']);
    }

    public function test_eintragen_und_austragen(): void
    {
        $this->enrollments()
            ->callTableAction('create', data: ['user_id' => $this->student->id])
            ->assertHasNoTableActionErrors();

        $enrollment = $this->class->enrollments()->sole();
        $this->assertSame($this->year->id, $enrollment->school_year_id);

        $this->enrollments()->callTableAction('leave', $enrollment);

        $this->assertNotNull($enrollment->fresh()->left_at);
        $this->assertSame(1, $this->class->enrollments()->count());
    }

    public function test_keine_zweite_aktive_klasse_im_schuljahr(): void
    {
        $other = SchoolClass::create(['program_id' => $this->program->id, 'school_year_id' => $this->year->id, 'name' => '2BHIT', 'stage' => 2]);
        $enrollment = $other->enrollments()->create(['user_id' => $this->student->id]);

        $this->enrollments()
            ->callTableAction('create', data: ['user_id' => $this->student->id])
            ->assertHasTableActionErrors(['user_id' => 'Ist in diesem Schuljahr schon in einer Klasse. Dort zuerst austragen.']);

        $enrollment->update(['left_at' => now()]);
        $this->enrollments()
            ->callTableAction('create', data: ['user_id' => $this->student->id])
            ->assertHasNoTableActionErrors();
    }

    public function test_eintragen_nur_fuer_schueler(): void
    {
        $this->enrollments()
            ->callTableAction('create', data: ['user_id' => $this->teacher->id])
            ->assertHasTableActionErrors(['user_id']);

        $this->assertSame(0, $this->class->enrollments()->count());
    }

    public function test_lehrkraft_zuordnen_doppelt_wird_abgelehnt(): void
    {
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $data = ['subject_id' => $subject->id, 'teacher_id' => $this->teacher->id];

        $this->assignments()->callTableAction('create', data: $data)->assertHasNoTableActionErrors();
        $this->assignments()->callTableAction('create', data: $data)->assertHasTableActionErrors(['teacher_id' => 'Ist für dieses Fach und diesen Zeitraum schon eingetragen.']);
        $this->assignments()->callTableAction('create', data: [...$data, 'semester' => 1])->assertHasNoTableActionErrors();
        $this->assignments()->callTableAction('create', data: [...$data, 'teacher_id' => $this->student->id])->assertHasTableActionErrors(['teacher_id' => 'Zuordnen kann man nur Lehrkräfte.']);

        $this->assertSame(2, $this->class->teachingAssignments()->count());
    }

    public function test_loeschen_nur_ohne_schueler_und_lehrkraefte(): void
    {
        Livewire::test(EditSchoolClass::class, ['record' => $this->class->getRouteKey()])
            ->assertActionVisible(DeleteAction::class);

        $this->class->enrollments()->create(['user_id' => $this->student->id]);

        Livewire::test(EditSchoolClass::class, ['record' => $this->class->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);
    }

    private function createClass(array $data)
    {
        return Livewire::test(CreateSchoolClass::class)
            ->fillForm(['program_id' => $this->program->id, 'school_year_id' => $this->year->id, 'name' => '1AHIT', 'stage' => 1, ...$data])
            ->call('create');
    }

    private function enrollments()
    {
        return Livewire::test(EnrollmentsRelationManager::class, ['ownerRecord' => $this->class, 'pageClass' => EditSchoolClass::class]);
    }

    private function assignments()
    {
        return Livewire::test(TeachingAssignmentsRelationManager::class, ['ownerRecord' => $this->class, 'pageClass' => EditSchoolClass::class]);
    }

    private function schoolYear(School $school, string $name): SchoolYear
    {
        return $school->schoolYears()->create(['name' => $name, 'starts_on' => '2026-09-07', 'second_term_starts_on' => '2027-02-08', 'ends_on' => '2027-07-02']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
