<?php

namespace App\Support;

use App\Enums\AttemptType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Herausforderung (E-045, Ökonomie-Modell §3.3): zufällige Fragen aus Themen, in denen die Person schon eine Lerneinheit
 * abgeschlossen hat, für Brainpower. Werte in config/gamification.php unter challenge. Eine offene Herausforderung lässt
 * sich bis Mitternacht in Wien fortsetzen, danach ist sie abgelaufen.
 */
final class Challenge
{
    /**
     * Fragen, aus denen gezogen wird: veröffentlicht, im Lehrplan (Subject::visibleTo), Level reicht, Thema schon gelernt.
     *
     * @return Collection<int, Question>
     */
    public static function pool(User $user): Collection
    {
        $level = $user->progression()->level;

        return Question::published()
            ->whereHas('topic', fn (Builder $topics) => $topics->published()
                ->where('level_required', '<=', $level)
                ->whereHas('subject', fn (Builder $subjects) => $subjects->visibleTo($user))
                ->whereHas('quizAttempts', fn (Builder $attempts) => $attempts->finished()->where('user_id', $user->id)))
            ->with('topic:id,subject_id')
            ->get();
    }

    /**
     * Startet eine Herausforderung und bucht die Brainpower ab. Lädt vorher auf, dann unter Sperre auf das Konto:
     * keine offene von heute, Tageslimit, genug Fragen, genug Brainpower. Ein doppelter Tipp startet deshalb nur eine.
     */
    public static function start(User $user): QuizAttempt
    {
        $config = config('gamification.challenge');
        Brainpower::refill($user);

        return DB::transaction(function () use ($user, $config): QuizAttempt {
            $locked = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail(['id', 'brainpower_current']);

            if (self::current($user)) {
                throw new ConflictHttpException('Du hast noch eine offene Herausforderung.');
            }
            if (self::startedToday($user) >= $config['daily_limit']) {
                throw new ConflictHttpException('Für heute hast du alle Herausforderungen gespielt. Morgen wieder.');
            }
            $questions = self::draw(self::pool($user), $config['questions']);
            if ($questions === null) {
                throw ValidationException::withMessages(['challenge' => 'Schließe zuerst ein paar Themen ab.']);
            }
            if ($locked->brainpower_current < $config['cost']) {
                throw ValidationException::withMessages(['challenge' => 'Dafür reicht deine Brainpower gerade nicht. Morgen lädt sie sich wieder auf.']);
            }

            $locked->forceFill(['brainpower_current' => $locked->brainpower_current - $config['cost']])->save();
            $user->forceFill($locked->only(['brainpower_current']))->syncOriginal();

            return QuizAttempt::create([
                'user_id' => $user->id,
                'attempt_type' => AttemptType::Challenge,
                'question_ids' => $questions,
                'total_questions' => count($questions),
                'brainpower_spent' => $config['cost'],
            ]);
        });
    }

    /** Offene Herausforderung von heute, sonst null. Ältere sind abgelaufen und blockieren nichts. */
    public static function current(User $user): ?QuizAttempt
    {
        return self::today($user)->whereNull('finished_at')->latest()->first();
    }

    /** Am Tag des Starts in Wien noch offen? Danach lässt sie sich weder beantworten noch abschließen. */
    public static function expired(QuizAttempt $attempt): bool
    {
        return $attempt->created_at->clone()->setTimezone(DailyXp::TIMEZONE)->toDateString() !== DailyXp::today();
    }

    /** Bonus nach richtigen ersten Antworten: die höchste erreichte Stufe aus der Config. */
    public static function bonus(int $correct): int
    {
        return collect(config('gamification.challenge.bonus'))
            ->filter(fn (int $xp, int $from) => $correct >= $from)
            ->max() ?? 0;
    }

    /**
     * Was Hub und Startbildschirm zeigen (Design `Challenge`): Kosten und Regeln aus der Config (die App kennt keine Werte
     * selbst, E-037), Starts heute übrig, die offene mit Stand und ob sie gesperrt ist, weil es zu wenige gelernte Fragen gibt.
     *
     * @return array{cost: int, questions: int, xp_per_correct: int, bonus: list<array{from: int, xp: int}>, perfect_trophies: int,
     *     left_today: int, open: array{id: string, answered: int, total: int}|null, locked: bool}
     */
    public static function status(User $user): array
    {
        $config = config('gamification.challenge');
        $open = self::current($user);

        return [
            'cost' => $config['cost'],
            'questions' => $config['questions'],
            'xp_per_correct' => $config['xp_per_correct'],
            'bonus' => collect($config['bonus'])->map(fn (int $xp, int $from) => ['from' => $from, 'xp' => $xp])->values()->all(),
            'perfect_trophies' => $config['perfect_trophies'],
            'left_today' => max(0, $config['daily_limit'] - self::startedToday($user)),
            'open' => $open ? ['id' => $open->id, 'answered' => $open->answers()->count(), 'total' => $open->total_questions] : null,
            'locked' => ! $open && self::pool($user)->count() < $config['questions'],
        ];
    }

    private static function startedToday(User $user): int
    {
        return self::today($user)->count();
    }

    private static function today(User $user): Builder
    {
        $start = CarbonImmutable::now(DailyXp::TIMEZONE)->startOfDay();

        return QuizAttempt::where('user_id', $user->id)
            ->where('attempt_type', AttemptType::Challenge)
            ->where('created_at', '>=', $start->utc())
            ->where('created_at', '<', $start->addDay()->utc());
    }

    /**
     * $count zufällige Fragen, abwechselnd aus den Fächern (erst eine aus jedem, dann die zweite …). Null, wenn der Pool kleiner ist.
     *
     * @param  Collection<int, Question>  $pool
     * @return array<int, string>|null
     */
    private static function draw(Collection $pool, int $count): ?array
    {
        if ($pool->count() < $count) {
            return null;
        }

        $bySubject = $pool->shuffle()->groupBy('topic.subject_id')->shuffle()->map->values();
        $rounds = $bySubject->max(fn ($questions) => $questions->count());

        return collect(range(0, $rounds - 1))
            ->flatMap(fn (int $round) => $bySubject->map(fn ($questions) => $questions->get($round))->filter())
            ->take($count)
            ->pluck('id')
            ->all();
    }
}
