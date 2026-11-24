<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\ServicePlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Add-ons: extras a customer buys for an invitation they already own. Prices are set per plan in admin
 * (ServicePlan extra_guest_price / extra_photo_price / feature_prices / extend_price + extend_months); a
 * blank price means that add-on isn't sold for that plan. Upgrading to a bigger plan of the same product
 * costs the price difference.
 *
 * Flow: the customer picks add-ons in the invitation dashboard → order() makes a `pending_payment` order of
 * type «addon» linked to the invitation (prices always computed here, never trusted from the browser) →
 * Telegram alert → the customer pays with the KHQR modal → an admin marks it paid → Order::booted() calls
 * apply(), which raises the invitation's own limits once (orders.addons_applied_at guards against twice).
 */
class InvitationAddons
{
    public const GUESTS = 'guests';

    public const PHOTOS = 'photos';

    public const FEATURE = 'feature';

    public const UPGRADE = 'upgrade';

    public const EXTEND = 'extend';

    /** Highest gallery size an add-on can reach (the dashboard + validation cap). */
    public const MAX_GALLERY = 100;

    /** Most guests / photos / extensions one add-on line can buy. */
    public const MAX_QUANTITY = ['guests' => 5000, 'photos' => 100, 'extend' => 10];

    /**
     * What this invitation can buy right now, with prices — drives the dashboard's «Add more» panel.
     *
     * @return array{guests: ?array, photos: ?array, features: array, upgrades: array, extend: ?array}
     */
    public function options(Invitation $invitation): array
    {
        $plan = $invitation->plan;
        $template = $invitation->template;
        $photos = $invitation->maxGalleryPhotos();

        $features = [];
        $upgrades = [];
        if ($plan) {
            foreach ($template?->premiumFields() ?? [] as $key) {
                $price = $plan->featurePrice($key);
                if ($price !== null && ! $invitation->fieldUnlocked($key)) {
                    $features[$key] = ['label' => __(InvitationTemplate::FIELD_CATALOG[$key]['label'] ?? $key), 'price' => $price];
                }
            }

            $upgrades = ServicePlan::where('service_id', $plan->service_id)
                ->where('id', '!=', $plan->id)
                ->where('in_stock', true)
                ->where('price', '>', $plan->price)
                ->orderBy('price')
                ->get()
                ->map(fn (ServicePlan $to) => [
                    'id' => $to->id,
                    'label' => $to->label,
                    'price' => round((float) $to->price - (float) $plan->price, 2),
                    'max_recipients' => $to->max_recipients,
                    'max_gallery_photos' => $to->max_gallery_photos,
                    'new_features' => collect($to->unlockedFeatures())
                        ->filter(fn ($key) => $template?->isPremiumField($key) && ! $invitation->fieldUnlocked($key))
                        ->map(fn ($key) => __(InvitationTemplate::FIELD_CATALOG[$key]['label'] ?? $key))->values()->all(),
                ])->all();
        }

        return [
            'guests' => $plan?->extra_guest_price > 0
                ? ['price' => (float) $plan->extra_guest_price, 'current' => $invitation->max_recipients, 'max' => self::MAX_QUANTITY['guests']]
                : null,
            'photos' => $plan?->extra_photo_price > 0 && $template?->usesField('photo_gallery') && $invitation->fieldUnlocked('photo_gallery') && $photos < self::MAX_GALLERY
                ? ['price' => (float) $plan->extra_photo_price, 'current' => $photos, 'max' => self::MAX_GALLERY - $photos]
                : null,
            'features' => $features,
            'upgrades' => $upgrades,
            'extend' => $plan?->extend_price > 0 && $invitation->expires_at !== null
                ? ['price' => (float) $plan->extend_price, 'months' => $plan->extendMonths(), 'expires_at' => $invitation->expires_at, 'max' => self::MAX_QUANTITY['extend']]
                : null,
        ];
    }

    /**
     * Create the pending add-on order. $lines: [['type' => 'guests', 'quantity' => 50], ['type' => 'feature', 'key' => 'rsvp_enabled'],
     * ['type' => 'upgrade', 'plan_id' => 7], ['type' => 'extend', 'quantity' => 1], …]. Throws ValidationException (key `addons`)
     * when a line isn't for sale.
     */
    public function order(Invitation $invitation, array $lines): Order
    {
        $options = $this->options($invitation);
        $plan = $invitation->plan;
        $template = $invitation->template;
        $name = $template?->name ?? __('Invitation');
        $items = [];

        foreach ($lines as $line) {
            $type = $line['type'] ?? null;
            $qty = max(1, (int) ($line['quantity'] ?? 1));

            $items[] = match (true) {
                $type === self::GUESTS && $options['guests'] !== null && $qty <= $options['guests']['max'] => [
                    'label' => __('+:n guests', ['n' => $qty]), 'unit' => $options['guests']['price'], 'qty' => $qty, 'plan' => $plan?->id, 'value' => null,
                ],
                $type === self::PHOTOS && $options['photos'] !== null && $qty <= $options['photos']['max'] => [
                    'label' => __('+:n gallery photos', ['n' => $qty]), 'unit' => $options['photos']['price'], 'qty' => $qty, 'plan' => $plan?->id, 'value' => null,
                ],
                $type === self::FEATURE && isset($options['features'][$line['key'] ?? '']) => [
                    'label' => __('Unlock: :feature', ['feature' => $options['features'][$line['key']]['label']]), 'unit' => $options['features'][$line['key']]['price'], 'qty' => 1, 'plan' => $plan?->id, 'value' => $line['key'],
                ],
                $type === self::UPGRADE && ($up = collect($options['upgrades'])->firstWhere('id', (int) ($line['plan_id'] ?? 0))) !== null => [
                    'label' => __('Upgrade to :plan', ['plan' => $up['label']]), 'unit' => $up['price'], 'qty' => 1, 'plan' => $up['id'], 'value' => (string) $plan?->id,
                ],
                $type === self::EXTEND && $options['extend'] !== null && $qty <= $options['extend']['max'] => [
                    'label' => __('+:n months', ['n' => $qty * $options['extend']['months']]), 'unit' => $options['extend']['price'], 'qty' => $qty, 'plan' => $plan?->id, 'value' => (string) $options['extend']['months'],
                ],
                default => throw ValidationException::withMessages(['addons' => __('That add-on isn\'t available for this invitation.')]),
            } + ['type' => $type];
        }

        if (! $items) {
            throw ValidationException::withMessages(['addons' => __('Choose at least one add-on.')]);
        }
        if (collect($items)->where('type', self::UPGRADE)->count() > 1) {
            throw ValidationException::withMessages(['addons' => __('Choose one plan to upgrade to.')]);
        }

        $customer = $invitation->customer;

        return DB::transaction(function () use ($invitation, $items, $customer, $name, $template) {
            $order = Order::create([
                'customer_id' => $invitation->customer_id,
                'invitation_id' => $invitation->id,
                'type' => Order::TYPE_ADDON,
                'order_number' => 'RD-'.strtoupper(Str::random(8)),
                'customer_name' => $customer?->name ?? '',
                'customer_email' => $customer?->email,
                'customer_phone' => $customer?->phone ?? '',
                'total' => round(collect($items)->sum(fn ($i) => $i['unit'] * $i['qty']), 2),
                'discount_amount' => 0,
                'status' => 'pending_payment',
            ]);

            foreach ($items as $i) {
                $order->items()->create([
                    'service_id' => $template?->service_id,
                    'service_plan_id' => $i['plan'],
                    'addon_type' => $i['type'],
                    'addon_value' => $i['value'],
                    'service_name_snapshot' => $i['label'].' — '.$name,
                    'plan_label_snapshot' => null,
                    'unit_price' => $i['unit'],
                    'quantity' => $i['qty'],
                    'line_total' => round($i['unit'] * $i['qty'], 2),
                ]);
            }

            return $order;
        });
    }

    /**
     * Raise the invitation's limits for a paid add-on order — once. Safe to call repeatedly.
     */
    public function apply(Order $order): void
    {
        if (! $order->isAddon() || ! $order->isPaid() || $order->addons_applied_at !== null || ! $order->invitation_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->first();
            if (! $order || $order->addons_applied_at !== null) {
                return;
            }
            $invitation = Invitation::withTrashed()->whereKey($order->invitation_id)->lockForUpdate()->first();
            if (! $invitation) {
                return;
            }

            foreach ($order->items as $item) {
                match ($item->addon_type) {
                    self::GUESTS => $invitation->max_recipients += $item->quantity,
                    self::PHOTOS => $invitation->max_gallery_photos = min(self::MAX_GALLERY, $invitation->maxGalleryPhotos() + $item->quantity),
                    self::FEATURE => $invitation->features = array_values(array_unique([...($invitation->features ?? $invitation->plan?->features ?? []), $item->addon_value])),
                    self::UPGRADE => $this->upgrade($invitation, ServicePlan::find($item->service_plan_id)),
                    self::EXTEND => $invitation->expires_at = ($invitation->expires_at && $invitation->expires_at->isFuture() ? $invitation->expires_at->copy() : now())
                        ->addMonths($item->quantity * max(1, (int) $item->addon_value)),
                    default => null,
                };
            }
            if ($order->items->contains('addon_type', self::EXTEND)) {
                $invitation->expiry_reminded_at = null; // a new expiry date earns a new reminder
            }

            $invitation->save();
            $order->forceFill(['addons_applied_at' => now()])->saveQuietly();
        });
    }

    /**
     * Move to a bigger plan, keeping everything already bought: limits go up by the difference between the
     * plans (so earlier «more guests» add-ons aren't lost), features are merged, and the expiry follows the
     * new plan's retention (longer retention → later date; no retention → no expiry).
     */
    private function upgrade(Invitation $invitation, ?ServicePlan $to): void
    {
        $from = $invitation->plan;
        if (! $to || ($from && $to->id === $from->id)) {
            return;
        }

        $invitation->max_recipients += max(0, (int) $to->max_recipients - (int) ($from?->max_recipients ?? 0));
        $photosGain = max(0, (int) ($to->max_gallery_photos ?: 0) - (int) ($from?->max_gallery_photos ?: 0));
        if ($photosGain > 0) {
            $invitation->max_gallery_photos = min(self::MAX_GALLERY, $invitation->maxGalleryPhotos() + $photosGain);
        }
        $invitation->features = array_values(array_unique([...($invitation->features ?? $from?->features ?? []), ...$to->unlockedFeatures()]));

        if ($to->retention_months === null) {
            $invitation->expires_at = null;
        } elseif ($invitation->expires_at && $from?->retention_months && $to->retention_months > $from->retention_months) {
            $invitation->expires_at = $invitation->expires_at->copy()->addMonths($to->retention_months - $from->retention_months);
        }

        $invitation->service_plan_id = $to->id;
        $invitation->setRelation('plan', $to);
    }
}
