<?php

namespace Tests\Feature\Api;

use App\Mail\AlreadyRegistered;
use App\Mail\RegistrationCode;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Registrierung mit Code per Mail (E-025, Plan Schule §4).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = '2026034@htl-md.schule';

    protected function setUp(): void
    {
        parent::setUp();

        // Anfragen kommen wie von der Vue-App, damit Sanctum Session-Cookies nutzt
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
        Mail::fake();

        $school = School::create(['name' => 'HTL Mödling']);
        $school->emailDomains()->create(['domain' => 'htl-md.schule', 'allows_registration' => true]);
        $school->emailDomains()->create(['domain' => 'lehrer.htl-md.schule', 'allows_registration' => false]);
    }

    public function test_ganze_registrierung(): void
    {
        $this->travelTo('2026-10-16 10:00:00');

        $token = $this->verify($this->requestCode('2026034@HTL-MD.schule'));
        $response = $this->postJson('/api/v1/register', $this->registration($token))
            ->assertCreated()
            ->assertJsonPath('data.gamer_tag', 'Neo_2026')
            ->assertJsonPath('data.school_class', null);

        $this->assertStringNotContainsString('htl-md', $response->getContent());
        $user = User::where('email', self::EMAIL)->sole();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNull($user->name);
        $this->assertSame(['student'], $user->getRoleNames()->all());
        $this->assertSame('2026-10-16 10:00:00', $user->consented_at->toDateTimeString());
        $this->assertSame('2026-10-entwurf', $user->terms_version);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_nur_mit_freigegebener_schul_domain(): void
    {
        foreach (['lena@gmail.com', 'lena@lehrer.htl-md.schule'] as $email) {
            $this->postJson('/api/v1/register/code', ['email' => $email])
                ->assertJsonValidationErrors(['email' => 'Registrieren geht nur mit deiner Schul-Mail.']);
        }

        Mail::assertNothingSent();
    }

    public function test_bestehende_adresse_bekommt_hinweis_statt_code(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->postJson('/api/v1/register/code', ['email' => self::EMAIL])
            ->assertAccepted()
            ->assertJsonPath('message', 'Wir haben dir einen Code geschickt. Schau in dein Schulpostfach.');

        Mail::assertSent(AlreadyRegistered::class, fn ($mail) => $mail->hasTo(self::EMAIL));
        Mail::assertNotSent(RegistrationCode::class);
    }

    public function test_falscher_code_und_nach_fuenf_versuchen_ist_schluss(): void
    {
        $code = $this->requestCode();
        $falsch = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/register/verify', ['email' => self::EMAIL, 'code' => $falsch])
                ->assertJsonValidationErrors(['code' => 'Der Code stimmt nicht oder ist abgelaufen.']);
        }

        $this->postJson('/api/v1/register/verify', ['email' => self::EMAIL, 'code' => $code])
            ->assertJsonValidationErrors(['code']);
    }

    public function test_code_gilt_zehn_minuten_und_nur_einmal(): void
    {
        $this->travelTo('2026-10-16 10:00:00');
        $code = $this->requestCode();

        $this->travelTo('2026-10-16 10:10:01');
        $this->postJson('/api/v1/register/verify', ['email' => self::EMAIL, 'code' => $code])->assertJsonValidationErrors(['code']);

        $code = $this->requestCode();
        $this->verify($code);
        $this->postJson('/api/v1/register/verify', ['email' => self::EMAIL, 'code' => $code])->assertJsonValidationErrors(['code']);
    }

    public function test_token_gilt_nur_einmal_und_bleibt_bei_fehlern_im_formular(): void
    {
        User::factory()->create(['gamer_tag' => 'Neo_2026']);
        $token = $this->verify($this->requestCode());

        $this->postJson('/api/v1/register', $this->registration($token))
            ->assertJsonValidationErrors(['gamer_tag' => 'Diesen Gamer-Tag gibt es schon.']);

        $this->postJson('/api/v1/register', $this->registration($token, ['gamer_tag' => 'Trinity']))->assertCreated();

        $this->flushSession();
        $this->postJson('/api/v1/register', $this->registration($token, ['gamer_tag' => 'Morpheus']))
            ->assertJsonValidationErrors(['token' => 'Die Bestätigung ist abgelaufen. Fang bitte nochmal mit deiner E-Mail an.']);
        $this->assertSame(0, User::where('gamer_tag', 'Morpheus')->count());
    }

    public function test_ohne_gueltigen_token_kein_konto(): void
    {
        $this->postJson('/api/v1/register', $this->registration('erfunden'))
            ->assertJsonValidationErrors(['token' => 'Die Bestätigung ist abgelaufen. Fang bitte nochmal mit deiner E-Mail an.']);

        $this->assertSame(0, User::count());
    }

    public function test_alter_zustimmung_und_felder_werden_geprueft(): void
    {
        $token = $this->verify($this->requestCode());

        $this->postJson('/api/v1/register', $this->registration($token, [
            'is_14' => false, 'accepts_terms' => false, 'gamer_tag' => 'a b', 'password_confirmation' => 'anders123',
        ]))->assertJsonValidationErrors([
            'is_14' => 'Bestätige, dass du mindestens 14 bist.',
            'accepts_terms' => 'Stimm bitte der Datenschutzerklärung und den Nutzungsregeln zu.',
            'gamer_tag' => 'Nur Buchstaben, Ziffern, _ und -.',
            'password',
        ]);

        $this->assertSame(0, User::count());
    }

    public function test_code_mails_sind_begrenzt_je_adresse_ip_und_tag(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/register/code', ['email' => self::EMAIL])->assertAccepted();
        }
        $this->postJson('/api/v1/register/code', ['email' => self::EMAIL])->assertTooManyRequests();

        // Andere Adressen von derselben IP: insgesamt 10 pro Stunde
        for ($i = 1; $i <= 7; $i++) {
            $this->postJson('/api/v1/register/code', ['email' => "202600{$i}@htl-md.schule"])->assertAccepted();
        }
        $this->postJson('/api/v1/register/code', ['email' => '2026100@htl-md.schule'])->assertTooManyRequests();

        // Alle zusammen pro Tag, auch von anderen IPs. Bisher gingen 10 Code-Mails raus, abgewiesene zählen nicht.
        config(['accounts.codes_per_day' => 10]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/v1/register/code', ['email' => '2026200@htl-md.schule'])->assertTooManyRequests();
    }

    private function requestCode(string $email = self::EMAIL): string
    {
        $this->postJson('/api/v1/register/code', ['email' => $email])->assertAccepted();

        $code = null;
        Mail::assertSent(RegistrationCode::class, function (RegistrationCode $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo(self::EMAIL);
        });

        return $code;
    }

    private function verify(string $code): string
    {
        return $this->postJson('/api/v1/register/verify', ['email' => self::EMAIL, 'code' => $code])
            ->assertOk()
            ->json('token');
    }

    private function registration(string $token, array $overrides = []): array
    {
        return [
            'token' => $token,
            'gamer_tag' => 'Neo_2026',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'is_14' => true,
            'accepts_terms' => true,
            ...$overrides,
        ];
    }
}
