<?php

namespace App\Models;

use App\Models\Concerns\BlocksDeletionWhileInUse;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    use BlocksDeletionWhileInUse, HasFactory, HasTranslationsWithoutEmpty, HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon_name',
        'color_token',
        'order_index',
        'is_active',
    ];

    public array $translatable = ['name', 'description'];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('order_index');
    }

    /** Lernverlauf in einem seiner Themen, oder das Fach steht im Lehrplan, in einer Lehrkraft-Zuordnung oder in einem Erfolg. */
    public function isInUse(): bool
    {
        $topics = Topic::where('subject_id', $this->id)->select('id');

        return QuizAttempt::whereIn('topic_id', $topics)->exists()
            || QuestionAnswer::whereIn('question_id', Question::whereIn('topic_id', $topics)->select('id'))->exists()
            || CurriculumEntry::where('subject_id', $this->id)->exists()
            || DB::table('teaching_assignments')->where('subject_id', $this->id)->exists()
            || Achievement::where('subject_id', $this->id)->exists();
    }

    /** Aktive Fächer mit mindestens einem veröffentlichten Thema, noch ohne Blick auf die Person. */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('topics', fn (Builder $topics) => $topics->published());
    }

    /**
     * Was die App dieser Person zeigt (E-024): Schüler:innen nur den Lehrplan ihrer Klasse bis zum aktuellen
     * Abschnitt, ohne aktive Klasse nichts. Wer Inhalte pflegt, prüft oder unterrichtet, sieht alles.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->visible();

        if ($user->seesAllContent()) {
            return;
        }

        $class = $user->activeEnrollment?->schoolClass;

        if (! $class) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', CurriculumEntry::where('program_id', $class->program_id)
            ->where('stage_from', '<=', $class->currentStage())
            ->select('subject_id'));
    }
}
