<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
  <a href="/dashboard" wire:navigate class="mb-6 inline-flex items-center gap-1.5 text-sm font-semibold text-plum-500 transition-colors hover:text-brand-700 dark:text-plum-400 dark:hover:text-brand-300">
    <x-app-icon name="chevron-right" class="h-4 w-4 rotate-180" />
    ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង
  </a>

  @php
    $statusStyles = [
      'pending_payment' => 'bg-gold-500/15 text-gold-600 dark:text-gold-400',
      'paid' => 'bg-brand-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300',
      'fulfilled' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
      'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    ];
  @endphp

  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs text-plum-500 dark:text-plum-400">លេខការបញ្ជាទិញ</p>
      <h1 class="text-xl font-extrabold tracking-wide text-plum-900 dark:text-white">#{{ $order->order_number }}</h1>
    </div>
    <span class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide {{ $statusStyles[$order->status] ?? 'bg-plum-100 text-plum-600' }}">
      {{ $statusLabels[$order->status] ?? $order->status }}
    </span>
  </div>
  <p class="mt-1 text-xs text-plum-400 dark:text-plum-500">បានបញ្ជាទិញនៅ {{ $order->created_at->format('d M Y, h:i A') }}</p>

  <div class="mt-6 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-plum-500 dark:text-plum-400">ទំនិញក្នុងការបញ្ជាទិញ</h2>
    <ul class="flex flex-col gap-3">
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

  <div class="mt-6 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-plum-500 dark:text-plum-400">ព័ត៌មានទំនាក់ទំនង</h2>
    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
      <div>
        <dt class="text-xs text-plum-400 dark:text-plum-500">ឈ្មោះ</dt>
        <dd class="text-plum-800 dark:text-plum-200">{{ $order->customer_name }}</dd>
      </div>
      <div>
        <dt class="text-xs text-plum-400 dark:text-plum-500">ទូរស័ព្ទ</dt>
        <dd class="text-plum-800 dark:text-plum-200">{{ $order->customer_phone }}</dd>
      </div>
      @if ($order->customer_email)
        <div>
          <dt class="text-xs text-plum-400 dark:text-plum-500">អ៊ីមែល</dt>
          <dd class="text-plum-800 dark:text-plum-200">{{ $order->customer_email }}</dd>
        </div>
      @endif
    </dl>
    @if ($order->notes)
      <div class="mt-4 border-t border-plum-100 pt-4 dark:border-plum-800">
        <dt class="text-xs text-plum-400 dark:text-plum-500">កំណត់ចំណាំ</dt>
        <dd class="mt-1 text-sm text-plum-700 dark:text-plum-300">{{ $order->notes }}</dd>
      </div>
    @endif
  </div>

  @if ($order->status === 'pending_payment')
    <div class="mt-6 flex items-start gap-3 rounded-lg border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
      <x-app-icon name="chat" class="mt-0.5 h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
      <p class="text-xs leading-relaxed text-plum-700 dark:text-plum-300">
        ក្រុមការងាររបស់យើងនឹងទាក់ទងអ្នកទៅកាន់លេខ <strong>{{ $order->customer_phone }}</strong> ក្នុងពេលឆាប់ៗនេះ ដើម្បីបញ្ជាក់ការទូទាត់ប្រាក់ និងដឹកជញ្ជូនទំនិញរបស់អ្នក។
      </p>
    </div>
  @endif

  @if ($order->review)
    <div class="mt-6 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
      <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-plum-500 dark:text-plum-400">មតិយោបល់របស់អ្នក</h2>
      <div class="flex gap-0.5 text-gold-500">
        @for ($i = 0; $i < 5; $i++)
          <x-app-icon name="star" class="h-4 w-4 {{ $i < $order->review->rating ? 'fill-current' : 'text-plum-200 dark:text-plum-700' }}" />
        @endfor
      </div>
      @if ($order->review->comment)
        <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $order->review->comment }}&rdquo;</p>
      @endif
    </div>
  @endif
</div>
