<?php

namespace App\Models;

use App\Enums\AttemptType;
use App\Support\Achievements;
use App\Support\Booster;
use App\Support\Challenge;
use App\Support\DailyXp;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Eine Lerneinheit zu einem Thema oder eine Herausforderung aus mehreren Themen (E-045, ohne topic_id, mit question_ids).
 * Die ID ist der Idempotency-Key für den Abschluss.
 */
class QuizAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'topic_id',
        'correct_count',
        'total_questions',
        'score_percentage',
        'xp_earned',
        'brainpower_spent',
        'attempt_type',
        'question_ids',
        'xp_bonus',
        'trophies_earned',
        'finished_at',
    ];

    protected $casts = [
        'correct_count' => 'integer',
        'total_questions' => 'integer',
        'score_percentage' => 'integer',
        'xp_earned' => 'integer',
        'brainpower_spent' => 'integer',
        'attempt_type' => AttemptType::class,
        'question_ids' => 'array',
        'xp_bonus' => 'integer',
        'trophies_earned' => 'integer',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class);
    }

    /** Erfolge, die der Abschluss dieser Lerneinheit gebracht hat. */
    public function achievementUnlocks(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    #[Scope]
    protected function finished(Builder $query): void
    {
        $query->whereNotNull('finished_at');
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * Schließt die Lerneinheit ab und schreibt die EP gut. Ein zweiter Aufruf ändert nichts.
     * Die Sperre auf user_stats lässt Abschlüsse derselben Person nacheinander laufen, erst danach
     * wird die Tagesregel geprüft (E-015). Zuletzt, noch unter der Sperre, kommen die Erfolge (E-042).
     * Herausforderung (E-045): feste EP je richtiger Antwort ohne Tagesregel und Booster, dazu Bonus und bei 10 von 10 eine Trophäe.
     */
    public function finish(): void
    {
        UserStat::firstOrCreate(['user_id' => $this->user_id]);

        DB::transaction(function (): void {
            $stats = UserStat::where('user_id', $this->user_id)->lockForUpdate()->firstOrFail();
            $attempt = self::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($attempt->isFinished()) {
                $this->setRawAttributes($attempt->getAttributes(), true);

                return;
            }

            $challenge = $attempt->attempt_type === AttemptType::Challenge;
            $answers = $attempt->answers()->with('question:id,xp_reward')->get();
            $earned = $challenge ? [] : DailyXp::alreadyEarned($this->user_id, $answers->pluck('question_id')->all());
            $today = DailyXp::today();
            $xp = 0;

            foreach ($answers as $answer) {
                $answer->xp_awarded = match (true) {
                    ! $answer->is_correct || in_array($answer->question_id, $earned, true) => 0,
                    $challenge => config('gamification.challenge.xp_per_correct'),
                    default => Booster::apply($answer->question->xp_reward, Booster::multiplierFor($answer, $today)),
                };
                $answer->xp_date = $today;
                $answer->save();
                $xp += $answer->xp_awarded;
            }

            $correct = $answers->where('is_correct', true)->count();
            // Der Bonus zählt zu den Gesamt-EP, nicht zum Fach-Level: Er hängt an keiner Antwort
            $bonus = $challenge ? Challenge::bonus($correct) : 0;
            $trophies = $challenge && $correct === $attempt->total_questions ? config('gamification.challenge.perfect_trophies') : 0;

            $stats->increment('total_xp', $xp + $bonus);
            if ($trophies > 0) {
                $stats->increment('trophies', $trophies);
            }
            $stats->recordStudyDay($today);
            $stats->save();
            $attempt->update([
                'correct_count' => $correct,
                'score_percentage' => $attempt->total_questions > 0 ? (int) round($correct / $attempt->total_questions * 100) : 0,
                'xp_earned' => $xp + $bonus,
                'xp_bonus' => $bonus,
                'trophies_earned' => $trophies,
                'finished_at' => now(),
            ]);
            Achievements::check($attempt->user, $attempt);
            $this->setRawAttributes($attempt->getAttributes(), true);
        });
    }
}
