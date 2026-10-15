<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\Concerns\DemotesPublishedContent;
use App\Filament\Resources\TopicResource;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    use DemotesPublishedContent;

    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TopicResource::deleteAction(),
        ];
    }
}
