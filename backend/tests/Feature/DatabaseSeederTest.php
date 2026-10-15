<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legt_in_testing_rollen_und_demo_accounts_an(): void
    {
        $this->seed();

        $this->assertSame(5, Role::count());
        $this->assertTrue(User::where('email', 'admin@learnharder.app')->first()->hasRole('admin'));
        $this->assertSame(5, User::count());
    }

    public function test_legt_in_produktion_keine_demo_accounts_an(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])
            ->expectsOutputToContain('Demo-Accounts übersprungen')
            ->assertSuccessful();

        $this->assertSame(5, Role::count());
        $this->assertSame(0, User::count());
    }
}
