<?php

namespace App\Models;

use App\Enums\ProgramForm;
use App\Enums\ProgramUnit;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ausbildungsgang, z. B. Höhere Lehranstalt Elektrotechnik oder Kolleg Informatik-Systemtechnik.
 */
class Program extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'form',
        'unit',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'form' => ProgramForm::class,
        'unit' => ProgramUnit::class,
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function curriculumEntries(): HasMany
    {
        return $this->hasMany(CurriculumEntry::class);
    }

    /**
     * Fächer laut Lehrplan, mit den Abschnitten, in denen sie vorkommen.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'curriculum_entries')
            ->using(CurriculumEntry::class)
            ->withPivot(['id', 'stage_from', 'stage_to'])
            ->withTimestamps();
    }
}
