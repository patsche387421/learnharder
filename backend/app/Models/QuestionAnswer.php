<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
