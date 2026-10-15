<?php

namespace App\Http\Resources;

use App\Models\TheorySection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Theorieblock als fertiges HTML. HTML im Markdown wird maskiert, unsichere Links (javascript:) entfernt,
 * damit die App den Inhalt per v-html anzeigen darf.
 *
 * @mixin TheorySection
 */
class TheorySectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'html' => Str::markdown($this->content ?? '', [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]),
        ];
    }
}
