<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
  <div class="mb-8 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white">{{ __('Dashboard') }}</h1>
      <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ __('Manage your orders and digital invitations') }}</p>
    </div>
    <form method="POST" action="/logout">
      @csrf
      <button type="submit" class="text-sm font-semibold text-plum-500 hover:text-red-600 dark:text-plum-400 dark:hover:text-red-400">
        {{ __('Log out') }}
      </button>
    </form>
  </div>

  <h2 class="mb-3 text-base font-bold text-plum-900 dark:text-white">{{ __('My orders') }}</h2>
  @if ($orders->isEmpty())
    <p class="mb-10 text-sm text-plum-500 dark:text-plum-400">{{ __("You don't have any orders yet") }}</p>
  @else
    @php
      $orderStatusStyles = [
        'pending_payment' => 'bg-gold-500/15 text-gold-600 dark:text-gold-400',
        'paid' => 'bg-brand-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300',
        'fulfilled' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
      ];
      $orderStatusLabels = [
        'pending_payment' => __('Pending payment'),
        'paid' => __('Paid'),
        'fulfilled' => __('Fulfilled'),
        'cancelled' => __('Cancelled'),
      ];
    @endphp
    <div class="mb-10 flex flex-col gap-3">
      @foreach ($orders as $order)
        <a href="/dashboard/orders/{{ $order->id }}" wire:navigate wire:key="order-{{ $order->id }}"
          class="flex items-center justify-between rounded-lg border border-plum-200 bg-white p-4 transition-colors hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
          <div>
            <p class="text-sm font-bold text-plum-900 dark:text-white">{{ $order->order_number }}</p>
            <p class="text-xs text-plum-500 dark:text-plum-400">
              {{ __(':count items', ['count' => $order->items->count()]) }} &middot; {{ $order->created_at->format('d M Y') }}
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-plum-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $orderStatusStyles[$order->status] ?? 'bg-plum-100 text-plum-600' }}">
              {{ $orderStatusLabels[$order->status] ?? $order->status }}
            </span>
            <x-app-icon name="chevron-right" class="h-4.5 w-4.5 text-plum-300" />
          </div>
        </a>
      @endforeach
    </div>
  @endif

  <h2 class="mb-3 text-base font-bold text-plum-900 dark:text-white">{{ __('My invitations') }}</h2>
  @if ($invitations->isEmpty())
    <div class="flex flex-col items-center gap-4 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
      <x-app-icon name="envelope" class="h-14 w-14 text-plum-200 dark:text-plum-700" />
      <p class="text-base font-medium text-plum-500 dark:text-plum-400">{{ __("You don't have any invitations yet") }}</p>
      <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
        {{ __('Choose a template') }}
      </a>
    </div>
  @else
    <div class="flex flex-col gap-3">
      @foreach ($invitations as $invitation)
        {{-- Opens the invitation's own sidebar workspace in a new tab, so this overview stays put. --}}
        <a href="/dashboard/invitations/{{ $invitation->id }}" target="_blank" wire:key="invitation-{{ $invitation->id }}"
          class="group flex items-center justify-between rounded-lg border border-plum-200 bg-white p-4 transition-colors hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
          <div>
            <p class="text-sm font-bold text-plum-900 dark:text-white">{{ $invitation->template->name }}</p>
            <p class="text-xs text-plum-500 dark:text-plum-400">
              {{ __(':count / :max recipients', ['count' => $invitation->recipients()->count(), 'max' => $invitation->max_recipients]) }}
              @if ($invitation->expires_at)
                &middot; {{ __('Expires :date', ['date' => $invitation->expires_at->format('d M Y')]) }}
              @endif
            </p>
          </div>
          <span class="flex items-center gap-1.5 text-xs font-semibold text-plum-400 group-hover:text-brand-600 dark:group-hover:text-brand-400">
            {{ __('Manage') }} <x-app-icon name="external" class="h-4 w-4" />
          </span>
        </a>
      @endforeach
    </div>
  @endif
</div>
