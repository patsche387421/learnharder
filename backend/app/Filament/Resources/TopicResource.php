<?php

namespace App\Filament\Resources;

use App\Enums\ContentStatus;
use App\Filament\Resources\TopicResource\Pages\CreateTopic;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\Pages\ListTopics;
use App\Models\Subject;
use App\Models\Topic;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Thema';

    protected static ?string $pluralModelLabel = 'Themen';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Fach')
                    // Postgres kann nicht nach JSON sortieren, Namen zeigt das CMS auf Deutsch (E-012)
                    ->relationship('subject', 'slug', fn (Builder $query) => $query->orderBy('order_index'))
                    ->getOptionLabelFromRecordUsing(fn (Subject $record): string => $record->getTranslation('name', 'de'))
                    ->required(),
                TextInput::make('slug')
                    ->label('URL-Kürzel')
                    ->required(),
                // Nur Deutsch, direkt in den Eintrag der JSON-Spalte (E-012)
                TextInput::make('title.de')
                    ->label('Titel')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description.de')
                    ->label('Beschreibung')
                    ->columnSpanFull(),
                TextInput::make('level_required')
                    ->label('Ab Level')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('order_index')
                    ->label('Reihenfolge')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class)
                    ->default(ContentStatus::Draft)
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
                TextColumn::make('subject.name')
                    ->label('Fach')
                    ->state(fn (Topic $record): string => $record->subject->getTranslation('name', 'de'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('URL-Kürzel')
                    ->searchable(),
                TextColumn::make('level_required')
                    ->label('Ab Level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('order_index')
                    ->label('Reihenfolge')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class),
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
            'index' => ListTopics::route('/'),
            'create' => CreateTopic::route('/create'),
            'edit' => EditTopic::route('/{record}/edit'),
        ];
    }
}
