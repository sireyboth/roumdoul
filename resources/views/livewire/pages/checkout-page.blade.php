<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">{{ __('Checkout') }}</h1>

  @if ($items->isEmpty())
    <div class="mt-10 flex flex-col items-center gap-4 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
      <x-app-icon name="cart" class="h-14 w-14 text-plum-200 dark:text-plum-700" />
      <p class="text-base font-medium text-plum-500 dark:text-plum-400">{{ __('Your cart is empty — please add products before checking out') }}</p>
      <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
        {{ __('Start shopping now') }}
      </a>
    </div>
  @else
    <form wire:submit="placeOrder" class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
      <div class="flex flex-col gap-5 lg:col-span-2">
        <div class="rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
          <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-plum-900 dark:text-white">
            <x-app-icon name="user" class="h-5 w-5 text-brand-600" /> {{ __('Contact information') }}
          </h2>

          <div class="flex flex-col gap-4">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-plum-700 dark:text-plum-300">{{ __('Full name') }}</label>
              <input type="text" wire:model="customer_name"
                class="w-full rounded-lg border border-plum-200 px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800" />
              @error('customer_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-plum-700 dark:text-plum-300">{{ __('Email') }}</label>
                <input type="email" wire:model="customer_email"
                  class="w-full rounded-lg border border-plum-200 px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800" />
                @error('customer_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-plum-700 dark:text-plum-300">{{ __('Phone number / Telegram') }}</label>
                <input type="text" wire:model="customer_phone"
                  class="w-full rounded-lg border border-plum-200 px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800" />
                @error('customer_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-plum-700 dark:text-plum-300">{{ __('Notes (optional)') }}</label>
              <textarea wire:model="notes" rows="3"
                class="w-full rounded-lg border border-plum-200 px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800"></textarea>
            </div>
          </div>
        </div>

        <div class="flex items-start gap-3 rounded-lg border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
          <x-app-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
          <p class="text-xs leading-relaxed text-plum-700 dark:text-plum-300">
            {{ __('After you place your order, our team will contact you right away to confirm payment (ABA/Wing/QR). Automatic payment is coming soon.') }}
          </p>
        </div>
      </div>

      <div class="h-fit rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
        <h2 class="text-base font-bold text-plum-900 dark:text-white">{{ __('Order summary') }}</h2>
        <ul class="mt-4 flex flex-col gap-3">
          @foreach ($items as $item)
            <li class="flex justify-between gap-2 text-sm">
              <span class="{{ $item->in_stock ? 'text-plum-600 dark:text-plum-300' : 'text-red-600 dark:text-red-400' }}">
                {{ $item->service->name_en }}
                @if ($item->plan) <span class="text-plum-400">({{ $item->plan->label }})</span> @endif
                &times; {{ $item->quantity }}
                @unless ($item->in_stock)
                  <span class="font-bold uppercase">&mdash; {{ __('Sold out') }}</span>
                @endunless
              </span>
              <span class="shrink-0 font-semibold text-plum-900 dark:text-white">${{ number_format($item->line_total, 2) }}</span>
            </li>
          @endforeach
        </ul>
        <div class="mt-4 flex items-center justify-between border-t border-plum-100 pt-4 text-sm text-plum-600 dark:border-plum-800 dark:text-plum-300">
          <span>{{ __('Subtotal') }}</span>
          <span>${{ number_format($subtotal, 2) }}</span>
        </div>
        @if ($appliedPromoCode)
          <div class="mt-1.5 flex items-center justify-between text-sm text-green-600 dark:text-green-400">
            <span>{{ __('Code :code', ['code' => $appliedPromoCode->code]) }}</span>
            <span>&minus;${{ number_format($discount, 2) }}</span>
          </div>
        @endif
        <div class="mt-2 flex items-center justify-between border-t border-plum-100 pt-4 text-base font-bold text-plum-900 dark:border-plum-800 dark:text-white">
          <span>{{ __('Total') }}</span>
          <span class="text-brand-700 dark:text-brand-300">${{ number_format($total, 2) }}</span>
        </div>
        @if ($hasOutOfStock)
          <p class="mt-4 flex items-start gap-1.5 text-xs leading-relaxed text-red-600 dark:text-red-400">
            <x-app-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            {{ __('Please go back to your cart to remove out-of-stock items.') }}
          </p>
          <a href="/cart" wire:navigate
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-full bg-plum-200 px-6 py-3 text-sm font-bold text-plum-700 transition-colors hover:bg-plum-300 dark:bg-plum-800 dark:text-plum-200 dark:hover:bg-plum-700">
            {{ __('Back to cart') }}
          </a>
        @else
          <button type="submit"
            class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700">
            <span wire:loading.remove wire:target="placeOrder">{{ __('Place order') }}</span>
            <span wire:loading wire:target="placeOrder">{{ __('Processing...') }}</span>
          </button>
        @endif
      </div>
    </form>
  @endif
</div>
