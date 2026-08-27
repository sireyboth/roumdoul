<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">កន្ត្រករបស់អ្នក</h1>

  @if ($items->isEmpty())
    <div class="mt-10 flex flex-col items-center gap-4 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
      <x-app-icon name="cart" class="h-14 w-14 text-plum-200 dark:text-plum-700" />
      <p class="text-base font-medium text-plum-500 dark:text-plum-400">កន្ត្រករបស់អ្នកនៅទទេ</p>
      <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
        ចាប់ផ្តើមទិញឥឡូវនេះ
      </a>
    </div>
  @else
    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
      <div class="flex flex-col gap-4 lg:col-span-2">
        @foreach ($items as $item)
          <div wire:key="cart-item-{{ $item->key }}"
            class="flex items-center gap-4 rounded-lg border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
              <x-app-icon name="{{ $item->service->category->icon }}" class="h-7 w-7" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-plum-900 dark:text-white">{{ $item->service->name_en }}</p>
              @if ($item->plan)
                <p class="text-xs text-plum-500 dark:text-plum-400">{{ $item->plan->label }}</p>
              @endif
              <p class="mt-1 text-sm font-semibold text-brand-700 dark:text-brand-300">${{ number_format($item->unit_price, 2) }}</p>
            </div>

            <div class="flex items-center rounded-full border border-plum-200 dark:border-plum-700">
              <button type="button" wire:click="updateQuantity('{{ $item->key }}', {{ $item->quantity - 1 }})"
                class="flex h-8 w-8 items-center justify-center text-plum-500 hover:text-brand-700">&minus;</button>
              <span class="w-6 text-center text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $item->quantity }}</span>
              <button type="button" wire:click="updateQuantity('{{ $item->key }}', {{ $item->quantity + 1 }})"
                class="flex h-8 w-8 items-center justify-center text-plum-500 hover:text-brand-700">+</button>
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

        <a href="/shop" wire:navigate class="mt-2 flex w-fit items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">
          <x-app-icon name="chevron-left" class="h-4 w-4" /> បន្តទិញទំនិញ
        </a>
      </div>

      <div class="h-fit rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
        <h2 class="text-base font-bold text-plum-900 dark:text-white">សង្ខេបការបញ្ជាទិញ</h2>
        <div class="mt-4 flex items-center justify-between text-sm text-plum-600 dark:text-plum-300">
          <span>សរុបរង</span>
          <span>${{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between border-t border-plum-100 pt-4 text-base font-bold text-plum-900 dark:border-plum-800 dark:text-white">
          <span>សរុប</span>
          <span class="text-brand-700 dark:text-brand-300">${{ number_format($subtotal, 2) }}</span>
        </div>
        <a href="/checkout" wire:navigate
          class="mt-5 block rounded-full bg-brand-600 px-6 py-3 text-center text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700">
          បន្តទៅការទូទាត់
        </a>
      </div>
    </div>
  @endif
</div>
