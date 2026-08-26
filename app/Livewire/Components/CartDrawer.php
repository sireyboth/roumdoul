<?php

namespace App\Livewire\Components;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public function removeItem(string $key, CartService $cart): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // no-op: render() re-reads the cart on every request
    }

    public function render(CartService $cart)
    {
        return view('livewire.components.cart-drawer', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }
}
