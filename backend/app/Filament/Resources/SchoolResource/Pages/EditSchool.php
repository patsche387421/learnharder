<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use App\Models\School;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Abteilungen und Schuljahre sperren das Löschen (RESTRICT), deshalb gar nicht erst anbieten
            DeleteAction::make()
                ->hidden(fn (School $record): bool => $record->departments()->exists() || $record->schoolYears()->exists()),
        ];
    }
}
