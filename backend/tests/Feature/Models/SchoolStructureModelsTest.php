<?php

namespace Tests\Feature\Models;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\Department;
use App\Models\Program;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Subject;
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
