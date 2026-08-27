<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_km',
        'slug',
        'short_description',
        'description',
        'image_path',
        'base_price',
        'is_featured',
        'is_active',
        'in_stock',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'in_stock' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ServicePlan::class)->orderBy('sort_order');
    }
}
