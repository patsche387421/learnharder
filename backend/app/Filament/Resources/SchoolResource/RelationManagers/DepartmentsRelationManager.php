<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use App\Models\Department;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departments';

    protected static ?string $title = 'Abteilungen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kürzel')
                    ->required()
                    ->maxLength(10)
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn (Builder $query) => $query->where('school_id', $this->getOwnerRecord()->getKey())),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('color_token')
                    ->label('Farb-Token')
                    ->helperText('Token aus frontend/src/styles/tokens.css, z. B. --subject-pos')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Abteilung')
            ->pluralModelLabel('Abteilungen')
            ->columns([
                TextColumn::make('code')
                    ->label('Kürzel'),
                TextColumn::make('name'),
                TextColumn::make('programs_count')
                    ->label('Ausbildungsgänge')
                    ->counts('programs'),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // Ausbildungsgänge sperren das Löschen (RESTRICT), stattdessen deaktivieren
                DeleteAction::make()
                    ->hidden(fn (Department $record): bool => $record->programs()->exists()),
            ]);
    }
}
