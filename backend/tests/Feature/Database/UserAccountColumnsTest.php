<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Spalten für Konten aus M4a (E-025, E-027, E-028).
 */
class UserAccountColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_schueler_in_braucht_keinen_namen(): void
    {
        $user = User::factory()->create(['name' => null]);

        $this->assertNull($user->fresh()->name);
    }

    public function test_klarnamen_spalten_gibt_es_nicht_mehr(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'first_name'));
        $this->assertFalse(Schema::hasColumn('users', 'last_name'));
    }

    public function test_sperre_und_einwilligung_werden_gespeichert(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'consented_at' => '2026-10-16 10:00:00',
            'terms_version' => '2026-10-entwurf',
            'parental_consent_at' => '2026-10-16 09:00:00',
            'parental_consent_by' => $admin->id,
            'deactivated_at' => '2026-10-17 08:00:00',
            'deactivated_by' => $admin->id,
            'deactivation_reason' => 'Beleidigender Gamer-Tag',
            'last_login_at' => '2026-10-16 10:05:00',
        ])->fresh();

        $this->assertTrue($user->isDeactivated());
        $this->assertTrue($user->deactivatedBy->is($admin));
        $this->assertTrue($user->parentalConsentBy->is($admin));
        $this->assertSame('2026-10-entwurf', $user->terms_version);
        $this->assertSame('2026-10-16 10:05:00', $user->last_login_at->toDateTimeString());
        $this->assertFalse(User::factory()->create()->isDeactivated());
    }

    public function test_geloeschter_admin_laesst_den_verweis_leer(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create([
            'deactivated_at' => now(),
            'deactivated_by' => $admin->id,
            'parental_consent_by' => $admin->id,
        ]);

        $admin->delete();

        $user->refresh();
        $this->assertNull($user->deactivated_by);
        $this->assertNull($user->parental_consent_by);
        $this->assertTrue($user->isDeactivated());
    }
}
