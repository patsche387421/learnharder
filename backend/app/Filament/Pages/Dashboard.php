<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Startseite des Panels. Filament nennt sie auch auf Deutsch „Dashboard“.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Übersicht';

    protected static ?string $navigationLabel = 'Übersicht';
}
