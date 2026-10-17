<?php

namespace App\Models;

use App\Enums\AchievementRule;
use App\Models\Concerns\BlocksDeletionWhileInUse;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Erfolg aus dem Katalog (E-040 bis E-044). Die Bedingung ist ein Typ mit Schwellwert, beim Fach-Level optional ein Fach.
 * Wurde er schon vergeben, lässt er sich nicht löschen, nur deaktivieren.
 */
class Achievement extends Model
{
    use BlocksDeletionWhileInUse, HasFactory, HasTranslationsWithoutEmpty, HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'icon_name',
        'rule',
        'threshold',
        'subject_id',
        'is_secret',
        'is_active',
        'sort',
        'trophy_reward',
    ];

    public array $translatable = ['title', 'description'];

    protected $casts = [
        'rule' => AchievementRule::class,
        'threshold' => 'integer',
        'is_secret' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'trophy_reward' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function isInUse(): bool
    {
        return $this->unlocks()->exists();
    }
}
