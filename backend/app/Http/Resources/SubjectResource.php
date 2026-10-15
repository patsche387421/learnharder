<?php

namespace App\Http\Resources;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fach für die App. code ist der Slug, die App baut daraus Kürzel und Icon (subject-pos).
 *
 * @mixin Subject
 */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'color_token' => $this->color_token,
            'topics_count' => $this->whenCounted('topics'),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
        ];
    }
}
