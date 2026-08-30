<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
  <div class="mb-8 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white">ផ្ទាំងគ្រប់គ្រង</h1>
      <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">គ្រប់គ្រងការបញ្ជាទិញ និងកម្មវត្ថុអញ្ជើញឌីជីថលរបស់អ្នក</p>
    </div>
    <form method="POST" action="/logout">
      @csrf
      <button type="submit" class="text-sm font-semibold text-plum-500 hover:text-red-600 dark:text-plum-400 dark:hover:text-red-400">
        ចាកចេញ
      </button>
    </form>
  </div>

  <h2 class="mb-3 text-base font-bold text-plum-900 dark:text-white">ការបញ្ជាទិញរបស់ខ្ញុំ</h2>
  @if ($orders->isEmpty())
    <p class="mb-10 text-sm text-plum-500 dark:text-plum-400">អ្នកមិនទាន់មានការបញ្ជាទិញនៅឡើយទេ</p>
  @else
    @php
      $orderStatusStyles = [
        'pending_payment' => 'bg-gold-500/15 text-gold-600 dark:text-gold-400',
        'paid' => 'bg-brand-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300',
        'fulfilled' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
      ];
      $orderStatusLabels = \App\Filament\Resources\Orders\Schemas\OrderForm::STATUSES;
    @endphp
    <div class="mb-10 flex flex-col gap-3">
      @foreach ($orders as $order)
        <div wire:key="order-{{ $order->id }}"
          class="flex items-center justify-between rounded-lg border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
          <div>
            <p class="text-sm font-bold text-plum-900 dark:text-white">{{ $order->order_number }}</p>
            <p class="text-xs text-plum-500 dark:text-plum-400">
              {{ $order->items->count() }} ទំនិញ &middot; {{ $order->created_at->format('d M Y') }}
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-plum-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $orderStatusStyles[$order->status] ?? 'bg-plum-100 text-plum-600' }}">
              {{ $orderStatusLabels[$order->status] ?? $order->status }}
            </span>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <h2 class="mb-3 text-base font-bold text-plum-900 dark:text-white">កម្មវត្ថុអញ្ជើញរបស់ខ្ញុំ</h2>
  @if ($invitations->isEmpty())
    <div class="flex flex-col items-center gap-4 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
      <x-app-icon name="envelope" class="h-14 w-14 text-plum-200 dark:text-plum-700" />
      <p class="text-base font-medium text-plum-500 dark:text-plum-400">អ្នកមិនទាន់មានកម្មវត្ថុអញ្ជើញនៅឡើយទេ</p>
      <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
        ជ្រើសរើសគំរូ
      </a>
    </div>
  @else
    <div class="flex flex-col gap-3">
      @foreach ($invitations as $invitation)
        <div wire:key="invitation-{{ $invitation->id }}"
          class="flex items-center justify-between rounded-lg border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
          <div>
            <p class="text-sm font-bold text-plum-900 dark:text-white">{{ $invitation->template->name }}</p>
            <p class="text-xs text-plum-500 dark:text-plum-400">
              {{ $invitation->recipients()->count() }} / {{ $invitation->max_recipients }} អ្នកទទួល
              @if ($invitation->expires_at)
                &middot; ផុតកំណត់ {{ $invitation->expires_at->format('d M Y') }}
              @endif
            </p>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
