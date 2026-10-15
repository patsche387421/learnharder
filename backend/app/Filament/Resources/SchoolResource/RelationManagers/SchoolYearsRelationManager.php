<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolYearsRelationManager extends RelationManager
{
    protected static string $relationship = 'schoolYears';

    protected static ?string $title = 'Schuljahre';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->regex('/^\d{4}\/\d{2}$/')
                    ->placeholder('2026/27')
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn (Builder $query) => $query->where('school_id', $this->getOwnerRecord()->getKey())),
                DatePicker::make('starts_on')
                    ->label('Beginn')
                    ->required(),
                DatePicker::make('second_term_starts_on')
                    ->label('Beginn 2. Semester')
                    ->required()
                    ->after('starts_on'),
                DatePicker::make('ends_on')
                    ->label('Ende')
                    ->required()
                    ->after('second_term_starts_on'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Schuljahr')
            ->pluralModelLabel('Schuljahre')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('starts_on')
                    ->label('Beginn')
                    ->date('d.m.Y'),
                TextColumn::make('second_term_starts_on')
                    ->label('2. Semester')
                    ->date('d.m.Y'),
                TextColumn::make('ends_on')
                    ->label('Ende')
                    ->date('d.m.Y'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            // Kein Löschen: Klassen hängen am Schuljahr (RESTRICT)
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
