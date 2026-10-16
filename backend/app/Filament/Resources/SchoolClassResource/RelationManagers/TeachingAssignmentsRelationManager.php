<?php

namespace App\Filament\Resources\SchoolClassResource\RelationManagers;

use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Wer welches Fach in der Klasse unterrichtet. Mehrere Lehrkräfte pro Fach sind erlaubt.
 */
class TeachingAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teachingAssignments';

    protected static ?string $title = 'Lehrkräfte';

    private const SEMESTERS = [1 => '1. Semester', 2 => '2. Semester'];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Fach')
                    ->relationship('subject', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Subject $record): string => $record->getTranslation('name', 'de'))
                    ->required(),
                Select::make('teacher_id')
                    ->label('Lehrkraft')
                    ->relationship('teacher', 'name', modifyQueryUsing: fn (Builder $query) => $query->role('teacher'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->scopedExists(User::class, 'id', fn (Builder $query) => $query->role('teacher'))
                    // Wie teaching_assignments_unique (NULLS NOT DISTINCT): leeres Semester zählt als gleicher Wert
                    ->scopedUnique(TeachingAssignment::class, 'teacher_id', ignoreRecord: true, modifyQueryUsing: fn (Builder $query, Get $get) => $query
                        ->where('school_class_id', $this->getOwnerRecord()->getKey())
                        ->where('subject_id', $get('subject_id'))
                        ->when($get('semester'), fn (Builder $query, $semester) => $query->where('semester', $semester), fn (Builder $query) => $query->whereNull('semester')))
                    ->validationMessages([
                        'exists' => 'Zuordnen kann man nur Lehrkräfte.',
                        'unique' => 'Ist für dieses Fach und diesen Zeitraum schon eingetragen.',
                    ]),
                Select::make('semester')
                    ->options(self::SEMESTERS)
                    ->placeholder('Ganzes Schuljahr'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Zuordnung')
            ->pluralModelLabel('Zuordnungen')
            ->columns([
                TextColumn::make('subject_name')
                    ->label('Fach')
                    ->state(fn (TeachingAssignment $record): string => $record->subject->getTranslation('name', 'de')),
                TextColumn::make('teacher.name')
                    ->label('Lehrkraft'),
                TextColumn::make('semester')
                    ->formatStateUsing(fn (?int $state): string => self::SEMESTERS[$state] ?? 'Ganzes Schuljahr')
                    ->placeholder('Ganzes Schuljahr'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
