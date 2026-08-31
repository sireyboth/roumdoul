<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'invitation_template_id',
        'order_id',
        'service_plan_id',
        'slug',
        'field_values',
        'max_recipients',
        'expires_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'invitation_template_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InvitationRecipient::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Whether the order this invitation was created from has actually been paid for. Checked
     * on every access, not just at creation time — an order can be reverted to pending (refund,
     * mistake, chargeback) after its invitation already exists, and access should follow that.
     */
    public function isPaid(): bool
    {
        return $this->order !== null && in_array($this->order->status, Order::PAID_STATUSES, true);
    }

    /**
     * Whether the plan this invitation was purchased under has unlocked a given key in its
     * `features` list. `features` now stores InvitationTemplate::FIELD_CATALOG keys directly
     * (e.g. 'venue_address', 'countdown_enabled', 'photo_gallery') rather than separate
     * feature names, so this doubles as the low-level check behind fieldUnlocked() below.
     */
    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->plan?->features ?? [], true);
    }

    /**
     * Whether a given FIELD_CATALOG key should actually render for this invitation's
     * recipients. FREE_FIELDS are always unlocked; everything else needs the purchased
     * plan's `features` to include that exact field key — this is the pricing upsell lever
     * (e.g. Basic doesn't include 'venue_address', so no map link; Premium does).
     */
    public function fieldUnlocked(string $key): bool
    {
        return in_array($key, InvitationTemplate::FREE_FIELDS, true) || $this->hasFeature($key);
    }
}
