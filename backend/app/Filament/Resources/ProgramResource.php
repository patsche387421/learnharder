<?php

namespace App\Filament\Resources;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use App\Filament\Resources\ProgramResource\Pages\CreateProgram;
use App\Filament\Resources\ProgramResource\Pages\EditProgram;
use App\Filament\Resources\ProgramResource\Pages\ListPrograms;
use App\Filament\Resources\ProgramResource\RelationManagers\CurriculumEntriesRelationManager;
use App\Models\Department;
use App\Models\Program;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ausbildungsgänge mit Lehrplan. Nur für Admins (SchoolStructurePolicy).
 */
class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Schule';

    protected static ?string $modelLabel = 'Ausbildungsgang';

    protected static ?string $pluralModelLabel = 'Ausbildungsgänge';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->label('Abteilung')
                    ->relationship('department', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Department $record): string => "{$record->code} – {$record->name} ({$record->school->name})")
                    ->required()
                    ->live(),
                TextInput::make('code')
                    ->label('Kürzel')
                    ->required()
                    ->maxLength(10)
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('department_id', $get('department_id'))),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('form')
                    ->label('Ausbildungsform')
                    ->options(ProgramForm::class)
                    ->required(),
                Select::make('unit')
                    ->label('Zählt in')
                    ->options(ProgramUnit::class)
                    ->required(),
                TextInput::make('duration')
                    ->label('Dauer')
                    ->helperText('Anzahl Jahrgänge oder Semester')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(10),
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.code')
                    ->label('Abteilung')
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kürzel')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('form')
                    ->label('Ausbildungsform')
                    ->badge(),
                TextColumn::make('duration')
                    ->label('Dauer')
                    ->formatStateUsing(fn (Program $record): string => "{$record->duration} {$record->unit->getLabel()}"),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('form')
                    ->label('Ausbildungsform')
                    ->options(ProgramForm::class),
                SelectFilter::make('department')
                    ->label('Abteilung')
                    ->relationship('department', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CurriculumEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }
}
