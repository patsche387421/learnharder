<?php

namespace App\Filament\Resources;

use App\Exceptions\AccountRuleViolation;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Support\Accounts;
use App\Support\GamerTag;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Konten im CMS (E-025, E-027). Personal legt der Admin an, Schüler:innen unter 14 nur mit Elternformular.
 * Ein Passwort vergibt der Admin nie: Die Person setzt es über „Passwort vergessen“.
 * Sperren, Rollen und Löschen laufen über App\Support\Accounts, dort stehen die Schutzregeln.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Schule';

    protected static ?string $modelLabel = 'Konto';

    protected static ?string $pluralModelLabel = 'Konten';

    protected static ?int $navigationSort = 4;

    public const ROLE_LABELS = [
        'admin' => 'Admin',
        'autor' => 'Autor:in',
        'tester' => 'Tester:in',
        'teacher' => 'Lehrkraft',
        'student' => 'Schüler:in',
    ];

    public static function form(Schema $schema): Schema
    {
        $isStudent = fn (Get $get, ?User $record): bool => $record
            ? in_array('student', $get('roles') ?? [], true)
            : $get('kind') === 'student';

        return $schema
            ->columns(1)
            ->components([
                Radio::make('kind')
                    ->label('Art des Kontos')
                    ->options([
                        'staff' => 'Personal (Lehrkraft, Redaktion, Admin)',
                        'student' => 'Schüler:in unter 14',
                    ])
                    ->helperText('Schüler:innen ab 14 registrieren sich selbst.')
                    ->default('staff')
                    ->required()
                    ->live()
                    ->visibleOn('create'),
                Callout::make('Nur mit unterschriebenem Elternformular')
                    ->description('Lege das Konto erst an, wenn das unterschriebene Einwilligungsformular der Eltern vorliegt. Das Formular bewahrt die Schule auf, nicht die App.')
                    ->danger()
                    ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('kind') === 'student'),
                Text::make(fn (?User $record): string => self::consentText($record))
                    ->visible(fn (?User $record): bool => $record?->parental_consent_at !== null),
                Text::make(fn (?User $record): string => self::deactivationText($record))
                    ->color('danger')
                    ->visible(fn (?User $record): bool => $record?->isDeactivated() ?? false),
                TextInput::make('name')
                    ->label('Name')
                    ->helperText('Nur fürs Personal. Schüler:innen haben keinen Namen in der App.')
                    ->maxLength(255)
                    ->required(fn (Get $get, ?User $record): bool => ! $isStudent($get, $record))
                    ->visible(fn (Get $get, ?User $record): bool => ! $isStudent($get, $record)),
                TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rules(fn (?User $record): array => [self::uniqueEmail($record?->id)])
                    ->dehydrateStateUsing(fn (string $state): string => Str::lower(trim($state))),
                TextInput::make('gamer_tag')
                    ->label('Gamer-Tag')
                    ->helperText('3 bis 20 Zeichen: Buchstaben, Ziffern, _ und -. Der einzige Name, den andere sehen.')
                    ->required(fn (Get $get, ?User $record): bool => $isStudent($get, $record))
                    ->rules(fn (?User $record): array => GamerTag::rules($record?->id)),
                CheckboxList::make('roles')
                    ->label('Rollen')
                    ->options(fn (string $operation): array => $operation === 'create'
                        ? array_intersect_key(self::ROLE_LABELS, array_flip(Accounts::STAFF_ROLES))
                        : self::ROLE_LABELS)
                    ->required()
                    ->live()
                    ->visible(fn (Get $get, string $operation): bool => $operation === 'edit' || $get('kind') === 'staff'),
                Checkbox::make('parental_consent')
                    ->label('Das unterschriebene Formular liegt mir vor')
                    ->accepted()
                    ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && $get('kind') === 'student'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('gamer_tag')
                    ->label('Gamer-Tag')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–'),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->placeholder('–'),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rollen')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::ROLE_LABELS[$state] ?? $state),
                TextColumn::make('activeEnrollment.schoolClass.name')
                    ->label('Klasse')
                    ->placeholder('–'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (User $record): string => $record->isDeactivated() ? 'Gesperrt' : 'Aktiv')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Gesperrt' ? 'danger' : 'success'),
                TextColumn::make('last_login_at')
                    ->label('Letzter Login')
                    ->since()
                    ->sortable()
                    ->placeholder('nie'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rolle')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => self::ROLE_LABELS[$record->name] ?? $record->name),
                Filter::make('without_class')
                    ->label('Ohne Klasse')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('enrollments', fn (Builder $enrollments) => $enrollments->whereNull('left_at'))),
                TernaryFilter::make('deactivated_at')
                    ->label('Gesperrt')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deactivate')
                    ->label('Sperren')
                    ->color('danger')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Grund (intern)')
                            ->helperText('Sachlich formulieren. Die Person kann den Grund mit einer Auskunft nach DSGVO einsehen.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (User $record): bool => ! $record->isDeactivated())
                    ->action(fn (User $record, array $data) => self::guarded(
                        fn () => Accounts::deactivate($record, self::actor(), $data['reason']),
                        'Gesperrt. Die Person ist überall abgemeldet.',
                    )),
                Action::make('reactivate')
                    ->label('Entsperren')
                    ->icon('heroicon-o-lock-open')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->isDeactivated())
                    ->action(fn (User $record) => self::guarded(fn () => Accounts::reactivate($record, self::actor()), 'Entsperrt.')),
                // Seltene Aktionen im Menü: Die Zeile bleibt schmal, und Löschen liegt nicht direkt neben Sperren
                ActionGroup::make([
                    Action::make('resetGamerTag')
                        ->label('Gamer-Tag zurücksetzen')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->modalDescription('Der Gamer-Tag wird zu „Spieler-“ mit einer Zahl. Für Namen, die beleidigen oder verraten, wer dahintersteckt.')
                        ->visible(fn (User $record): bool => $record->gamer_tag !== null)
                        ->action(fn (User $record) => self::guarded(fn () => Accounts::resetGamerTag($record, self::actor()), 'Gamer-Tag zurückgesetzt.')),
                    Action::make('delete')
                        ->label('Löschen')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalDescription('Das Konto und der gesamte Lernverlauf werden sofort gelöscht. Das lässt sich nicht rückgängig machen.')
                        ->visible(fn (User $record): bool => ! $record->hasAnyRole(Accounts::STAFF_ROLES))
                        ->action(fn (User $record) => self::guarded(fn () => Accounts::deleteByAdmin($record, self::actor()), 'Gelöscht.')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    /** Führt eine Änderung aus und meldet abgelehnte Schutzregeln, statt einen Fehler zu zeigen. */
    public static function guarded(callable $change, string $success): bool
    {
        try {
            $change();
        } catch (AccountRuleViolation $e) {
            Notification::make()->title('Nicht gespeichert')->body($e->getMessage())->danger()->send();

            return false;
        }

        Notification::make()->title($success)->success()->send();

        return true;
    }

    /** E-Mail-Adressen sind ohne Groß- und Kleinschreibung eindeutig (Index auf lower(email)). */
    private static function uniqueEmail(?int $ignoreUserId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreUserId) {
            $taken = User::whereRaw('lower(email) = ?', [Str::lower(trim((string) $value))])
                ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists();

            if ($taken) {
                $fail('Für diese E-Mail gibt es schon ein Konto.');
            }
        };
    }

    private static function consentText(?User $record): string
    {
        $by = $record?->parentalConsentBy?->name ?? 'unbekannt';

        return 'Elternformular bestätigt am '.$record?->parental_consent_at?->timezone('Europe/Vienna')->format('d.m.Y').' von '.$by.'.';
    }

    private static function deactivationText(?User $record): string
    {
        $by = $record?->deactivatedBy?->name ?? 'unbekannt';

        return 'Gesperrt am '.$record?->deactivated_at?->timezone('Europe/Vienna')->format('d.m.Y H:i').' von '.$by.'. Grund: '.$record?->deactivation_reason;
    }
}
