<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_km',
        'slug',
        'icon',
        'sort_order',
    ];

    /** Name in the current interface language, falling back to the other language when empty. */
    public function getNameAttribute(): string
    {
        $km = (string) $this->name_km;
        $en = (string) $this->name_en;

        return app()->getLocale() === 'km' ? ($km ?: $en) : ($en ?: $km);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
