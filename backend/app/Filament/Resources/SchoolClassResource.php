<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages\CreateSchoolClass;
use App\Filament\Resources\SchoolClassResource\Pages\EditSchoolClass;
use App\Filament\Resources\SchoolClassResource\Pages\ListSchoolClasses;
use App\Filament\Resources\SchoolClassResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\SchoolClassResource\RelationManagers\TeachingAssignmentsRelationManager;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Klassen je Schuljahr mit Schüler:innen und Lehrkräften. Nur für Admins (SchoolStructurePolicy).
 */
class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Schule';

    protected static ?string $modelLabel = 'Klasse';

    protected static ?string $pluralModelLabel = 'Klassen';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('program_id')
                    ->label('Ausbildungsgang')
                    ->relationship('program', 'code')
                    ->getOptionLabelFromRecordUsing(fn (Program $record): string => "{$record->code} – {$record->name} ({$record->department->school->name})")
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('school_year_id', null)),
                Select::make('school_year_id')
                    ->label('Schuljahr')
                    ->helperText('Nur Schuljahre der Schule, zu der der Ausbildungsgang gehört.')
                    ->relationship('schoolYear', 'name', modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('school_id', self::schoolIdOf($get('program_id'))))
                    // Die Datenbank prüft nicht, ob Ausbildungsgang und Schuljahr zur selben Schule gehören.
                    // Filament lehnt beim Speichern jedes Schuljahr ab, das nicht in dieser gefilterten Auswahl steht.
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->placeholder('2AHIT')
                    ->required()
                    ->maxLength(20)
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('school_year_id', $get('school_year_id'))),
                TextInput::make('stage')
                    ->label('Abschnitt')
                    ->helperText('Jahrgang oder Semester zu Beginn des Schuljahres.')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(fn (Get $get): int => Program::find($get('program_id'))?->duration ?? 10),
                Select::make('head_teacher_id')
                    ->label('Klassenvorstand')
                    ->relationship('headTeacher', 'name', modifyQueryUsing: fn (Builder $query) => $query->role('teacher'))
                    ->searchable()
                    ->preload()
                    // Dass hier eine Lehrkraft steht, prüft die App, nicht die Datenbank (Plan Schule §4)
                    ->scopedExists(User::class, 'id', fn (Builder $query) => $query->role('teacher')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('program.code')
                    ->label('Ausbildungsgang'),
                TextColumn::make('schoolYear.name')
                    ->label('Schuljahr'),
                TextColumn::make('stage')
                    ->label('Abschnitt'),
                TextColumn::make('enrollments_count')
                    ->label('Schüler:innen')
                    ->counts(['enrollments' => fn (Builder $enrollments) => $enrollments->whereNull('left_at')]),
            ])
            ->filters([
                SelectFilter::make('schoolYear')
                    ->label('Schuljahr')
                    ->relationship('schoolYear', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
            TeachingAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolClasses::route('/'),
            'create' => CreateSchoolClass::route('/create'),
            'edit' => EditSchoolClass::route('/{record}/edit'),
        ];
    }

    private static function schoolIdOf(?string $programId): ?string
    {
        return $programId ? Program::find($programId)?->department->school_id : null;
    }
}
