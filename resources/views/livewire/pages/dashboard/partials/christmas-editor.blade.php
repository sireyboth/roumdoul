{{-- Christmas template editor — replaces the generic field form on the Details tab for
     invitations whose template is invitations/templates/christmas.blade.php.

     Form on the left, live preview on the right (wide screens); form on top, preview below on
     phones. The form edits the same `fieldValues` + save() as every other template; Alpine's
     `xmEditor` (resources/js/modules/christmasEditor.js) splits event_date into Date + Start time,
     maps the dress-code select, and live-updates the preview iframe (the owner preview route
     with ?editor=1) via postMessage. Always English, whatever the dashboard language is — copy
     from lang/en/christmas.php through ChristmasTemplate::t(). --}}
@php
  use App\Support\Invitations\ChristmasTemplate as Xmas;

  $t = fn (string $key, array $replace = []) => Xmas::t($key, $replace);
  $templateFields = $invitation->template->fields ?? [];
  $has = fn (string $key) => in_array($key, $templateFields, true);
  $locked = fn (string $key) => ! $invitation->fieldUnlocked($key);
  $err = fn (string $key) => $errors->first('fieldValues.'.$key) ?: null;
  $label = fn (string $key) => Xmas::label($key);
  $ph = fn (string $key) => $t("fields.{$key}.placeholder");
  $input = 'w-full rounded-lg border border-plum-200 bg-transparent px-4 py-3 text-base focus:border-brand-500 focus:outline-none dark:border-plum-700';
  $card = 'rounded-2xl border border-plum-200 bg-white p-4 sm:p-6 dark:border-plum-800 dark:bg-plum-900';

  $moreFields = array_values(array_filter(['end_time', 'message', 'dress_code', 'what_to_bring', 'map_url', 'rsvp_deadline', 'contact_phone'], $has));
  $extraFields = array_values(array_filter(Xmas::EXTRA_FIELDS, $has));
  $openWhen = fn (array $keys) => collect($keys)->contains(fn ($k) => filled($fieldValues[$k] ?? null) || $errors->has('fieldValues.'.$k));

  $config = [
    'dressCodes' => Xmas::DRESS_CODES,
    'moreFields' => $moreFields,
    'extraFields' => $extraFields,
    'moreOpen' => $openWhen($moreFields),
    'extrasOpen' => $openWhen(array_diff($extraFields, ['rsvp_enabled'])) || ! empty($fieldValues['rsvp_enabled']),
    'strings' => ['downloaded' => $t('editor.download_done'), 'downloadFailed' => $t('editor.download_failed')],
    // the template's two pages, each its own route; ?editor=1 keeps the preview in editor mode
    'pages' => [
      'cover' => route('dashboard.invitations.preview', ['invitation' => $invitation, 'editor' => 1]),
      'details' => route('dashboard.invitations.preview.details', ['invitation' => $invitation, 'editor' => 1]),
    ],
  ];
  $previewSrc = $config['pages']['cover'];
@endphp

<form wire:submit="save" lang="en-GB" novalidate x-data="xmEditor(@js($config))"
  x-on:input="dirty = true; push()" x-on:change="dirty = true; push()"
  x-on:field-error.window="showError($event.detail.key)"
  x-on:xm-form-reset.window="afterReset()"
  class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,26rem)] xl:gap-8">

  {{-- ===================== form ===================== --}}
  <div class="flex min-w-0 flex-col gap-5">
    <p class="flex items-start gap-2 rounded-xl bg-gold-500/10 px-4 py-3 text-sm text-plum-700 dark:text-plum-200">
      <x-app-icon name="light-bulb" class="mt-0.5 h-5 w-5 shrink-0 text-gold-500" />
      <span>
        <span class="hidden xl:inline">{{ $t('editor.intro') }}</span>
        <span class="xl:hidden">{{ $t('editor.intro_mobile') }}</span>
        {{ $t('editor.required_note') }}
      </span>
    </p>

    {{-- required --}}
    <section class="@container {{ $card }}">
      <div class="grid gap-4 @lg:grid-cols-2">
        @if ($has('headline'))
          <x-dashboard.xmas-field key="headline" :label="$label('headline')" required :error="$err('headline')">
            <x-slot:aside><span class="text-xs text-plum-400" x-text="($wire.fieldValues.headline || '').length + ' / {{ Xmas::LIMITS['headline'] }}'"></span></x-slot:aside>
            <input id="xm-input-headline" type="text" wire:model="fieldValues.headline" maxlength="{{ Xmas::LIMITS['headline'] }}" placeholder="{{ $ph('headline') }}" required class="{{ $input }}" />
          </x-dashboard.xmas-field>
        @endif

        @if ($has('sender_name'))
          <x-dashboard.xmas-field key="sender_name" :label="$label('sender_name')" required :error="$err('sender_name')">
            <input id="xm-input-sender_name" type="text" wire:model="fieldValues.sender_name" maxlength="{{ Xmas::LIMITS['sender_name'] }}" placeholder="{{ $ph('sender_name') }}" required autocomplete="name" class="{{ $input }}" />
          </x-dashboard.xmas-field>
        @endif

        @if ($has('event_date'))
          {{-- one stored value (event_date «Y-m-d\TH:i»), two inputs; its error shows under both --}}
          <div id="xm-field-event_date" class="col-span-full grid scroll-mt-24 gap-4 @lg:grid-cols-2">
            <x-dashboard.xmas-field key="date" for="xm-input-event_date" :label="$label('event_date')" required>
              <input id="xm-input-event_date" type="date" x-model="date" min="{{ now()->toDateString() }}" required class="{{ $input }}" />
            </x-dashboard.xmas-field>
            <x-dashboard.xmas-field key="start_time" :label="$label('start_time')" required>
              <input id="xm-input-start_time" type="time" x-model="time" required class="{{ $input }}" />
            </x-dashboard.xmas-field>
            @if ($err('event_date'))
              <p class="col-span-full -mt-3 text-xs text-red-600 dark:text-red-400" role="alert">{{ $err('event_date') }}</p>
            @endif
          </div>
        @endif

        @if ($has('venue_name'))
          <x-dashboard.xmas-field key="venue_name" :label="$label('venue_name')" required :error="$err('venue_name')" class="col-span-full">
            <input id="xm-input-venue_name" type="text" wire:model="fieldValues.venue_name" maxlength="{{ Xmas::LIMITS['venue_name'] }}" placeholder="{{ $ph('venue_name') }}" required class="{{ $input }}" />
          </x-dashboard.xmas-field>
        @endif

        @if ($has('venue_address'))
          <x-dashboard.xmas-field key="venue_address" :label="$label('venue_address')" :required="! $locked('venue_address')" :locked="$locked('venue_address')" :error="$err('venue_address')" class="col-span-full">
            <x-slot:aside><span class="text-xs text-plum-400" x-text="($wire.fieldValues.venue_address || '').length + ' / {{ Xmas::LIMITS['venue_address'] }}'"></span></x-slot:aside>
            <textarea id="xm-input-venue_address" wire:model="fieldValues.venue_address" rows="2" maxlength="{{ Xmas::LIMITS['venue_address'] }}" placeholder="{{ $ph('venue_address') }}" @unless ($locked('venue_address')) required @endunless class="{{ $input }}"></textarea>
          </x-dashboard.xmas-field>
        @endif
      </div>
    </section>

    {{-- optional --}}
    <section class="@container {{ $card }}">
      <button type="button" @click="moreOpen = ! moreOpen" :aria-expanded="moreOpen.toString()" aria-controls="xm-more"
        class="flex w-full items-center justify-between gap-3 text-left">
        <span class="flex items-center gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
            <x-app-icon name="sparkles" class="h-5 w-5" />
          </span>
          <span>
            <span class="block text-lg font-bold text-plum-900 dark:text-white">{{ $t('editor.more') }}</span>
            <span class="block text-xs text-plum-500 dark:text-plum-400">{{ $t('editor.more_hint') }}</span>
          </span>
        </span>
        <x-app-icon name="chevron-right" class="h-5 w-5 shrink-0 text-plum-400 transition-transform" ::class="moreOpen && 'rotate-90'" />
      </button>

      <div id="xm-more" x-show="moreOpen" x-collapse @if (! $config['moreOpen']) style="display: none" @endif>
        <div class="grid gap-4 pt-5 @lg:grid-cols-2">
          <x-dashboard.xmas-field key="guest_name" :label="$label('guest_name')" :hint="$t('fields.guest_name.hint')">
            <input id="xm-input-guest_name" type="text" x-model="guestName" maxlength="{{ Xmas::GUEST_NAME_MAX }}" placeholder="{{ $ph('guest_name') }}" autocomplete="off" class="{{ $input }}" />
          </x-dashboard.xmas-field>

          @if ($has('end_time'))
            <x-dashboard.xmas-field key="end_time" :label="$label('end_time')" :error="$err('end_time')">
              <input id="xm-input-end_time" type="time" wire:model="fieldValues.end_time" :min="time || null" class="{{ $input }}" />
            </x-dashboard.xmas-field>
          @endif

          @if ($has('message'))
            <x-dashboard.xmas-field key="message" :label="$label('message')" :error="$err('message')" class="col-span-full">
              <x-slot:aside>
                <span class="text-xs" :class="msgLen >= {{ Xmas::LIMITS['message'] }} ? 'font-semibold text-red-600 dark:text-red-400' : 'text-plum-400'"
                  x-text="msgLen + ' / {{ Xmas::LIMITS['message'] }}'"></span>
              </x-slot:aside>
              <textarea id="xm-input-message" wire:model="fieldValues.message" rows="3" maxlength="{{ Xmas::LIMITS['message'] }}" placeholder="{{ $ph('message') }}"
                x-on:input="msgLen = $event.target.value.length" class="{{ $input }}"></textarea>
            </x-dashboard.xmas-field>
          @endif

          @if ($has('dress_code'))
            <x-dashboard.xmas-field key="dress_code" :label="$label('dress_code')" :error="$err('dress_code')">
              <select id="xm-input-dress_code" x-model="dressChoice" class="{{ $input }} dark:bg-plum-900">
                <option value="">{{ $t('editor.dress_none') }}</option>
                @foreach (Xmas::DRESS_CODES as $choice)
                  <option value="{{ $choice }}">{{ $choice }}</option>
                @endforeach
                <option value="Other">{{ $t('editor.dress_other') }}</option>
              </select>
              <input type="text" x-show="dressChoice === 'Other'" x-model="dressOther" maxlength="{{ Xmas::LIMITS['dress_code'] }}" style="display: none"
                placeholder="{{ $t('editor.dress_other_placeholder') }}" aria-label="{{ $t('editor.dress_other_placeholder') }}" class="{{ $input }} mt-2" />
            </x-dashboard.xmas-field>
          @endif

          @if ($has('what_to_bring'))
            <x-dashboard.xmas-field key="what_to_bring" :label="$label('what_to_bring')" :error="$err('what_to_bring')">
              <input id="xm-input-what_to_bring" type="text" wire:model="fieldValues.what_to_bring" maxlength="{{ Xmas::LIMITS['what_to_bring'] }}" placeholder="{{ $ph('what_to_bring') }}" class="{{ $input }}" />
            </x-dashboard.xmas-field>
          @endif

          @if ($has('map_url'))
            <x-dashboard.xmas-field key="map_url" :label="$label('map_url')" :locked="$locked('map_url')" :error="$err('map_url')" class="col-span-full">
              <input id="xm-input-map_url" type="url" inputmode="url" wire:model="fieldValues.map_url" maxlength="2048" placeholder="{{ $ph('map_url') }}" class="{{ $input }}" />
            </x-dashboard.xmas-field>
          @endif

          @if ($has('rsvp_deadline'))
            <x-dashboard.xmas-field key="rsvp_deadline" :label="$label('rsvp_deadline')" :error="$err('rsvp_deadline')">
              <input id="xm-input-rsvp_deadline" type="date" wire:model="fieldValues.rsvp_deadline" :max="date || null" class="{{ $input }}" />
            </x-dashboard.xmas-field>
          @endif

          @if ($has('contact_phone'))
            <x-dashboard.xmas-field key="contact_phone" :label="$label('contact_phone')" :error="$err('contact_phone')">
              <input id="xm-input-contact_phone" type="text" wire:model="fieldValues.contact_phone" maxlength="{{ Xmas::LIMITS['contact_phone'] }}" placeholder="{{ $ph('contact_phone') }}" class="{{ $input }}" />
            </x-dashboard.xmas-field>
          @endif
        </div>
      </div>
    </section>

    {{-- extras the template row ticks (RSVP buttons, music) --}}
    @if ($extraFields)
      <section class="@container {{ $card }}">
        <button type="button" @click="extrasOpen = ! extrasOpen" :aria-expanded="extrasOpen.toString()" aria-controls="xm-extras"
          class="flex w-full items-center justify-between gap-3 text-left">
          <span class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
              <x-app-icon name="bolt" class="h-5 w-5" />
            </span>
            <span class="text-lg font-bold text-plum-900 dark:text-white">{{ $t('editor.extras') }}</span>
          </span>
          <x-app-icon name="chevron-right" class="h-5 w-5 shrink-0 text-plum-400 transition-transform" ::class="extrasOpen && 'rotate-90'" />
        </button>

        <div id="xm-extras" x-show="extrasOpen" x-collapse @if (! $config['extrasOpen']) style="display: none" @endif>
          <div class="grid gap-4 pt-5 @lg:grid-cols-2">
            @if ($has('rsvp_enabled'))
              <x-dashboard.xmas-field key="rsvp_enabled" :label="$label('rsvp_enabled')" :locked="$locked('rsvp_enabled')" :hint="$t('fields.rsvp_enabled.hint')" :error="$err('rsvp_enabled')" class="col-span-full">
                <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                  <input id="xm-input-rsvp_enabled" type="checkbox" wire:model="fieldValues.rsvp_enabled" class="peer sr-only" />
                  <span class="h-7 w-12 rounded-full bg-plum-300 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 dark:bg-plum-700"></span>
                  <span class="pointer-events-none absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                </span>
              </x-dashboard.xmas-field>
            @endif

            @if ($has('rsvp_message'))
              <x-dashboard.xmas-field key="rsvp_message" :label="$label('rsvp_message')" :locked="$locked('rsvp_message')" :error="$err('rsvp_message')">
                <input id="xm-input-rsvp_message" type="text" wire:model="fieldValues.rsvp_message" maxlength="{{ Xmas::LIMITS['rsvp_message'] }}" placeholder="{{ $ph('rsvp_message') }}" class="{{ $input }}" />
              </x-dashboard.xmas-field>
            @endif

            @if ($has('music_url'))
              <x-dashboard.xmas-field key="music_url" :label="$label('music_url')" :locked="$locked('music_url')" :error="$err('music_url')">
                <input id="xm-input-music_url" type="url" inputmode="url" wire:model="fieldValues.music_url" maxlength="2048" placeholder="{{ $ph('music_url') }}" class="{{ $input }}" />
              </x-dashboard.xmas-field>
            @endif
          </div>
        </div>
      </section>
    @endif
  </div>

  {{-- ===================== live preview ===================== --}}
  <div class="min-w-0 xl:sticky xl:top-6">
    <div class="mx-auto w-full max-w-[26rem]">
      <div class="mb-2 flex items-center justify-between gap-2">
        <h2 class="text-sm font-bold uppercase tracking-wide text-plum-500 dark:text-plum-400">{{ $t('editor.preview') }}</h2>
        <span x-show="! ready" class="h-4 w-4 animate-spin rounded-full border-2 border-plum-300 border-t-brand-500" aria-hidden="true"></span>
      </div>
      {{-- the invitation is two pages: switch the preview between them --}}
      <div role="tablist" aria-label="{{ $t('editor.preview_pages') }}" class="mb-3 grid grid-cols-2 gap-1 rounded-full border border-plum-200 bg-white p-1 dark:border-plum-700 dark:bg-plum-900">
        @foreach (['cover' => $t('editor.preview_cover'), 'details' => $t('editor.preview_details')] as $key => $tabLabel)
          <button type="button" role="tab" @click="showPage('{{ $key }}')" :aria-selected="page === '{{ $key }}'" :disabled="downloading"
            :class="page === '{{ $key }}' ? 'bg-brand-600 text-white shadow-sm' : 'text-plum-600 hover:bg-plum-50 dark:text-plum-300 dark:hover:bg-plum-800'"
            class="rounded-full px-4 py-2 text-sm font-semibold transition disabled:cursor-wait">{{ $tabLabel }}</button>
        @endforeach
      </div>
      <div wire:ignore class="overflow-hidden rounded-[1.75rem] border-[6px] border-plum-900 bg-plum-900 shadow-xl dark:border-plum-700">
        <iframe x-ref="frame" src="{{ $previewSrc }}" title="{{ $t('editor.preview') }}"
          class="block h-[80svh] max-h-[56rem] w-full bg-[#f5f0e6] xl:h-[calc(100vh-15rem)] xl:min-h-[32rem]"></iframe>
      </div>
      <p class="mt-2 text-center text-xs text-plum-500 dark:text-plum-400">{{ $t('editor.preview_hint') }}</p>
    </div>
  </div>

  {{-- ===================== actions ===================== --}}
  <div class="sticky bottom-0 z-10 col-span-full flex flex-wrap items-center gap-3 bg-plum-50/90 py-4 backdrop-blur dark:bg-plum-950/90">
    <button type="submit" wire:loading.attr="disabled" wire:target="save"
      :class="dirty && 'ring-4 ring-brand-300/60 dark:ring-brand-500/30'"
      class="flex min-w-32 items-center justify-center gap-2 rounded-full bg-brand-600 px-7 py-3 text-base font-bold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
      <span wire:loading.remove wire:target="save">{{ $t('editor.save') }}</span>
      <span wire:loading wire:target="save" class="flex items-center gap-2">
        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> {{ $t('editor.saving') }}
      </span>
    </button>

    <button type="button" @click="download()" :disabled="downloading || ! ready"
      class="flex items-center gap-2 rounded-full border border-plum-200 bg-white px-5 py-3 text-sm font-semibold text-plum-700 transition hover:border-brand-400 disabled:cursor-not-allowed disabled:opacity-60 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-200">
      <x-app-icon name="download" class="h-4 w-4" />
      <span x-show="! downloading">{{ $t('editor.download') }}</span>
      <span x-show="downloading" style="display: none">{{ $t('editor.downloading') }}</span>
    </button>

    <button type="button" wire:click="resetChristmasForm" wire:confirm="{{ $t('editor.reset_confirm') }}"
      class="rounded-full px-4 py-3 text-sm font-semibold text-plum-500 transition hover:bg-red-50 hover:text-red-600 dark:text-plum-400 dark:hover:bg-red-900/20 dark:hover:text-red-400">
      {{ $t('editor.reset') }}
    </button>

    <p wire:loading.remove wire:target="save" class="text-sm font-semibold">
      <span x-show="dirty" style="display: none" class="flex items-center gap-1.5 text-gold-600 dark:text-gold-400">
        <span class="h-2 w-2 rounded-full bg-gold-500"></span> {{ $t('editor.unsaved') }}
      </span>
      <span x-show="! dirty && justSaved" style="display: none" class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
        <x-app-icon name="check-circle" class="h-4 w-4" /> {{ $t('editor.saved') }}
      </span>
    </p>
  </div>
</form>
