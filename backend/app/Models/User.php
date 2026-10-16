<?php

namespace App\Models;

use App\Support\Progression;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'gamer_tag',
        'first_name',
        'last_name',
        'brainpower_current',
        'brainpower_max',
        'current_streak',
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
            'password' => 'hashed',
        ];
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

    /** Level, Rang und Prestige aus den Gesamt-EP. */
    public function progression(): Progression
    {
        return Progression::fromTotalXp($this->stats->total_xp ?? 0);
    }

    /**
     * Control Filament Admin Panel access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'autor', 'tester']);
    }
}
