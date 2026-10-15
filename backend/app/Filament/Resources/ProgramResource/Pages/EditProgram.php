<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Kein Löschen: Klassen hängen am Ausbildungsgang (RESTRICT). Nicht mehr angebotene Gänge deaktivieren.
 */
class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;
}
