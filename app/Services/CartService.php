<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'cart';

    protected const PROMO_SESSION_KEY = 'cart_promo_code';

    /** Most of one line a customer can order; also stops absurd totals. */
    public const MAX_QUANTITY = 99;

    public function add(int $serviceId, ?int $planId, int $quantity = 1): void
    {
        // A negative quantity would subtract from the order total, so never accept one.
        if ($quantity < 1) {
            return;
        }

        $cart = $this->raw();
        $key = $this->key($serviceId, $planId);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = min(self::MAX_QUANTITY, $cart[$key]['quantity'] + $quantity);
        } else {
            $cart[$key] = [
                'service_id' => $serviceId,
                'plan_id' => $planId,
                'quantity' => min(self::MAX_QUANTITY, $quantity),
            ];
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $cart = $this->raw();

        if (! isset($cart[$key])) {
            return;
        }

        if ($quantity < 1) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = min(self::MAX_QUANTITY, $quantity);
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::PROMO_SESSION_KEY);
    }

    public function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Hydrated cart line items, each with the Service/ServicePlan models and a computed line total.
     * Silently drops lines whose Service no longer exists, and any line that could only come from a
     * tampered request: a quantity below 1, a plan that isn't one of that Service's own plans, or no
     * plan at all on a Service that has plans (that would charge its base price instead).
     */
    public function items(): Collection
    {
        $cart = $this->raw();

        if (empty($cart)) {
            return collect();
        }

        $serviceIds = collect($cart)->pluck('service_id')->unique();

        $services = Service::with('plans')->whereIn('id', $serviceIds)->get()->keyBy('id');

        return collect($cart)
            ->map(function (array $line, string $key) use ($services) {
                $service = $services->get($line['service_id']);
                $quantity = (int) ($line['quantity'] ?? 0);

                // A product turned off in admin can no longer be bought, even if it is still in a cart.
                if (! $service || ! $service->is_active || $quantity < 1) {
                    return null;
                }

                $plan = null;

                if ($line['plan_id']) {
                    // Only this Service's own plans — never a cheaper plan of another product.
                    $plan = $service->plans->firstWhere('id', $line['plan_id']);

                    if (! $plan) {
                        return null;
                    }
                } elseif ($service->plans->isNotEmpty()) {
                    return null;
                }

                $quantity = min(self::MAX_QUANTITY, $quantity);
                $unitPrice = $service->discountedPrice($plan ? (float) $plan->price : (float) $service->base_price);

                return (object) [
                    'key' => $key,
                    'service' => $service,
                    'plan' => $plan,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                    'in_stock' => $service->in_stock && ($plan === null || $plan->in_stock),
                ];
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return collect($this->raw())->sum('quantity');
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum('line_total');
    }

    /**
     * Attempt to apply a promo code to the cart. Returns a message to show the customer either way.
     *
     * @return array{success: bool, message: string}
     */
    public function applyPromoCode(string $code): array
    {
        $promoCode = PromoCode::with('services')->where('code', strtoupper(trim($code)))->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            return ['success' => false, 'message' => __('This code is invalid or has expired.')];
        }

        if ($this->eligibleSubtotal($promoCode) <= 0) {
            return ['success' => false, 'message' => __('This code only works for: :items', ['items' => $promoCode->serviceNames()])];
        }

        Session::put(self::PROMO_SESSION_KEY, $promoCode->code);

        return ['success' => true, 'message' => __('Code applied successfully! (:label)', ['label' => $promoCode->label()])];
    }

    public function removePromoCode(): void
    {
        Session::forget(self::PROMO_SESSION_KEY);
    }

    public function appliedPromoCode(): ?PromoCode
    {
        $code = Session::get(self::PROMO_SESSION_KEY);

        if (! $code) {
            return null;
        }

        $promoCode = PromoCode::with('services')->where('code', $code)->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            $this->removePromoCode();

            return null;
        }

        return $promoCode;
    }

    public function discount(): float
    {
        $promoCode = $this->appliedPromoCode();

        return $promoCode ? $promoCode->discountFor($this->eligibleSubtotal($promoCode)) : 0.0;
    }

    /**
     * Total of the cart lines a code applies to: every line for a whole-order code, otherwise
     * only the lines for the products the code was limited to in admin.
     */
    public function eligibleSubtotal(PromoCode $promoCode): float
    {
        return (float) $this->items()
            ->filter(fn ($item) => $promoCode->appliesToService($item->service->id))
            ->sum('line_total');
    }

    public function total(): float
    {
        return max(0, $this->subtotal() - $this->discount());
    }

    public function key(int $serviceId, ?int $planId): string
    {
        return $serviceId.'_'.($planId ?? 'base');
    }
}
