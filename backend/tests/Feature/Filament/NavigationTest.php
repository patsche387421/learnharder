<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sieht_das_menue_deutsch_und_gruppiert(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Übersicht', 'Inhalte', 'Fächer', 'Themen', 'Fragen', 'Schule', 'Schulen', 'Ausbildungsgänge'])
            ->assertDontSee(['Subjects', 'Topics', 'Questions']);
    }

    public function test_autor_sieht_nur_die_inhalte(): void
    {
        $this->actingAs($this->userWithRole('autor'));

        $this->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Inhalte', 'Fächer', 'Themen', 'Fragen'])
            ->assertDontSee(['Schulen', 'Ausbildungsgänge', 'Erfolge']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
