<?php

namespace Tests\Feature\Api;

use App\Mail\PasswordCode;
use App\Models\User;
use App\Support\EmailCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * „Passwort vergessen“ mit Code per Mail (E-025), danach überall abgemeldet (E-027).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = '2026034@htl-md.schule';

    private const ANTWORT = 'Wenn es zu dieser Adresse ein Konto gibt, kommt gleich ein Code.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        Mail::fake();
    }

    public function test_neues_passwort_und_ueberall_abgemeldet(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL, 'remember_token' => 'alt']);
        DB::table('sessions')->insert(['id' => 'alte-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        $code = $this->requestCode('2026034@HTL-MD.schule');
        $this->postJson('/api/v1/password/reset', $this->reset($code))->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('neu-geheim1', $user->password));
        $this->assertNotSame('alt', $user->getRememberToken());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());

        $this->postJson('/api/v1/login', ['email' => self::EMAIL, 'password' => 'neu-geheim1'])->assertOk();
    }

    public function test_unbekannte_und_gesperrte_adressen_bekommen_keinen_code(): void
    {
        User::factory()->create(['email' => 'gesperrt@htl-md.schule', 'deactivated_at' => now()]);

        foreach (['niemand@htl-md.schule', 'gesperrt@htl-md.schule'] as $email) {
            $this->postJson('/api/v1/password/code', ['email' => $email])
                ->assertAccepted()
                ->assertJsonPath('message', self::ANTWORT);
        }

        Mail::assertNothingSent();
    }

    public function test_falscher_code_aendert_nichts(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);
        $code = $this->requestCode();

        $this->postJson('/api/v1/password/reset', $this->reset($code === '000000' ? '111111' : '000000'))
            ->assertJsonValidationErrors(['code' => 'Der Code stimmt nicht oder ist abgelaufen.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_code_der_registrierung_gilt_nicht_fuers_passwort(): void
    {
        User::factory()->create(['email' => self::EMAIL]);
        $code = EmailCode::issue('register', self::EMAIL);

        $this->postJson('/api/v1/password/reset', $this->reset($code))->assertJsonValidationErrors(['code']);
    }

    public function test_zu_kurzes_passwort_kostet_keinen_versuch(): void
    {
        User::factory()->create(['email' => self::EMAIL]);
        $code = $this->requestCode();

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/password/reset', $this->reset($code, 'kurz'))->assertJsonValidationErrors(['password']);
        }

        $this->postJson('/api/v1/password/reset', $this->reset($code))->assertNoContent();
    }

    public function test_code_mails_teilen_sich_das_limit_mit_der_registrierung(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/password/code', ['email' => self::EMAIL])->assertAccepted();
        }

        $this->postJson('/api/v1/register/code', ['email' => self::EMAIL])->assertTooManyRequests();
    }

    private function requestCode(string $email = self::EMAIL): string
    {
        $this->postJson('/api/v1/password/code', ['email' => $email])->assertAccepted();

        $code = null;
        Mail::assertSent(PasswordCode::class, function (PasswordCode $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo(self::EMAIL);
        });

        return $code;
    }

    private function reset(string $code, string $password = 'neu-geheim1'): array
    {
        return ['email' => self::EMAIL, 'code' => $code, 'password' => $password, 'password_confirmation' => $password];
    }
}
