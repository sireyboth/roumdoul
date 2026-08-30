<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /**
     * Statuses that count as "payment confirmed" — this is what actually grants access to
     * anything paid for (currently: provisioning Digital Invitations). Nothing is handed over
     * on a bare `pending_payment` order, since payment isn't verified automatically.
     */
    public const PAID_STATUSES = ['paid', 'fulfilled'];

    protected $fillable = [
        'customer_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total',
        'promo_code',
        'discount_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    protected static function booted(): void
    {
        static::updated(function (self $order) {
            if ($order->wasChanged('status') && in_array($order->status, self::PAID_STATUSES, true)) {
                $order->provisionInvitations();
            }
        });
    }

    /**
     * Create the Digital Invitation(s) for any purchased item that's an invitation template,
     * once — and only once — payment is confirmed. Safe to call more than once: it's a no-op
     * if this order has already been provisioned.
     */
    public function provisionInvitations(): void
    {
        if ($this->invitations()->exists()) {
            return;
        }

        foreach ($this->items()->with(['service', 'servicePlan'])->get() as $item) {
            $template = InvitationTemplate::where('service_id', $item->service_id)->first();

            if (! $template) {
                continue;
            }

            for ($i = 0; $i < $item->quantity; $i++) {
                Invitation::create([
                    'customer_id' => $this->customer_id,
                    'invitation_template_id' => $template->id,
                    'order_id' => $this->id,
                    'service_plan_id' => $item->service_plan_id,
                    'slug' => Str::random(12),
                    'field_values' => [],
                    'max_recipients' => $item->servicePlan?->max_recipients ?? 1,
                    'expires_at' => $item->servicePlan?->retention_months
                        ? now()->addMonths($item->servicePlan->retention_months)
                        : null,
                ]);
            }
        }
    }
}
