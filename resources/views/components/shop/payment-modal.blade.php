{{--
  "Pay with KHQR" for a pending_payment order, as a 3D glass modal: the shop's KHQR, account name + ID, the
  exact amount and the order number to write in the transfer note. No screenshot is asked for — the admin
  checks the payment in the bank app and marks the order paid in Filament.
  On the page it leaves a small «Waiting for your payment · Pay now» card that (re)opens the modal.
  Details: config('roumdoul.payment') (ROUMDOUL_PAY_* in .env). Styles: `.pay-*` in resources/css/app.css.

    <x-shop.payment-modal :order="$order" auto-open />     (opens by itself — the order confirmation page)
    <x-shop.payment-modal :order="$order" />               (opens from «Pay now» — the dashboard order page)
--}}
@props(['order', 'autoOpen' => false])
@php
    $pay = config('roumdoul.payment');
    $qrPath = trim((string) ($pay['qr_image'] ?? ''), '/');
    $qrUrl = $qrPath !== '' && is_file(public_path($qrPath)) ? asset($qrPath).'?v='.filemtime(public_path($qrPath)) : null;
    $amount = '$'.number_format((float) $order->total, 2);
    $accountId = (string) ($pay['account_id'] ?? '');
    $titleId = 'pay-title-'.$order->id;
@endphp

<div {{ $attributes }}
  x-data="{
    open: false,
    copied: null,
    copy(key, text) { navigator.clipboard?.writeText(text).then(() => { this.copied = key; setTimeout(() => this.copied = null, 1600) }) },
    tilt(e) {
      if (window.matchMedia('(prefers-reduced-motion: reduce), (hover: none)').matches) return;
      const r = this.$refs.card.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      this.$refs.card.style.setProperty('--ry', ((x - .5) * 10).toFixed(2) + 'deg');
      this.$refs.card.style.setProperty('--rx', ((.5 - y) * 8).toFixed(2) + 'deg');
      this.$refs.card.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
      this.$refs.card.style.setProperty('--my', (y * 100).toFixed(1) + '%');
    },
    untilt() { ['--rx', '--ry'].forEach(p => this.$refs.card.style.setProperty(p, '0deg')) },
  }"
  @if ($autoOpen) x-init="setTimeout(() => open = true, 450)" @endif
  @keydown.escape.window="open = false">

  {{-- on the page: a small reminder that (re)opens the modal --}}
  <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gold-400/40 bg-gold-50 p-4 dark:border-gold-400/20 dark:bg-plum-800">
    <div class="flex items-center gap-3">
      <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gold-100 text-gold-700 dark:bg-gold-400/10 dark:text-gold-400">
        <x-app-icon name="clock" class="h-5 w-5" />
      </span>
      <div>
        <p class="text-sm font-bold text-plum-900 dark:text-white">{{ __('Waiting for your payment') }}</p>
        <p class="text-xs text-plum-500 dark:text-plum-400">{{ __('Pay :amount with KHQR — no screenshot needed.', ['amount' => $amount]) }}</p>
      </div>
    </div>
    <button type="button" @click="open = true"
      class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700">
      {{ __('Pay now') }}
    </button>
  </div>

  {{-- the modal: moved to <body> so the page's enter animation (a transform) can't trap position:fixed --}}
  <template x-teleport="body">
    <div x-show="open" x-cloak class="pay-overlay" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
      x-trap.noscroll.inert="open"
      x-transition:enter="pay-fade-enter" x-transition:enter-start="pay-fade-from" x-transition:enter-end="pay-fade-to"
      x-transition:leave="pay-fade-leave" x-transition:leave-start="pay-fade-to" x-transition:leave-end="pay-fade-from">

      <div class="pay-backdrop" @click="open = false" aria-hidden="true"></div>

      <div class="pay-stage" @click.self="open = false">
        <div x-ref="card" class="pay-card" @pointermove="tilt($event)" @pointerleave="untilt()"
          x-show="open"
          x-transition:enter="pay-card-enter" x-transition:enter-start="pay-card-from" x-transition:enter-end="pay-card-to"
          x-transition:leave="pay-card-leave" x-transition:leave-start="pay-card-to" x-transition:leave-end="pay-card-from">
          <span class="pay-glass" aria-hidden="true"></span>
          <span class="pay-sheen" aria-hidden="true"></span>

          <button type="button" class="pay-x" @click="open = false" aria-label="{{ __('Close') }}">
            <x-app-icon name="x-mark" class="h-5 w-5" />
          </button>

          {{-- phones: stacked (title, QR, details). Wider screens: QR on the left, title + details on the right --}}
          <div class="pay-body">
          <header class="pay-head">
            <p class="pay-kicker">{{ __('Step 2 of 2') }} · KHQR</p>
            <h2 id="{{ $titleId }}" class="pay-title">{{ __('Pay with KHQR') }}</h2>
            <p class="pay-amount">{{ $amount }}</p>
          </header>

          {{-- the QR floats above the glass --}}
          <div class="pay-left">
          <div class="pay-qr">
            @if ($qrUrl)
              <img src="{{ $qrUrl }}" alt="{{ __('KHQR code for :name', ['name' => $pay['account_name']]) }}" draggable="false" />
            @else
              <p class="pay-qr-empty">{{ __('Pay to the account below with any bank app (KHQR).') }}</p>
            @endif
          </div>
          <p class="pay-name">{{ $pay['account_name'] }}</p>

          @if ($qrUrl)
            <a href="{{ $qrUrl }}" download="ROUMDOUL-KHQR.png" class="pay-save">
              <x-app-icon name="download" class="h-4 w-4" /> {{ __('Save QR to your phone') }}
            </a>
            <p class="pay-tip">{{ __('On your phone? Save the QR, then in your bank app tap Scan → choose from gallery.') }}</p>
          @endif
          </div>

          <div class="pay-info">
          <dl class="pay-rows">
            @foreach ([
                ['id', __('Account ID'), $accountId, preg_replace('/\s+/', '', $accountId)],
                ['amount', __('Amount'), $amount, number_format((float) $order->total, 2, '.', '')],
                ['ref', __('Note / remark'), $order->order_number, $order->order_number],
            ] as [$key, $label, $shown, $copyText])
              <div class="pay-row">
                <dt>{{ $label }}</dt>
                <dd>
                  <span>{{ $shown }}</span>
                  <button type="button" class="pay-copy" @click="copy(@js($key), @js($copyText))">
                    <span x-text="copied === @js($key) ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
                  </button>
                </dd>
              </div>
            @endforeach
          </dl>

          <ol class="pay-steps">
            <li>{{ __('Open your bank app (ABA, ACLEDA, Wing, …) and scan the KHQR.') }}</li>
            <li>{{ __('Check the name is :name and pay exactly :amount.', ['name' => $pay['account_name'], 'amount' => $amount]) }}</li>
            <li>{{ __('Write :ref in the note / remark, so we know it\'s your order.', ['ref' => $order->order_number]) }}</li>
          </ol>

          <p class="pay-done">
            <x-app-icon name="check-circle" class="h-4 w-4 shrink-0" />
            <span>{{ __('That\'s all — no need to send a screenshot. We check every payment in our bank app and mark your order as paid as soon as it arrives. You\'ll see it here and your order unlocks automatically.') }}</span>
          </p>
          </div>
          </div>
        </div>
      </div>
    </div>
  </template>
</div>
