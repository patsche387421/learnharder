<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonQuestionResource;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Support\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Herausforderung starten und fortsetzen (E-045). Antworten und Abschluss laufen über die Routen der Lerneinheit.
 */
class ChallengeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $attempt = Challenge::start($user);

        return response()->json(['data' => [
            ...$this->challenge($attempt),
            'brainpower' => $user->brainpower(),
        ]], 201);
    }

    /** Offene Herausforderung von heute mit den schon beantworteten Fragen, sonst null. */
    public function current(Request $request): JsonResponse
    {
        $attempt = Challenge::current($request->user());

        return response()->json(['data' => $attempt ? $this->challenge($attempt) : null]);
    }

    /** @return array<string, mixed> */
    private function challenge(QuizAttempt $attempt): array
    {
        $order = array_flip($attempt->question_ids);
        $questions = Question::published()
            ->whereIn('id', $attempt->question_ids)
            ->with(['options', 'topic.subject:id,slug,name'])
            ->get()
            ->sortBy(fn (Question $question) => $order[$question->id])
            ->values()
            // Feste EP statt xp_reward (nur für die Anzeige, gespeichert wird nichts), Antworten gemischt wie in der Lerneinheit
            ->each(fn (Question $question) => $question->setAttribute('xp_reward', config('gamification.challenge.xp_per_correct'))
                ->setRelation('options', $question->shuffledOptions($attempt->id)));

        return [
            'id' => $attempt->id,
            'questions' => LessonQuestionResource::collection($questions),
            'answered' => $attempt->answers()->get(['question_id', 'is_correct'])
                ->map(fn (QuestionAnswer $answer) => ['question_id' => $answer->question_id, 'correct' => $answer->is_correct]),
        ];
    }
}
