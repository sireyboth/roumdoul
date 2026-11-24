<?php

namespace App\Models;

use App\Services\CustomerNotifier;
use App\Services\InvitationAddons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    use HasFactory;

    /**
     * Statuses that count as "payment confirmed" — this is what actually grants access to
     * anything paid for (currently: provisioning Digital Invitations). Nothing is handed over
     * on a bare `pending_payment` order, since payment isn't verified automatically.
     */
    public const PAID_STATUSES = ['paid', 'fulfilled'];

    /** A normal checkout. */
    public const TYPE_PURCHASE = 'purchase';

    /** Extras for one invitation the customer already owns (orders.invitation_id). See App\Services\InvitationAddons. */
    public const TYPE_ADDON = 'addon';

    protected $fillable = [
        'customer_id',
        'invitation_id',
        'order_number',
        'type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total',
        'promo_code',
        'discount_amount',
        'status',
        'notes',
        'amount_received',
        'transaction_ref',
        'paid_at',
        'paid_by',
        'addons_applied_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'paid_at' => 'datetime',
        'addons_applied_at' => 'datetime',
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

    /** The invitation an add-on order is for. */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isAddon(): bool
    {
        return $this->type === self::TYPE_ADDON;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }

    /**
     * The admin's payment check (Filament «Mark as paid»): the amount that actually arrived in the bank must
     * cover the order total, and the bank transaction ID must not already belong to another order (one payment
     * can't unlock two orders). Throws a ValidationException (keys amount_received / transaction_ref) otherwise.
     * Marking paid then provisions invitations / applies add-ons and tells the customer (see booted()).
     */
    public function markPaid(float|string $amountReceived, string $transactionRef, ?User $by = null): void
    {
        $amount = round((float) $amountReceived, 2);
        $ref = trim($transactionRef);
        $total = round((float) $this->total, 2);

        $errors = [];
        if ($amount < $total) {
            $errors['amount_received'] = sprintf('The customer paid $%s but the order is $%s ($%s short).', number_format($amount, 2), number_format($total, 2), number_format($total - $amount, 2));
        }
        if ($ref === '') {
            $errors['transaction_ref'] = 'Enter the transaction ID from your bank app.';
        } elseif (static::where('transaction_ref', $ref)->whereKeyNot($this->getKey())->exists()) {
            $other = static::where('transaction_ref', $ref)->value('order_number');
            $errors['transaction_ref'] = "This transaction ID is already used on order {$other}.";
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $this->update([
            'amount_received' => $amount,
            'transaction_ref' => $ref,
            'paid_at' => now(),
            'paid_by' => $by?->getKey(),
            'status' => 'paid',
        ]);
    }

    protected static function booted(): void
    {
        static::updated(function (self $order) {
            if ($order->wasChanged('status') && in_array($order->status, self::PAID_STATUSES, true)) {
                $order->provisionInvitations();
                app(InvitationAddons::class)->apply($order);

                // only on the move from unpaid to paid (not paid → fulfilled)
                if (! in_array($order->getOriginal('status'), self::PAID_STATUSES, true)) {
                    app(CustomerNotifier::class)->orderPaid($order);
                }
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
        if ($this->isAddon() || $this->invitations()->exists()) {
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
                    // the invitation keeps its own copy of the plan's limits, so later plan edits don't change it
                    'max_gallery_photos' => $item->servicePlan?->max_gallery_photos,
                    'features' => $item->servicePlan?->unlockedFeatures() ?? [],
                    // what's free on this design today stays free for this customer, even if marked ★ later
                    'free_fields' => $template->freeFields(),
                    'expires_at' => $item->servicePlan?->retention_months
                        ? now()->addMonths($item->servicePlan->retention_months)
                        : null,
                ]);
            }
        }
    }
}
