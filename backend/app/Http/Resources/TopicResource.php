<?php

namespace App\Http\Resources;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Thema für die App. locked: Level zu niedrig für die Lerneinheit, die Theorie bleibt lesbar.
 *
 * @mixin Topic
 */
class TopicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'level_required' => $this->level_required,
            'locked' => $this->level_required > $request->user()->progression()->level,
            'questions_count' => $this->whenCounted('questions'),
            'done' => $this->whenHas('done'),
            'theory' => TheorySectionResource::collection($this->whenLoaded('theorySections')),
        ];
    }
}
