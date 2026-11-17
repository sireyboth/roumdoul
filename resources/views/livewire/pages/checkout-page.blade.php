<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">{{ __('Checkout') }}</h1>

  @if ($items->isEmpty())
    <div class="mt-10 flex flex-col items-center gap-4 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
      <x-app-icon name="cart" class="h-14 w-14 text-plum-200 dark:text-plum-700" />
      <p class="text-base font-medium text-plum-500 dark:text-plum-400">{{ __('Your cart is empty') }}</p>
      <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
        {{ __('Start shopping now') }}
      </a>
    </div>
  @else
    <form wire:submit="reviewOrder" class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
      <div class="flex flex-col gap-5 lg:col-span-2">
        <div class="flex flex-col gap-4">
          @foreach ($items as $item)
            <div wire:key="cart-item-{{ $item->key }}"
              class="flex items-center gap-4 rounded-lg border p-4 {{ $item->in_stock ? 'border-plum-200 bg-white dark:border-plum-800 dark:bg-plum-900' : 'border-red-200 bg-red-50/50 dark:border-red-900/40 dark:bg-red-900/10' }}">
              <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300 {{ $item->in_stock ? '' : 'opacity-50 grayscale' }}">
                <x-app-icon name="{{ $item->service->category->icon }}" class="h-7 w-7" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-plum-900 dark:text-white">{{ $item->service->name_en }}</p>
                @if ($item->plan)
                  <p class="text-xs text-plum-500 dark:text-plum-400">{{ $item->plan->label }}</p>
                @endif
                @if ($item->in_stock)
                  <p class="mt-1 text-sm font-semibold text-brand-700 dark:text-brand-300">${{ number_format($item->unit_price, 2) }}</p>
                @else
                  <p class="mt-1 flex items-center gap-1 text-xs font-bold uppercase tracking-wide text-red-600 dark:text-red-400">
                    <x-app-icon name="x-circle" class="h-3.5 w-3.5" /> {{ __('Sold out') }}
                  </p>
                @endif
              </div>

              <div class="flex items-center rounded-full border border-plum-200 dark:border-plum-700">
                <button type="button" wire:click="updateQuantity('{{ $item->key }}', {{ $item->quantity - 1 }})" @disabled(! $item->in_stock)
                  class="flex h-8 w-8 items-center justify-center text-plum-500 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40">&minus;</button>
                <span class="w-6 text-center text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $item->quantity }}</span>
                <button type="button" wire:click="updateQuantity('{{ $item->key }}', {{ $item->quantity + 1 }})" @disabled(! $item->in_stock)
                  class="flex h-8 w-8 items-center justify-center text-plum-500 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40">+</button>
              </div>

              <div class="w-20 shrink-0 text-right text-sm font-bold text-plum-900 dark:text-white">
                ${{ number_format($item->line_total, 2) }}
              </div>

              <button type="button" wire:click="removeItem('{{ $item->key }}')"
                class="shrink-0 text-plum-400 transition-colors hover:text-red-600">
                <x-app-icon name="trash" class="h-4.5 w-4.5" />
              </button>
            </div>
          @endforeach

          <a href="/shop" wire:navigate class="flex w-fit items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">
            <x-app-icon name="chevron-left" class="h-4 w-4" /> {{ __('Continue shopping') }}
          </a>
        </div>

        @guest('customer')
          <div class="rounded-lg border border-plum-200 bg-white p-6 text-center dark:border-plum-800 dark:bg-plum-900">
            <x-app-icon name="user" class="mx-auto h-8 w-8 text-brand-600" />
            <p class="mt-2 text-sm font-medium text-plum-700 dark:text-plum-300">{{ __('Log in or create an account to place your order.') }}</p>
            <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
              <button type="button" wire:click="goToAuth('login')"
                class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">{{ __('Log in') }}</button>
              <button type="button" wire:click="goToAuth('register')"
                class="rounded-full border border-plum-300 px-6 py-2.5 text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">{{ __('Create an account') }}</button>
            </div>
          </div>
        @else
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
        @endguest

        <div class="flex items-start gap-3 rounded-lg border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
          <x-app-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
          <p class="text-xs leading-relaxed text-plum-700 dark:text-plum-300">
            {{ __('After you place your order, our team will contact you right away to confirm payment (ABA/Wing/QR). Automatic payment is coming soon.') }}
          </p>
        </div>
      </div>

      <div class="h-fit rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
        <h2 class="text-base font-bold text-plum-900 dark:text-white">{{ __('Order summary') }}</h2>
        <x-shop.promo-code class="mt-4" :applied-promo-code="$appliedPromoCode" :promo-message="$promoMessage" :promo-success="$promoSuccess" />
        <div class="mt-4 flex items-center justify-between border-t border-plum-100 pt-4 text-sm text-plum-600 dark:border-plum-800 dark:text-plum-300">
          <span>{{ __('Subtotal') }}</span>
          <span>${{ number_format($subtotal, 2) }}</span>
        </div>
        @if ($appliedPromoCode)
          <div class="mt-1.5 flex items-center justify-between text-sm text-green-600 dark:text-green-400">
            <span>{{ __('Code :code', ['code' => $appliedPromoCode->code]) }}</span>
            <span>&minus;${{ number_format($discount, 2) }}</span>
          </div>
          @unless ($appliedPromoCode->appliesToWholeOrder())
            <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">{{ __('Only for: :items', ['items' => $appliedPromoCode->serviceNames()]) }}</p>
          @endunless
        @endif
        <div class="mt-2 flex items-center justify-between border-t border-plum-100 pt-4 text-base font-bold text-plum-900 dark:border-plum-800 dark:text-white">
          <span>{{ __('Total') }}</span>
          <span class="text-brand-700 dark:text-brand-300">${{ number_format($total, 2) }}</span>
        </div>
        @if ($hasOutOfStock)
          <p class="mt-4 flex items-start gap-1.5 text-xs leading-relaxed text-red-600 dark:text-red-400">
            <x-app-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            {{ __('Please remove out-of-stock items before checking out.') }}
          </p>
          <button type="button" disabled
            class="mt-3 flex w-full cursor-not-allowed items-center justify-center rounded-full bg-plum-200 px-6 py-3 text-sm font-bold text-plum-500 dark:bg-plum-800 dark:text-plum-500">
            {{ __('Place order') }}
          </button>
        @elseguest('customer')
          <button type="button" wire:click="goToAuth('login')"
            class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700">
            {{ __('Log in') }}
          </button>
        @else
          <button type="submit"
            class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700">
            <span wire:loading.remove wire:target="reviewOrder">{{ __('Place order') }}</span>
            <span wire:loading wire:target="reviewOrder">{{ __('Processing...') }}</span>
          </button>
        @endif
      </div>
    </form>

    {{-- ───────── Confirm before the order is created ───────── --}}
    {{-- Always in the page and toggled with x-show (not @if), so Alpine can animate it in AND out. --}}
    <div x-data="{ open: $wire.entangle('confirmingOrder') }" x-cloak>
      <div x-show="open" class="fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center"
        x-effect="open && $nextTick(() => $refs.confirm.focus())"
        @keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-plum-950/60 backdrop-blur-sm" @click="open = false"
          x-show="open"
          x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <div role="dialog" aria-modal="true" aria-labelledby="confirm-order-title"
          class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl dark:bg-plum-900"
          x-show="open"
          x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-3 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
          x-transition:leave="transition ease-in duration-200 motion-reduce:transition-none" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-3 sm:scale-95">
          <x-kbach-decor />

          <div class="relative">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
              <x-app-icon name="shield-check" class="h-6 w-6" />
            </span>
            <h2 id="confirm-order-title" class="mt-4 text-lg font-extrabold text-plum-900 dark:text-white">{{ __('Confirm your order') }}</h2>
            <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ __('Please check your order before you confirm.') }}</p>

            <ul class="mt-4 flex flex-col gap-2 rounded-xl bg-plum-50 p-4 text-sm dark:bg-plum-800/60">
              @foreach ($items as $item)
                <li class="flex justify-between gap-3">
                  <span class="text-plum-700 dark:text-plum-200">
                    {{ $item->service->name_en }}
                    @if ($item->plan) <span class="text-plum-400">({{ $item->plan->label }})</span> @endif
                    &times; {{ $item->quantity }}
                  </span>
                  <span class="shrink-0 font-semibold text-plum-900 dark:text-white">${{ number_format($item->line_total, 2) }}</span>
                </li>
              @endforeach
              @if ($discount > 0)
                <li class="flex justify-between gap-3 text-green-600 dark:text-green-400">
                  <span>{{ __('Code :code', ['code' => $appliedPromoCode->code]) }}</span>
                  <span>&minus;${{ number_format($discount, 2) }}</span>
                </li>
              @endif
              <li class="mt-1 flex justify-between gap-3 border-t border-plum-200 pt-2 text-base font-bold dark:border-plum-700">
                <span class="text-plum-900 dark:text-white">{{ __('Total') }}</span>
                <span class="text-brand-700 dark:text-brand-300">${{ number_format($total, 2) }}</span>
              </li>
            </ul>

            <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-plum-600 dark:text-plum-300">
              <x-app-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
              <span>{{ __("Next you'll see our KHQR with the exact amount — scan it in any bank app. No screenshot needed.") }}</span>
            </p>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <button type="button" @click="open = false"
                class="rounded-full border border-plum-300 px-5 py-2.5 text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
                {{ __('Cancel') }}
              </button>
              <button type="button" x-ref="confirm" wire:click="placeOrder" wire:loading.attr="disabled" wire:target="placeOrder"
                class="flex items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700 disabled:opacity-70">
                <span wire:loading.remove wire:target="placeOrder">{{ __('Confirm order') }}</span>
                <span wire:loading wire:target="placeOrder">{{ __('Processing...') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
