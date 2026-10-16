<?php

namespace Database\Seeders;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Models\CurriculumEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo-Schule für die Entwicklung: eine Klasse mit Lehrplan, die Demo-Schüler:in darin (Plan Schule, E-024).
 * Läuft nach `db:seed` und dem Import, weil der Lehrplan die importierten Fächer braucht:
 * `./vendor/bin/sail artisan db:seed --class=DemoSchoolSeeder`
 */
class DemoSchoolSeeder extends Seeder
{
    /** Fächer, die erst ab einem späteren Jahrgang im Lehrplan stehen. Daran sieht man den Filter. */
    private const LATER = ['medt' => 3];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo-Schule übersprungen (nur in local/testing).');

            return;
        }

        $student = User::where('email', 'schueler@learnharder.app')->first();
        $teacher = User::where('email', 'lehrer@learnharder.app')->first();
        $subjects = Subject::all();

        if (! $student || ! $teacher || $subjects->isEmpty()) {
            $this->command?->warn('Demo-Schule übersprungen: zuerst `db:seed` und den Import ausführen.');

            return;
        }

        $school = School::firstOrCreate(['name' => 'Demo-HTL'], ['city' => 'Mödling']);
        $program = $school->departments()->firstOrCreate(['code' => 'IT'], ['name' => 'Informationstechnologie'])
            ->programs()->firstOrCreate(['code' => 'HIT'], [
                'name' => 'Höhere Lehranstalt Informationstechnologie',
                'form' => ProgramForm::HigherTechnical,
                'unit' => ProgramUnit::Year,
                'duration' => 5,
            ]);
        $year = $school->schoolYears()->firstOrCreate(['name' => '2026/27'], [
            'starts_on' => '2026-09-07', 'second_term_starts_on' => '2027-02-08', 'ends_on' => '2027-07-02',
        ]);

        foreach ($subjects as $subject) {
            CurriculumEntry::firstOrCreate(
                ['program_id' => $program->id, 'subject_id' => $subject->id],
                ['stage_from' => self::LATER[$subject->slug] ?? 1, 'stage_to' => 5],
            );
        }

        $class = SchoolClass::firstOrCreate(
            ['school_year_id' => $year->id, 'name' => '2AHIT'],
            ['program_id' => $program->id, 'stage' => 2, 'head_teacher_id' => $teacher->id],
        );

        // Nur eintragen, wenn die Schüler:in im Schuljahr keine aktive Klasse hat (etwa nach dem Austragen im CMS)
        if (! $student->enrollments()->active()->where('school_year_id', $year->id)->exists()) {
            $class->enrollments()->create(['user_id' => $student->id]);
        }

        TeachingAssignment::firstOrCreate([
            'school_class_id' => $class->id,
            'subject_id' => $subjects->firstWhere('slug', 'pos')?->id ?? $subjects->first()->id,
            'teacher_id' => $teacher->id,
            'semester' => null,
        ]);

        $this->command?->info("Demo-Schule angelegt: Klasse {$class->name} mit {$student->gamer_tag}.");
    }
}
