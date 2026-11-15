<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HandlesPromoCode;
use App\Models\Order;
use App\Services\CartService;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CheckoutPage extends Component
{
    use HandlesPromoCode;

    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $notes = '';

    /** True while the "Confirm your order" dialog is open. */
    public bool $confirmingOrder = false;

    public function mount(): void
    {
        // Guests can view and edit their cart here; they only need to log in to place the order.
        if ($customer = Auth::guard('customer')->user()) {
            $this->customer_name = $customer->name;
            $this->customer_email = $customer->email;
            $this->customer_phone = (string) $customer->phone;
        }
    }

    public function updateQuantity(string $key, int $quantity, CartService $cart): void
    {
        $cart->updateQuantity($key, $quantity);
        $this->dispatch('cart-updated');
    }

    public function removeItem(string $key, CartService $cart): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
    }

    /** Sends a guest to log in / register, then straight back here with the cart intact. */
    public function goToAuth(string $page): void
    {
        session()->put('url.intended', url('/checkout'));

        $this->redirect($page === 'register' ? '/register' : '/login', navigate: true);
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * "Place order" button: check the form and the cart first, then ask the customer to confirm
     * in a dialog. The order itself is only created by placeOrder() from that dialog.
     */
    public function reviewOrder(CartService $cart): void
    {
        if (! Auth::guard('customer')->check()) {
            $this->goToAuth('login');

            return;
        }

        $items = $cart->items();

        if ($items->isEmpty() || $items->contains(fn ($item) => ! $item->in_stock)) {
            return;   // the page already shows the empty / sold-out state
        }

        $this->validate();
        $this->confirmingOrder = true;
    }

    public function placeOrder(CartService $cart, TelegramNotifier $telegram)
    {
        if (! Auth::guard('customer')->check()) {
            $this->goToAuth('login');

            return;
        }

        // Authenticated customer, so keyed by customer_id rather than IP — mainly guards
        // against scripted order-spam/promo-code abuse, not price tampering (prices are
        // always recomputed server-side from CartService, never trusted from the client).
        $throttleKey = 'place-order|'.Auth::guard('customer')->id();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->confirmingOrder = false;
            $this->addError('notes', __('You have tried to place an order too many times. Please wait a moment.'));

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $items = $cart->items();

        if ($items->isEmpty() || $items->contains(fn ($item) => ! $item->in_stock)) {
            $this->redirect('/checkout', navigate: true);

            return;
        }

        $this->confirmingOrder = false;
        $validated = $this->validate();

        // accounts made before sign-up asked for a phone: remember it for next time
        $customer = Auth::guard('customer')->user();
        if (! $customer->phone) {
            $customer->update(['phone' => $validated['customer_phone']]);
        }

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
        ])->title(__('Checkout | ROUMDOUL'));
    }
}
