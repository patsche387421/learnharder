<?php

namespace Database\Factories;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\CurriculumEntry;
use App\Models\Program;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Klasse samt eigener Kette (Plan Schule §3): Schule, Abteilung IT, Ausbildungsgang HIT, Schuljahr 2026/27
 * (2. Semester ab 2027-02-08). Standard ist 2AHIT im Jahrgang, ohne Fächer im Lehrplan.
 * Beispiel: SchoolClass::factory()->stage(3)->withSubjects($pos)->create().
 *
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => fn () => self::program(ProgramUnit::Year)->id,
            // Schuljahr aus derselben Schule wie der Ausbildungsgang
            'school_year_id' => fn (array $attributes) => self::schoolYear($attributes['program_id'])->id,
            'name' => '2AHIT',
            'stage' => 2,
        ];
    }

    /** Abschnitt $stage (Jahrgang, im Kolleg Semester), der Name folgt mit. */
    public function stage(int $stage): static
    {
        return $this->state(['stage' => $stage, 'name' => $stage.'AHIT']);
    }

    /** Kolleg in Semestern: Ab dem 2. Semester zählt der Abschnitt eins weiter (SchoolClass::currentStage). */
    public function semester(): static
    {
        return $this->state(['program_id' => fn () => self::program(ProgramUnit::Semester)->id]);
    }

    /** Fächer in den Lehrplan des Ausbildungsgangs, für alle Abschnitte 1 bis 5. */
    public function withSubjects(Subject ...$subjects): static
    {
        return $this->afterCreating(function (SchoolClass $class) use ($subjects) {
            foreach ($subjects as $subject) {
                CurriculumEntry::create(['program_id' => $class->program_id, 'subject_id' => $subject->id, 'stage_from' => 1, 'stage_to' => 5]);
            }
        });
    }

    /** Neue Schule mit Abteilung IT und Ausbildungsgang HIT, je Klasse eine eigene. */
    private static function program(ProgramUnit $unit): Program
    {
        $school = School::create(['name' => 'Test-HTL '.(School::count() + 1)]);

        return $school->departments()->create(['code' => 'IT', 'name' => 'Informationstechnologie'])->programs()->create([
            'code' => 'HIT', 'name' => 'Informationstechnologie',
            'form' => $unit === ProgramUnit::Year ? ProgramForm::HigherTechnical : ProgramForm::College,
            'unit' => $unit, 'duration' => 5,
        ]);
    }

    private static function schoolYear(string $programId): SchoolYear
    {
        return Program::findOrFail($programId)->department->school->schoolYears()->firstOrCreate(
            ['name' => '2026/27'],
            ['starts_on' => '2026-09-07', 'second_term_starts_on' => '2027-02-08', 'ends_on' => '2027-07-02'],
        );
    }
}
