<?php

namespace App\Models;

use App\Support\Achievements;
use App\Support\Booster;
use App\Support\DailyXp;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Eine Lerneinheit zu einem Thema. Die ID ist der Idempotency-Key für den Abschluss.
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
        'finished_at',
    ];

    protected $casts = [
        'correct_count' => 'integer',
        'total_questions' => 'integer',
        'score_percentage' => 'integer',
        'xp_earned' => 'integer',
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

            $answers = $attempt->answers()->with('question:id,xp_reward')->get();
            $earned = DailyXp::alreadyEarned($this->user_id, $answers->pluck('question_id')->all());
            $today = DailyXp::today();
            $xp = 0;

            foreach ($answers as $answer) {
                $answer->xp_awarded = $answer->is_correct && ! in_array($answer->question_id, $earned, true)
                    ? Booster::apply($answer->question->xp_reward, Booster::multiplierFor($answer, $today))
                    : 0;
                $answer->xp_date = $today;
                $answer->save();
                $xp += $answer->xp_awarded;
            }

            $correct = $answers->where('is_correct', true)->count();

            $stats->increment('total_xp', $xp);
            $stats->recordStudyDay($today);
            $stats->save();
            $attempt->update([
                'correct_count' => $correct,
                'score_percentage' => $attempt->total_questions > 0 ? (int) round($correct / $attempt->total_questions * 100) : 0,
                'xp_earned' => $xp,
                'finished_at' => now(),
            ]);
            Achievements::check($attempt->user, $attempt);
            $this->setRawAttributes($attempt->getAttributes(), true);
        });
    }
}
