<?php

namespace App\Filament\Resources\SchoolClassResource\RelationManagers;

use App\Models\ClassEnrollment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Schüler:innen der Klasse. Austragen setzt left_at, gelöscht wird nicht (Plan Schule §5).
 * Klassenwechsel: hier austragen, in der neuen Klasse eintragen.
 */
class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Schüler:innen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Schüler:in')
                    ->relationship('user', 'gamer_tag', modifyQueryUsing: fn (Builder $query) => $this->candidates($query))
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->gamer_tag} ({$record->email})")
                    ->searchable(['gamer_tag', 'email'])
                    ->required()
                    ->scopedExists(User::class, 'id', fn (Builder $query) => $query->role('student'))
                    // Wie der Teil-Index class_enrollments_active_unique: eine aktive Klasse pro Schuljahr
                    ->scopedUnique(ClassEnrollment::class, 'user_id', ignoreRecord: false, modifyQueryUsing: fn (Builder $query) => $query
                        ->where('school_year_id', $this->getOwnerRecord()->school_year_id)
                        ->whereNull('left_at'))
                    ->validationMessages([
                        'exists' => 'Eintragen kann man nur Schüler:innen.',
                        'unique' => 'Ist in diesem Schuljahr schon in einer Klasse. Dort zuerst austragen.',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('Schüler:in')
            ->pluralModelLabel('Schüler:innen')
            ->defaultSort('enrolled_at', 'desc')
            ->columns([
                TextColumn::make('user.gamer_tag')
                    ->label('Gamer-Tag')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('E-Mail'),
                TextColumn::make('enrolled_at')
                    ->label('Eingetragen')
                    ->date('d.m.Y'),
                TextColumn::make('left_at')
                    ->label('Ausgetragen')
                    ->date('d.m.Y')
                    ->placeholder('–'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Eintragen'),
            ])
            ->recordActions([
                Action::make('leave')
                    ->label('Austragen')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ClassEnrollment $record): bool => $record->left_at === null)
                    ->action(fn (ClassEnrollment $record) => $record->update(['left_at' => now()])),
            ]);
    }

    /** Schüler:innen ohne aktive Klasse im Schuljahr dieser Klasse */
    private function candidates(Builder $query): Builder
    {
        return $query->role('student')
            ->whereDoesntHave('enrollments', fn (Builder $enrollments) => $enrollments
                ->where('school_year_id', $this->getOwnerRecord()->school_year_id)
                ->whereNull('left_at'));
    }
}
