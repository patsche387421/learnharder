<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wer welches Fach in welcher Klasse unterrichtet. semester leer = ganzes Schuljahr.
 */
class TeachingAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_class_id',
        'subject_id',
        'teacher_id',
        'semester',
    ];

    protected $casts = [
        'semester' => 'integer',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
