<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Concerns\BlocksDeletionWhileInUse;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Random\Engine\Mt19937;
use Random\Randomizer;

class Question extends Model
{
    use BlocksDeletionWhileInUse, HasFactory, HasTranslationsWithoutEmpty, HasUuids;

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

    /**
     * Antworten gemischt, mit festem Startwert aus $seed und der Frage: in jeder Lerneinheit anders, beim Fortsetzen gleich.
     * Collection::shuffle() nimmt seit Laravel 13 keinen Startwert mehr, deshalb eine eigene Zufallsquelle.
     *
     * @return Collection<int, QuestionOption>
     */
    public function shuffledOptions(string $seed): Collection
    {
        return new Collection((new Randomizer(new Mt19937(crc32($seed.$this->id))))->shuffleArray($this->options->all()));
    }

    /** Schon beantwortet: Die Antworten gehören zum Lernverlauf. */
    public function isInUse(): bool
    {
        return QuestionAnswer::where('question_id', $this->id)->exists();
    }

    /** Nur veröffentlicht (E-014): Das sieht die App. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }
}
