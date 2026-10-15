<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Das CMS trägt Namen, Farben und Schrift von learnharder (Design-System, keine externen Fonts).
 */
class PanelDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_zeigt_name_farbe_und_lokale_schrift(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('<title>', false)
            ->assertSeeText('learnharder')
            ->assertDontSee('- Laravel', false)
            ->assertSee('--primary-600:oklch(0.531 0.108 57)', false)
            ->assertSee(asset('fonts/atkinson-hyperlegible-next/index.css'), false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('fonts.bunny.net', false);

        $this->assertFileExists(public_path('fonts/atkinson-hyperlegible-next/AtkinsonHyperlegibleNext-Variable.woff2'));
    }

    public function test_uebersicht_ohne_filament_werbung(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'admin']));

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertDontSeeText(['GitHub', 'Dokumentation']);
    }
}
