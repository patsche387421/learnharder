<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eine Person in einer Klasse. Austragen setzt left_at, gelöscht wird nicht: Der Verlauf bleibt (Plan Schule §5).
 */
class ClassEnrollment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'school_class_id',
        'school_year_id',
        'user_id',
        'enrolled_at',
        'left_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $enrollment): void {
            // Das Schuljahr steht schon an der Klasse, der zusammengesetzte Fremdschlüssel prüft, dass beide passen
            $enrollment->school_year_id ??= SchoolClass::whereKey($enrollment->school_class_id)->value('school_year_id');
            // Dieselbe Uhr wie beim Austragen (left_at), sonst kann die Datenbank-Uhr vorgehen und left_check schlägt fehl
            $enrollment->enrolled_at ??= now();
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Noch nicht ausgetragen */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('left_at');
    }
}
