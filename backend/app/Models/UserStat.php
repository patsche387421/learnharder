<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'total_xp',
        'trophies',
        'current_streak',
        'longest_streak',
        'last_study_date',
    ];

    protected $casts = [
        'total_xp' => 'integer',
        'trophies' => 'integer',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_study_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
