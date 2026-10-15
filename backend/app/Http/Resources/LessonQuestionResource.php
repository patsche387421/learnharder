<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Frage in der Lerneinheit, ohne Lösung und ohne Erklärung. Beides kommt erst nach dem Prüfen.
 *
 * @mixin Question
 */
class LessonQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'text' => $this->question_text,
            'xp_reward' => $this->xp_reward,
            'options' => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->option_text,
            ]),
        ];
    }
}
