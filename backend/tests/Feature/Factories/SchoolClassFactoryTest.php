<?php

namespace Tests\Feature\Factories;

use App\Enums\ProgramUnit;
use App\Models\SchoolClass;
use App\Models\Subject;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Die SchoolClassFactory baut eine gültige Kette (Schule, Abteilung, Ausbildungsgang, Schuljahr) je Klasse.
 */
class SchoolClassFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_ist_2ahit_im_jahrgang_mit_schuljahr_derselben_schule(): void
    {
        $class = SchoolClass::factory()->create();

        $this->assertSame('2AHIT', $class->name);
        $this->assertSame(2, $class->stage);
        $this->assertSame(ProgramUnit::Year, $class->program->unit);
        $this->assertSame('2026/27', $class->schoolYear->name);
        $this->assertTrue($class->schoolYear->school->is($class->program->department->school));
    }

    public function test_jede_klasse_bekommt_eine_eigene_schule(): void
    {
        [$a, $b] = SchoolClass::factory()->count(2)->create();

        $this->assertFalse($a->program->department->school->is($b->program->department->school));
    }

    public function test_abschnitt_und_semester(): void
    {
        $class = SchoolClass::factory()->stage(3)->semester()->create();

        $this->assertSame('3AHIT', $class->name);
        $this->assertSame(ProgramUnit::Semester, $class->program->unit);
        $this->assertSame(3, $class->currentStage(CarbonImmutable::parse('2026-10-17')));
        $this->assertSame(4, $class->currentStage(CarbonImmutable::parse('2027-02-08')));
    }

    public function test_faecher_kommen_in_den_lehrplan(): void
    {
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'DBI']]);

        $class = SchoolClass::factory()->withSubjects($pos, $dbi)->create();

        $this->assertEqualsCanonicalizing(
            [$pos->id, $dbi->id],
            $class->program->curriculumEntries()->pluck('subject_id')->all(),
        );
    }
}
