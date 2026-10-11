<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Question extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'topic_id',
        'type',
        'question_text',
        'explanation',
        'xp_reward',
        'difficulty',
        'status',
        'order_index',
    ];

    public array $translatable = ['question_text', 'explanation'];

    protected $casts = [
        'xp_reward' => 'integer',
        'difficulty' => 'integer',
        'order_index' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }
}
