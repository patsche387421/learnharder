<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Filament\Resources\TopicResource\Pages\ListTopics;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Filament\Actions\DeleteAction;
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
            ->assertSeeTextInOrder(['Thema', 'Typ', 'Frage', 'Erklärung', 'EP', 'Schwierigkeit', 'Status', 'Reihenfolge'])
            ->assertDontSeeText(['Slug', 'Order index', 'Question text', 'Xp reward', 'Difficulty', 'Save changes', 'Cancel']);
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
        $this->assertSame([$this->topic->id => 'Mathematik – Brüche'],
            Livewire::test(CreateQuestion::class)->instance()->form->getComponent('topic_id')->getOptions());
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

    public function test_schueler_kommt_nicht_ins_panel(): void
    {
        $this->actingAs($this->userWithRole('student'));

        $this->get('/admin/subjects')->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => $role]));

        return $user;
    }
}
