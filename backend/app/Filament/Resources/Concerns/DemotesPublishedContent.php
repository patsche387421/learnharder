<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\ContentStatus;

/**
 * Für Bearbeiten-Seiten: Wer nicht veröffentlichen darf, schickt einen veröffentlichten Inhalt
 * beim Speichern zurück in Prüfung. Ein Admin gibt ihn danach wieder frei.
 */
trait DemotesPublishedContent
{
    private bool $wasDemoted = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! static::getResource()::canPublish() && $this->getRecord()->status === ContentStatus::Published) {
            $data['status'] = ContentStatus::Review;
            $this->wasDemoted = true;
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->wasDemoted ? 'Gespeichert. Der Status ist jetzt „In Prüfung“.' : parent::getSavedNotificationTitle();
    }
}
