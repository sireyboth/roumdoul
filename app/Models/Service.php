<?php

namespace App\Models;

use App\Services\ImageOptimizer;
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
        'gallery_images',
        'demo_url',
        'how_to_use_steps',
        'faqs',
        'base_price',
        'discount_type',
        'discount_value',
        'is_featured',
        'is_active',
        'in_stock',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'in_stock' => 'boolean',
        'gallery_images' => 'array',
        'how_to_use_steps' => 'array',
        'faqs' => 'array',
    ];

    /**
     * Product images are shown on the homepage and shop, so shrink heavy admin uploads (a PNG
     * screenshot can be 2 MB+) right after they're saved. `services:optimize-images` does the
     * same for images uploaded before this existed.
     */
    protected static function booted(): void
    {
        static::saved(function (Service $service) {
            if ($service->wasChanged('image_path') || $service->wasChanged('gallery_images') || $service->wasRecentlyCreated) {
                rescue(fn () => $service->optimizeImages(), report: true);
            }
        });
    }

    /** Re-encodes this product's heavy images in place; returns how many were replaced. */
    public function optimizeImages(): int
    {
        $optimizer = app(ImageOptimizer::class);
        $changed = 0;

        $cover = $this->image_path ? $optimizer->optimizeStored($this->image_path) : null;
        if ($cover) {
            $this->image_path = $cover;
            $changed++;
        }

        $gallery = collect($this->gallery_images ?? [])->map(function ($path) use ($optimizer, &$changed) {
            $new = $path ? $optimizer->optimizeStored($path) : null;
            $new && $changed++;

            return $new ?? $path;
        })->all();

        if ($changed) {
            $this->gallery_images = $gallery ?: $this->gallery_images;
            $this->saveQuietly();
        }

        return $changed;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ServicePlan::class)->orderBy('sort_order');
    }

    public function invitationTemplates(): HasMany
    {
        return $this->hasMany(InvitationTemplate::class);
    }

    public function hasDiscount(): bool
    {
        return $this->discount_type !== null && (float) $this->discount_value > 0;
    }

    public function discountedPrice(float $price): float
    {
        if (! $this->hasDiscount()) {
            return $price;
        }

        $discount = $this->discount_type === 'percentage'
            ? $price * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return max(0, round($price - $discount, 2));
    }
}
