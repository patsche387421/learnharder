<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Lehrplan-Eintrag: ein Fach in den Abschnitten stage_from bis stage_to eines Ausbildungsgangs.
 */
class CurriculumEntry extends Pivot
{
    use HasUuids;

    protected $table = 'curriculum_entries';

    protected $fillable = [
        'program_id',
        'subject_id',
        'stage_from',
        'stage_to',
    ];

    protected $casts = [
        'stage_from' => 'integer',
        'stage_to' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
