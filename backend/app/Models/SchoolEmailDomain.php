<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEmailDomain extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_id',
        'domain',
        'allows_registration',
    ];

    protected $casts = [
        'allows_registration' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Domains immer klein und ohne Leerzeichen speichern, damit der Vergleich bei der Registrierung eindeutig ist.
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_strtolower(trim($value)),
        );
    }
}
