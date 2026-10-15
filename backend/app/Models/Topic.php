<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\BlocksDeletionWhileInUse;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use BlocksDeletionWhileInUse, HasFactory, HasTranslationsWithoutEmpty, HasUuids;

    protected $fillable = [
        'subject_id',
        'slug',
        'title',
        'description',
        'level_required',
        'order_index',
        'status',
    ];

    public array $translatable = ['title', 'description'];

    protected $casts = [
        'level_required' => 'integer',
        'order_index' => 'integer',
        'status' => ContentStatus::class,
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function theorySections(): HasMany
    {
        return $this->hasMany(TheorySection::class)->orderBy('order_index');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** Lerneinheiten zum Thema oder Antworten auf seine Fragen (eine Frage kann das Thema gewechselt haben). */
    public function isInUse(): bool
    {
        return $this->quizAttempts()->exists()
            || QuestionAnswer::whereIn('question_id', Question::where('topic_id', $this->id)->select('id'))->exists();
    }

    /** Nur veröffentlicht (E-014): Das sieht die App. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }
}
