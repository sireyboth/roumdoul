<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
        'usage_limit',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /** Products this code is limited to. None selected = the code applies to the whole order. */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function appliesToWholeOrder(): bool
    {
        return $this->services->isEmpty();
    }

    /** Whether a cart line for this service counts towards the discount. */
    public function appliesToService(int $serviceId): bool
    {
        return $this->appliesToWholeOrder() || $this->services->contains('id', $serviceId);
    }

    /** "Gemini Pro, ChatGPT Plus" — the products a limited code works on. */
    public function serviceNames(): string
    {
        return $this->services->pluck('name_en')->join(', ');
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /** $subtotal is the total of the lines the code applies to (see CartService::eligibleSubtotal()). */
    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percentage'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $subtotal), 2);
    }

    public function label(): string
    {
        return $this->type === 'percentage'
            ? number_format((float) $this->value, 0).'% off'
            : '$'.number_format((float) $this->value, 2).' off';
    }
}
