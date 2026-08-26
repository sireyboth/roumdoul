<div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
  <div class="flex flex-col items-center text-center">
    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
      <x-icon name="check-circle" class="h-9 w-9" />
    </span>
    <h1 class="mt-5 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">អរគុណសម្រាប់ការបញ្ជាទិញ!</h1>
    <p class="mt-2 text-sm text-plum-500 dark:text-plum-400">លេខការបញ្ជាទិញរបស់អ្នក</p>
    <p class="mt-1 text-lg font-bold tracking-wide text-brand-700 dark:text-brand-300">#{{ $order->order_number }}</p>
  </div>

  <div class="mt-10 rounded-2xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
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

    <div class="mt-4 flex items-center justify-between border-t border-plum-100 pt-4 text-base font-bold text-plum-900 dark:border-plum-800 dark:text-white">
      <span>សរុប</span>
      <span class="text-brand-700 dark:text-brand-300">${{ number_format($order->total, 2) }}</span>
    </div>
  </div>

  <div class="mt-6 flex items-start gap-3 rounded-2xl border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
    <x-icon name="chat" class="mt-0.5 h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
    <p class="text-xs leading-relaxed text-plum-700 dark:text-plum-300">
      ក្រុមការងាររបស់យើងនឹងទាក់ទងអ្នកទៅកាន់លេខ <strong>{{ $order->customer_phone }}</strong> ក្នុងពេលឆាប់ៗនេះ ដើម្បីបញ្ជាក់ការទូទាត់ប្រាក់ និងដឹកជញ្ជូនទំនិញរបស់អ្នក។
    </p>
  </div>

  <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
    <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
      បន្តទិញទំនិញ
    </a>
    <a href="/" wire:navigate class="rounded-full border border-plum-300 px-6 py-2.5 text-sm font-semibold text-plum-700 transition-colors hover:bg-plum-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
      ត្រឡប់ទៅទំព័រដើម
    </a>
  </div>
</div>
