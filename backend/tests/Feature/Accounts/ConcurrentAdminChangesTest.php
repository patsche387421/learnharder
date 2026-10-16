<?php

namespace Tests\Feature\Accounts;

use App\Models\User;
use App\Support\Accounts;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Zwei Admins ändern gleichzeitig (E-027): Die Schutzregel muss auf die Sperre der Admin-Zeilen warten.
 * Eine zweite Datenbankverbindung hält die Sperre. Dafür müssen die Daten wirklich gespeichert sein,
 * deshalb DatabaseTruncation statt RefreshDatabase (dort bliebe alles in einer offenen Transaktion).
 */
class ConcurrentAdminChangesTest extends TestCase
{
    use DatabaseTruncation;

    private const LOCK_NOT_AVAILABLE = '55P03';

    public function test_aenderung_wartet_auf_die_sperre_der_admin_zeilen(): void
    {
        $role = Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $zweiter = User::factory()->create();
        $admin->assignRole($role);
        $zweiter->assignRole($role);

        config(['database.connections.zweite' => config('database.connections.pgsql')]);
        $zweite = DB::connection('zweite');
        $zweite->beginTransaction();
        // Dieselbe Sperre wie ein UPDATE, das gerade die handelnde Person sperrt. Sie hält FOR UPDATE auf,
        // aber nicht die Prüfung des Fremdschlüssels deactivated_by. Warten muss die Änderung also allein wegen lockForUpdate.
        $zweite->select('select id from users where id = ? for no key update', [$admin->id]);

        DB::statement("set lock_timeout = '200ms'");

        try {
            Accounts::deactivate($zweiter, $admin, 'Test');
            $this->fail('Die Änderung hätte auf die Sperre warten müssen.');
        } catch (QueryException $e) {
            $this->assertSame(self::LOCK_NOT_AVAILABLE, $e->getCode());
        } finally {
            DB::statement('reset lock_timeout');
            $zweite->rollBack();
            DB::purge('zweite');
        }

        $this->assertFalse($zweiter->fresh()->isDeactivated());
    }

    protected function tearDown(): void
    {
        // DatabaseTruncation leert erst vor dem eigenen Test. Die Tests danach erwarten eine leere Datenbank.
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }
}
