<div x-data @cart-added.window="$store.cartDrawer.show()">
  <div x-show="$store.cartDrawer.open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 bg-plum-950/60" @click="$store.cartDrawer.hide()"></div>

  <aside x-show="$store.cartDrawer.open" x-cloak
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-plum-900">
    <div class="flex items-center justify-between border-b border-plum-200 px-5 py-4 dark:border-plum-800">
      <h2 class="flex items-center gap-2 text-lg font-bold text-plum-900 dark:text-white">
        <x-app-icon name="cart" class="h-5 w-5 text-brand-600" />
        កន្ត្រករបស់អ្នក
      </h2>
      <button type="button" @click="$store.cartDrawer.hide()"
        class="flex h-8 w-8 items-center justify-center rounded-full text-plum-500 transition-colors hover:bg-plum-100 dark:hover:bg-plum-800">
        <x-app-icon name="x-mark" class="h-5 w-5" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-4">
      @if ($items->isEmpty())
        <div class="flex h-full flex-col items-center justify-center gap-3 text-center">
          <x-app-icon name="cart" class="h-12 w-12 text-plum-200 dark:text-plum-700" />
          <p class="text-sm font-medium text-plum-500 dark:text-plum-400">កន្ត្រករបស់អ្នកនៅទទេ</p>
          <a href="/shop" wire:navigate @click="$store.cartDrawer.hide()"
            class="mt-1 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
            ចាប់ផ្តើមទិញឥឡូវនេះ
          </a>
        </div>
      @else
        <ul class="flex flex-col gap-4">
          @foreach ($items as $item)
            <li wire:key="cart-drawer-{{ $item->key }}" class="flex gap-3">
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300 {{ $item->in_stock ? '' : 'opacity-50 grayscale' }}">
                <x-app-icon name="{{ $item->service->category->icon }}" class="h-6 w-6" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $item->service->name_en }}</p>
                @if ($item->plan)
                  <p class="text-xs text-plum-500 dark:text-plum-400">{{ $item->plan->label }}</p>
                @endif
                @if ($item->in_stock)
                  <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">{{ $item->quantity }} &times; ${{ number_format($item->unit_price, 2) }}</p>
                @else
                  <p class="mt-1 flex items-center gap-1 text-xs font-bold uppercase tracking-wide text-red-600 dark:text-red-400">
                    <x-app-icon name="x-circle" class="h-3 w-3" /> Out of Stock
                  </p>
                @endif
              </div>
              <div class="flex flex-col items-end justify-between">
                <span class="text-sm font-bold text-brand-700 dark:text-brand-300">${{ number_format($item->line_total, 2) }}</span>
                <button type="button" wire:click="removeItem('{{ $item->key }}')"
                  class="text-plum-400 transition-colors hover:text-red-600">
                  <x-app-icon name="trash" class="h-4 w-4" />
                </button>
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </div>

    @if ($items->isNotEmpty())
      <div class="border-t border-plum-200 px-5 py-4 dark:border-plum-800">
        @if ($discount > 0)
          <div class="mb-1 flex items-center justify-between text-xs text-green-600 dark:text-green-400">
            <span>បញ្ចុះតម្លៃ</span>
            <span>&minus;${{ number_format($discount, 2) }}</span>
          </div>
        @endif
        <div class="mb-4 flex items-center justify-between text-sm font-semibold text-plum-900 dark:text-white">
          <span>សរុប</span>
          <span class="text-lg font-bold text-brand-700 dark:text-brand-300">${{ number_format($total, 2) }}</span>
        </div>
        @if ($hasOutOfStock)
          <p class="mb-2 flex items-start gap-1.5 text-xs leading-relaxed text-red-600 dark:text-red-400">
            <x-app-icon name="x-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
            សូមដកចេញនូវទំនិញដែលអស់ពីស្តុក មុននឹងទូទាត់ប្រាក់។
          </p>
        @endif
        <div class="flex flex-col gap-2">
          @if ($hasOutOfStock)
            <button type="button" disabled
              class="block w-full cursor-not-allowed rounded-full bg-plum-200 px-4 py-2.5 text-center text-sm font-semibold text-plum-500 dark:bg-plum-800 dark:text-plum-500">
              ទូទាត់ប្រាក់
            </button>
          @else
            <a href="/checkout" wire:navigate @click="$store.cartDrawer.hide()"
              class="block rounded-full bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-md shadow-brand-900/20 transition-colors hover:bg-brand-700">
              ទូទាត់ប្រាក់
            </a>
          @endif
          <a href="/cart" wire:navigate @click="$store.cartDrawer.hide()"
            class="block rounded-full border border-plum-300 px-4 py-2.5 text-center text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
            មើលកន្ត្រក
          </a>
        </div>
      </div>
    @endif
  </aside>
</div>
