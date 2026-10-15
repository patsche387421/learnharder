<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;

/**
 * Lerneinheiten in der App. Es gibt keine Filament-Ressource dafür, deshalb nur die Aktionen der API.
 */
class QuizAttemptPolicy
{
    /** Starten nur, wenn das Level reicht (topics.level_required). */
    public function create(User $user, Topic $topic): bool
    {
        return $topic->level_required <= $user->progression()->level;
    }

    /** Antworten und abschließen nur in der eigenen Lerneinheit. */
    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }
}
