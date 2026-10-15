<?php

namespace App\Models\Concerns;

/**
 * Inhalte, an denen Lernverlauf hängt, lassen sich nicht löschen: delete() liefert dann false.
 * Die Datenbank sperrt das zusätzlich per RESTRICT. So zeigt das CMS einen Hinweis statt eines Serverfehlers.
 */
trait BlocksDeletionWhileInUse
{
    abstract public function isInUse(): bool;

    protected static function bootBlocksDeletionWhileInUse(): void
    {
        static::deleting(fn (self $model): bool => ! $model->isInUse());
    }
}
