<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\QuestionAnswer;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Fächer und ihre Themen für die App. Sichtbar ist nur, was veröffentlicht und aktiv ist (E-014, E-017).
 * Erledigt ist ein Thema, sobald die Person eine Lerneinheit dazu abgeschlossen hat. Fach-EP und Fach-Level: E-018.
 */
class SubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subjects = Subject::visible()
            ->withCount([
                'topics' => fn (Builder $topics) => $topics->published(),
                'topics as topics_done_count' => fn (Builder $topics) => $topics->published()
                    ->whereHas('quizAttempts', fn (Builder $attempts) => $attempts->finished()->where('user_id', $request->user()->id)),
            ])
            ->orderBy('order_index')
            ->get();

        $xp = QuestionAnswer::xpBySubject($request->user()->id);
        $subjects->each(fn (Subject $subject) => $subject->setAttribute('xp', $xp[$subject->id] ?? 0));

        return SubjectResource::collection($subjects);
    }

    public function show(Request $request, string $subject): SubjectResource
    {
        $subject = Subject::visible()
            ->where('slug', $subject)
            ->with(['topics' => fn ($topics) => $topics->published()
                ->withCount(['questions' => fn (Builder $questions) => $questions->published()])
                ->withExists(['quizAttempts as done' => fn (Builder $attempts) => $attempts->finished()->where('user_id', $request->user()->id)])])
            ->firstOrFail();

        $subject->setAttribute('xp', QuestionAnswer::xpBySubject($request->user()->id)[$subject->id] ?? 0);

        return new SubjectResource($subject);
    }
}
