<?php

namespace Tests\Feature\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModelsTest extends TestCase
{
    use RefreshDatabase;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->topic = $subject->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen']]);
    }

    public function test_wertelisten_kommen_als_enum_zurueck(): void
    {
        $question = $this->topic->questions()->create(['question_text' => ['de' => 'Frage?']])->fresh();

        $this->assertSame(ContentStatus::Draft, $this->topic->fresh()->status);
        $this->assertSame(ContentStatus::Draft, $question->status);
        $this->assertSame(QuestionType::SingleChoice, $question->type);
        $this->assertSame(Difficulty::VeryEasy, $question->difficulty);
        $this->assertSame('In Prüfung', ContentStatus::Review->getLabel());
    }

    public function test_antworten_mischen_sich_je_startwert_immer_gleich(): void
    {
        $question = $this->topic->questions()->create(['question_text' => ['de' => 'Frage?']]);
        foreach (['a', 'b', 'c', 'd'] as $i => $text) {
            $question->options()->create(['option_text' => ['de' => $text], 'is_correct' => $i === 0, 'order_index' => $i]);
        }
        $order = fn (string $seed) => $question->fresh()->shuffledOptions($seed)->pluck('option_text')->all();

        // Fortsetzen zeigt dieselbe Reihenfolge, andere Lerneinheiten mischen anders
        $this->assertSame($order('lerneinheit-1'), $order('lerneinheit-1'));
        $this->assertEqualsCanonicalizing(['a', 'b', 'c', 'd'], $order('lerneinheit-1'));
        $orders = collect(range(1, 20))->map(fn (int $i) => implode($order("lerneinheit-{$i}")))->unique();
        $this->assertGreaterThan(1, $orders->count());
    }

    public function test_jeder_enum_wert_passt_zur_datenbankregel(): void
    {
        // Enums und CHECK-Regeln in der Migration dürfen nicht auseinanderlaufen
        foreach (ContentStatus::cases() as $status) {
            $this->topic->update(['status' => $status]);

            foreach (QuestionType::cases() as $type) {
                foreach (Difficulty::cases() as $difficulty) {
                    $this->topic->questions()->create([
                        'question_text' => ['de' => 'Frage?'],
                        'status' => $status, 'type' => $type, 'difficulty' => $difficulty,
                    ]);
                }
            }
        }

        $this->assertSame(count(ContentStatus::cases()) * count(QuestionType::cases()) * count(Difficulty::cases()), Question::count());
    }
}
