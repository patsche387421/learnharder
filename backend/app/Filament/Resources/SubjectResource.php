<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages\CreateSubject;
use App\Filament\Resources\SubjectResource\Pages\EditSubject;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Models\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Fach';

    protected static ?string $pluralModelLabel = 'Fächer';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('URL-Kürzel')
                    ->helperText('Kurzer Name für die Adresse, z. B. pos. Nur Kleinbuchstaben, Ziffern und Bindestriche.')
                    ->required()
                    ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                    ->validationMessages(['regex' => 'Nur Kleinbuchstaben, Ziffern und Bindestriche, z. B. schleifen-2.'])
                    ->unique(ignoreRecord: true),
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                TextInput::make('name.de')
                    ->label('Name')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description.de')
                    ->label('Beschreibung')
                    ->columnSpanFull(),
                TextInput::make('icon_name')
                    ->label('Icon')
                    ->helperText('Name des Icons aus dem Design-System, z. B. code.')
                    ->required(),
                TextInput::make('order_index')
                    ->label('Reihenfolge')
                    ->helperText('Position in der Fächerliste. Kleinere Zahlen kommen zuerst.')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->helperText('Inaktive Fächer erscheinen nicht in der App.')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('URL-Kürzel')
                    ->searchable(),
                TextColumn::make('icon_name')
                    ->label('Icon')
                    ->searchable(),
                TextColumn::make('order_index')
                    ->label('Reihenfolge')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
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
                //
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
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }
}
