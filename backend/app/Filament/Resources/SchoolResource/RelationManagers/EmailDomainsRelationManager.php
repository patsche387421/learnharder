<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use App\Models\SchoolEmailDomain;
use Closure;
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

class EmailDomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailDomains';

    protected static ?string $title = 'Mail-Domains';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->regex('/^\s*[a-z0-9-]+(\.[a-z0-9-]+)+\s*$/i')
                    ->helperText('Ohne @, z. B. htl.moedling.at')
                    // Die Datenbank vergleicht ohne Groß-/Kleinschreibung, die Prüfung hier auch
                    ->rule(fn (?SchoolEmailDomain $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $exists = SchoolEmailDomain::query()
                            ->whereRaw('lower(domain) = ?', [mb_strtolower(trim((string) $value))])
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($exists) {
                            $fail('Diese Domain ist schon eingetragen.');
                        }
                    }),
                Toggle::make('allows_registration')
                    ->label('Registrierung erlaubt')
                    ->helperText('Nur Schüler:innen-Domains. Lehrkräfte legt der Admin an.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Mail-Domain')
            ->pluralModelLabel('Mail-Domains')
            ->columns([
                TextColumn::make('domain'),
                IconColumn::make('allows_registration')
                    ->label('Registrierung')
                    ->boolean(),
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
