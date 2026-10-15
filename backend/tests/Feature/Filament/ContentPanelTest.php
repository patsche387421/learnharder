<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Models\Subject;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']]);
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
