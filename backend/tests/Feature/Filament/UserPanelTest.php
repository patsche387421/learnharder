<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Konten im CMS (E-025, E-027).
 */
class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'autor', 'tester', 'teacher', 'student'] as $role) {
            Role::findOrCreate($role);
        }

        $this->admin = $this->userWithRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_nur_admin_verwaltet_konten(): void
    {
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->get("/admin/users/{$this->admin->id}/edit")->assertOk();

        foreach (['autor', 'tester'] as $role) {
            $this->actingAs($this->userWithRole($role))->get('/admin/users')->assertForbidden();
        }
    }

    public function test_admin_legt_lehrkraft_ohne_passwort_an(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['kind' => 'staff', 'name' => 'Erika Muster', 'email' => 'Erika.Muster@HTL-MD.schule', 'roles' => ['teacher']])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Konto angelegt');

        $teacher = User::where('email', 'erika.muster@htl-md.schule')->sole();
        $this->assertSame('Erika Muster', $teacher->name);
        $this->assertSame(['teacher'], $teacher->getRoleNames()->all());
        $this->assertFalse(Hash::check('', $teacher->password));
        $this->assertNull($teacher->parental_consent_at);
    }

    public function test_personal_braucht_namen_und_rolle(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['kind' => 'staff', 'email' => 'x@htl-md.schule', 'roles' => []])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'roles' => 'required']);
    }

    public function test_schueler_in_unter_14_nur_mit_bestaetigtem_elternformular(): void
    {
        $data = ['kind' => 'student', 'email' => '2026099@htl-md.schule', 'gamer_tag' => 'Kleine_1'];

        Livewire::test(CreateUser::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['parental_consent' => 'accepted']);
        $this->assertSame(0, User::where('email', '2026099@htl-md.schule')->count());

        Livewire::test(CreateUser::class)
            ->fillForm([...$data, 'parental_consent' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = User::where('email', '2026099@htl-md.schule')->sole();
        $this->assertNull($student->name);
        $this->assertSame(['student'], $student->getRoleNames()->all());
        $this->assertNotNull($student->parental_consent_at);
        $this->assertTrue($student->parentalConsentBy->is($this->admin));
    }

    public function test_gamer_tag_und_email_sind_ohne_gross_klein_eindeutig(): void
    {
        User::factory()->create(['email' => 'lena@htl-md.schule', 'gamer_tag' => 'Lena42']);

        Livewire::test(CreateUser::class)
            ->fillForm(['kind' => 'student', 'email' => 'LENA@htl-md.schule', 'gamer_tag' => 'lena42', 'parental_consent' => true])
            ->call('create')
            ->assertHasFormErrors(['email', 'gamer_tag']);

        Livewire::test(CreateUser::class)
            ->fillForm(['kind' => 'student', 'email' => 'neu@htl-md.schule', 'gamer_tag' => 'ab', 'parental_consent' => true])
            ->call('create')
            ->assertHasFormErrors(['gamer_tag' => 'min']);
    }

    public function test_rollen_aendern_mit_admin_schutz(): void
    {
        $zweiter = $this->userWithRole('admin');

        Livewire::test(EditUser::class, ['record' => $zweiter->getRouteKey()])
            ->fillForm(['roles' => ['teacher']])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['teacher'], $zweiter->fresh()->getRoleNames()->all());

        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['roles' => ['teacher'], 'name' => 'Neuer Name'])
            ->call('save')
            ->assertNotified('Nicht gespeichert');

        $this->admin->refresh();
        $this->assertTrue($this->admin->hasRole('admin'));
        $this->assertNotSame('Neuer Name', $this->admin->name);
    }

    public function test_sperren_entsperren_und_gamer_tag(): void
    {
        $student = $this->userWithRole('student', ['gamer_tag' => 'Beleidigung']);

        Livewire::test(ListUsers::class)
            ->callTableAction('deactivate', $student, data: ['reason' => 'Beleidigender Gamer-Tag'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Gesperrt. Die Person ist überall abgemeldet.');
        $this->assertSame('Beleidigender Gamer-Tag', $student->fresh()->deactivation_reason);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('deactivate', $student)
            ->callTableAction('reactivate', $student);
        $this->assertFalse($student->fresh()->isDeactivated());

        Livewire::test(ListUsers::class)->callTableAction('resetGamerTag', $student);
        $this->assertStringStartsWith('Spieler-', $student->fresh()->gamer_tag);
    }

    public function test_sperren_braucht_einen_grund_und_nie_sich_selbst(): void
    {
        $student = $this->userWithRole('student');

        Livewire::test(ListUsers::class)
            ->callTableAction('deactivate', $student, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);

        Livewire::test(ListUsers::class)
            ->callTableAction('deactivate', $this->admin, data: ['reason' => 'Test'])
            ->assertNotified('Nicht gespeichert');
        $this->assertFalse($this->admin->fresh()->isDeactivated());
    }

    public function test_loeschen_nur_bei_schueler_innen(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $teacher)
            ->callTableAction('delete', $student);

        $this->assertModelMissing($student);
        $this->assertModelExists($teacher);
    }

    /** Vier Aktionen nebeneinander passen nicht in die Zeile. Seltene und endgültige liegen im Menü. */
    public function test_seltene_aktionen_liegen_im_menue(): void
    {
        $actions = Livewire::test(ListUsers::class)->instance()->getTable()->getRecordActions();

        $direct = array_values(array_filter($actions, fn ($action) => ! $action instanceof ActionGroup));
        $groups = array_values(array_filter($actions, fn ($action) => $action instanceof ActionGroup));

        $this->assertSame(['edit', 'deactivate', 'reactivate'], array_map(fn ($action) => $action->getName(), $direct));
        $this->assertCount(1, $groups);
        $this->assertSame(['resetGamerTag', 'delete'], array_keys($groups[0]->getFlatActions()));
    }

    public function test_filter_ohne_klasse_und_gesperrt(): void
    {
        $gesperrt = $this->userWithRole('student', ['deactivated_at' => now()]);
        $aktiv = $this->userWithRole('student');

        Livewire::test(ListUsers::class)
            ->filterTable('deactivated_at', true)
            ->assertCanSeeTableRecords([$gesperrt])
            ->assertCanNotSeeTableRecords([$aktiv]);

        Livewire::test(ListUsers::class)
            ->filterTable('without_class')
            ->assertCanSeeTableRecords([$gesperrt, $aktiv]);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
