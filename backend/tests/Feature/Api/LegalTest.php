<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\V1\LegalController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Rechtstexte (E-023): öffentlich, mit der Fassung aus config/legal.php, passend zu dem, was die App speichert.
 */
class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_alle_texte_ohne_login_mit_titel_und_fassung(): void
    {
        $titles = [
            'datenschutz' => 'Datenschutzerklärung',
            'impressum' => 'Impressum',
            'nutzungsregeln' => 'Nutzungsregeln',
            'einwilligung-eltern' => 'Einwilligung der Eltern',
        ];

        foreach ($titles as $slug => $title) {
            $html = $this->getJson("/api/v1/legal/{$slug}")
                ->assertOk()
                ->assertJsonPath('data.title', $title)
                ->assertJsonPath('data.version', config('legal.version'))
                ->json('data.html');

            $this->assertStringNotContainsString('<h1>', $html, $slug);
            $this->assertStringContainsString('Entwurf', $html, $slug);
            // Leere Tabellenköpfe erscheinen als leere Zeile über der Tabelle, auch im Ausdruck
            $this->assertStringNotContainsString('<th></th>', $html, $slug);
        }

        $this->assertSame(array_keys($titles), LegalController::DOCUMENTS);
    }

    public function test_unbekannter_text_gibt_404(): void
    {
        $this->getJson('/api/v1/legal/agb')->assertNotFound();
        $this->getJson('/api/v1/legal/..%2F..%2F.env')->assertNotFound();
    }

    /** Ändert sich die Fassung in config/legal.php, muss sie auch in den Texten stehen und umgekehrt. */
    public function test_texte_nennen_die_aktuelle_fassung(): void
    {
        foreach (LegalController::DOCUMENTS as $slug) {
            $markdown = file_get_contents(resource_path("legal/{$slug}.md"));

            // Fassungen beginnen mit Jahr und Monat, z. B. 2026-10-entwurf
            preg_match_all('/Fassung (\d{4}-\d{2}[^\s.)]*)/u', $markdown, $versions);
            $this->assertNotEmpty($versions[1], $slug);
            $this->assertSame([config('legal.version')], array_values(array_unique($versions[1])), $slug);
        }
    }

    /**
     * Die Datenschutzerklärung muss zu dem passen, was die App speichert (E-023). Kommt eine Spalte mit Personendaten dazu,
     * schlägt dieser Test fehl: Dann den Text ergänzen und die Spalte hier eintragen.
     */
    public function test_datenschutzerklaerung_passt_zu_den_gespeicherten_daten(): void
    {
        $described = [
            'users' => ['id', 'name', 'email', 'gamer_tag', 'password', 'remember_token', 'email_verified_at', 'consented_at', 'terms_version',
                'parental_consent_at', 'parental_consent_by', 'deactivated_at', 'deactivated_by', 'deactivation_reason', 'last_login_at',
                'brainpower_current', 'brainpower_refilled_on',
                // Noch ohne Funktion
                'last_active_at', 'created_at', 'updated_at'],
            'sessions' => ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'],
        ];

        foreach ($described as $table => $columns) {
            $this->assertEqualsCanonicalizing($columns, Schema::getColumnListing($table), "Neue Spalte in {$table}: Datenschutzerklärung prüfen");
        }

        $text = file_get_contents(resource_path('legal/datenschutz.md'));
        foreach (['IP-Adresse', 'Browser-Kennung', '12 Monate', '10 Minuten', '2 Stunden', 'Hetzner', 'Scaleway', 'Klasse', 'Gamer-Tag', 'Lernfortschritt'] as $term) {
            $this->assertStringContainsString($term, $text);
        }
        $this->assertSame(10, config('accounts.code_minutes'));
        $this->assertSame(120, config('session.lifetime'));
    }
}
