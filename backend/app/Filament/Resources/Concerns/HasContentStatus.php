<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\ContentStatus;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Status-Feld und Veröffentlichen für Ressourcen mit ContentStatus (Themen, Fragen).
 * Veröffentlichen dürfen nur Admins (ContentPolicy::publish, Gate::before).
 */
trait HasContentStatus
{
    public static function canPublish(): bool
    {
        return auth()->user()?->can('publish', static::getModel()) ?? false;
    }

    /**
     * @param  string  $singular  mit Artikel, z. B. „die Frage“
     * @param  string  $plural  z. B. „Fragen“
     */
    protected static function statusSelect(string $singular, string $plural): Select
    {
        return Select::make('status')
            ->label('Status')
            ->options(ContentStatus::class)
            ->default(ContentStatus::Draft)
            // Ohne Recht ist „Veröffentlicht“ nicht wählbar. Ist der Inhalt schon veröffentlicht, ist stattdessen
            // das ganze Feld gesperrt und er geht beim Speichern zurück in Prüfung (DemotesPublishedContent).
            ->disableOptionWhen(fn (string $value, ?Model $record): bool => $value === ContentStatus::Published->value
                && ! static::canPublish() && $record?->status !== ContentStatus::Published)
            ->disabled(fn (?Model $record): bool => ! static::canPublish() && $record?->status === ContentStatus::Published)
            ->helperText(fn (?Model $record): string => match (true) {
                static::canPublish() => "Nur veröffentlichte {$plural} erscheinen in der App.",
                $record?->status === ContentStatus::Published => "Beim Speichern geht {$singular} zurück in Prüfung. Veröffentlichen dürfen nur Admins.",
                default => "Nur veröffentlichte {$plural} erscheinen in der App. Veröffentlichen dürfen nur Admins.",
            })
            ->required();
    }

    protected static function publishBulkAction(): BulkAction
    {
        return BulkAction::make('publish')
            ->label('Veröffentlichen')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Die ausgewählten Einträge erscheinen danach in der App.')
            ->visible(fn (): bool => static::canPublish())
            ->action(fn (Collection $records) => $records->each->update(['status' => ContentStatus::Published]))
            ->deselectRecordsAfterCompletion();
    }
}
