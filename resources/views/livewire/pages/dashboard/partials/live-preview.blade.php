{{-- LIVE PREVIEW — a simple invitation-shaped wireframe, the SAME layout for every template
     (partials/live-preview-page.blade.php), showing only the fields this template uses. It shows WHAT
     the customer typed, updated while they type, in the Roumdoul colours. It never saves anything:
     the real design is the «Preview» button, and Save stays the only way to keep changes.

     Built from the small components in resources/views/components/preview/. The data they read:
     App\Support\Invitations\LivePreviewData.

     The mapping is the field key: the form's input has name="groom_name" and every slot for it here has
     data-preview-slot="groom_name". resources/js/modules/livePreview.js copies values across.

     Laptop (xl and up): drawn inside an iPhone frame (pure HTML + CSS), sticky beside the form.
     Phone: hidden until «Live preview» next to Save is tapped, then the same element covers the screen
     without the frame (the phone is the frame). Styles: «Live preview» in resources/css/app.css.

     wire:ignore — Livewire must not re-render this after a save: the slots already show what was typed. --}}
@php
  $preview = $this->livePreviewData();
@endphp

<aside id="live-preview" class="live-preview" wire:ignore aria-label="{{ __('Live preview') }}"
  x-init="window.livePreview && window.livePreview.start()"
  {{-- texts the script needs, in the customer's language --}}
  data-locale="{{ app()->getLocale() }}"
  data-text-days-left="{{ __(':count days left') }}"
  data-text-one-day-left="{{ __('1 day left') }}"
  data-text-today="{{ __('Today is the day') }}"
  data-text-passed="{{ __('This day has passed') }}">

  {{-- Phone only (full screen): title + Close --}}
  <div class="live-preview-bar">
    <p class="flex items-center gap-2 text-base font-bold text-white">
      <x-app-icon name="eye" class="h-5 w-5 text-brand-400" /> {{ __('Live preview') }}
    </p>
    <button type="button" data-live-preview-close
      class="flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
      <x-app-icon name="x-mark" class="h-4 w-4" /> {{ __('Close') }}
    </button>
  </div>

  {{-- The iPhone. On phones only .live-preview-scroll shows; the rest is the frame. --}}
  <div class="live-preview-phone">
    <span class="phone-side-button phone-side-button--silent" aria-hidden="true"></span>
    <span class="phone-side-button phone-side-button--volume-up" aria-hidden="true"></span>
    <span class="phone-side-button phone-side-button--volume-down" aria-hidden="true"></span>
    <span class="phone-side-button phone-side-button--power" aria-hidden="true"></span>

    <div class="phone-screen">
      {{-- status bar: time on the left, signal / Wi-Fi / battery on the right --}}
      <div class="phone-status-bar" aria-hidden="true">
        <span class="phone-time">9:41</span>
        <span class="phone-icons">
          <svg viewBox="0 0 18 12" width="18" height="12"><rect x="0" y="8" width="3" height="4" rx="1" /><rect x="5" y="5.5" width="3" height="6.5" rx="1" /><rect x="10" y="3" width="3" height="9" rx="1" /><rect x="15" y="0" width="3" height="12" rx="1" /></svg>
          <svg viewBox="0 0 16 12" width="16" height="12"><path d="M8 11.5 10.4 9a3.4 3.4 0 0 0-4.8 0Z" /><path d="M3.6 7a6.3 6.3 0 0 1 8.8 0l1.5-1.5a8.4 8.4 0 0 0-11.8 0Z" /><path d="M.7 4.1a10.4 10.4 0 0 1 14.6 0L16.8 2.6a12.5 12.5 0 0 0-17.6 0Z" /></svg>
          <svg viewBox="0 0 27 12" width="27" height="12"><rect x=".5" y=".5" width="22" height="11" rx="3" fill="none" stroke="currentColor" opacity=".4" /><rect x="2" y="2" width="19" height="8" rx="1.8" /><rect x="24" y="4" width="2" height="4" rx="1" opacity=".4" /></svg>
        </span>
      </div>
      <span class="phone-island" aria-hidden="true"></span>

      {{-- The preview itself: the only part that scrolls --}}
      <div class="live-preview-scroll" data-preview-scroll>
        <x-preview.screen :preview="$preview">
          @include('livewire.pages.dashboard.partials.live-preview-page')
        </x-preview.screen>
      </div>

      <span class="phone-home-bar" aria-hidden="true"></span>
    </div>
  </div>
</aside>
