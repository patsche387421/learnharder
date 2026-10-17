<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fakten für den Hub (Konzept Spielwelt §4). Der Server liefert, was ist, die App wählt Text und Bild.
 */
class HubController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $topic = $this->nextTopic($request->user());

        return response()->json(['data' => [
            'next_topic' => $topic ? new TopicResource($topic) : null,
        ]]);
    }

    /**
     * Nächstes offenes Thema: zuerst im Fach der zuletzt abgeschlossenen Lerneinheit, dann in den übrigen
     * sichtbaren Fächern (E-024) nach Reihenfolge. Offen heißt veröffentlicht, mit Fragen, nicht gesperrt
     * und noch nicht abgeschlossen. Null, wenn alles erledigt ist.
     */
    private function nextTopic(User $user): ?Topic
    {
        $lastSubjectId = QuizAttempt::finished()
            ->where('user_id', $user->id)
            ->latest('finished_at')
            ->first()?->topic?->subject_id;

        $subjects = Subject::visibleTo($user)->orderBy('order_index')->get();
        $subjects = $subjects->where('id', $lastSubjectId)->concat($subjects->where('id', '!=', $lastSubjectId));

        $open = Topic::published()
            ->whereIn('subject_id', $subjects->modelKeys())
            ->where('level_required', '<=', $user->progression()->level)
            ->whereHas('questions', fn (Builder $questions) => $questions->published())
            ->whereDoesntHave('quizAttempts', fn (Builder $attempts) => $attempts->finished()->where('user_id', $user->id))
            ->orderBy('order_index')
            ->get()
            ->groupBy('subject_id');

        foreach ($subjects as $subject) {
            if ($topic = $open->get($subject->id)?->first()) {
                return $topic->setRelation('subject', $subject);
            }
        }

        return null;
    }
}
