<div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
  <div class="flex flex-col items-center text-center">
    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
      <x-app-icon name="check-circle" class="h-9 w-9" />
    </span>
    <h1 class="mt-5 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">អរគុណសម្រាប់ការបញ្ជាទិញ!</h1>
    <p class="mt-2 text-sm text-plum-500 dark:text-plum-400">លេខការបញ្ជាទិញរបស់អ្នក</p>
    <p class="mt-1 text-lg font-bold tracking-wide text-brand-700 dark:text-brand-300">#{{ $order->order_number }}</p>
  </div>

  <div class="mt-10 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <div class="flex items-center justify-between border-b border-plum-100 pb-4 dark:border-plum-800">
      <span class="text-sm text-plum-500 dark:text-plum-400">ស្ថានភាព</span>
      <span class="rounded-full bg-gold-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-gold-700 dark:bg-gold-400/10 dark:text-gold-400">
        រង់ចាំការទូទាត់
      </span>
    </div>

    <ul class="mt-4 flex flex-col gap-3">
      @foreach ($order->items as $item)
        <li class="flex justify-between gap-2 text-sm">
          <span class="text-plum-600 dark:text-plum-300">
            {{ $item->service_name_snapshot }}
            @if ($item->plan_label_snapshot) <span class="text-plum-400">({{ $item->plan_label_snapshot }})</span> @endif
            &times; {{ $item->quantity }}
          </span>
          <span class="shrink-0 font-semibold text-plum-900 dark:text-white">${{ number_format($item->line_total, 2) }}</span>
        </li>
      @endforeach
    </ul>

    @if ($order->discount_amount > 0)
      <div class="mt-4 flex items-center justify-between border-t border-plum-100 pt-4 text-sm text-green-600 dark:border-plum-800 dark:text-green-400">
        <span>លេខកូដ {{ $order->promo_code }}</span>
        <span>&minus;${{ number_format($order->discount_amount, 2) }}</span>
      </div>
    @endif
    <div class="mt-2 flex items-center justify-between {{ $order->discount_amount > 0 ? '' : 'border-t border-plum-100 pt-4' }} text-base font-bold text-plum-900 dark:border-plum-800 dark:text-white">
      <span>សរុប</span>
      <span class="text-brand-700 dark:text-brand-300">${{ number_format($order->total, 2) }}</span>
    </div>
  </div>

  <div class="mt-6 flex items-start gap-3 rounded-lg border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
    <x-app-icon name="chat" class="mt-0.5 h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
    <p class="text-xs leading-relaxed text-plum-700 dark:text-plum-300">
      ក្រុមការងាររបស់យើងនឹងទាក់ទងអ្នកទៅកាន់លេខ <strong>{{ $order->customer_phone }}</strong> ក្នុងពេលឆាប់ៗនេះ ដើម្បីបញ្ជាក់ការទូទាត់ប្រាក់ និងដឹកជញ្ជូនទំនិញរបស់អ្នក។
    </p>
  </div>

  <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
    <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
      បន្តទិញទំនិញ
    </a>
    <a href="/dashboard" wire:navigate class="rounded-full border border-plum-300 px-6 py-2.5 text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
      ពិនិត្យមើលការបញ្ជាទិញ
    </a>
  </div>

  {{-- Post-order feedback modal --}}
  <div x-data="{ open: @js(! $reviewSubmitted) }">
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-plum-950/60" @click="open = false"></div>

    <div x-show="open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="w-full max-w-sm rounded-lg bg-white p-6 dark:bg-plum-900">
        @if ($reviewSubmitted)
          <div class="flex flex-col items-center text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
              <x-app-icon name="check-circle" class="h-7 w-7" />
            </span>
            <p class="mt-3 text-base font-bold text-plum-900 dark:text-white">អរគុណសម្រាប់មតិយោបល់របស់អ្នក!</p>
            <button type="button" @click="open = false"
              class="mt-5 rounded-full bg-brand-600 px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
              បិទ
            </button>
          </div>
        @else
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-plum-900 dark:text-white">វាយតម្លៃបទពិសោធន៍ទិញរបស់អ្នក</h2>
            <button type="button" @click="open = false" class="text-plum-400 hover:text-plum-600">
              <x-app-icon name="x-mark" class="h-5 w-5" />
            </button>
          </div>
          <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">មតិយោបល់របស់អ្នកជួយពួកយើងកែលម្អសេវាកម្ម</p>

          <div class="mt-5 flex justify-center gap-1.5">
            @for ($i = 1; $i <= 5; $i++)
              <button type="button" wire:click="$set('rating', {{ $i }})" wire:key="rating-star-{{ $i }}">
                <x-app-icon name="star" class="h-8 w-8 transition-colors {{ $i <= $rating ? 'fill-current text-gold-500' : 'text-plum-200 dark:text-plum-700' }}" />
              </button>
            @endfor
          </div>

          <textarea wire:model="comment" rows="3" placeholder="មតិយោបល់ (មិនចាំបាច់)..."
            class="mt-4 w-full rounded-lg border border-plum-200 px-3.5 py-2.5 text-sm outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800"></textarea>
          @error('comment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

          <div class="mt-5 flex gap-2">
            <button type="button" @click="open = false"
              class="flex-1 rounded-full border border-plum-300 px-4 py-2.5 text-sm font-semibold text-plum-600 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-300 dark:hover:bg-plum-800">
              រំលង
            </button>
            <button type="button" wire:click="submitReview"
              class="flex-1 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
              <span wire:loading.remove wire:target="submitReview">ផ្ញើមតិយោបល់</span>
              <span wire:loading wire:target="submitReview">កំពុងផ្ញើ...</span>
            </button>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
