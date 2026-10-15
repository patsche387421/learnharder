<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Filament\Resources\SubjectResource\Pages\CreateSubject;
use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bedienung der Inhaltsformulare: Fach vor Thema, Fehlermeldungen, Kürzel-Regeln.
 */
class ContentFormTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    private Subject $pos;

    private Topic $fractions;

    private Topic $loops;

    protected function setUp(): void
    {
        parent::setUp();

        $this->math = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik'], 'order_index' => 1]);
        $this->pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS'], 'order_index' => 2]);
        $this->fractions = $this->math->topics()->create(['slug' => 'brueche', 'title' => ['de' => 'Brüche']]);
        $this->loops = $this->pos->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen']]);

        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'autor']));
        $this->actingAs($user);
    }

    public function test_thema_ist_gesperrt_bis_ein_fach_gewaehlt_ist(): void
    {
        Livewire::test(CreateQuestion::class)
            ->assertFormFieldDisabled('topic_id')
            ->fillForm(['subject_id' => $this->pos->id])
            ->assertFormFieldEnabled('topic_id');
    }

    public function test_thema_zeigt_nur_themen_des_fachs(): void
    {
        $options = Livewire::test(CreateQuestion::class)
            ->fillForm(['subject_id' => $this->pos->id])
            ->instance()->form->getComponent('topic_id')->getOptions();

        $this->assertSame([$this->loops->id => 'Schleifen'], $options);
    }

    public function test_fachwechsel_leert_das_thema(): void
    {
        Livewire::test(CreateQuestion::class)
            ->fillForm(['subject_id' => $this->pos->id, 'topic_id' => $this->loops->id])
            ->fillForm(['subject_id' => $this->math->id])
            ->assertSchemaStateSet(['topic_id' => null]);
    }

    public function test_bearbeiten_waehlt_das_fach_der_frage_vor(): void
    {
        $question = $this->loops->questions()->create(['question_text' => ['de' => 'Was ist eine Schleife?']]);

        Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
            ->assertSchemaStateSet(['subject_id' => $this->pos->id, 'topic_id' => $this->loops->id]);
    }

    public function test_kuerzel_nur_kleinbuchstaben_ziffern_und_bindestriche(): void
    {
        Livewire::test(CreateSubject::class)
            ->fillForm(['slug' => 'Mathe 2', 'name.de' => 'Mathe 2', 'icon_name' => 'code', 'order_index' => 3])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'Nur Kleinbuchstaben, Ziffern und Bindestriche, z. B. schleifen-2.']);
    }

    public function test_fehlermeldung_schreibt_das_feld_gross(): void
    {
        Livewire::test(CreateTopic::class)
            ->fillForm(['subject_id' => $this->pos->id, 'slug' => 'neu', 'title.de' => ''])
            ->call('create')
            ->assertHasFormErrors(['title.de' => 'Das Feld Titel ist ein Pflichtfeld.']);
    }

    public function test_fach_kuerzel_ist_eindeutig(): void
    {
        Livewire::test(CreateSubject::class)
            ->fillForm(['slug' => 'pos', 'name.de' => 'Noch einmal POS', 'icon_name' => 'code', 'order_index' => 3])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'Dieser Wert für URL-Kürzel ist schon vergeben.']);
    }

    public function test_themen_kuerzel_ist_pro_fach_eindeutig(): void
    {
        Livewire::test(CreateTopic::class)
            ->fillForm(['subject_id' => $this->pos->id, 'slug' => 'schleifen', 'title.de' => 'Schleifen 2'])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        Livewire::test(CreateTopic::class)
            ->fillForm(['subject_id' => $this->math->id, 'slug' => 'schleifen', 'title.de' => 'Schleifen in Mathe'])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}
