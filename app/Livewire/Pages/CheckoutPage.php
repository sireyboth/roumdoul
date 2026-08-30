<?php

namespace App\Livewire\Pages;

use App\Models\Order;
use App\Services\CartService;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ទូទាត់ប្រាក់ | ROUMDOUL')]
class CheckoutPage extends Component
{
    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $notes = '';

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->customer_name = $customer->name;
        $this->customer_email = $customer->email;
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => [ 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function placeOrder(CartService $cart, TelegramNotifier $telegram)
    {
        $items = $cart->items();

        if ($items->isEmpty() || $items->contains(fn ($item) => ! $item->in_stock)) {
            $this->redirect('/cart', navigate: true);

            return;
        }

        $validated = $this->validate();

        $order = DB::transaction(function () use ($validated, $items, $cart) {
            $promoCode = $cart->appliedPromoCode();

            $order = Order::create([
                'customer_id' => Auth::guard('customer')->id(),
                'order_number' => 'RD-'.strtoupper(Str::random(8)),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'total' => $cart->total(),
                'promo_code' => $promoCode?->code,
                'discount_amount' => $cart->discount(),
                'status' => 'pending_payment',
                'notes' => $validated['notes'] ?: null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'service_id' => $item->service->id,
                    'service_plan_id' => $item->plan?->id,
                    'service_name_snapshot' => $item->service->name_en,
                    'plan_label_snapshot' => $item->plan?->label,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ]);
            }

            $promoCode?->increment('times_used');

            $cart->clear();

            return $order;
        });

        $telegram->sendNewOrder($order);

        $this->dispatch('cart-updated');

        return $this->redirect("/order/{$order->id}/confirmation", navigate: true);
    }

    public function render(CartService $cart)
    {
        $items = $cart->items();

        return view('livewire.pages.checkout-page', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'total' => $cart->total(),
            'appliedPromoCode' => $cart->appliedPromoCode(),
            'hasOutOfStock' => $items->contains(fn ($item) => ! $item->in_stock),
        ]);
    }
}
