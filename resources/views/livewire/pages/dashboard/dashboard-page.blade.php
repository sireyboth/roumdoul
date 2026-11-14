@php
  $orderStatusStyles = [
    'pending_payment' => 'bg-gold-500/15 text-gold-700 ring-gold-500/30 dark:text-gold-400',
    'paid' => 'bg-brand-50 text-brand-700 ring-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300',
    'fulfilled' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400',
    'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400',
  ];
  $invitationStatusStyles = [
    'active' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400',
    'unpaid' => 'bg-gold-500/15 text-gold-700 ring-gold-500/30 dark:text-gold-400',
    'expired' => 'bg-plum-100 text-plum-600 ring-plum-400/30 dark:bg-plum-800 dark:text-plum-300',
  ];
  $input = 'w-full rounded-xl border border-plum-200 bg-white text-sm text-plum-800 placeholder:text-plum-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-100';
  $hasInvitationFilters = $invitationSearch !== '' || $invitationStatus !== '' || $invitationSort !== 'newest';
  $hasOrderFilters = $orderSearch !== '' || $orderStatus !== '';
@endphp

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8" x-data="{ tab: $wire.entangle('tab').live }">

  {{-- ───────── Header ───────── --}}
  <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 p-6 text-white shadow-xl shadow-brand-900/10 sm:p-8">
    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-20 right-24 h-48 w-48 rounded-full bg-gold-400/20 blur-2xl"></div>

    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-4">
        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/15 text-xl font-extrabold uppercase ring-1 ring-white/25 backdrop-blur">
          {{ mb_substr($customer->name ?? 'R', 0, 1) }}
        </div>
        <div>
          <p class="text-sm font-medium text-white/75">{{ __('Welcome back,') }}</p>
          <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $customer->name }}</h1>
          <p class="mt-0.5 text-sm text-white/75">{{ __('Manage your orders and digital invitations') }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a href="/shop" wire:navigate
          class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-brand-700 shadow-sm transition hover:bg-brand-50">
          <x-app-icon name="sparkles" class="h-4 w-4" /> {{ __('Choose a template') }}
        </a>
        <form method="POST" action="/logout">
          @csrf
          <button type="submit" title="{{ __('Log out') }}"
            class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/20">
            <x-app-icon name="logout" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('Log out') }}</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  {{-- ───────── Stats ───────── --}}
  <div class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
    @foreach ([
      ['icon' => 'envelope', 'label' => __('Invitations'), 'value' => $stats['invitations'], 'hint' => __(':count active', ['count' => $stats['active']]), 'tone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300'],
      ['icon' => 'users', 'label' => __('Guests invited'), 'value' => number_format($stats['guests']), 'hint' => __(':count opened', ['count' => number_format($stats['opened'])]), 'tone' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300'],
      ['icon' => 'cart', 'label' => __('Orders'), 'value' => $stats['orders'], 'hint' => $stats['pending'] ? __(':count awaiting payment', ['count' => $stats['pending']]) : __('All paid up'), 'tone' => 'bg-gold-500/15 text-gold-600 dark:text-gold-400'],
      ['icon' => 'check-circle', 'label' => __('Total spent'), 'value' => '$'.number_format($stats['spent'], 2), 'hint' => __('Paid orders'), 'tone' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400'],
    ] as $card)
      <div class="rounded-2xl border border-plum-200/70 bg-white p-4 shadow-sm dark:border-plum-800 dark:bg-plum-900 sm:p-5">
        <div class="flex items-center justify-between">
          <p class="text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">{{ $card['label'] }}</p>
          <span class="grid h-9 w-9 place-items-center rounded-xl {{ $card['tone'] }}">
            <x-app-icon :name="$card['icon']" class="h-5 w-5" />
          </span>
        </div>
        <p class="mt-3 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">{{ $card['value'] }}</p>
        <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">{{ $card['hint'] }}</p>
      </div>
    @endforeach
  </div>

  {{-- ───────── Tabs ───────── --}}
  <div class="mb-5 flex items-center gap-1 rounded-2xl border border-plum-200/70 bg-white p-1 shadow-sm dark:border-plum-800 dark:bg-plum-900 sm:inline-flex" role="tablist">
    @foreach (['invitations' => [__('My invitations'), $stats['invitations'], 'envelope'], 'orders' => [__('My orders'), $stats['orders'], 'cart']] as $key => [$label, $count, $icon])
      <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
        :class="tab === '{{ $key }}' ? 'bg-brand-600 text-white shadow-sm' : 'text-plum-600 hover:bg-plum-100 dark:text-plum-300 dark:hover:bg-plum-800'"
        @class([
          'flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition sm:flex-none',
          'bg-brand-600 text-white shadow-sm' => $tab === $key,
        ])>
        <x-app-icon :name="$icon" class="h-4 w-4" />
        {{ $label }}
        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold"
          :class="tab === '{{ $key }}' ? 'bg-white/20 text-white' : 'bg-plum-100 text-plum-600 dark:bg-plum-800 dark:text-plum-300'">{{ $count }}</span>
      </button>
    @endforeach
  </div>

  {{-- ═════════════════ INVITATIONS ═════════════════ --}}
  <section x-show="tab === 'invitations'" @if ($tab !== 'invitations') style="display: none" @endif>

    @if ($stats['invitations'] === 0)
      <div class="flex flex-col items-center gap-4 rounded-3xl border border-dashed border-plum-300 bg-white/50 px-6 py-20 text-center dark:border-plum-700 dark:bg-plum-900/40">
        <span class="grid h-16 w-16 place-items-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
          <x-app-icon name="envelope" class="h-8 w-8" />
        </span>
        <div>
          <p class="text-base font-bold text-plum-800 dark:text-white">{{ __("You don't have any invitations yet") }}</p>
          <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ __('Pick a template, fill in your details and send a personal link to every guest.') }}</p>
        </div>
        <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
          {{ __('Choose a template') }}
        </a>
      </div>
    @else
      {{-- Toolbar --}}
      <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
          <input type="search" wire:model.live.debounce.350ms="invitationSearch"
            placeholder="{{ __('Search by template or guest name…') }}" class="{{ $input }} py-2.5 pl-10 pr-3" />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <div class="flex gap-1 overflow-x-auto rounded-xl bg-plum-100 p-1 dark:bg-plum-800/60">
            @foreach (['' => __('All')] + $invitationStatusLabels as $value => $label)
              <button type="button" wire:click="$set('invitationStatus', '{{ $value }}')" wire:key="istatus-{{ $value ?: 'all' }}"
                @class([
                  'whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                  'bg-white text-plum-900 shadow-sm dark:bg-plum-900 dark:text-white' => $invitationStatus === $value,
                  'text-plum-500 hover:text-plum-800 dark:text-plum-400 dark:hover:text-white' => $invitationStatus !== $value,
                ])>{{ $label }}</button>
            @endforeach
          </div>

          <label class="relative">
            <span class="sr-only">{{ __('Sort by') }}</span>
            <select wire:model.live="invitationSort" class="{{ $input }} py-2 pl-3 pr-8 font-medium">
              @foreach ($invitationSortLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          </label>

          @if ($hasInvitationFilters)
            <button type="button" wire:click="clearInvitationFilters"
              class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-plum-500 hover:text-brand-600 dark:text-plum-400">
              <x-app-icon name="x-mark" class="h-3.5 w-3.5" /> {{ __('Clear filters') }}
            </button>
          @endif
        </div>
      </div>

      {{-- Grid --}}
      <div class="relative" wire:loading.class="pointer-events-none opacity-50"
        wire:target="invitationSearch,invitationStatus,invitationSort,invitationPerPage,clearInvitationFilters,gotoPage,nextPage,previousPage">
        @if ($invitations->isEmpty())
          <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-plum-300 py-16 text-center dark:border-plum-700">
            <x-app-icon name="search" class="h-10 w-10 text-plum-300 dark:text-plum-600" />
            <p class="text-sm font-medium text-plum-500 dark:text-plum-400">{{ __('No invitations match your filters.') }}</p>
            <button type="button" wire:click="clearInvitationFilters" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Clear filters') }}</button>
          </div>
        @else
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($invitations as $invitation)
              @php
                $state = ! $invitation->isPaid() ? 'unpaid' : ($invitation->isExpired() ? 'expired' : 'active');
                $cover = $invitation->field_values['cover_image'] ?? null;
                $cover = is_string($cover) && $cover !== '' ? $cover : $invitation->template->service?->image_path;
                $coverUrl = $cover ? (str_starts_with($cover, 'http') ? $cover : \Illuminate\Support\Facades\Storage::disk('s3')->url($cover)) : null;
                $usage = $invitation->max_recipients ? min(100, round($invitation->recipients_count / $invitation->max_recipients * 100)) : 0;
                $daysLeft = $invitation->expires_at && ! $invitation->isExpired() ? (int) now()->diffInDays($invitation->expires_at) : null;
              @endphp
              {{-- Opens the invitation's own sidebar workspace in a new tab, so this overview stays put. --}}
              <a href="/dashboard/invitations/{{ $invitation->id }}" target="_blank" wire:key="invitation-{{ $invitation->id }}"
                class="group flex flex-col overflow-hidden rounded-2xl border border-plum-200/70 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5 dark:border-plum-800 dark:bg-plum-900 dark:hover:border-brand-500/40">
                <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-brand-100 via-brand-50 to-gold-300/40 dark:from-brand-900/40 dark:via-plum-900 dark:to-plum-800">
                  @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                  @else
                    <div class="grid h-full place-items-center">
                      <x-app-icon name="envelope" class="h-12 w-12 text-brand-300 dark:text-brand-700" />
                    </div>
                  @endif
                  <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/40 to-transparent"></div>
                  <span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset backdrop-blur {{ $invitationStatusStyles[$state] }}">
                    {{ $invitationStatusLabels[$state] }}
                  </span>
                  <span class="absolute bottom-3 left-3 rounded-full bg-black/40 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
                    {{ $invitation->template->name }}
                  </span>
                </div>

                <div class="flex flex-1 flex-col gap-3 p-4">
                  <div>
                    <p class="line-clamp-1 text-base font-bold text-plum-900 dark:text-white">{{ \App\Livewire\Pages\Dashboard\DashboardPage::invitationTitle($invitation) }}</p>
                    <p class="mt-0.5 text-xs text-plum-500 dark:text-plum-400">
                      {{ __('Created :date', ['date' => $invitation->created_at->format('d M Y')]) }}
                      @if ($invitation->expires_at)
                        &middot;
                        <span @class(['font-semibold text-red-600 dark:text-red-400' => $daysLeft !== null && $daysLeft <= 14])>
                          {{ $invitation->isExpired() ? __('Expired :date', ['date' => $invitation->expires_at->format('d M Y')]) : __('Expires :date', ['date' => $invitation->expires_at->format('d M Y')]) }}
                        </span>
                      @endif
                    </p>
                  </div>

                  <div>
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                      <span class="font-medium text-plum-600 dark:text-plum-300">{{ __(':count / :max recipients', ['count' => $invitation->recipients_count, 'max' => $invitation->max_recipients]) }}</span>
                      <span class="text-plum-400">{{ $usage }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-plum-100 dark:bg-plum-800">
                      <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400" style="width: {{ $usage }}%"></div>
                    </div>
                  </div>

                  <div class="mt-auto flex items-center justify-between border-t border-plum-100 pt-3 dark:border-plum-800">
                    <div class="flex items-center gap-3 text-xs text-plum-500 dark:text-plum-400">
                      <span class="inline-flex items-center gap-1" title="{{ __('Opened') }}"><x-app-icon name="eye" class="h-3.5 w-3.5" /> {{ $invitation->opened_count }}</span>
                      <span class="inline-flex items-center gap-1" title="{{ __('Attending') }}"><x-app-icon name="check-circle" class="h-3.5 w-3.5" /> {{ $invitation->attending_count }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 transition group-hover:gap-1.5 dark:text-brand-400">
                      {{ __('Manage') }} <x-app-icon name="external" class="h-3.5 w-3.5" />
                    </span>
                  </div>
                </div>
              </a>
            @endforeach
          </div>
        @endif

        @include('livewire.pages.dashboard.partials.pagination', [
          'paginator' => $invitations,
          'pageName' => 'invitationsPage',
          'perPageModel' => 'invitationPerPage',
          'perPageOptions' => \App\Livewire\Pages\Dashboard\DashboardPage::PER_PAGE_OPTIONS,
        ])
      </div>
    @endif
  </section>

  {{-- ═════════════════ ORDERS ═════════════════ --}}
  <section x-show="tab === 'orders'" @if ($tab !== 'orders') style="display: none" @endif>

    @if ($stats['orders'] === 0)
      <div class="flex flex-col items-center gap-4 rounded-3xl border border-dashed border-plum-300 bg-white/50 px-6 py-20 text-center dark:border-plum-700 dark:bg-plum-900/40">
        <span class="grid h-16 w-16 place-items-center rounded-2xl bg-gold-500/15 text-gold-600">
          <x-app-icon name="cart" class="h-8 w-8" />
        </span>
        <p class="text-base font-bold text-plum-800 dark:text-white">{{ __("You don't have any orders yet") }}</p>
        <a href="/shop" wire:navigate class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
          {{ __('Browse the shop') }}
        </a>
      </div>
    @else
      {{-- Toolbar --}}
      <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
          <input type="search" wire:model.live.debounce.350ms="orderSearch"
            placeholder="{{ __('Search by order number or product…') }}" class="{{ $input }} py-2.5 pl-10 pr-3" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex gap-1 overflow-x-auto rounded-xl bg-plum-100 p-1 dark:bg-plum-800/60">
            @foreach (['' => __('All')] + $orderStatusLabels as $value => $label)
              <button type="button" wire:click="$set('orderStatus', '{{ $value }}')" wire:key="ostatus-{{ $value ?: 'all' }}"
                @class([
                  'whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                  'bg-white text-plum-900 shadow-sm dark:bg-plum-900 dark:text-white' => $orderStatus === $value,
                  'text-plum-500 hover:text-plum-800 dark:text-plum-400 dark:hover:text-white' => $orderStatus !== $value,
                ])>{{ $label }}</button>
            @endforeach
          </div>
          {{-- Sorting on phones, where the table headers aren't shown --}}
          <label class="md:hidden">
            <span class="sr-only">{{ __('Sort by') }}</span>
            <select wire:change="sortOrdersBy($event.target.value)" class="{{ $input }} py-2 pl-3 pr-8 font-medium">
              @foreach (['created_at' => __('Date'), 'total' => __('Total'), 'order_number' => __('Order'), 'status' => __('Status')] as $col => $label)
                <option value="{{ $col }}" @selected($orderSort === $col)>{{ __('Sort: :field', ['field' => $label]) }}</option>
              @endforeach
            </select>
          </label>
          @if ($hasOrderFilters)
            <button type="button" wire:click="clearOrderFilters"
              class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-plum-500 hover:text-brand-600 dark:text-plum-400">
              <x-app-icon name="x-mark" class="h-3.5 w-3.5" /> {{ __('Clear filters') }}
            </button>
          @endif
        </div>
      </div>

      <div wire:loading.class="pointer-events-none opacity-50"
        wire:target="orderSearch,orderStatus,orderPerPage,sortOrdersBy,clearOrderFilters,gotoPage,nextPage,previousPage">
        @if ($orders->isEmpty())
          <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-plum-300 py-16 text-center dark:border-plum-700">
            <x-app-icon name="search" class="h-10 w-10 text-plum-300 dark:text-plum-600" />
            <p class="text-sm font-medium text-plum-500 dark:text-plum-400">{{ __('No orders match your filters.') }}</p>
            <button type="button" wire:click="clearOrderFilters" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Clear filters') }}</button>
          </div>
        @else
          {{-- Desktop table --}}
          <div class="hidden overflow-hidden rounded-2xl border border-plum-200/70 bg-white shadow-sm dark:border-plum-800 dark:bg-plum-900 md:block">
            <table class="w-full text-left text-sm">
              <thead class="border-b border-plum-200/70 bg-plum-50/80 text-xs uppercase tracking-wide text-plum-500 dark:border-plum-800 dark:bg-plum-800/40 dark:text-plum-400">
                <tr>
                  @foreach (['order_number' => __('Order'), 'created_at' => __('Date'), 'items' => __('Items'), 'status' => __('Status'), 'total' => __('Total')] as $col => $label)
                    <th scope="col" @class(['px-5 py-3 font-semibold', 'text-right' => $col === 'total'])
                      @if ($orderSort === $col) aria-sort="{{ $orderDirection === 'asc' ? 'ascending' : 'descending' }}" @endif>
                      @if ($col === 'items')
                        {{ $label }}
                      @else
                        <button type="button" wire:click="sortOrdersBy('{{ $col }}')"
                          @class(['group inline-flex items-center gap-1 uppercase tracking-wide hover:text-plum-900 dark:hover:text-white', 'text-brand-600 dark:text-brand-400' => $orderSort === $col])>
                          {{ $label }}
                          <span @class(['transition', 'opacity-0 group-hover:opacity-50' => $orderSort !== $col])>
                            <x-app-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform {{ $orderSort === $col && $orderDirection === 'asc' ? 'rotate-180' : '' }}" />
                          </span>
                        </button>
                      @endif
                    </th>
                  @endforeach
                  <th class="w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-plum-100 dark:divide-plum-800">
                @foreach ($orders as $order)
                  <tr wire:key="order-{{ $order->id }}" class="group cursor-pointer transition hover:bg-brand-50/40 dark:hover:bg-plum-800/40"
                    onclick="Livewire.navigate('/dashboard/orders/{{ $order->id }}')">
                    <td class="px-5 py-3.5">
                      <a href="/dashboard/orders/{{ $order->id }}" wire:navigate class="font-bold text-plum-900 group-hover:text-brand-600 dark:text-white">{{ $order->order_number }}</a>
                    </td>
                    <td class="px-5 py-3.5 text-plum-600 dark:text-plum-300">
                      {{ $order->created_at->format('d M Y') }}
                      <span class="block text-xs text-plum-400">{{ $order->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-plum-600 dark:text-plum-300">{{ __(':count items', ['count' => $order->items_count]) }}</td>
                    <td class="px-5 py-3.5">
                      <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $orderStatusStyles[$order->status] ?? 'bg-plum-100 text-plum-600 ring-plum-300' }}">
                        {{ $orderStatusLabels[$order->status] ?? $order->status }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-right font-bold text-plum-900 dark:text-white">${{ number_format($order->total, 2) }}</td>
                    <td class="pr-4"><x-app-icon name="chevron-right" class="h-4 w-4 text-plum-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500" /></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          {{-- Mobile cards --}}
          <div class="flex flex-col gap-3 md:hidden">
            @foreach ($orders as $order)
              <a href="/dashboard/orders/{{ $order->id }}" wire:navigate wire:key="order-m-{{ $order->id }}"
                class="flex items-center justify-between gap-3 rounded-2xl border border-plum-200/70 bg-white p-4 shadow-sm transition hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold text-plum-900 dark:text-white">{{ $order->order_number }}</p>
                  <p class="text-xs text-plum-500 dark:text-plum-400">
                    {{ __(':count items', ['count' => $order->items_count]) }} &middot; {{ $order->created_at->format('d M Y') }}
                  </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1.5">
                  <span class="text-sm font-bold text-plum-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $orderStatusStyles[$order->status] ?? 'bg-plum-100 text-plum-600 ring-plum-300' }}">
                    {{ $orderStatusLabels[$order->status] ?? $order->status }}
                  </span>
                </div>
              </a>
            @endforeach
          </div>
        @endif

        @include('livewire.pages.dashboard.partials.pagination', [
          'paginator' => $orders,
          'pageName' => 'ordersPage',
          'perPageModel' => 'orderPerPage',
          'perPageOptions' => \App\Livewire\Pages\Dashboard\DashboardPage::PER_PAGE_OPTIONS,
        ])
      </div>
    @endif
  </section>
</div>
