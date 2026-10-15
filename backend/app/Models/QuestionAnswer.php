<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Erste Antwort pro Frage in einer Lerneinheit. xp_awarded und xp_date setzt erst der Abschluss.
 */
class QuestionAnswer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'user_id',
        'question_id',
        'selected_option_ids',
        'is_correct',
        'xp_awarded',
        'xp_date',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
        'xp_awarded' => 'integer',
        'xp_date' => 'date:Y-m-d',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Fach-EP einer Person (E-018): vergebene EP je Fach, auch aus Themen, die nicht mehr veröffentlicht sind.
     *
     * @return Collection<string, int> subject_id => EP
     */
    public static function xpBySubject(int $userId): Collection
    {
        return self::query()
            ->join('questions', 'questions.id', '=', 'question_answers.question_id')
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            ->where('question_answers.user_id', $userId)
            ->groupBy('topics.subject_id')
            ->selectRaw('topics.subject_id, sum(question_answers.xp_awarded) as xp')
            ->pluck('xp', 'subject_id')
            ->map(fn ($xp) => (int) $xp);
    }
}
