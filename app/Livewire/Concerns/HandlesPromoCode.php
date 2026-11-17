<?php

namespace App\Livewire\Concerns;

use App\Services\CartService;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Promo-code box shared by the cart and checkout pages (view: components/shop/promo-code).
 * The code itself lives in the session via CartService, so applying it on either page
 * carries over to the other. The code is checked by itself shortly after the customer stops
 * typing (updatedPromoCodeInput); Enter and the Apply button still work too.
 */
trait HandlesPromoCode
{
    /** Codes shorter than this aren't checked while typing (still checked on Enter/Apply). */
    public const PROMO_MIN_AUTO_LENGTH = 3;

    /** Checks per minute per session — typing checks codes automatically, so this stops code guessing. */
    public const PROMO_MAX_ATTEMPTS = 15;

    public string $promoCodeInput = '';

    public ?string $promoMessage = null;

    public bool $promoSuccess = false;

    /** Runs when the input changes (debounced in the view): check the code without a click. */
    public function updatedPromoCodeInput(): void
    {
        if (mb_strlen(trim($this->promoCodeInput)) < self::PROMO_MIN_AUTO_LENGTH) {
            $this->promoMessage = null;

            return;
        }

        $this->applyPromoCode(app(CartService::class));
    }

    public function applyPromoCode(CartService $cart): void
    {
        if (trim($this->promoCodeInput) === '') {
            return;
        }

        $throttleKey = 'promo-code|'.session()->getId();
        if (RateLimiter::tooManyAttempts($throttleKey, self::PROMO_MAX_ATTEMPTS)) {
            $this->promoSuccess = false;
            $this->promoMessage = __('Too many tries. Please wait a moment and try again.');

            return;
        }
        RateLimiter::hit($throttleKey, 60);

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
