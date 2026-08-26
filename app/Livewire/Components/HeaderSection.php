<?php

namespace App\Livewire\Components;

use App\Models\Category;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class HeaderSection extends Component
{
    public int $cartCount = 0;

    public function mount(CartService $cart): void
    {
        $this->cartCount = $cart->count();
    }

    #[On('cart-updated')]
    public function refreshCartCount(CartService $cart): void
    {
        $this->cartCount = $cart->count();
    }

    public function render()
    {
        return view('livewire.components.header-section', [
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }
}
