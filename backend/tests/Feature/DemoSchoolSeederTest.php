<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\CurriculumEntry;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_traegt_die_demo_schuelerin_in_eine_klasse_mit_lehrplan_ein(): void
    {
        $this->seed();
        $pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $medt = Subject::create(['slug' => 'medt', 'name' => ['de' => 'MEDT']]);

        $this->seed(DemoSchoolSeeder::class);

        $enrollment = User::where('email', 'schueler@learnharder.app')->sole()->activeEnrollment;
        $this->assertSame('2AHIT', $enrollment->schoolClass->name);
        $this->assertSame(2, $enrollment->schoolClass->currentStage());
        $this->assertSame(1, CurriculumEntry::where('subject_id', $pos->id)->value('stage_from'));
        $this->assertSame(3, CurriculumEntry::where('subject_id', $medt->id)->value('stage_from'));
        $this->assertSame(1, $enrollment->schoolClass->teachingAssignments()->count());
        $this->assertTrue(School::sole()->emailDomains()->where('domain', 'htl-md.schule')->value('allows_registration'));
    }

    public function test_zweiter_lauf_aendert_nichts(): void
    {
        $this->seed();
        Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);

        $this->seed(DemoSchoolSeeder::class);
        $this->seed(DemoSchoolSeeder::class);

        $this->assertSame(1, School::count());
        $this->assertSame(1, School::sole()->emailDomains()->count());
        $this->assertSame(1, ClassEnrollment::count());
        $this->assertSame(1, CurriculumEntry::count());
    }

    public function test_ohne_import_oder_in_produktion_passiert_nichts(): void
    {
        $this->seed();
        $this->artisan('db:seed', ['--class' => DemoSchoolSeeder::class])
            ->expectsOutputToContain('zuerst `db:seed` und den Import')
            ->assertSuccessful();

        Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--class' => DemoSchoolSeeder::class, '--force' => true])
            ->expectsOutputToContain('nur in local/testing')
            ->assertSuccessful();

        $this->assertSame(0, School::count());
    }
}
