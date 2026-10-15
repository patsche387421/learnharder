<?php

namespace Tests\Feature;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportLegacyContentTest extends TestCase
{
    use RefreshDatabase;

    private string $dataPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Kleiner V1-Datenbestand: ein Fach, ein Thema, drei Fragen. Für Frage 3 fehlt die Lösung.
        $this->dataPath = sys_get_temp_dir().'/lh-import-'.uniqid();
        File::ensureDirectoryExists($this->dataPath.'/pos');

        $this->writeJson('manifest.json', ['faecher' => [[
            'id' => 'POS',
            'name' => 'POS',
            'themen' => [['id' => 'pos-schleifen', 'name' => 'Schleifen']],
        ]]]);
        $this->writeJson('pos/schleifen_fragen.json', ['fragen' => [
            ['frage' => 'Erste?', 'optionen' => ['a', 'b', 'c']],
            ['frage' => 'Zweite?', 'optionen' => ['a', 'b']],
            ['frage' => 'Dritte?', 'optionen' => ['a', 'b']],
        ]]);
        $this->writeJson('pos/schleifen_antworten.json', ['loesungen' => [2, 0]]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dataPath);

        parent::tearDown();
    }

    public function test_importiert_fragen_mit_der_richtigen_loesung(): void
    {
        $this->artisan('learnharder:import-legacy-content', ['--path' => $this->dataPath])
            ->assertSuccessful();

        $first = Question::where('order_index', 1)->first();
        $correct = $first->options()->where('is_correct', true)->get();

        $this->assertCount(1, $correct);
        $this->assertSame(3, $correct->first()->order_index);
    }

    public function test_ueberspringt_fragen_ohne_loesung_statt_option_0_zu_raten(): void
    {
        $this->artisan('learnharder:import-legacy-content', ['--path' => $this->dataPath])
            ->expectsOutputToContain('Übersprungen: pos/schleifen Frage 3 hat keine gültige Lösung.')
            ->assertSuccessful();

        $this->assertSame(2, Question::count());
    }

    public function test_zweiter_import_ueberschreibt_gepflegte_inhalte_nicht(): void
    {
        $this->artisan('learnharder:import-legacy-content', ['--path' => $this->dataPath])->assertSuccessful();
        $question = Question::where('order_index', 1)->sole();
        $question->update(['question_text' => ['de' => 'Im CMS korrigiert']]);

        $this->artisan('learnharder:import-legacy-content', ['--path' => $this->dataPath])
            ->expectsOutputToContain('Übersprungen: pos/schleifen hat schon Theorie oder Fragen, nichts überschrieben.')
            ->assertSuccessful();

        $this->assertModelExists($question);
        $this->assertSame('Im CMS korrigiert', $question->fresh()->getTranslation('question_text', 'de'));
    }

    public function test_bricht_ab_wenn_manifest_fehlt(): void
    {
        $this->artisan('learnharder:import-legacy-content', ['--path' => $this->dataPath.'/gibt-es-nicht'])
            ->assertFailed();
    }

    private function writeJson(string $file, array $data): void
    {
        File::put($this->dataPath.'/'.$file, json_encode($data));
    }
}
