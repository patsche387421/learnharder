<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Spatie Rollen anlegen
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $autorRole = Role::firstOrCreate(['name' => 'autor']);
        $testerRole = Role::firstOrCreate(['name' => 'tester']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        // Demo-Accounts haben ein bekanntes Passwort und dürfen nie in Produktion landen.
        // Den echten Admin dort per `php artisan make:filament-user` anlegen.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo-Accounts übersprungen (nur in local/testing).');

            return;
        }

        // 2. Super-Admin für Filament CMS
        $admin = User::firstOrCreate(
            ['email' => 'admin@learnharder.app'],
            [
                'name' => 'Administrator',
                'first_name' => 'Admin',
                'last_name' => 'System',
                'gamer_tag' => 'AdminRoot',
                'brainpower_current' => 200,
                'brainpower_max' => 200,
                'password' => Hash::make('learnharder123'),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // 3. Redakteur / Autor Account
        $autor = User::firstOrCreate(
            ['email' => 'autor@learnharder.app'],
            [
                'name' => 'Content Autor',
                'first_name' => 'Max',
                'last_name' => 'Redakteur',
                'gamer_tag' => 'ContentCreator',
                'brainpower_current' => 150,
                'brainpower_max' => 150,
                'password' => Hash::make('learnharder123'),
            ]
        );
        $autor->syncRoles([$autorRole]);

        // 4. Test-Lehrkraft Account
        $teacher = User::firstOrCreate(
            ['email' => 'lehrer@learnharder.app'],
            [
                'name' => 'Prof. Dr. Mustermann',
                'first_name' => 'Erika',
                'last_name' => 'Mustermann',
                'gamer_tag' => 'ProfMuster',
                'brainpower_current' => 100,
                'brainpower_max' => 100,
                'password' => Hash::make('learnharder123'),
            ]
        );
        $teacher->syncRoles([$teacherRole]);

        // 5. Schüler Account
        $student = User::firstOrCreate(
            ['email' => 'schueler@learnharder.app'],
            [
                'name' => 'Lukas Lerner',
                'first_name' => 'Lukas',
                'last_name' => 'Lerner',
                'gamer_tag' => 'CodeNinja99',
                'brainpower_current' => 100,
                'brainpower_max' => 100,
                'current_streak' => 5,
                'password' => Hash::make('learnharder123'),
            ]
        );
        $student->syncRoles([$studentRole]);

        UserStat::firstOrCreate(
            ['user_id' => $student->id],
            [
                'total_xp' => 450,
                'trophies' => 12,
                'current_streak' => 5,
                'longest_streak' => 7,
            ]
        );
    }
}
