<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'total_xp',
        'trophies',
        'current_streak',
        'longest_streak',
        'last_study_date',
    ];

    protected $casts = [
        'total_xp' => 'integer',
        'trophies' => 'integer',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_study_date' => 'date',
    ];

    /**
     * Zählt einen Lerntag für den Streak: eine abgeschlossene Lerneinheit am Kalendertag in Wien ($today, Y-m-d).
     * Am selben Tag ändert sich nichts, nach gestern zählt er weiter, nach einer Lücke beginnt er wieder bei 1.
     * Speichert nicht, das macht der Aufrufer.
     */
    public function recordStudyDay(string $today): void
    {
        $last = $this->last_study_date?->toDateString();
        if ($last === $today) {
            return;
        }

        $this->current_streak = $last === self::dayBefore($today) ? $this->current_streak + 1 : 1;
        $this->longest_streak = max($this->longest_streak, $this->current_streak);
        $this->last_study_date = $today;
    }

    /** Laufender Streak am Tag $today: Ist gestern ausgefallen, ist er 0, auch wenn die Spalte noch den alten Wert hat. */
    public function currentStreak(string $today): int
    {
        return in_array($this->last_study_date?->toDateString(), [$today, self::dayBefore($today)], true)
            ? $this->current_streak
            : 0;
    }

    /** Schon gelernt am Tag $day? Dann ist der Streak für heute sicher, sonst ist er in Gefahr (Hub, Konzept Spielwelt §4). */
    public function studiedOn(string $day): bool
    {
        return $this->last_study_date?->toDateString() === $day;
    }

    private static function dayBefore(string $day): string
    {
        return CarbonImmutable::parse($day)->subDay()->toDateString();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
