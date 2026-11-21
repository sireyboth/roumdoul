<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'label',
        'badge',
        'price',
        'in_stock',
        'retention_months',
        'max_recipients',
        'max_gallery_photos',
        'extra_guest_price',
        'extra_photo_price',
        'feature_prices',
        'extend_price',
        'extend_months',
        'features',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'boolean',
        'max_gallery_photos' => 'integer',
        'features' => 'array',
        'extra_guest_price' => 'decimal:2',
        'extra_photo_price' => 'decimal:2',
        'feature_prices' => 'array',
        'extend_price' => 'decimal:2',
        'extend_months' => 'integer',
    ];

    /** Months one «extend» add-on adds (blank = 6). */
    public const DEFAULT_EXTEND_MONTHS = 6;

    public function extendMonths(): int
    {
        return $this->extend_months ?: self::DEFAULT_EXTEND_MONTHS;
    }

    /** Price to unlock one premium field on its own, or null when it isn't sold separately. */
    public function featurePrice(string $key): ?float
    {
        $price = ($this->feature_prices ?? [])[$key] ?? null;

        return is_numeric($price) && $price > 0 ? (float) $price : null;
    }

    /**
     * Whether this plan includes the premium extras. Admin sets it with one switch («Includes premium extras»):
     * on = every premium field the template uses, off = none. Stored in `features` (all premium keys, or []);
     * an older plan with only some keys ticked also counts as «on».
     */
    public function includesPremium(): bool
    {
        return filled($this->features);
    }

    /**
     * The premium field keys a new invitation on this plan gets — always the full current premium list when the
     * plan includes premium, so a premium field added to the catalog later is included without re-saving plans.
     */
    public function unlockedFeatures(): array
    {
        return $this->includesPremium() ? array_keys(InvitationTemplate::premiumFieldOptions()) : [];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
