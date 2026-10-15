<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Rechte für Lerninhalte im CMS. Admin darf alles (Gate::before im AppServiceProvider).
 *
 * Filament erlaubt eine Aktion, wenn die Policy-Methode fehlt. Deshalb steht hier jede
 * Aktion, die Filament prüft, auch wenn sie nur false liefert.
 */
abstract class ContentPolicy
{
    /** Rollen, die Inhalte sehen dürfen */
    private const READERS = ['autor', 'tester'];

    /** Rollen, die Inhalte anlegen und bearbeiten dürfen */
    private const EDITORS = ['autor'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::READERS);
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasAnyRole(self::READERS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function update(User $user, Model $record): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function replicate(User $user, Model $record): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    public function reorder(User $user): bool
    {
        return $user->hasAnyRole(self::EDITORS);
    }

    // Veröffentlichen nur für Admin: erst dann sehen Schüler:innen den Inhalt. Ohne Datensatz für neue Einträge.

    public function publish(User $user, ?Model $record = null): bool
    {
        return false;
    }

    // Löschen nur für Admin: am Inhalt hängt der Lernverlauf der Schüler:innen.

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
