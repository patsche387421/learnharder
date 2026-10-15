<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'city',
        'country',
    ];

    public function emailDomains(): HasMany
    {
        return $this->hasMany(SchoolEmailDomain::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('code');
    }

    public function schoolYears(): HasMany
    {
        return $this->hasMany(SchoolYear::class)->orderBy('starts_on');
    }
}
