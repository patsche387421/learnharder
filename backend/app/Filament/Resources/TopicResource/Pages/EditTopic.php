<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\Concerns\DemotesPublishedContent;
use App\Filament\Resources\TopicResource;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditTopic extends EditRecord
{
    use DemotesPublishedContent;

    /** Die Theorie (Relation Manager) hat den Status geändert */
    public const STATUS_CHANGED = 'topic-status-changed';

    protected static string $resource = TopicResource::class;

    #[On(self::STATUS_CHANGED)]
    public function refreshStatus(): void
    {
        $this->refreshFormData(['status']);
    }

    protected function getHeaderActions(): array
    {
        return [
            TopicResource::deleteAction(),
        ];
    }
}
