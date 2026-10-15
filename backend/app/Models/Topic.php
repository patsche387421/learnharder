<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasTranslationsWithoutEmpty;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory, HasTranslationsWithoutEmpty, HasUuids;

    protected $fillable = [
        'subject_id',
        'slug',
        'title',
        'description',
        'level_required',
        'order_index',
        'status',
    ];

    public array $translatable = ['title', 'description'];

    protected $casts = [
        'level_required' => 'integer',
        'order_index' => 'integer',
        'status' => ContentStatus::class,
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function theorySections(): HasMany
    {
        return $this->hasMany(TheorySection::class)->orderBy('order_index');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }
}
