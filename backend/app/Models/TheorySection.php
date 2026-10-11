<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class TheorySection extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'topic_id',
        'title',
        'content',
        'order_index',
    ];

    public array $translatable = ['title', 'content'];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
