<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Ein vergebener Erfolg: einmal je Person, mit Datum und den Trophäen, die es dafür gab. Erfolge werden nie entzogen (E-042).
 */
class UserAchievement extends Pivot
{
    protected $table = 'user_achievements';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'trophies_awarded',
        'quiz_attempt_id',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'trophies_awarded' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
