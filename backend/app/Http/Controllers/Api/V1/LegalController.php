<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Rechtstexte aus resources/legal/ (E-023). Öffentlich, damit man sie vor der Registrierung lesen kann.
 * Eine Quelle für Text und Fassung: Die Fassung steht in config/legal.php und wird bei der Zustimmung gespeichert.
 */
class LegalController extends Controller
{
    public const DOCUMENTS = ['datenschutz', 'impressum', 'nutzungsregeln', 'einwilligung-eltern'];

    public function show(string $slug): JsonResponse
    {
        abort_unless(in_array($slug, self::DOCUMENTS, true), 404);

        // Die erste Zeile ist die Überschrift, die App zeigt sie als Seitentitel
        [$heading, $body] = explode("\n", file_get_contents(resource_path("legal/{$slug}.md")), 2);

        return response()->json(['data' => [
            'slug' => $slug,
            'title' => Str::after($heading, '# '),
            'version' => config('legal.version'),
            'html' => Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
        ]]);
    }
}
