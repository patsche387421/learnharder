<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Booster;
use Database\Seeders\TestPersonasSeeder;
use Illuminate\Console\Command;

/**
 * Stand der Testpersonen als JSON für die Testzentrale (scripts/dev.py). Nur lesen: lädt keine Brainpower auf und
 * ändert nichts. Zurücksetzen geht über `db:seed --class=TestPersonasSeeder`.
 */
class TestPersonas extends Command
{
    protected $signature = 'learnharder:test-personas';

    protected $description = 'Zeigt den Stand der Testpersonen als JSON (nur lokal)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Testpersonen gibt es nur lokal.');

            return self::FAILURE;
        }

        $personas = User::where('email', 'like', '%@'.TestPersonasSeeder::DOMAIN)
            ->with(['stats', 'activeEnrollment.schoolClass'])
            ->orderBy('id')
            ->get()
            ->map(function (User $user) {
                $progress = $user->progression();
                $booster = Booster::current($user);

                return [
                    'gamer_tag' => $user->gamer_tag,
                    'email' => $user->email,
                    'level' => $progress->level,
                    'prestige' => $progress->prestige,
                    'xp' => $progress->total,
                    'streak' => $user->streak(),
                    'brainpower' => $user->brainpower(),
                    'booster' => $booster ? [
                        'multiplier' => $booster['multiplier'],
                        'minutes' => (int) ceil(now()->diffInSeconds($user->booster_ends_at) / 60),
                    ] : null,
                    'school_class' => $user->activeEnrollment?->schoolClass?->name,
                    'deactivated' => $user->deactivated_at !== null,
                ];
            });

        $this->line($personas->toJson(JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
