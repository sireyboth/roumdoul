<?php

namespace App\Livewire\Pages;

use App\Models\Order;
use App\Services\CartService;
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

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function placeOrder(CartService $cart)
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            $this->redirect('/cart', navigate: true);

            return;
        }

        $validated = $this->validate();

        $order = DB::transaction(function () use ($validated, $items, $cart) {
            $order = Order::create([
                'order_number' => 'RD-'.strtoupper(Str::random(8)),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'total' => $cart->subtotal(),
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

            $cart->clear();

            return $order;
        });

        $this->dispatch('cart-updated');

        return $this->redirect("/order/{$order->id}/confirmation", navigate: true);
    }

    public function render(CartService $cart)
    {
        return view('livewire.pages.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }
}
