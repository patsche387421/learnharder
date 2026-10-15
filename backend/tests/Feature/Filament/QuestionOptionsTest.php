<?php

namespace Tests\Feature\Filament;

use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuestionOptionsTest extends TestCase
{
    use RefreshDatabase;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->topic = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']])
            ->topics()->create(['slug' => 'primzahlen', 'title' => ['de' => 'Primzahlen']]);

        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'autor']));
        $this->actingAs($user);

        Repeater::fake();
    }

    public function test_bearbeiten_zeigt_die_antworten_in_reihenfolge(): void
    {
        $question = $this->question(QuestionType::SingleChoice);
        $question->options()->createMany([
            ['option_text' => ['de' => '4'], 'is_correct' => false, 'order_index' => 2],
            ['option_text' => ['de' => '3'], 'is_correct' => true, 'order_index' => 1],
        ]);

        $options = Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])->get('data.options');

        $this->assertSame([['de' => '3'], ['de' => '4']], array_column(array_values($options), 'option_text'));
        $this->assertSame([true, false], array_column(array_values($options), 'is_correct'));
    }

    public function test_neue_frage_speichert_antworten_mit_reihenfolge(): void
    {
        $this->create(QuestionType::SingleChoice, [['2', false], ['3', true], ['4', false]])
            ->assertHasNoFormErrors();

        $options = Question::firstOrFail()->options;
        $this->assertSame(['2', '3', '4'], $options->map->getTranslation('option_text', 'de')->all());
        $this->assertSame([1, 2, 3], $options->pluck('order_index')->all());
        $this->assertSame([false, true, false], $options->pluck('is_correct')->all());
    }

    public function test_eine_richtige_antwort_heisst_genau_eine(): void
    {
        // Wer von mehreren richtigen auf eine richtige wechselt, muss selbst entscheiden, welche bleibt
        $this->fill(QuestionType::MultipleChoice, [['2', true], ['3', true]])
            ->fillForm(['type' => QuestionType::SingleChoice->value])
            ->call('create')
            ->assertHasFormErrors(['options' => 'Markiere genau eine richtige Antwort.']);
        $this->create(QuestionType::SingleChoice, [['2', false], ['3', false]])
            ->assertHasFormErrors(['options' => 'Markiere genau eine richtige Antwort.'])
            ->assertSeeText('Markiere genau eine richtige Antwort.');

        $this->assertSame(0, Question::count());
    }

    public function test_mehrere_richtige_brauchen_mindestens_eine(): void
    {
        $this->create(QuestionType::MultipleChoice, [['2', false], ['4', false]])
            ->assertHasFormErrors(['options' => 'Markiere mindestens eine richtige Antwort.']);
        $this->create(QuestionType::MultipleChoice, [['2', true], ['3', true], ['4', false]])
            ->assertHasNoFormErrors();
    }

    public function test_bei_einer_richtigen_schaltet_der_neue_schalter_die_anderen_aus(): void
    {
        $component = Livewire::test(CreateQuestion::class)
            ->fillForm(['type' => QuestionType::SingleChoice->value, 'options' => [
                ['option_text' => ['de' => '2'], 'is_correct' => true],
                ['option_text' => ['de' => '3'], 'is_correct' => false],
            ]]);
        $keys = array_keys($component->get('data.options'));

        $component->set("data.options.{$keys[1]}.is_correct", true);

        $this->assertFalse($component->get("data.options.{$keys[0]}.is_correct"));
        $this->assertTrue($component->get("data.options.{$keys[1]}.is_correct"));
    }

    public function test_bei_mehreren_richtigen_bleiben_alle_schalter_an(): void
    {
        $component = Livewire::test(CreateQuestion::class)
            ->fillForm(['type' => QuestionType::MultipleChoice->value, 'options' => [
                ['option_text' => ['de' => '2'], 'is_correct' => true],
                ['option_text' => ['de' => '3'], 'is_correct' => false],
            ]]);
        $keys = array_keys($component->get('data.options'));

        $component->set("data.options.{$keys[1]}.is_correct", true);

        $this->assertTrue($component->get("data.options.{$keys[0]}.is_correct"));
        $this->assertTrue($component->get("data.options.{$keys[1]}.is_correct"));
    }

    public function test_frage_braucht_mindestens_zwei_antworten(): void
    {
        $this->create(QuestionType::SingleChoice, [['2', true]])
            ->assertHasFormErrors(['options']);
    }

    private function question(QuestionType $type): Question
    {
        return $this->topic->questions()->create(['question_text' => ['de' => 'Welche Zahl ist prim?'], 'type' => $type]);
    }

    /**
     * @param  list<array{0: string, 1: bool}>  $options  Antworttext und richtig/falsch
     */
    private function create(QuestionType $type, array $options): mixed
    {
        return $this->fill($type, $options)->call('create');
    }

    /**
     * @param  list<array{0: string, 1: bool}>  $options  Antworttext und richtig/falsch
     */
    private function fill(QuestionType $type, array $options): mixed
    {
        return Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $this->topic->subject_id,
                'topic_id' => $this->topic->id,
                'question_text.de' => 'Welche Zahl ist prim?',
                'type' => $type->value,
                'options' => array_map(fn (array $option): array => ['option_text' => ['de' => $option[0]], 'is_correct' => $option[1]], $options),
            ]);
    }
}
