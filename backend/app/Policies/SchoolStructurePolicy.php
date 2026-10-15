<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Rechte für die Schulstruktur (Schulen, Abteilungen, Ausbildungsgänge, Schuljahre, Lehrplan).
 * Nur der Admin pflegt sie, er darf alles über Gate::before im AppServiceProvider.
 *
 * Filament erlaubt eine Aktion, wenn die Policy-Methode fehlt. Deshalb steht hier jede
 * Aktion, die Filament prüft, auch wenn sie nur false liefert.
 */
abstract class SchoolStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Model $record): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function replicate(User $user, Model $record): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }

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
