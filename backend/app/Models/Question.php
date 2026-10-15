<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, HasTranslationsWithoutEmpty, HasUuids;

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
        'order_index' => 'integer',
        'type' => QuestionType::class,
        'difficulty' => Difficulty::class,
        'status' => ContentStatus::class,
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    /** Nur veröffentlicht (E-014): Das sieht die App. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }
}
