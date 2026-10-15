<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages\CreateSchool;
use App\Filament\Resources\SchoolResource\Pages\EditSchool;
use App\Filament\Resources\SchoolResource\Pages\ListSchools;
use App\Filament\Resources\SchoolResource\RelationManagers\DepartmentsRelationManager;
use App\Filament\Resources\SchoolResource\RelationManagers\EmailDomainsRelationManager;
use App\Filament\Resources\SchoolResource\RelationManagers\SchoolYearsRelationManager;
use App\Models\School;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Schulen mit Mail-Domains, Abteilungen und Schuljahren. Nur für Admins (SchoolStructurePolicy).
 */
class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|\UnitEnum|null $navigationGroup = 'Schule';

    protected static ?string $modelLabel = 'Schule';

    protected static ?string $pluralModelLabel = 'Schulen';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('city')
                    ->label('Ort')
                    ->maxLength(255),
                TextInput::make('country')
                    ->label('Land')
                    ->required()
                    ->length(2)
                    ->default('AT'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Ort'),
                TextColumn::make('departments_count')
                    ->label('Abteilungen')
                    ->counts('departments'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EmailDomainsRelationManager::class,
            DepartmentsRelationManager::class,
            SchoolYearsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'edit' => EditSchool::route('/{record}/edit'),
        ];
    }
}
