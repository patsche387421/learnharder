<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ein Thema mit Theorie. Theorie ist auch bei gesperrten Themen lesbar: Lernen wird nie gesperrt (Ökonomie-Modell §1).
 */
class TopicController extends Controller
{
    public function show(string $subject, string $topic): TopicResource
    {
        $topic = Subject::visible()
            ->where('slug', $subject)
            ->firstOrFail()
            ->topics()
            ->published()
            ->where('slug', $topic)
            ->withCount(['questions' => fn (Builder $questions) => $questions->published()])
            ->withSum(['questions as xp_total' => fn (Builder $questions) => $questions->published()], 'xp_reward')
            ->with('theorySections')
            ->firstOrFail();

        return new TopicResource($topic);
    }
}
