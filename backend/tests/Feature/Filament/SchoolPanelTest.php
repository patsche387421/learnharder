<?php

namespace Tests\Feature\Filament;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Filament\Resources\ProgramResource\Pages\CreateProgram;
use App\Filament\Resources\ProgramResource\Pages\EditProgram;
use App\Filament\Resources\ProgramResource\RelationManagers\CurriculumEntriesRelationManager;
use App\Filament\Resources\SchoolResource\Pages\EditSchool;
use App\Filament\Resources\SchoolResource\RelationManagers\DepartmentsRelationManager;
use App\Filament\Resources\SchoolResource\RelationManagers\EmailDomainsRelationManager;
use App\Models\Department;
use App\Models\Program;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolPanelTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Department $department;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'HTL Mödling']);
        $this->department = $this->school->departments()->create(['code' => 'ET', 'name' => 'Elektrotechnik']);
        $this->program = $this->department->programs()->create([
            'code' => 'HET', 'name' => 'Höhere Lehranstalt Elektrotechnik',
            'form' => ProgramForm::HigherTechnical, 'unit' => ProgramUnit::Year, 'duration' => 5,
        ]);
    }

    public function test_admin_oeffnet_alle_seiten(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (['/admin/schools', '/admin/schools/create', "/admin/schools/{$this->school->id}/edit",
            '/admin/programs', '/admin/programs/create', "/admin/programs/{$this->program->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_autor_und_tester_sehen_die_schulstruktur_nicht(): void
    {
        foreach (['autor', 'tester'] as $role) {
            $this->actingAs($this->userWithRole($role));

            $this->get('/admin/schools')->assertForbidden();
            $this->get('/admin/programs')->assertForbidden();
        }
    }

    public function test_admin_legt_ausbildungsgang_an(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(CreateProgram::class)
            ->fillForm([
                'department_id' => $this->department->id, 'code' => 'KIF', 'name' => 'Kolleg Informatik-Systemtechnik',
                'form' => ProgramForm::College->value, 'unit' => ProgramUnit::Semester->value, 'duration' => 4,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $program = Program::where('code', 'KIF')->firstOrFail();
        $this->assertSame(ProgramForm::College, $program->form);
        $this->assertSame(ProgramUnit::Semester, $program->unit);
    }

    public function test_kuerzel_des_ausbildungsgangs_ist_pro_abteilung_eindeutig(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(CreateProgram::class)
            ->fillForm([
                'department_id' => $this->department->id, 'code' => 'HET', 'name' => 'Doppelt',
                'form' => ProgramForm::HigherTechnical->value, 'unit' => ProgramUnit::Year->value, 'duration' => 5,
            ])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_abteilungskuerzel_ist_pro_schule_eindeutig(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->relationManager(DepartmentsRelationManager::class, $this->school, EditSchool::class)
            ->callTableAction('create', data: ['code' => 'ET', 'name' => 'Doppelt'])
            ->assertHasTableActionErrors(['code']);
    }

    public function test_mail_domain_ist_ohne_gross_kleinschreibung_eindeutig(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->school->emailDomains()->create(['domain' => 'htl.moedling.at']);

        // Ohne Prüfung im Formular würde der Index in Postgres einen Serverfehler auslösen
        $this->relationManager(EmailDomainsRelationManager::class, $this->school, EditSchool::class)
            ->callTableAction('create', data: ['domain' => 'HTL.Moedling.at'])
            ->assertHasTableActionErrors(['domain']);
    }

    public function test_lehrplan_prueft_abschnitte_und_zeigt_den_deutschen_fachnamen(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren und Software-Engineering']]);
        // Jeder Aufruf mit frischer Komponente, nach einem Prüffehler bleibt das Formular sonst offen
        $curriculum = fn () => $this->relationManager(CurriculumEntriesRelationManager::class, $this->program, EditProgram::class);

        $curriculum()->callTableAction('create', data: ['subject_id' => $subject->id, 'stage_from' => 3, 'stage_to' => 2])
            ->assertHasTableActionErrors(['stage_to']);
        $curriculum()->callTableAction('create', data: ['subject_id' => $subject->id, 'stage_from' => 1, 'stage_to' => 6])
            ->assertHasTableActionErrors(['stage_to']);
        $curriculum()->callTableAction('create', data: ['subject_id' => $subject->id, 'stage_from' => 1, 'stage_to' => 5])
            ->assertHasNoTableActionErrors();

        $entry = $this->program->curriculumEntries()->firstOrFail();
        $curriculum()->assertTableColumnStateSet('subject_name', 'Programmieren und Software-Engineering', $entry);
    }

    public function test_loeschen_wird_nur_ohne_abhaengige_daten_angeboten(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $emptyDepartment = $this->school->departments()->create(['code' => 'BT', 'name' => 'Bautechnik']);

        $this->relationManager(DepartmentsRelationManager::class, $this->school, EditSchool::class)
            ->assertTableActionHidden('delete', $this->department)
            ->assertTableActionVisible('delete', $emptyDepartment);
        Livewire::test(EditSchool::class, ['record' => $this->school->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);
    }

    private function relationManager(string $class, $ownerRecord, string $pageClass)
    {
        return Livewire::test($class, ['ownerRecord' => $ownerRecord, 'pageClass' => $pageClass]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
