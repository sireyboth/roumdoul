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
     * Whether the plan this invitation was purchased under unlocks a given feature key
     * (e.g. 'map', 'countdown', 'rsvp'). Templates check this before rendering optional components.
     */
    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->plan?->features ?? [], true);
    }
}
