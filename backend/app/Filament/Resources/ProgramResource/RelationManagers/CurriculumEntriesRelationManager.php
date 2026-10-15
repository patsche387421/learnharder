<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use App\Models\CurriculumEntry;
use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lehrplan: welches Fach in welchen Abschnitten des Ausbildungsgangs vorkommt.
 */
class CurriculumEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'curriculumEntries';

    protected static ?string $title = 'Lehrplan';

    public function form(Schema $schema): Schema
    {
        $duration = $this->getOwnerRecord()->duration;

        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Fach')
                    ->relationship('subject', 'slug')
                    // Fachnamen liegen als JSON vor, CMS zeigt Deutsch (E-012)
                    ->getOptionLabelFromRecordUsing(fn (Subject $record): string => $record->getTranslation('name', 'de'))
                    ->required()
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn (Builder $query) => $query->where('program_id', $this->getOwnerRecord()->getKey())),
                TextInput::make('stage_from')
                    ->label('Ab Abschnitt')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue($duration),
                TextInput::make('stage_to')
                    ->label('Bis Abschnitt')
                    ->required()
                    ->integer()
                    ->gte('stage_from')
                    ->maxValue($duration),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Lehrplan-Eintrag')
            ->pluralModelLabel('Lehrplan-Einträge')
            ->defaultSort('stage_from')
            ->columns([
                TextColumn::make('subject_name')
                    ->label('Fach')
                    ->state(fn (CurriculumEntry $record): string => $record->subject->getTranslation('name', 'de')),
                TextColumn::make('stage_from')
                    ->label('Ab'),
                TextColumn::make('stage_to')
                    ->label('Bis'),
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
