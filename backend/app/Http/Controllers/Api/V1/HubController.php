<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttemptType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\Challenge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fakten für den Hub (Konzept Spielwelt §4). Der Server liefert, was ist, die App wählt Text und Bild.
 * challenge: Stand der Herausforderung für ihre Zeile (Design `Challenge`).
 */
class HubController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $last = QuizAttempt::finished()->where('user_id', $user->id)->latest('finished_at')->first();
        // Das Fach kommt aus der letzten Lerneinheit zu einem Thema, eine Herausforderung hat keins
        $lastLesson = QuizAttempt::finished()->where('user_id', $user->id)->where('attempt_type', AttemptType::Standard)->latest('finished_at')->first();
        $topic = $this->nextTopic($user, $lastLesson?->topic?->subject_id);

        // Ende der letzten Lerneinheit, null vor der ersten. Ob das heute war, entscheidet die App nach Gerätezeit.
        return response()->json(['data' => [
            'next_topic' => $topic ? new TopicResource($topic) : null,
            'last_study_at' => $last?->finished_at?->toIso8601String(),
            'challenge' => Challenge::status($user),
        ]]);
    }

    /**
     * Nächstes offenes Thema: zuerst im Fach der zuletzt abgeschlossenen Lerneinheit, dann in den übrigen
     * sichtbaren Fächern (E-024) nach Reihenfolge. Offen heißt veröffentlicht, mit Fragen, nicht gesperrt
     * und noch nicht abgeschlossen. Null, wenn alles erledigt ist.
     */
    private function nextTopic(User $user, ?string $lastSubjectId): ?Topic
    {
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
