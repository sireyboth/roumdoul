{{--
  «Add more» section of the invitation workspace: buy extras for this invitation (InvitationAddons). Each card buys
  one thing and opens its payment page (KHQR modal). Prices come from the invitation's plan; a card only shows when
  that add-on is sold for the plan. Wide layout: cards sit two/three across on desktop, stack on phones.
--}}
@php
  $o = $this->addonOptions();
  $pending = $this->pendingAddonOrders();
  $money = fn ($v) => '$'.number_format((float) $v, 2);
  $card = 'flex flex-col rounded-2xl border border-plum-200 bg-white p-5 dark:border-plum-800 dark:bg-plum-900';
  $buy = 'mt-auto inline-flex items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:opacity-60';
  $chip = 'rounded-full border border-plum-200 px-3 py-1 text-xs font-semibold text-plum-600 transition-colors hover:border-brand-400 hover:text-brand-700 dark:border-plum-700 dark:text-plum-300';
  $stepBtn = 'grid h-9 w-9 place-items-center rounded-full border border-plum-200 text-lg font-bold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300';
@endphp

<p class="mt-1 max-w-2xl text-sm text-plum-500 dark:text-plum-400">
  {{ __('Need more? Add it to this invitation — everything you already filled in stays. Pay with KHQR and it switches on as soon as we confirm your payment.') }}
</p>

@if ($pending->isNotEmpty())
  <div class="mt-6 flex flex-col gap-2">
    @foreach ($pending as $order)
      <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gold-400/40 bg-gold-50 px-4 py-3 dark:border-gold-400/20 dark:bg-plum-800">
        <div class="flex items-center gap-3">
          <x-app-icon name="clock" class="h-5 w-5 shrink-0 text-gold-600 dark:text-gold-400" />
          <div>
            <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $order->items->pluck('service_name_snapshot')->map(fn ($n) => \Illuminate\Support\Str::before($n, ' — '))->join(', ') }}</p>
            <p class="text-xs text-plum-500 dark:text-plum-400">{{ __('Waiting for your payment') }} · {{ $money($order->total) }} · #{{ $order->order_number }}</p>
          </div>
        </div>
        <a href="{{ route('order.confirmation', $order) }}" wire:navigate class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Pay now') }}</a>
      </div>
    @endforeach
  </div>
@endif

<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
  {{-- more guests --}}
  @if ($o['guests'])
    @php $n = max(1, (int) ($addonQty['guests'] ?? 10)); @endphp
    <div class="{{ $card }}">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-app-icon name="user-plus" class="h-5 w-5" /></span>
        <div>
          <h3 class="text-base font-bold text-plum-900 dark:text-white">{{ __('More guests') }}</h3>
          <p class="text-xs text-plum-500 dark:text-plum-400">{{ __('Now :n guest links · :price each', ['n' => $o['guests']['current'], 'price' => $money($o['guests']['price'])]) }}</p>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-3">
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.guests', {{ max(1, $n - 10) }})" aria-label="{{ __('Fewer') }}">−</button>
        <input type="number" min="1" max="{{ $o['guests']['max'] }}" wire:model.live.debounce.400ms="addonQty.guests" value="{{ $n }}"
          class="w-24 rounded-lg border border-plum-200 px-3 py-2 text-center text-lg font-bold dark:border-plum-700 dark:bg-plum-800" />
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.guests', {{ min($o['guests']['max'], $n + 10) }})" aria-label="{{ __('More') }}">+</button>
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        @foreach ([10, 25, 50, 100] as $q)<button type="button" class="{{ $chip }}" wire:click="$set('addonQty.guests', {{ $q }})">+{{ $q }}</button>@endforeach
      </div>
      <p class="mt-4 text-sm text-plum-600 dark:text-plum-300">{{ __(':from → :to guest links', ['from' => $o['guests']['current'], 'to' => $o['guests']['current'] + $n]) }}</p>
      <button type="button" class="{{ $buy }} mt-4" wire:click="buyAddon('guests')" wire:loading.attr="disabled" wire:target="buyAddon">
        {{ __('Add :n guests · :total', ['n' => $n, 'total' => $money($n * $o['guests']['price'])]) }}
      </button>
    </div>
  @endif

  {{-- more gallery photos --}}
  @if ($o['photos'])
    @php $n = max(1, min($o['photos']['max'], (int) ($addonQty['photos'] ?? 5))); @endphp
    <div class="{{ $card }}">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-app-icon name="photo" class="h-5 w-5" /></span>
        <div>
          <h3 class="text-base font-bold text-plum-900 dark:text-white">{{ __('More gallery photos') }}</h3>
          <p class="text-xs text-plum-500 dark:text-plum-400">{{ __('Now :n photos · :price each', ['n' => $o['photos']['current'], 'price' => $money($o['photos']['price'])]) }}</p>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-3">
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.photos', {{ max(1, $n - 5) }})" aria-label="{{ __('Fewer') }}">−</button>
        <input type="number" min="1" max="{{ $o['photos']['max'] }}" wire:model.live.debounce.400ms="addonQty.photos" value="{{ $n }}"
          class="w-24 rounded-lg border border-plum-200 px-3 py-2 text-center text-lg font-bold dark:border-plum-700 dark:bg-plum-800" />
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.photos', {{ min($o['photos']['max'], $n + 5) }})" aria-label="{{ __('More') }}">+</button>
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        @foreach (array_filter([5, 10, 20], fn ($q) => $q <= $o['photos']['max']) as $q)<button type="button" class="{{ $chip }}" wire:click="$set('addonQty.photos', {{ $q }})">+{{ $q }}</button>@endforeach
      </div>
      <p class="mt-4 text-sm text-plum-600 dark:text-plum-300">{{ __(':from → :to photos', ['from' => $o['photos']['current'], 'to' => $o['photos']['current'] + $n]) }}</p>
      <button type="button" class="{{ $buy }} mt-4" wire:click="buyAddon('photos')" wire:loading.attr="disabled" wire:target="buyAddon">
        {{ __('Add :n photos · :total', ['n' => $n, 'total' => $money($n * $o['photos']['price'])]) }}
      </button>
    </div>
  @endif

  {{-- extend time --}}
  @if ($o['extend'])
    @php $n = max(1, min($o['extend']['max'], (int) ($addonQty['extend'] ?? 1))); $months = $n * $o['extend']['months']; $from = $o['extend']['expires_at']->isFuture() ? $o['extend']['expires_at'] : now(); @endphp
    <div class="{{ $card }}">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300"><x-app-icon name="calendar" class="h-5 w-5" /></span>
        <div>
          <h3 class="text-base font-bold text-plum-900 dark:text-white">{{ __('Extend time') }}</h3>
          <p class="text-xs text-plum-500 dark:text-plum-400">{{ __('Expires :date · :price per :m months', ['date' => $o['extend']['expires_at']->format('d M Y'), 'price' => $money($o['extend']['price']), 'm' => $o['extend']['months']]) }}</p>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-3">
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.extend', {{ max(1, $n - 1) }})" aria-label="{{ __('Fewer') }}">−</button>
        <span class="w-24 text-center text-lg font-bold text-plum-900 dark:text-white">+{{ $months }} {{ __('months') }}</span>
        <button type="button" class="{{ $stepBtn }}" wire:click="$set('addonQty.extend', {{ min($o['extend']['max'], $n + 1) }})" aria-label="{{ __('More') }}">+</button>
      </div>
      <p class="mt-4 text-sm text-plum-600 dark:text-plum-300">{{ __('New expiry: :date', ['date' => $from->copy()->addMonths($months)->format('d M Y')]) }}</p>
      <button type="button" class="{{ $buy }} mt-4" wire:click="buyAddon('extend')" wire:loading.attr="disabled" wire:target="buyAddon">
        {{ __('Extend :m months · :total', ['m' => $months, 'total' => $money($n * $o['extend']['price'])]) }}
      </button>
    </div>
  @endif
</div>

{{-- unlock single features --}}
@if ($o['features'])
  <h2 class="mt-10 text-base font-bold text-plum-900 dark:text-white">{{ __('Unlock a feature') }}</h2>
  <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($o['features'] as $key => $feature)
      <div class="flex items-center justify-between gap-3 rounded-2xl border border-plum-200 bg-white px-4 py-3 dark:border-plum-800 dark:bg-plum-900">
        <span class="flex items-center gap-2 text-sm font-semibold text-plum-800 dark:text-plum-100"><x-app-icon name="lock-closed" class="h-4 w-4 text-plum-400" /> {{ $feature['label'] }}</span>
        <button type="button" class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
          wire:click="buyAddon('feature', @js($key))" wire:loading.attr="disabled" wire:target="buyAddon">{{ __('Unlock · :price', ['price' => $money($feature['price'])]) }}</button>
      </div>
    @endforeach
  </div>
@endif

{{-- upgrade the plan --}}
@if ($o['upgrades'])
  <h2 class="mt-10 text-base font-bold text-plum-900 dark:text-white">{{ __('Upgrade your plan') }}</h2>
  <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ __('Pay only the difference. Your guests, photos and add-ons stay.') }}</p>
  <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($o['upgrades'] as $up)
      <div class="{{ $card }}">
        <h3 class="text-base font-bold text-plum-900 dark:text-white">{{ $up['label'] }}</h3>
        <ul class="mt-3 flex flex-col gap-1.5 text-sm text-plum-600 dark:text-plum-300">
          @if ($up['max_recipients'])<li class="flex gap-2"><x-app-icon name="check-circle" class="h-4 w-4 shrink-0 text-green-600" /> {{ __(':n guest links', ['n' => $up['max_recipients']]) }}</li>@endif
          @if ($up['max_gallery_photos'])<li class="flex gap-2"><x-app-icon name="check-circle" class="h-4 w-4 shrink-0 text-green-600" /> {{ __(':n gallery photos', ['n' => $up['max_gallery_photos']]) }}</li>@endif
          @foreach ($up['new_features'] as $label)<li class="flex gap-2"><x-app-icon name="check-circle" class="h-4 w-4 shrink-0 text-green-600" /> {{ $label }}</li>@endforeach
        </ul>
        <button type="button" class="{{ $buy }} mt-5" wire:click="buyAddon('upgrade', '{{ $up['id'] }}')" wire:loading.attr="disabled" wire:target="buyAddon">
          {{ __('Upgrade · :price', ['price' => $money($up['price'])]) }}
        </button>
      </div>
    @endforeach
  </div>
@endif
