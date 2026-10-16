<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Notfallweg: Admin über den Server anlegen oder wieder aktivieren (E-027).
 */
class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_legt_neues_admin_konto_ohne_bekanntes_passwort_an(): void
    {
        $this->artisan('learnharder:make-admin', ['email' => 'Direktion@HTL-MD.schule', '--name' => 'Erika Muster'])
            ->assertSuccessful();

        $admin = User::where('email', 'direktion@htl-md.schule')->sole();
        $this->assertSame('Erika Muster', $admin->name);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertTrue($admin->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_neues_konto_braucht_einen_namen(): void
    {
        $this->artisan('learnharder:make-admin', ['email' => 'neu@htl-md.schule'])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_bestehende_lehrkraft_wird_zusaetzlich_admin(): void
    {
        $teacher = User::factory()->create(['email' => 'lehrer@htl-md.schule']);
        $teacher->assignRole(Role::findOrCreate('teacher'));

        $this->artisan('learnharder:make-admin', ['email' => 'LEHRER@htl-md.schule'])->assertSuccessful();

        $this->assertEqualsCanonicalizing(['teacher', 'admin'], $teacher->fresh()->getRoleNames()->all());
        $this->assertSame(1, User::count());
    }

    public function test_gesperrter_admin_wird_entsperrt(): void
    {
        $admin = User::factory()->create(['email' => 'admin@htl-md.schule', 'deactivated_at' => now(), 'deactivation_reason' => 'Versehen']);
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->artisan('learnharder:make-admin', ['email' => 'admin@htl-md.schule'])
            ->expectsOutput('Das Konto war gesperrt und ist jetzt entsperrt.')
            ->assertSuccessful();

        $admin->refresh();
        $this->assertFalse($admin->isDeactivated());
        $this->assertNull($admin->deactivation_reason);
    }
}
