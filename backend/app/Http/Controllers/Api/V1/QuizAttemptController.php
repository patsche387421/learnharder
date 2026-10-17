<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonQuestionResource;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Support\Booster;
use App\Support\DailyXp;
use App\Support\Progression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Lerneinheit: starten, Antworten prüfen, abschließen. Der Server prüft und rechnet, die App zeigt nur an.
 */
class QuizAttemptController extends Controller
{
    public function store(Request $request, string $topic): JsonResponse
    {
        // Postgres lehnt Nicht-UUIDs in der Abfrage mit einem Fehler ab, deshalb vorher 404
        abort_unless(Str::isUuid($topic), 404);

        $topic = Topic::published()
            ->whereKey($topic)
            ->whereHas('subject', fn ($subjects) => $subjects->visibleTo($request->user()))
            ->firstOrFail();

        Gate::authorize('create', [QuizAttempt::class, $topic]);

        $questions = $topic->questions()->published()->with('options')->get();

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['topic' => 'Zu diesem Thema gibt es noch keine Fragen.']);
        }

        $attempt = QuizAttempt::create([
            'user_id' => $request->user()->id,
            'topic_id' => $topic->id,
            'total_questions' => $questions->count(),
        ]);

        return response()->json(['data' => [
            'id' => $attempt->id,
            'questions' => LessonQuestionResource::collection($questions),
        ]], 201);
    }

    public function answer(Request $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('update', $attempt);

        if ($attempt->isFinished()) {
            throw new ConflictHttpException('Diese Lerneinheit ist schon abgeschlossen.');
        }

        $data = $request->validate([
            'question_id' => ['required', 'uuid'],
            'option_ids' => ['required', 'array', 'min:1'],
            'option_ids.*' => ['required', 'uuid', 'distinct'],
        ]);

        $question = Question::published()
            ->where('topic_id', $attempt->topic_id)
            ->whereKey($data['question_id'])
            ->with('options')
            ->first();

        if ($question === null) {
            throw ValidationException::withMessages(['question_id' => 'Diese Frage gehört nicht zu dieser Lerneinheit.']);
        }

        $request->validate([
            'option_ids' => [$question->type === QuestionType::SingleChoice ? 'max:1' : 'max:6'],
            'option_ids.*' => [Rule::in($question->options->pluck('id')->all())],
        ]);

        $selected = collect($data['option_ids'])->sort()->values()->all();
        $correctIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all();
        $isCorrect = $selected === $correctIds;

        // Nur die erste Antwort zählt. Spätere Antworten auf dieselbe Frage sind die Wiederholung am Ende (0 EP).
        $answer = QuestionAnswer::createOrFirst(
            ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
            ['user_id' => $attempt->user_id, 'selected_option_ids' => $selected, 'is_correct' => $isCorrect,
                'xp_multiplier' => Booster::multiplier($request->user())],
        );
        // Dieselbe erste Antwort noch einmal (z. B. nach einem Netzfehler) ist keine Wiederholung
        $repeat = ! $answer->wasRecentlyCreated && $answer->selected_option_ids !== $selected;
        // Eine Antwort von gestern (Lerneinheit über Nacht offen) bekommt beim Abschluss heute keinen Faktor mehr
        $multiplier = Booster::multiplierFor($answer, DailyXp::today());

        $xp = ! $repeat && $answer->is_correct
            && ! in_array($question->id, DailyXp::alreadyEarned($attempt->user_id, [$question->id]), true)
            ? Booster::apply($question->xp_reward, $multiplier)
            : 0;

        return response()->json(['data' => [
            'correct' => $isCorrect,
            'correct_option_ids' => $correctIds,
            'explanation' => $question->explanation,
            'xp' => $xp,
            // Faktor des Boosters, der beim ersten Antworten lief, nur am selben Tag (1 ohne). Die App zeigt „2×“ nur, wenn es EP gab.
            'multiplier' => $multiplier,
            'repeat' => $repeat,
        ]]);
    }

    public function finish(Request $request, QuizAttempt $attempt): JsonResponse
    {
        Gate::authorize('update', $attempt);

        if (! $attempt->answers()->exists()) {
            throw ValidationException::withMessages(['answers' => 'Beantworte zuerst mindestens eine Frage.']);
        }

        $attempt->finish();

        // Vorher-Werte aus den EP dieser Lerneinheit, damit ein wiederholter Abschluss dasselbe zeigt
        $user = $attempt->user()->first();
        $after = $user->progression();
        $before = Progression::fromTotalXp($after->total - $attempt->xp_earned);
        // Anteil des Boosters aus den gespeicherten EP und Faktoren, unabhängig vom heutigen xp_reward
        $fromBooster = $attempt->answers()->get()->sum(fn (QuestionAnswer $answer) => Booster::share($answer));

        return response()->json(['data' => [
            'xp_earned' => $attempt->xp_earned,
            'xp_from_booster' => $fromBooster,
            'correct_count' => $attempt->correct_count,
            'total_questions' => $attempt->total_questions,
            'level_up' => $after->level !== $before->level || $after->prestige !== $before->prestige,
            'before' => $before->toArray(),
            'progress' => $after->toArray(),
            'streak' => $user->streak(),
        ]]);
    }
}
