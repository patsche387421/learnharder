<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\TheorySection;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learnharder:import-legacy-content {--path= : Pfad zum data-Verzeichnis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importiert die bestehenden JSON-Lerninhalte (Fächer, Themen, Theorie, Fragen) in die relationale Datenbank';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawPath = $this->option('path');
        if ($rawPath) {
            $baseDataPath = Str::startsWith($rawPath, ['/', '\\']) ? $rawPath : base_path($rawPath);
        } else {
            // Mount von _archiv/legacy/assets/data im Sail-Container (compose.yaml)
            $baseDataPath = '/var/www/legacy-data';
        }

        if (! file_exists($baseDataPath.'/manifest.json')) {
            $this->error("manifest.json nicht gefunden unter: {$baseDataPath}/manifest.json");

            return Command::FAILURE;
        }

        $this->info("Starte Content-Migration aus: {$baseDataPath}");

        $manifestContent = file_get_contents($baseDataPath.'/manifest.json');
        $manifest = json_decode($manifestContent, true);

        if (! isset($manifest['faecher'])) {
            $this->error('Keine Fächer in manifest.json gefunden.');

            return Command::FAILURE;
        }

        $totalSubjects = 0;
        $totalTopics = 0;
        $totalTheory = 0;
        $totalQuestions = 0;
        $totalSkipped = 0;

        DB::transaction(function () use (
            $manifest,
            $baseDataPath,
            &$totalSubjects,
            &$totalTopics,
            &$totalTheory,
            &$totalQuestions,
            &$totalSkipped
        ) {
            foreach ($manifest['faecher'] as $fIndex => $fachData) {
                $slug = strtolower($fachData['id']);
                $nameDe = $fachData['vollname'] ?? $fachData['name'];
                $descDe = $fachData['beschreibung'] ?? '';

                $subject = Subject::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => ['de' => $nameDe],
                        'description' => ['de' => $descDe],
                        'icon_name' => basename($fachData['icon'] ?? 'book', '.svg'),
                        'color_token' => "--fach-{$slug}",
                        'order_index' => $fIndex + 1,
                        'is_active' => true,
                    ]
                );

                $totalSubjects++;
                $this->line("<info>[Fach]</info> {$subject->getTranslation('name', 'de')} ({$slug})");

                $fachFolder = $baseDataPath.'/'.$slug;

                if (isset($fachData['themen']) && is_array($fachData['themen'])) {
                    foreach ($fachData['themen'] as $tIndex => $themaData) {
                        $topicRawId = $themaData['id'];
                        // Strip Fach-Präfix wenn vorhanden, z.B. "pos-variablen" -> "variablen"
                        $topicSlug = Str::startsWith($topicRawId, $slug.'-')
                            ? substr($topicRawId, strlen($slug) + 1)
                            : $topicRawId;

                        $topic = Topic::firstOrCreate(
                            [
                                'subject_id' => $subject->id,
                                'slug' => $topicSlug,
                            ],
                            [
                                'title' => ['de' => $themaData['name']],
                                'description' => ['de' => $themaData['beschreibung'] ?? ''],
                                'order_index' => $tIndex + 1,
                                'level_required' => 1,
                                'status' => ContentStatus::Published,
                            ]
                        );

                        $totalTopics++;

                        // 1. Theorie importieren
                        $theorieFile = $fachFolder.'/'.$topicSlug.'_theorie.json';
                        if (file_exists($theorieFile)) {
                            $theorieJson = json_decode(file_get_contents($theorieFile), true);
                            if (isset($theorieJson['themen']) && is_array($theorieJson['themen'])) {
                                TheorySection::where('topic_id', $topic->id)->delete();
                                foreach ($theorieJson['themen'] as $secIndex => $sec) {
                                    TheorySection::create([
                                        'topic_id' => $topic->id,
                                        'title' => ['de' => $sec['titel']],
                                        'content' => ['de' => $sec['text']],
                                        'order_index' => $secIndex + 1,
                                    ]);
                                    $totalTheory++;
                                }
                            }
                        }

                        // 2. Fragen und Antworten importieren
                        $fragenFile = $fachFolder.'/'.$topicSlug.'_fragen.json';
                        $antwortenFile = $fachFolder.'/'.$topicSlug.'_antworten.json';

                        if (file_exists($fragenFile)) {
                            $fragenJson = json_decode(file_get_contents($fragenFile), true);
                            $loesungen = [];

                            if (file_exists($antwortenFile)) {
                                $antJson = json_decode(file_get_contents($antwortenFile), true);
                                $loesungen = $antJson['loesungen'] ?? [];
                            }

                            if (isset($fragenJson['fragen']) && is_array($fragenJson['fragen'])) {
                                Question::where('topic_id', $topic->id)->delete();
                                foreach ($fragenJson['fragen'] as $qIndex => $qData) {
                                    // Ohne gültige Lösung wird die Frage übersprungen, statt Option 0 als richtig zu raten
                                    $correctIdx = $loesungen[$qIndex] ?? null;
                                    $optionCount = is_array($qData['optionen'] ?? null) ? count($qData['optionen']) : 0;
                                    if (! is_int($correctIdx) || $correctIdx < 0 || $correctIdx >= $optionCount) {
                                        $this->warn("Übersprungen: {$slug}/{$topicSlug} Frage ".($qIndex + 1).' hat keine gültige Lösung.');
                                        $totalSkipped++;

                                        continue;
                                    }

                                    $question = Question::create([
                                        'topic_id' => $topic->id,
                                        'type' => QuestionType::SingleChoice,
                                        'question_text' => ['de' => $qData['frage']],
                                        'xp_reward' => 2,
                                        'difficulty' => Difficulty::VeryEasy,
                                        'status' => ContentStatus::Published,
                                        'order_index' => $qIndex + 1,
                                    ]);

                                    $totalQuestions++;

                                    if (isset($qData['optionen']) && is_array($qData['optionen'])) {
                                        foreach ($qData['optionen'] as $optIndex => $optText) {
                                            QuestionOption::create([
                                                'question_id' => $question->id,
                                                'option_text' => ['de' => $optText],
                                                'is_correct' => ($optIndex === $correctIdx),
                                                'order_index' => $optIndex + 1,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });

        $this->newLine();
        $this->info('Migration erfolgreich abgeschlossen!');
        $this->table(
            ['Entität', 'Anzahl importiert'],
            [
                ['Fächer (Subjects)', $totalSubjects],
                ['Themen (Topics)', $totalTopics],
                ['Theorieblöcke (Theory Sections)', $totalTheory],
                ['Fragen (Questions)', $totalQuestions],
                ['Übersprungene Fragen (ohne Lösung)', $totalSkipped],
            ]
        );

        return Command::SUCCESS;
    }
}
