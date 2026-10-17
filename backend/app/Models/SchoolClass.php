<?php

namespace App\Models;

use App\Enums\ProgramUnit;
use App\Support\DailyXp;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Klasse in einem Schuljahr, z. B. 2AHIT 2026/27 (Plan Schule §3).
 */
class SchoolClass extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'program_id',
        'school_year_id',
        'name',
        'stage',
        'head_teacher_id',
        'previous_class_id',
        'join_code',
    ];

    protected $casts = [
        'stage' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function headTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_teacher_id');
    }

    public function previousClass(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_class_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /**
     * Aktueller Abschnitt (Plan Schule §4): Jahrgang wie eingetragen. Bei Semestern zählt ab dem
     * zweiten Semester eins dazu. Der Tag gilt in Wien wie bei den EP (DailyXp).
     */
    public function currentStage(?CarbonInterface $today = null): int
    {
        $today = ($today ?? CarbonImmutable::parse(DailyXp::today()))->toDateString();

        $secondTerm = $this->program->unit === ProgramUnit::Semester
            && $today >= $this->schoolYear->second_term_starts_on->toDateString();

        return $this->stage + ($secondTerm ? 1 : 0);
    }
}
