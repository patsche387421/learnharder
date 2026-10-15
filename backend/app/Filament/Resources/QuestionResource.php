<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Frage';

    protected static ?string $pluralModelLabel = 'Fragen';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('topic_id')
                    ->label('Thema')
                    // Postgres kann nicht nach JSON sortieren: nach Fach, dann Thema, Titel auf Deutsch (E-012)
                    ->relationship('topic', 'slug', fn (Builder $query) => $query
                        ->with('subject')
                        ->orderBy(Subject::select('order_index')->whereColumn('subjects.id', 'topics.subject_id'))
                        ->orderBy('order_index'))
                    ->getOptionLabelFromRecordUsing(fn (Topic $record): string => $record->subject->getTranslation('name', 'de').' – '.$record->getTranslation('title', 'de'))
                    ->required(),
                Select::make('type')
                    ->label('Typ')
                    ->options(QuestionType::class)
                    ->default(QuestionType::SingleChoice)
                    ->required(),
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                Textarea::make('question_text.de')
                    ->label('Frage')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('explanation.de')
                    ->label('Erklärung')
                    ->columnSpanFull(),
                TextInput::make('xp_reward')
                    ->label('EP')
                    ->required()
                    ->numeric()
                    ->default(2),
                Select::make('difficulty')
                    ->label('Schwierigkeit')
                    ->options(Difficulty::class)
                    ->default(Difficulty::VeryEasy)
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class)
                    ->default(ContentStatus::Draft)
                    ->required(),
                TextInput::make('order_index')
                    ->label('Reihenfolge')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('topic.title')
                    ->label('Thema')
                    ->state(fn (Question $record): string => $record->topic->getTranslation('title', 'de'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Typ'),
                TextColumn::make('xp_reward')
                    ->label('EP')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('difficulty')
                    ->label('Schwierigkeit')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('order_index')
                    ->label('Reihenfolge')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Geändert')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class),
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(QuestionType::class),
                SelectFilter::make('difficulty')
                    ->label('Schwierigkeit')
                    ->options(Difficulty::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }
}
