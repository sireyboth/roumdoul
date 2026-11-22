<?php

namespace App\Models;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
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
        'max_gallery_photos',
        'features',
        'free_fields',
        'expires_at',
        'expiry_reminded_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'expires_at' => 'datetime',
        'expiry_reminded_at' => 'datetime',
        'max_gallery_photos' => 'integer',
        'features' => 'array',
        'free_fields' => 'array',
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

    /**
     * How many photos this invitation's photo gallery may hold: its own `max_gallery_photos` (copied from
     * the plan when paid, raised by «more photos» add-ons), else the plan's, else
     * InvitationManagePage::MAX_GALLERY_PHOTOS. Editing a plan in admin no longer changes invitations already sold.
     */
    public function maxGalleryPhotos(): int
    {
        return $this->max_gallery_photos ?: $this->plan?->max_gallery_photos ?: InvitationManagePage::MAX_GALLERY_PHOTOS;
    }

    /**
     * Add-on orders for this invitation (more guests / photos, feature unlocks, upgrades, extensions).
     */
    public function addonOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('type', Order::TYPE_ADDON);
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
     * Whether this invitation has unlocked a given key: its own `features` (copied from the plan when paid,
     * plus feature add-ons), falling back to the plan's list for invitations made before that column existed. `features` now stores InvitationTemplate::FIELD_CATALOG keys directly
     * (e.g. 'venue_address', 'countdown_enabled', 'photo_gallery') rather than separate
     * feature names, so this doubles as the low-level check behind fieldUnlocked() below.
     */
    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->features ?? $this->plan?->features ?? [], true);
    }

    /**
     * Whether a given FIELD_CATALOG key should actually render for this invitation's recipients. Unlocked when:
     *  - the template doesn't mark it ★ (InvitationTemplate::premiumFields(), the admin's choice), or
     *  - it was free when this invitation was paid (`free_fields`, so marking it ★ later never takes it away), or
     *  - its plan / add-ons unlocked it (`features`, hasFeature()).
     * Keys in InvitationTemplate::UNLOCKED_WITH follow the field they're an alternative to.
     */
    public function fieldUnlocked(string $key): bool
    {
        $key = InvitationTemplate::UNLOCKED_WITH[$key] ?? $key;

        if (in_array($key, $this->free_fields ?? [], true) || $this->hasFeature($key)) {
            return true;
        }

        return $this->template
            ? ! $this->template->isPremiumField($key)
            : in_array($key, InvitationTemplate::FREE_FIELDS, true);
    }
}
