<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'cart';

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
                $unitPrice = $plan ? (float) $plan->price : (float) $service->base_price;

                return (object) [
                    'key' => $key,
                    'service' => $service,
                    'plan' => $plan,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $line['quantity'],
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

    public function key(int $serviceId, ?int $planId): string
    {
        return $serviceId.'_'.($planId ?? 'base');
    }
}
