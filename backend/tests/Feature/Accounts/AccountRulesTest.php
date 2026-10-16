<?php

namespace Tests\Feature\Accounts;

use App\Exceptions\AccountRuleViolation;
use App\Models\User;
use App\Support\Accounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Schutzregeln für Konten (E-027): sperren, entsperren, Rollen, löschen, Gamer-Tag.
 */
class AccountRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->userWithRole('admin');
    }

    public function test_sperren_speichert_wer_wann_und_warum_und_meldet_ueberall_ab(): void
    {
        $student = $this->userWithRole('student', ['remember_token' => 'alt']);
        $this->sessionFor($student);
        $this->sessionFor($this->admin);

        Accounts::deactivate($student, $this->admin, 'Beleidigender Gamer-Tag');

        $student->refresh();
        $this->assertTrue($student->isDeactivated());
        $this->assertTrue($student->deactivatedBy->is($this->admin));
        $this->assertSame('Beleidigender Gamer-Tag', $student->deactivation_reason);
        $this->assertNotSame('alt', $student->getRememberToken());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $student->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $this->admin->id)->count());
    }

    public function test_entsperren_raeumt_die_sperre_auf(): void
    {
        $student = $this->userWithRole('student');
        Accounts::deactivate($student, $this->admin, 'Versehen');

        Accounts::reactivate($student, $this->admin);

        $student->refresh();
        $this->assertFalse($student->isDeactivated());
        $this->assertNull($student->deactivated_by);
        $this->assertNull($student->deactivation_reason);
    }

    public function test_niemand_sperrt_sich_selbst(): void
    {
        $this->userWithRole('admin');

        $this->assertRefused('Du kannst dich nicht selbst sperren.', fn () => Accounts::deactivate($this->admin, $this->admin, 'Test'));
        $this->assertFalse($this->admin->fresh()->isDeactivated());
    }

    public function test_es_bleibt_immer_ein_aktiver_admin(): void
    {
        $zweiter = $this->userWithRole('admin');

        Accounts::deactivate($zweiter, $this->admin, 'Schule verlassen');

        // Der gesperrte Admin kann den letzten aktiven weder sperren noch herabstufen
        $this->assertRefused('Nur aktive Admins dürfen Konten verwalten.', fn () => Accounts::deactivate($this->admin, $zweiter, 'Rache'));
        $this->assertRefused('Nur aktive Admins dürfen Konten verwalten.', fn () => Accounts::syncRoles($this->admin, ['teacher'], $zweiter));
        $this->assertSame([$this->admin->id], User::role('admin')->whereNull('deactivated_at')->pluck('id')->all());
    }

    public function test_nur_aktive_admins_verwalten_konten(): void
    {
        $student = $this->userWithRole('student');
        $gesperrt = $this->userWithRole('admin', ['deactivated_at' => now()]);

        $this->assertRefused('Nur aktive Admins dürfen Konten verwalten.', fn () => Accounts::deactivate($student, $gesperrt, 'Test'));
        $this->assertRefused('Nur aktive Admins dürfen Konten verwalten.', fn () => Accounts::deactivate($student, $this->userWithRole('teacher'), 'Test'));
        $this->assertFalse($student->fresh()->isDeactivated());
    }

    public function test_admin_rolle_nimmt_man_sich_nicht_selbst(): void
    {
        $this->userWithRole('admin');

        $this->assertRefused('Du kannst dir die Admin-Rolle nicht selbst nehmen.', fn () => Accounts::syncRoles($this->admin, ['teacher'], $this->admin));
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_herabstufen_geht_solange_ein_admin_bleibt(): void
    {
        $zweiter = $this->userWithRole('admin');
        Role::findOrCreate('teacher');
        Role::findOrCreate('autor');

        Accounts::syncRoles($zweiter, ['teacher', 'autor'], $this->admin);

        $this->assertEqualsCanonicalizing(['teacher', 'autor'], $zweiter->fresh()->getRoleNames()->all());
    }

    public function test_admin_loescht_schueler_in_aber_kein_personal(): void
    {
        $student = $this->userWithRole('student');
        $this->sessionFor($student);
        $lehrer = $this->userWithRole('teacher');

        Accounts::deleteByAdmin($student, $this->admin);

        $this->assertModelMissing($student);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $student->id)->count());
        $this->assertRefused('Personal wird gesperrt, nicht gelöscht.', fn () => Accounts::deleteByAdmin($lehrer, $this->admin));
        $this->assertRefused('Du kannst dich nicht selbst löschen.', fn () => Accounts::deleteByAdmin($this->admin, $this->admin));
        $this->assertModelExists($lehrer);
    }

    public function test_gamer_tag_zuruecksetzen(): void
    {
        $student = $this->userWithRole('student', ['gamer_tag' => 'Beleidigung']);

        $tag = Accounts::resetGamerTag($student, $this->admin);

        $this->assertMatchesRegularExpression('/^Spieler-\d{5}$/', $tag);
        $this->assertSame($tag, $student->fresh()->gamer_tag);
    }

    private function assertRefused(string $message, callable $change): void
    {
        try {
            $change();
            $this->fail("Erwartet: {$message}");
        } catch (AccountRuleViolation $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }

    private function sessionFor(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => fake()->uuid(), 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
    }
}
