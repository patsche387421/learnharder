<?php

namespace App\Filament\Resources;

use App\Enums\AchievementRule;
use App\Filament\Resources\AchievementResource\Pages\CreateAchievement;
use App\Filament\Resources\AchievementResource\Pages\EditAchievement;
use App\Filament\Resources\AchievementResource\Pages\ListAchievements;
use App\Filament\Resources\Concerns\DeletesOnlyUnused;
use App\Models\Achievement;
use App\Models\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Katalog der Erfolge (E-040 bis E-044), nur für Admins (AchievementPolicy). Die Bedingung ist ein Typ mit Schwellwert,
 * die Logik dazu steht im Code (App\Support\AchievementValues). Vergebene Erfolge lassen sich nur deaktivieren.
 */
class AchievementResource extends Resource
{
    use DeletesOnlyUnused;

    public const IN_USE_HINT = 'Vergebene Erfolge bleiben erhalten. Schalte „Aktiv“ aus, dann werden sie nicht mehr vergeben.';

    /** Icons aus dem Design-System (Spiel-Icons), Design `Achievement` */
    public const ICONS = [
        'achievement' => 'Erfolg (Standard)',
        'streak' => 'Streak',
        'xp' => 'EP und Level',
        'challenge' => 'Herausforderung',
        'trophy' => 'Trophäe',
    ];

    protected static ?string $model = Achievement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Spiel';

    protected static ?string $modelLabel = 'Erfolg';

    protected static ?string $pluralModelLabel = 'Erfolge';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                TextInput::make('title.de')
                    ->label('Titel')
                    ->helperText('Kurz, steht auf der Kachel, z. B. „Eine Woche“.')
                    ->required()
                    ->maxLength(40),
                TextInput::make('slug')
                    ->label('Kürzel')
                    ->helperText('Fester Name für den Code, z. B. streak-60. Nur Kleinbuchstaben, Ziffern und Bindestriche.')
                    ->required()
                    ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                    ->validationMessages(['regex' => 'Nur Kleinbuchstaben, Ziffern und Bindestriche, z. B. streak-60.'])
                    ->unique(ignoreRecord: true),
                Textarea::make('description.de')
                    ->label('Bedingung')
                    ->helperText('So steht es auf der Kachel, solange der Erfolg fehlt, z. B. „7 Tage in Folge lernen“.')
                    ->required()
                    ->columnSpanFull(),
                Select::make('rule')
                    ->label('Typ')
                    ->options(AchievementRule::class)
                    ->default(AchievementRule::LessonsFinished)
                    ->live()
                    // Das Fach gibt es nur beim Fach-Level (achievements_subject_check)
                    ->afterStateUpdated(fn (Set $set, $state) => self::isSubjectLevel($state) ? null : $set('subject_id', null))
                    ->required(),
                TextInput::make('threshold')
                    ->label('Schwellwert')
                    ->helperText('Ab wie viel der Erfolg zählt, z. B. 7 beim Streak. Über 1 steht die Zahl auf der Kachel.')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->default(1),
                Select::make('subject_id')
                    ->label('Fach')
                    ->helperText('Leer: in irgendeinem Fach. Mit Fach zeigt die App dessen Icon.')
                    ->options(fn (): array => Subject::orderBy('order_index')->get()
                        ->mapWithKeys(fn (Subject $subject): array => [$subject->id => $subject->getTranslation('name', 'de')])
                        ->all())
                    ->placeholder('Irgendein Fach')
                    ->visible(fn (Get $get): bool => self::isSubjectLevel($get('rule')))
                    ->dehydratedWhenHidden(),
                Select::make('icon_name')
                    ->label('Icon')
                    ->options(self::ICONS)
                    ->default('achievement')
                    ->selectablePlaceholder(false)
                    ->required(),
                TextInput::make('trophy_reward')
                    ->label('Trophäen')
                    ->helperText('Einmal bei der Vergabe. Eine Änderung gilt nur für neue Vergaben.')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('sort')
                    ->label('Reihenfolge')
                    ->helperText('Position im Raster. Kleinere Zahlen kommen zuerst.')
                    ->required()
                    ->integer()
                    ->default(0),
                Toggle::make('is_secret')
                    ->label('Geheim')
                    ->helperText('Bis zum Erreichen zeigt die App nur „Geheimer Erfolg“, ohne Titel und Bedingung.'),
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->helperText('Inaktive Erfolge werden nicht mehr vergeben. Wer sie schon hat, behält sie.')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn ($query) => $query->withCount('unlocks'))
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->getStateUsing(fn (Achievement $record): string => $record->getTranslation('title', 'de')),
                TextColumn::make('rule')
                    ->label('Typ')
                    ->badge(),
                TextColumn::make('threshold')
                    ->label('Schwellwert')
                    ->numeric(),
                TextColumn::make('trophy_reward')
                    ->label('Trophäen')
                    ->numeric(),
                TextColumn::make('unlocks_count')
                    ->label('Vergeben an')
                    ->numeric(),
                IconColumn::make('is_secret')
                    ->label('Geheim')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make('sort')
                    ->label('Reihenfolge')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::deleteBulkAction(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAchievements::route('/'),
            'create' => CreateAchievement::route('/create'),
            'edit' => EditAchievement::route('/{record}/edit'),
        ];
    }

    private static function isSubjectLevel(mixed $rule): bool
    {
        return ($rule instanceof AchievementRule ? $rule : AchievementRule::tryFrom((string) $rule)) === AchievementRule::SubjectLevel;
    }
}
