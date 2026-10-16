<?php

namespace Tests\Feature\Lang;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Das CMS duzt wie die App (docs/design/Sprache.md). Filament siezt, lang/vendor/filament-* überschreibt das.
 */
class FilamentDuFormTest extends TestCase
{
    #[TestWith(['filament-actions::delete.multiple.notifications.deleted_none.missing_authorization_failure_message'])]
    #[TestWith(['filament-actions::modal.confirmation'])]
    #[TestWith(['filament-actions::notifications.throttled.body'])]
    #[TestWith(['filament-panels::unsaved-changes-alert.body'])]
    #[TestWith(['filament-panels::error-notifications.body'])]
    #[TestWith(['filament-panels::auth/pages/login.heading'])]
    #[TestWith(['filament-forms::components.select.placeholder'])]
    #[TestWith(['filament-forms::components.select.search_prompt'])]
    #[TestWith(['filament-tables::table.columns.select.search_prompt'])]
    public function test_cms_text_ist_in_du_form(string $key): void
    {
        app()->setLocale('de');

        $this->assertNotSame($key, __($key), "Schlüssel {$key} fehlt");
        $this->assertDoesNotMatchRegularExpression('/\b(Sie|Ihr|Ihre|Ihren|Ihrem|Ihrer|Ihnen)\b/u', __($key));
    }

    public function test_ueberschreiben_ersetzt_nur_einzelne_schluessel(): void
    {
        app()->setLocale('de');

        // Steht nur in vendor/filament, nicht im Override derselben Datei
        $this->assertSame('Keine Optionen verfügbar.', __('filament-forms::components.select.no_options_message'));
        $this->assertSame('Abbrechen', __('filament-actions::modal.actions.cancel.label'));
    }
}
