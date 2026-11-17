@props(['appliedPromoCode' => null, 'promoMessage' => null, 'promoSuccess' => false])

{{-- No <form> here: on checkout this sits inside the place-order form, and forms can't nest.
     The code is checked by itself ~0.8 s after typing stops (HandlesPromoCode::updatedPromoCodeInput);
     Enter in the input applies it right away instead of submitting the order. --}}
<div {{ $attributes }}>
  @if ($appliedPromoCode)
    <div class="flex items-center justify-between gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm dark:border-green-900/40 dark:bg-green-900/10">
      <span class="flex items-center gap-1.5 font-semibold text-green-700 dark:text-green-400">
        <x-app-icon name="check-circle" class="h-4 w-4" /> {{ $appliedPromoCode->code }}
      </span>
      <button type="button" wire:click="removePromoCode" class="text-xs font-semibold text-plum-500 hover:text-red-600">{{ __('Remove') }}</button>
    </div>
  @else
    <div class="flex gap-2">
      <input type="text" wire:model.live.debounce.800ms="promoCodeInput" wire:keydown.enter.prevent="applyPromoCode" placeholder="{{ __('Promo code') }}"
        autocomplete="off" autocapitalize="characters" spellcheck="false"
        class="w-full rounded-lg border border-plum-200 px-3 py-2 text-sm uppercase outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800" />
      <button type="button" wire:click="applyPromoCode" wire:loading.attr="disabled" wire:target="applyPromoCode,promoCodeInput"
        class="shrink-0 rounded-lg border border-plum-300 px-4 py-2 text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 disabled:opacity-60 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
        {{ __('Apply') }}
      </button>
    </div>
  @endif
  @if ($promoMessage)
    <p class="mt-1.5 text-xs {{ $promoSuccess ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $promoMessage }}</p>
  @endif
</div>
