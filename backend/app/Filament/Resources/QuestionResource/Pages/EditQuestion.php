<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\Concerns\DemotesPublishedContent;
use App\Filament\Resources\QuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    use DemotesPublishedContent;

    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
