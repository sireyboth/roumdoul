<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'cart';

    protected const PROMO_SESSION_KEY = 'cart_promo_code';

    public function add(int $serviceId, ?int $planId, int $quantity = 1): void
    {
        $cart = $this->raw();
        $key = $this->key($serviceId, $planId);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'service_id' => $serviceId,
                'plan_id' => $planId,
                'quantity' => $quantity,
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
            $cart[$key]['quantity'] = $quantity;
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
     * Silently drops lines whose Service no longer exists.
     */
    public function items(): Collection
    {
        $cart = $this->raw();

        if (empty($cart)) {
            return collect();
        }

        $serviceIds = collect($cart)->pluck('service_id')->unique();
        $planIds = collect($cart)->pluck('plan_id')->filter()->unique();

        $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');
        $plans = ServicePlan::whereIn('id', $planIds)->get()->keyBy('id');

        return collect($cart)
            ->map(function (array $line, string $key) use ($services, $plans) {
                $service = $services->get($line['service_id']);

                if (! $service) {
                    return null;
                }

                $plan = $line['plan_id'] ? $plans->get($line['plan_id']) : null;
                $unitPrice = $service->discountedPrice($plan ? (float) $plan->price : (float) $service->base_price);

                return (object) [
                    'key' => $key,
                    'service' => $service,
                    'plan' => $plan,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['quantity'],
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
        $promoCode = PromoCode::where('code', strtoupper(trim($code)))->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            return ['success' => false, 'message' => 'លេខកូដមិនត្រឹមត្រូវ ឬផុតកំណត់ហើយ។'];
        }

        Session::put(self::PROMO_SESSION_KEY, $promoCode->code);

        return ['success' => true, 'message' => 'បានប្រើប្រាស់លេខកូដដោយជោគជ័យ! ('.$promoCode->label().')'];
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

        $promoCode = PromoCode::where('code', $code)->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            $this->removePromoCode();

            return null;
        }

        return $promoCode;
    }

    public function discount(): float
    {
        $promoCode = $this->appliedPromoCode();

        return $promoCode ? $promoCode->discountFor($this->subtotal()) : 0.0;
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
