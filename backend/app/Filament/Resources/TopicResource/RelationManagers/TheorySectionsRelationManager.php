<?php

namespace App\Filament\Resources\TopicResource\RelationManagers;

use App\Enums\ContentStatus;
use App\Filament\Resources\TopicResource;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Models\TheorySection;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TheorySectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'theorySections';

    protected static ?string $title = 'Theorie';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                TextInput::make('title.de')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),
                TextInput::make('order_index')
                    ->label('Reihenfolge')
                    ->helperText('Position im Thema. Kleinere Zahlen kommen zuerst.')
                    ->required()
                    ->integer()
                    ->default(fn (): int => (int) $this->getOwnerRecord()->theorySections()->max('order_index') + 1),
                MarkdownEditor::make('content.de')
                    ->label('Text')
                    ->helperText('Kurz halten, ein Gedanke pro Absatz. Markdown für **fett**, Listen und `Code`.')
                    ->required()
                    ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList', 'codeBlock'], ['undo', 'redo']])
                    // Keine Uploads: Bilder in der Theorie brauchen erst Speicherort und Prüfung
                    ->fileAttachments(false)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Abschnitt')
            ->pluralModelLabel('Abschnitte')
            ->defaultSort('order_index')
            ->columns([
                TextColumn::make('order_index')
                    ->label('Reihenfolge')
                    ->numeric(),
                TextColumn::make('title')
                    ->label('Titel')
                    ->state(fn (TheorySection $record): string => $record->getTranslation('title', 'de')),
                TextColumn::make('content')
                    ->label('Text')
                    ->state(fn (TheorySection $record): string => $record->getTranslation('content', 'de'))
                    ->limit(80)
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn () => $this->sendTopicBackToReview()),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => $this->sendTopicBackToReview()),
                DeleteAction::make(),
            ]);
    }

    /**
     * Theorie gehört zum Thema: Ändert sie jemand ohne Recht zum Veröffentlichen, geht das Thema zurück in Prüfung (E-014).
     */
    private function sendTopicBackToReview(): void
    {
        $topic = $this->getOwnerRecord();

        if (TopicResource::canPublish() || $topic->status !== ContentStatus::Published) {
            return;
        }

        $topic->update(['status' => ContentStatus::Review]);
        // Das Formular des Themas daneben zeigt sonst bis zum Neuladen noch „Veröffentlicht“ (EditTopic)
        $this->dispatch(EditTopic::STATUS_CHANGED);

        Notification::make()
            ->title('Das Thema ist jetzt „In Prüfung“.')
            ->body('Veröffentlichen dürfen nur Admins.')
            ->info()
            ->send();
    }
}
