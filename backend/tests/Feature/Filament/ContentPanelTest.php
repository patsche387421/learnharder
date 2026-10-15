<?php

namespace Tests\Feature\Filament;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\Pages\ListTopics;
use App\Models\CurriculumEntry;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\School;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContentPanelTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;

    private Topic $topic;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']]);
        $this->topic = $this->subject->topics()->create(['slug' => 'brueche', 'title' => ['de' => 'Brüche']]);
        $this->question = $this->topic->questions()->create(['question_text' => ['de' => 'Was ist 1/2 + 1/4?']]);
    }

    public function test_admin_oeffnet_alle_inhaltsseiten(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (['subjects' => $this->subject, 'topics' => $this->topic, 'questions' => $this->question] as $path => $record) {
            $this->get("/admin/{$path}")->assertOk();
            $this->get("/admin/{$path}/create")->assertOk();
            $this->get("/admin/{$path}/{$record->id}/edit")->assertOk();
        }
    }

    public function test_inhaltsseiten_zeigen_deutsche_feldnamen(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->get("/admin/subjects/{$this->subject->id}/edit")
            ->assertSeeTextInOrder(['URL-Kürzel', 'Name', 'Beschreibung', 'Icon', 'Reihenfolge', 'Aktiv', 'Speichern']);
        $this->get('/admin/topics')
            ->assertSeeTextInOrder(['Fach', 'URL-Kürzel', 'Ab Level', 'Reihenfolge', 'Status']);
        $this->get("/admin/topics/{$this->topic->id}/edit")
            ->assertSeeTextInOrder(['Fach', 'URL-Kürzel', 'Titel', 'Beschreibung', 'Ab Level', 'Reihenfolge', 'Status']);
        $this->get('/admin/questions')
            ->assertSeeTextInOrder(['Thema', 'Typ', 'EP', 'Schwierigkeit', 'Status', 'Reihenfolge']);
        $this->get("/admin/questions/{$this->question->id}/edit")
            ->assertSeeTextInOrder(['Fach', 'Thema', 'Frage', 'Erklärung', 'Typ', 'Schwierigkeit', 'Antworten', 'EP', 'Reihenfolge', 'Status'])
            ->assertDontSeeText(['Slug', 'Order index', 'Question text', 'Xp reward', 'Difficulty', 'Save changes', 'Cancel']);
    }

    public function test_formular_bearbeitet_nur_den_deutschen_eintrag(): void
    {
        $this->topic->setTranslation('title', 'en', 'Fractions')->save();
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(EditTopic::class, ['record' => $this->topic->getRouteKey()])
            ->assertSchemaStateSet(['title.de' => 'Brüche'])
            ->fillForm(['title.de' => 'Bruchrechnen'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->topic->refresh();
        $this->assertSame('Bruchrechnen', $this->topic->getTranslation('title', 'de'));
        $this->assertSame('Fractions', $this->topic->getTranslation('title', 'en'));
    }

    public function test_neues_thema_speichert_titel_auf_deutsch(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(CreateTopic::class)
            ->fillForm(['subject_id' => $this->subject->id, 'slug' => 'prozent', 'title.de' => 'Prozentrechnung', 'status' => 'draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        $topic = Topic::where('slug', 'prozent')->firstOrFail();
        $this->assertSame(['de' => 'Prozentrechnung'], $topic->getTranslations('title'));
    }

    public function test_wertelisten_erscheinen_deutsch_und_filtern(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->get('/admin/questions')
            ->assertSeeText(['Entwurf', 'Eine richtige Antwort', '1 – sehr leicht'])
            ->assertDontSeeText(['single_choice', 'draft']);

        Livewire::test(ListQuestions::class)
            ->filterTable('status', ContentStatus::Published->value)
            ->assertCanNotSeeTableRecords([$this->question])
            ->filterTable('status', ContentStatus::Draft->value)
            ->assertCanSeeTableRecords([$this->question]);
    }

    public function test_neue_frage_speichert_die_gewaehlten_werte(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        Repeater::fake();

        Livewire::test(CreateQuestion::class)
            ->assertSchemaStateSet(['type' => QuestionType::SingleChoice, 'difficulty' => Difficulty::VeryEasy, 'status' => ContentStatus::Draft])
            ->fillForm([
                'subject_id' => $this->subject->id, 'topic_id' => $this->topic->id, 'question_text.de' => 'Welche sind Primzahlen?',
                'type' => QuestionType::MultipleChoice->value, 'difficulty' => Difficulty::Medium->value, 'status' => ContentStatus::Review->value,
                'options' => [['option_text' => ['de' => '2'], 'is_correct' => true], ['option_text' => ['de' => '3'], 'is_correct' => true]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $question = Question::where('question_text->de', 'Welche sind Primzahlen?')->firstOrFail();
        $this->assertSame(QuestionType::MultipleChoice, $question->type);
        $this->assertSame(Difficulty::Medium, $question->difficulty);
        $this->assertSame(ContentStatus::Review, $question->status);
    }

    public function test_fach_und_thema_erscheinen_deutsch_auch_bei_englischer_locale(): void
    {
        config(['app.fallback_locale' => 'en']);
        app()->setLocale('en');
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(ListTopics::class)
            ->assertTableColumnStateSet('subject.name', 'Mathematik', $this->topic)
            ->searchTable('Mathe')
            ->assertCanSeeTableRecords([$this->topic]);
        Livewire::test(ListQuestions::class)
            ->assertTableColumnStateSet('topic.title', 'Brüche', $this->question);

        $this->assertSame([$this->subject->id => 'Mathematik'],
            Livewire::test(CreateTopic::class)->instance()->form->getComponent('subject_id')->getOptions());
        $this->assertSame([$this->topic->id => 'Brüche'],
            Livewire::test(CreateQuestion::class)->fillForm(['subject_id' => $this->subject->id])->instance()->form->getComponent('topic_id')->getOptions());
    }

    public function test_autor_kann_bearbeiten_aber_nicht_loeschen(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->get('/admin/subjects/create')->assertOk();
        $this->get("/admin/subjects/{$this->subject->id}/edit")->assertOk();

        Livewire::test(EditSubject::class, ['record' => $this->subject->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);
        Livewire::test(ListSubjects::class)
            ->assertTableBulkActionHidden('delete');
    }

    public function test_tester_sieht_nur_die_liste(): void
    {
        $this->actingAs($this->userWithRole('tester'));

        $this->get('/admin/subjects')->assertOk();
        $this->get('/admin/subjects/create')->assertForbidden();
        $this->get("/admin/subjects/{$this->subject->id}/edit")->assertForbidden();
    }

    public function test_admin_kann_loeschen(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(EditSubject::class, ['record' => $this->subject->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($this->subject);
    }

    public function test_inhalt_mit_lernverlauf_wird_nicht_geloescht_sondern_erklaert(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->learn();

        Livewire::test(EditQuestion::class, ['record' => $this->question->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified(Notification::make()->danger()->title('Nicht gelöscht')->body(QuestionResource::IN_USE_HINT)->persistent());
        Livewire::test(EditTopic::class, ['record' => $this->topic->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified('Nicht gelöscht');
        Livewire::test(EditSubject::class, ['record' => $this->subject->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified('Nicht gelöscht');

        $this->assertModelExists($this->question);
        $this->assertModelExists($this->topic);
        $this->assertModelExists($this->subject);
    }

    public function test_mehrfach_loeschen_ueberspringt_inhalt_mit_lernverlauf(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->learn();
        $unused = $this->topic->questions()->create(['question_text' => ['de' => 'Was ist 1/3 + 1/3?']]);

        Livewire::test(ListQuestions::class)
            ->callTableBulkAction('delete', [$this->question, $unused])
            ->assertNotified('1 von 2 gelöscht');

        $this->assertModelExists($this->question);
        $this->assertModelMissing($unused);
    }

    public function test_fach_im_lehrplan_wird_nicht_geloescht(): void
    {
        $program = School::create(['name' => 'HTL Mödling'])
            ->departments()->create(['code' => 'IT', 'name' => 'Informationstechnologie'])
            ->programs()->create([
                'code' => 'HIT', 'name' => 'Höhere Lehranstalt Informationstechnologie',
                'form' => ProgramForm::HigherTechnical, 'unit' => ProgramUnit::Year, 'duration' => 5,
            ]);
        CurriculumEntry::create(['program_id' => $program->id, 'subject_id' => $this->subject->id, 'stage_from' => 1, 'stage_to' => 5]);

        $this->assertFalse($this->subject->delete());
        $this->assertModelExists($this->subject);
    }

    public function test_schueler_kommt_nicht_ins_panel(): void
    {
        $this->actingAs($this->userWithRole('student'));

        $this->get('/admin/subjects')->assertForbidden();
    }

    /** Eine Schülerin hat die Frage beantwortet: Lerneinheit und Antwort hängen jetzt am Inhalt. */
    private function learn(): void
    {
        $student = User::factory()->create();
        $attempt = QuizAttempt::create(['user_id' => $student->id, 'topic_id' => $this->topic->id]);
        QuestionAnswer::create([
            'quiz_attempt_id' => $attempt->id, 'user_id' => $student->id, 'question_id' => $this->question->id,
            'selected_option_ids' => [], 'is_correct' => false, 'xp_awarded' => 0, 'xp_date' => '2026-10-16',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => $role]));

        return $user;
    }
}
