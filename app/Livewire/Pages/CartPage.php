<?php

namespace App\Livewire\Pages;

use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('កន្ត្រករបស់អ្នក | ROUMDOUL')]
class CartPage extends Component
{
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

    public function render(CartService $cart)
    {
        $items = $cart->items();

        return view('livewire.pages.cart-page', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
            'hasOutOfStock' => $items->contains(fn ($item) => ! $item->service->in_stock),
        ]);
    }
}
