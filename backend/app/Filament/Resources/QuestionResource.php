<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Filament\Resources\Concerns\DeletesOnlyUnused;
use App\Filament\Resources\Concerns\HasContentStatus;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionResource extends Resource
{
    use DeletesOnlyUnused, HasContentStatus;

    public const IN_USE_HINT = 'Beantwortete Fragen bleiben für den Lernverlauf erhalten. Setz sie auf Entwurf, dann verschwinden sie aus der App.';

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
                // Nur Hilfe zur Auswahl, gespeichert wird das Thema
                Select::make('subject_id')
                    ->label('Fach')
                    ->options(fn (): array => Subject::orderBy('order_index')->get()
                        ->mapWithKeys(fn (Subject $subject): array => [$subject->id => $subject->getTranslation('name', 'de')])
                        ->all())
                    ->afterStateHydrated(fn (Select $component, ?Question $record) => $component->state($record?->topic?->subject_id))
                    ->afterStateUpdated(fn (Set $set) => $set('topic_id', null))
                    ->dehydrated(false)
                    ->live()
                    ->required(),
                Select::make('topic_id')
                    ->label('Thema')
                    // Postgres kann nicht nach JSON sortieren, Titel zeigt das CMS auf Deutsch (E-012)
                    ->relationship('topic', 'slug', fn (Builder $query, Get $get) => $query
                        ->where('subject_id', $get('subject_id'))
                        ->orderBy('order_index'))
                    ->getOptionLabelFromRecordUsing(fn (Topic $record): string => $record->getTranslation('title', 'de'))
                    ->disabled(fn (Get $get): bool => blank($get('subject_id')))
                    ->placeholder(fn (Get $get): string => blank($get('subject_id')) ? 'Zuerst ein Fach wählen' : 'Thema wählen')
                    ->required(),
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                Textarea::make('question_text.de')
                    ->label('Frage')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('explanation.de')
                    ->label('Erklärung')
                    ->helperText('Erscheint nach dem Antworten und begründet die richtige Lösung.')
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Typ')
                    ->options(QuestionType::class)
                    ->default(QuestionType::SingleChoice)
                    ->live()
                    ->required(),
                Select::make('difficulty')
                    ->label('Schwierigkeit')
                    ->options(Difficulty::class)
                    ->default(Difficulty::VeryEasy)
                    ->required(),
                Repeater::make('options')
                    ->label('Antworten')
                    ->helperText(fn (Get $get): string => self::isSingleChoice($get('type'))
                        ? '2 bis 6 Antworten. Genau eine ist richtig.'
                        : '2 bis 6 Antworten. Mindestens eine ist richtig.')
                    ->relationship()
                    ->orderColumn('order_index')
                    ->table([
                        TableColumn::make('Antwort')
                            ->markAsRequired(),
                        TableColumn::make('Richtig')
                            ->width('6rem')
                            ->alignment(Alignment::Center),
                    ])
                    ->schema([
                        TextInput::make('option_text.de')
                            ->label('Antwort')
                            ->required(),
                        Toggle::make('is_correct')
                            ->label('Richtig')
                            // Bei einer richtigen Antwort schaltet das Einschalten die anderen aus
                            ->fixIndistinctState(fn (Get $get): bool => self::isSingleChoice($get('../../type'))),
                    ])
                    ->defaultItems(4)
                    ->minItems(2)
                    ->maxItems(6)
                    ->addActionLabel('Antwort hinzufügen')
                    ->rules([fn (Get $get): Closure => self::correctAnswersRule($get('type'))])
                    ->columnSpanFull(),
                TextInput::make('xp_reward')
                    ->label('EP')
                    ->helperText('Erfahrungspunkte für eine richtige Antwort.')
                    ->required()
                    ->numeric()
                    ->default(2),
                TextInput::make('order_index')
                    ->label('Reihenfolge')
                    ->helperText('Position im Thema. Kleinere Zahlen kommen zuerst.')
                    ->required()
                    ->numeric()
                    ->default(0),
                self::statusSelect('die Frage', 'Fragen'),
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
                TextColumn::make('options_count')
                    ->label('Antworten')
                    ->counts('options'),
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
                    self::publishBulkAction(),
                    self::deleteBulkAction(),
                ]),
            ]);
    }

    /**
     * Eine richtige Antwort bei single_choice, mindestens eine bei multiple_choice.
     */
    private static function correctAnswersRule(QuestionType|string|null $type): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type): void {
            $type = self::questionType($type);
            $correct = count(array_filter(array_column((array) $value, 'is_correct')));

            if ($type === QuestionType::SingleChoice && $correct !== 1) {
                $fail('Markiere genau eine richtige Antwort.');
            }

            if ($type === QuestionType::MultipleChoice && $correct < 1) {
                $fail('Markiere mindestens eine richtige Antwort.');
            }
        };
    }

    private static function isSingleChoice(QuestionType|string|null $type): bool
    {
        return self::questionType($type) === QuestionType::SingleChoice;
    }

    /**
     * Der Formular-State liefert den Typ je nach Zeitpunkt als Enum oder als String.
     */
    private static function questionType(QuestionType|string|null $type): ?QuestionType
    {
        return $type instanceof QuestionType ? $type : QuestionType::tryFrom((string) $type);
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
