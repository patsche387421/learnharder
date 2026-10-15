<?php

namespace Tests\Feature\Filament;

use App\Enums\ContentStatus;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\Pages\ListTopics;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Veröffentlichen dürfen nur Admins. Wer es nicht darf, schickt Veröffentlichtes beim Speichern zurück in Prüfung.
 */
class PublishingTest extends TestCase
{
    use RefreshDatabase;

    private Topic $topic;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->topic = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']])
            ->topics()->create(['slug' => 'brueche', 'title' => ['de' => 'Brüche'], 'status' => ContentStatus::Published]);
        $this->question = $this->topic->questions()->create(['question_text' => ['de' => 'Was ist 1/2 + 1/4?'], 'status' => ContentStatus::Published]);
        $this->question->options()->createMany([
            ['option_text' => ['de' => '3/4'], 'is_correct' => true, 'order_index' => 1],
            ['option_text' => ['de' => '2/6'], 'is_correct' => false, 'order_index' => 2],
        ]);

        Repeater::fake();
    }

    public function test_autor_kann_neue_frage_nicht_veroeffentlichen(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->newQuestion(ContentStatus::Published)->assertHasFormErrors(['status']);
        $this->newQuestion(ContentStatus::Review)->assertHasNoFormErrors();

        $this->assertSame(ContentStatus::Review, Question::where('question_text->de', 'Neue Frage?')->sole()->status);
    }

    public function test_autor_schickt_veroeffentlichte_frage_zurueck_in_pruefung(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        Livewire::test(EditQuestion::class, ['record' => $this->question->getRouteKey()])
            ->assertFormFieldDisabled('status')
            ->assertSeeText('Beim Speichern geht die Frage zurück in Prüfung.')
            ->fillForm(['question_text.de' => 'Was ist 1/2 + 1/4? (korrigiert)'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Gespeichert. Der Status ist jetzt „In Prüfung“.');

        $this->assertSame(ContentStatus::Review, $this->question->fresh()->status);
    }

    public function test_autor_schickt_veroeffentlichtes_thema_zurueck_in_pruefung(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        Livewire::test(EditTopic::class, ['record' => $this->topic->getRouteKey()])
            ->fillForm(['title.de' => 'Bruchrechnen'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(ContentStatus::Review, $this->topic->fresh()->status);
    }

    public function test_entwurf_bleibt_entwurf_und_laesst_sich_nicht_selbst_veroeffentlichen(): void
    {
        $this->question->update(['status' => ContentStatus::Draft]);
        $this->actingAs($this->userWithRole('autor'));

        Livewire::test(EditQuestion::class, ['record' => $this->question->getRouteKey()])
            ->assertFormFieldEnabled('status')
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['status' => ContentStatus::Published->value])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(ContentStatus::Draft, $this->question->fresh()->status);
    }

    public function test_admin_aendert_veroeffentlichtes_ohne_statuswechsel(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(EditQuestion::class, ['record' => $this->question->getRouteKey()])
            ->assertFormFieldEnabled('status')
            ->fillForm(['question_text.de' => 'Was ist 1/2 + 1/4? (korrigiert)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(ContentStatus::Published, $this->question->fresh()->status);
    }

    public function test_nur_admin_veroeffentlicht_mehrere_auf_einmal(): void
    {
        $this->topic->update(['status' => ContentStatus::Review]);
        $draft = $this->topic->subject->topics()->create(['slug' => 'prozent', 'title' => ['de' => 'Prozent']]);

        $this->actingAs($this->userWithRole('autor'));
        Livewire::test(ListTopics::class)->assertTableBulkActionHidden('publish');

        $this->actingAs($this->userWithRole('admin'));
        Livewire::test(ListTopics::class)->callTableBulkAction('publish', [$this->topic, $draft]);

        $this->assertSame(ContentStatus::Published, $this->topic->fresh()->status);
        $this->assertSame(ContentStatus::Published, $draft->fresh()->status);
    }

    private function newQuestion(ContentStatus $status): mixed
    {
        return Livewire::test(CreateQuestion::class)
            ->fillForm([
                'subject_id' => $this->topic->subject_id, 'topic_id' => $this->topic->id,
                'question_text.de' => 'Neue Frage?', 'status' => $status->value,
                'options' => [['option_text' => ['de' => 'Ja'], 'is_correct' => true], ['option_text' => ['de' => 'Nein'], 'is_correct' => false]],
            ])
            ->call('create');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role]));

        return $user;
    }
}
