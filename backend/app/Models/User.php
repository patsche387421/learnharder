<?php

namespace App\Models;

use App\Support\Brainpower;
use App\Support\DailyXp;
use App\Support\Progression;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    // Kein HasApiTokens: Die App nutzt Sanctum nur mit Session-Cookies, es gibt keine Token-Tabelle
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'gamer_tag',
        'brainpower_current',
        'brainpower_refilled_on',
        'last_active_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'brainpower_refilled_on' => 'date',
            'consented_at' => 'datetime',
            'parental_consent_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Gesperrt durch Admin (E-027): keine Anmeldung in App und CMS. */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deactivated_by');
    }

    /** Wer im CMS bestätigt hat, dass das Elternformular vorliegt (unter 14, E-025). */
    public function parentalConsentBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parental_consent_by');
    }

    public function stats(): HasOne
    {
        return $this->hasOne(UserStat::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    /** Letzte aktive Einschreibung, unabhängig vom Datum: In den Sommerferien gilt die Klasse weiter (Plan Schule §4). */
    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(ClassEnrollment::class)
            ->ofMany(['enrolled_at' => 'max', 'id' => 'max'], fn (Builder $enrollments) => $enrollments->whereNull('left_at'));
    }

    /** Diese Rollen sehen in der App alle veröffentlichten Fächer, alle anderen nur den Lehrplan ihrer Klasse (E-024). */
    public function seesAllContent(): bool
    {
        return $this->hasAnyRole(['admin', 'autor', 'tester', 'teacher']);
    }

    /** Level, Rang und Prestige aus den Gesamt-EP. */
    public function progression(): Progression
    {
        return Progression::fromTotalXp($this->stats->total_xp ?? 0);
    }

    /**
     * Stand und Maximum. Vorher Brainpower::refill() aufrufen, sonst fehlt das Aufladen des heutigen Tages.
     *
     * @return array{current: int, max: int}
     */
    public function brainpower(): array
    {
        $max = Brainpower::max($this->progression());

        return ['current' => min($this->brainpower_current, $max), 'max' => $max];
    }

    /**
     * Laufender und längster Streak, Tag in Wien wie bei den EP (E-015).
     *
     * @return array{current: int, longest: int}
     */
    public function streak(): array
    {
        return [
            'current' => $this->stats?->currentStreak(DailyXp::today()) ?? 0,
            'longest' => $this->stats->longest_streak ?? 0,
        ];
    }

    /**
     * Control Filament Admin Panel access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Filament fragt das bei jeder Anfrage, eine Sperre wirkt also sofort (E-027)
        return $this->hasAnyRole(['admin', 'autor', 'tester']) && ! $this->isDeactivated();
    }
}
