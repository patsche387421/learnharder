<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Notfallweg auf dem Server (E-027): legt einen Admin an oder macht ein bestehendes Konto wieder zum aktiven Admin.
 * Wer den Server hat, ist der letzte Admin. Ein Passwort setzt der Befehl nicht, das geht über „Passwort vergessen“.
 * make:filament-user reicht nicht: Er vergibt keine Rolle, und ohne Rolle lässt Filament niemanden ins CMS.
 */
class MakeAdmin extends Command
{
    protected $signature = 'learnharder:make-admin {email : E-Mail des Kontos} {--name= : Name, nur für ein neues Konto}';

    protected $description = 'Legt einen Admin an oder macht ein bestehendes Konto zum aktiven Admin';

    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user && ! $this->option('name')) {
            $this->error('Für ein neues Konto braucht es --name="Vorname Nachname".');

            return self::FAILURE;
        }

        DB::transaction(function () use (&$user, $email) {
            if (! $user) {
                $user = new User;
                // Ein Passwort, das niemand kennt
                $user->forceFill(['name' => $this->option('name'), 'email' => $email, 'password' => Str::random(64)])->save();
                $this->info("Konto {$email} angelegt.");
            }

            if ($user->isDeactivated()) {
                $user->forceFill(['deactivated_at' => null, 'deactivated_by' => null, 'deactivation_reason' => null])->save();
                $this->warn('Das Konto war gesperrt und ist jetzt entsperrt.');
            }

            $user->assignRole(Role::findOrCreate('admin', 'web'));
        });

        $this->info("{$email} ist aktiver Admin. Das Passwort setzt die Person in der App über „Passwort vergessen“.");

        return self::SUCCESS;
    }
}
