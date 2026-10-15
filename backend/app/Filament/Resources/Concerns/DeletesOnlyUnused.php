<?php

namespace App\Filament\Resources\Concerns;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

/**
 * Löschen für Inhalte mit Lernverlauf. Das Model verweigert es (BlocksDeletionWhileInUse),
 * die Meldung sagt, was die Redaktion stattdessen tun kann. Die Ressource setzt IN_USE_HINT.
 */
trait DeletesOnlyUnused
{
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->failureNotificationTitle('Nicht gelöscht')
            ->failureNotificationBody(static::IN_USE_HINT);
    }

    public static function deleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->missingBulkProcessingFailureNotificationMessage(
                fn (int $failureCount): string => "{$failureCount} nicht gelöscht. ".static::IN_USE_HINT,
            );
    }
}
