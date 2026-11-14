<?php

namespace App\Livewire\Concerns;

use App\Services\CartService;

/**
 * Promo-code box shared by the cart and checkout pages (view: components/shop/promo-code).
 * The code itself lives in the session via CartService, so applying it on either page
 * carries over to the other.
 */
trait HandlesPromoCode
{
    public string $promoCodeInput = '';

    public ?string $promoMessage = null;

    public bool $promoSuccess = false;

    public function applyPromoCode(CartService $cart): void
    {
        if (trim($this->promoCodeInput) === '') {
            return;
        }

        $result = $cart->applyPromoCode($this->promoCodeInput);
        $this->promoSuccess = $result['success'];
        $this->promoMessage = $result['message'];

        if ($result['success']) {
            $this->promoCodeInput = '';
        }
    }

    public function removePromoCode(CartService $cart): void
    {
        $cart->removePromoCode();
        $this->promoMessage = null;
    }
}
