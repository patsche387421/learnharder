<?php

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolClass extends EditRecord
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Einschreibungen und Zuordnungen hängen an der Klasse (RESTRICT)
            DeleteAction::make()
                ->hidden(fn (SchoolClass $record): bool => $record->enrollments()->exists() || $record->teachingAssignments()->exists()),
        ];
    }
}
