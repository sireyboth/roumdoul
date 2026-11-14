@php
  $navItems = [
    'overview' => ['label' => __('Overview'), 'icon' => 'squares'],
    'details' => ['label' => __('Details'), 'icon' => 'pencil'],
    'media' => ['label' => __('Images'), 'icon' => 'photo'],
    'program' => ['label' => __('Program'), 'icon' => 'calendar'],
    'guests' => ['label' => __('Guests'), 'icon' => 'users'],
    'responses' => ['label' => __('Responses'), 'icon' => 'chat'],
    'guide' => ['label' => __('Guide'), 'icon' => 'question'],
  ];
  $isPaid = $invitation->isPaid();
  $daysLeft = $invitation->expires_at ? max(0, (int) floor(now()->diffInDays($invitation->expires_at, false))) : null;
  $previewUrl = route('dashboard.invitations.preview', $invitation);
@endphp

<div x-data="{ sidebarOpen: false, dirty: false, justSaved: false }" class="min-h-screen lg:flex"
  x-on:invitation-saved.window="dirty = false; justSaved = true"
  x-init="window.addEventListener('beforeunload', (e) => { if ($data.dirty) { e.preventDefault(); e.returnValue = '' } })">
  {{-- Mobile top bar --}}
  <div class="sticky top-0 z-30 flex items-center gap-3 border-b border-plum-200 bg-white/90 px-4 py-3 backdrop-blur lg:hidden dark:border-plum-800 dark:bg-plum-900/90">
    <button type="button" @click="sidebarOpen = true" aria-label="{{ __('Open menu') }}" class="text-plum-600 dark:text-plum-300">
      <x-app-icon name="bars" class="h-6 w-6" />
    </button>
    <p class="truncate text-sm font-bold text-plum-900 dark:text-white">{{ $invitation->template->name }}</p>
  </div>

  {{-- Mobile backdrop --}}
  <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-plum-950/50 lg:hidden"></div>

  {{-- Sidebar --}}
  <aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-plum-200 bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 dark:border-plum-800 dark:bg-plum-900">
    <div class="flex items-center justify-between px-5 pt-5">
      <a href="/" class="flex items-center gap-2.5">
        <img src="/images/Roumdoul_Logo.png" alt="ROUMDOUL" class="h-9 w-9 rounded-full object-contain" />
        <span class="text-sm font-extrabold tracking-wide text-plum-900 dark:text-white">ROUMDOUL</span>
      </a>
      <button type="button" @click="sidebarOpen = false" aria-label="{{ __('Close menu') }}" class="text-plum-400 lg:hidden">
        <x-app-icon name="x-mark" class="h-5 w-5" />
      </button>
    </div>

    {{-- The invitation this workspace is for --}}
    <div class="mx-4 mt-5 rounded-xl bg-plum-50 p-4 dark:bg-plum-800/50">
      <p class="text-sm font-bold leading-snug text-plum-900 dark:text-white">{{ $invitation->template->name }}</p>
      @if ($invitation->plan)
        <p class="mt-0.5 text-xs text-plum-500 dark:text-plum-400">{{ $invitation->plan->label }}</p>
      @endif
      <div class="mt-3 flex items-center gap-2">
        @if ($invitation->isExpired())
          <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Expired') }}</span>
        @elseif (! $isPaid)
          <span class="rounded-full bg-gold-500/15 px-2.5 py-0.5 text-[11px] font-bold text-gold-600 dark:text-gold-400">{{ __('Awaiting payment') }}</span>
        @else
          <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-[11px] font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ __('Active') }}</span>
        @endif
        @if ($invitation->expires_at)
          <span class="text-[11px] text-plum-500 dark:text-plum-400">{{ __('Expires :date', ['date' => $invitation->expires_at->format('d M Y')]) }}</span>
        @endif
      </div>
    </div>

    <nav class="mt-5 flex-1 overflow-y-auto px-3">
      @foreach ($sections as $key)
        <button type="button" wire:click="showSection('{{ $key }}')" @click="sidebarOpen = false" wire:key="nav-{{ $key }}"
          @class([
            'mb-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition-colors',
            'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $section === $key,
            'text-plum-600 hover:bg-plum-50 hover:text-plum-900 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white' => $section !== $key,
          ])>
          <x-app-icon :name="$navItems[$key]['icon']" class="h-5 w-5 shrink-0" />
          <span class="flex-1">{{ $navItems[$key]['label'] }}</span>
          @if ($key === 'guests')
            <span class="text-xs font-bold text-plum-400">{{ $stats['recipients'] }}/{{ $invitation->max_recipients }}</span>
          @elseif ($key === 'responses' && $stats['attending'] > 0)
            <span class="rounded-full bg-green-100 px-2 text-xs font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ $stats['attending'] }}</span>
          @endif
        </button>
      @endforeach

      @if ($isPaid)
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
          class="mb-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-plum-600 transition-colors hover:bg-plum-50 hover:text-plum-900 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
          <x-app-icon name="eye" class="h-5 w-5 shrink-0" />
          <span class="flex-1">{{ __('Preview') }}</span>
          <x-app-icon name="external" class="h-4 w-4 text-plum-400" />
        </a>
      @endif
    </nav>

    <div class="border-t border-plum-200 p-3 dark:border-plum-800">
      <a href="/dashboard" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-plum-600 hover:bg-plum-50 dark:text-plum-300 dark:hover:bg-plum-800">
        <x-app-icon name="chevron-left" class="h-5 w-5" /> {{ __('Back to dashboard') }}
      </a>
      <div class="flex items-center justify-between px-3 py-2">
        <span class="flex items-center gap-2 text-sm font-semibold text-plum-500 dark:text-plum-400">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle><path d="M12 3a13 13 0 0 0 0 18 13 13 0 0 0 0-18"></path><path d="M3 12h18"></path>
          </svg>
          {{ __('Language') }}
        </span>
        <x-language-toggle />
      </div>
      <div class="flex items-center justify-between px-3 py-2">
        <form method="POST" action="/logout">
          @csrf
          <button type="submit" class="flex items-center gap-2 text-sm font-semibold text-plum-500 hover:text-red-600 dark:text-plum-400 dark:hover:text-red-400">
            <x-app-icon name="logout" class="h-5 w-5" /> {{ __('Log out') }}
          </button>
        </form>
        <x-theme-toggle class="flex" />
      </div>
    </div>
  </aside>

  {{-- Main content --}}
  {{-- Thin progress bar while another section loads (only shows if it takes > 200ms) --}}
  <div wire:loading.delay wire:target="showSection" class="ws-progress" aria-hidden="true"></div>

  <main class="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-12 lg:py-10">
    @php
      $wide = $section === 'guide' || ($isPaid && in_array($section, ['details', 'media', 'program'], true));
      $roomy = in_array($section, ['guests', 'responses'], true);
    @endphp
    {{-- Keyed by section: a new section is a new element, so the enter animation replays --}}
    <div wire:key="section-{{ $section }}" @class(['ws-enter mx-auto', 'max-w-none' => $wide, 'max-w-6xl' => $roomy && ! $wide, 'max-w-3xl' => ! $wide && ! $roomy])>
      <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white">{{ $navItems[$section]['label'] }}</h1>

      @if ($section === 'guide')
        @include('livewire.pages.dashboard.partials.guide')

      @elseif (! $isPaid)
        <div class="mt-8 flex flex-col items-center gap-3 rounded-xl border border-dashed border-gold-400 bg-gold-500/10 py-16 text-center">
          <x-app-icon name="clock" class="h-10 w-10 text-gold-600" />
          <p class="text-base font-semibold text-plum-800 dark:text-plum-100">{{ __('Waiting for payment confirmation') }}</p>
          <p class="max-w-sm text-sm text-plum-500 dark:text-plum-400">
            {{ __("You'll be able to fill in details and add guests as soon as your order is confirmed as paid.") }}
          </p>
          <button type="button" wire:click="showSection('guide')" class="mt-2 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
            {{ __('Learn how to use it →') }}
          </button>
        </div>

      @elseif ($section === 'overview')
        <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ $invitation->template->name }}</p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
          @foreach ([
            ['label' => __('Guests'), 'value' => $stats['recipients'].' / '.$invitation->max_recipients],
            ['label' => __('Viewed'), 'value' => $stats['viewed']],
            ['label' => __('Attending'), 'value' => $stats['attending']],
            ['label' => __('Days left'), 'value' => $daysLeft ?? '—'],
          ] as $tile)
            <div class="rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
              <p class="text-xs font-semibold text-plum-500 dark:text-plum-400">{{ $tile['label'] }}</p>
              <p class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white">{{ $tile['value'] }}</p>
            </div>
          @endforeach
        </div>

        @php
          $steps = [
            ['done' => $stats['filled'] > 0, 'title' => __('Fill in the invitation details'), 'hint' => __(':filled / :total fields filled', ['filled' => $stats['filled'], 'total' => $stats['fields']]), 'section' => $sections[1] ?? null],
            ['done' => $stats['recipients'] > 0, 'title' => __('Add guests'), 'hint' => __('Each guest gets their own personal link'), 'section' => 'guests'],
            ['done' => $stats['viewed'] > 0, 'title' => __('Share the links'), 'hint' => __('Copy each link and send it by Telegram, Messenger…'), 'section' => 'guests'],
          ];
        @endphp
        <div class="mt-6 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-bold text-plum-900 dark:text-white">{{ __('Setup steps') }}</h2>
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
              class="flex items-center gap-1.5 rounded-full border border-plum-200 px-3.5 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
              <x-app-icon name="eye" class="h-4 w-4" /> {{ __('Preview') }}
            </a>
          </div>
          <ol class="mt-4 flex flex-col gap-2">
            @foreach ($steps as $i => $step)
              <li>
                <button type="button" @if ($step['section']) wire:click="showSection('{{ $step['section'] }}')" @endif
                  class="flex w-full items-center gap-3 rounded-lg p-3 text-left transition-colors hover:bg-plum-50 dark:hover:bg-plum-800">
                  @if ($step['done'])
                    <x-app-icon name="check-circle" class="h-6 w-6 shrink-0 text-green-600 dark:text-green-400" />
                  @else
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-plum-300 text-xs font-bold text-plum-400 dark:border-plum-600">{{ $i + 1 }}</span>
                  @endif
                  <span class="flex-1">
                    <span class="block text-sm font-semibold text-plum-900 dark:text-white">{{ $step['title'] }}</span>
                    <span class="block text-xs text-plum-500 dark:text-plum-400">{{ $step['hint'] }}</span>
                  </span>
                  <x-app-icon name="chevron-right" class="h-4 w-4 text-plum-300" />
                </button>
              </li>
            @endforeach
          </ol>
        </div>

        <button type="button" wire:click="showSection('guide')"
          class="mt-4 flex w-full items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 text-left transition-colors hover:border-brand-400 dark:border-brand-500/30 dark:bg-brand-500/10">
          <x-app-icon name="question" class="h-6 w-6 shrink-0 text-brand-600 dark:text-brand-400" />
          <span class="flex-1">
            <span class="block text-sm font-semibold text-plum-900 dark:text-white">{{ __('First time here?') }}</span>
            <span class="block text-xs text-plum-500 dark:text-plum-400">{{ __('Read how it works and what each field means') }}</span>
          </span>
          <x-app-icon name="chevron-right" class="h-4 w-4 text-plum-300" />
        </button>

      @elseif (in_array($section, ['details', 'media', 'program'], true))
        @php
          $formFields = $this->sectionFields($section);
          $isFilled = fn (string $key) => filled($fieldValues[$key] ?? null) || ! empty($imageUploads[$key]) || ! empty($galleryUploads[$key]) || ! empty($audioUploads[$key]);
          $initialFocus = in_array($focusField, $formFields, true) ? $focusField : ($formFields[0] ?? null);
        @endphp
        <p class="mt-2 flex items-start gap-2 rounded-xl bg-gold-500/10 px-4 py-3 text-base text-plum-700 dark:text-plum-200">
          <x-app-icon name="light-bulb" class="mt-0.5 h-5 w-5 shrink-0 text-gold-500" />
          {{ __('Tap any field to see where it appears on the invitation. Empty fields are not shown.') }}
        </p>

        <form wire:submit="save" class="mt-6 xl:grid xl:grid-cols-[22rem_minmax(0,1fr)] xl:items-start xl:gap-8"
          x-data="{ uploading: 0, zoom: null, focus: @js($initialFocus) }"
          @if ($focusField) x-init="$nextTick(() => document.getElementById('field-{{ $focusField }}')?.scrollIntoView({ block: 'center' }))" @endif
          x-on:photo-upload-start="uploading++"
          x-on:photo-upload-end="uploading = Math.max(0, uploading - 1); dirty = true"
          x-on:input="dirty = true" x-on:change="dirty = true"
          x-on:field-error.window="focus = $event.detail.key; $nextTick(() => document.getElementById('field-' + $event.detail.key)?.scrollIntoView({ behavior: 'smooth', block: 'center' }))">

          @include('livewire.pages.dashboard.partials.field-help-zoom', ['zoomFields' => $formFields])

          {{-- Help panel (wide screens): the focused field's diagram + tips, then a checklist of every field here --}}
          <aside class="sticky top-6 hidden max-h-[calc(100vh-3rem)] flex-col gap-4 overflow-y-auto xl:flex">
            <div class="rounded-xl border border-brand-200 bg-white p-6 dark:border-brand-500/30 dark:bg-plum-900">
              @foreach ($formFields as $key)
                <div x-show="focus === '{{ $key }}'" @if ($key !== $initialFocus) style="display: none" @endif wire:key="help-{{ $key }}">
                  @include('livewire.pages.dashboard.partials.field-help', ['catalogField' => $fieldCatalog[$key], 'filled' => $isFilled($key)])
                </div>
              @endforeach
            </div>

            <div class="rounded-xl border border-plum-200 bg-white p-3 dark:border-plum-800 dark:bg-plum-900">
              <p class="px-3 pb-2 pt-1 text-xs font-bold uppercase tracking-wide text-plum-400">{{ __('Fields on this tab') }}</p>
              @foreach ($formFields as $key)
                <button type="button" wire:key="jump-{{ $key }}"
                  @click="focus = '{{ $key }}'; document.getElementById('field-{{ $key }}').scrollIntoView({ behavior: 'smooth', block: 'center' })"
                  :class="focus === '{{ $key }}' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-plum-600 hover:bg-plum-50 dark:text-plum-300 dark:hover:bg-plum-800'"
                  class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-semibold">
                  @if (! $invitation->fieldUnlocked($key))
                    <x-app-icon name="lock-closed" class="h-4 w-4 shrink-0 text-gold-500" />
                  @elseif ($isFilled($key))
                    <x-app-icon name="check-circle" class="h-4 w-4 shrink-0 text-green-600 dark:text-green-400" />
                  @else
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center"><span class="h-2.5 w-2.5 rounded-full border-2 border-plum-300 dark:border-plum-600"></span></span>
                  @endif
                  <span class="truncate">{{ $fieldCatalog[$key]['label'] }}</span>
                </button>
              @endforeach
            </div>
          </aside>

          {{-- Wide cards (schedule, story, either/or) get a row to themselves; runs of ordinary
               cards between them split into two balanced columns that stack independently, so
               nothing leaves an empty gap. Container queries: the switch follows the space left
               beside the help panel, not the screen size. --}}
          <div class="min-w-0">
          @php $layout = $this->sectionLayout($section); @endphp
          <div class="@container flex flex-col gap-5">
            @foreach ($layout['segments'] as $s => $segment)
              @if (isset($segment['wide']))
                @include('livewire.pages.dashboard.partials.form-group', ['groupKey' => $segment['wide'], 'group' => $layout['groups'][$segment['wide']]])
              @else
                <div wire:key="segment-{{ $s }}" class="grid items-start gap-5 @5xl:grid-cols-2">
                  @foreach ($segment['columns'] as $column)
                    <div class="flex min-w-0 flex-col gap-5">
                      @foreach ($column as $groupKey)
                        @include('livewire.pages.dashboard.partials.form-group', ['groupKey' => $groupKey, 'group' => $layout['groups'][$groupKey]])
                      @endforeach
                    </div>
                  @endforeach
                </div>
              @endif
            @endforeach
          </div>

          {{-- Save bar: always says where things stand — uploading, unsaved, saving, saved --}}
          <div class="sticky bottom-0 mt-4 flex flex-wrap items-center gap-3 bg-plum-50/90 py-4 backdrop-blur dark:bg-plum-950/90">
            <button type="submit" :disabled="uploading > 0" wire:loading.attr="disabled" wire:target="save"
              :class="dirty && 'ring-4 ring-brand-300/60 dark:ring-brand-500/30'"
              class="flex min-w-32 items-center justify-center gap-2 rounded-full bg-brand-600 px-7 py-3 text-base font-bold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
              <span x-show="uploading > 0" style="display: none">{{ __('Uploading photos…') }}</span>
              <span x-show="uploading === 0" class="flex items-center gap-2">
                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                  <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> {{ __('Saving…') }}
                </span>
              </span>
            </button>
            <p wire:loading.remove wire:target="save" class="text-sm font-semibold">
              <span x-show="uploading > 0" style="display: none" class="text-plum-500 dark:text-plum-400">{{ __('Wait for the photos to finish uploading before saving') }}</span>
              <span x-show="uploading === 0 && dirty" style="display: none" class="flex items-center gap-1.5 text-gold-600 dark:text-gold-400">
                <span class="h-2 w-2 rounded-full bg-gold-500"></span> {{ __('You have unsaved changes') }}
              </span>
              <span x-show="uploading === 0 && ! dirty && justSaved" style="display: none" class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                <x-app-icon name="check-circle" class="h-4 w-4" /> {{ __('Saved') }}
              </span>
            </p>
          </div>
          </div>
        </form>

      @elseif ($section === 'guests')
        @php $usage = $invitation->max_recipients ? min(100, $stats['recipients'] / $invitation->max_recipients * 100) : 0; @endphp
        @include('livewire.pages.dashboard.partials.flow-steps', ['flow' => [
          ['icon' => 'user-plus', 'title' => __("Type the guest's name"), 'text' => __('One at a time')],
          ['icon' => 'link', 'title' => __('Press Copy link'), 'text' => __("The link carries that guest's name")],
          ['icon' => 'paper-airplane', 'title' => __('Send it to the guest'), 'text' => 'Telegram · Messenger · SMS'],
          ['icon' => 'eye', 'title' => __('Watch for «Opened»'), 'text' => __('Know when the guest has opened it')],
        ]])
        <div class="mt-6 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
          <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-plum-700 dark:text-plum-200">{{ __(':count / :max guests', ['count' => $stats['recipients'], 'max' => $invitation->max_recipients]) }}</span>
            <span class="text-plum-500 dark:text-plum-400">{{ __(':count opened', ['count' => $stats['viewed']]) }}</span>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-plum-100 dark:bg-plum-800">
            <div class="h-full rounded-full bg-brand-500" style="width: {{ $usage }}%"></div>
          </div>

          @if ($invitation->recipients->count() < $invitation->max_recipients)
            <form wire:submit="addRecipient" class="mt-5 flex gap-2">
              <input type="text" wire:model="newRecipientName" placeholder="{{ __("Guest's name") }}"
                wire:loading.attr="disabled" wire:target="addRecipient"
                class="flex-1 rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none disabled:opacity-50 dark:border-plum-700" />
              <button type="submit" wire:loading.attr="disabled" wire:target="addRecipient"
                class="shrink-0 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="addRecipient">{{ __('Add') }}</span>
                <span wire:loading wire:target="addRecipient">{{ __('Adding…') }}</span>
              </button>
            </form>
            @error('newRecipientName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-plum-100 pt-4 text-sm dark:border-plum-800">
              <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-plum-200 px-4 py-2 font-semibold text-plum-700 hover:border-brand-500 hover:text-brand-600 dark:border-plum-700 dark:text-plum-200">
                <x-app-icon name="arrow-up-tray" class="h-4 w-4" />
                <span wire:loading.remove wire:target="guestFile,importGuests">{{ __('Import list (CSV)') }}</span>
                <span wire:loading wire:target="guestFile,importGuests">{{ __('Importing…') }}</span>
                <input type="file" accept=".csv,.txt,text/csv,text/plain" class="sr-only"
                  wire:model="guestFile" x-on:livewire-upload-finish="$wire.importGuests()" />
              </label>
              <span class="text-xs text-plum-500 dark:text-plum-400">{{ __('One name per row (first column) · Excel → Save As → CSV UTF-8') }}</span>
            </div>
            @error('guestFile') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          @else
            <p class="mt-5 text-sm text-plum-500 dark:text-plum-400">{{ __("You've used all :max guest links on this plan.", ['max' => $invitation->max_recipients]) }}</p>
          @endif
        </div>

        @if ($stats['recipients'] > 0)
          {{-- Toolbar: search · filter · sort · export --}}
          <div class="mt-6 flex flex-col gap-3">
            <div class="flex flex-col gap-2 sm:flex-row">
              <div class="relative flex-1">
                <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
                <input type="search" wire:model.live.debounce.350ms="guestSearch" placeholder="{{ __('Search guests…') }}"
                  class="w-full rounded-xl border border-plum-200 bg-white py-2.5 pl-10 pr-3 text-sm text-plum-800 placeholder:text-plum-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-100" />
              </div>
              <div class="flex gap-2">
                <select wire:model.live="guestSort" aria-label="{{ __('Sort by') }}"
                  class="flex-1 rounded-xl border border-plum-200 bg-white py-2.5 pl-3 pr-8 text-sm font-medium text-plum-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-200 sm:flex-none">
                  <option value="newest">{{ __('Newest first') }}</option>
                  <option value="oldest">{{ __('Oldest first') }}</option>
                  <option value="name">{{ __('Name A–Z') }}</option>
                  <option value="opened">{{ __('Recently opened') }}</option>
                </select>
                <button type="button" wire:click="exportGuests" title="{{ __('Export list (CSV)') }}"
                  class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-plum-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-plum-700 hover:border-brand-500 hover:text-brand-600 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-200">
                  <x-app-icon name="upload" class="h-4 w-4" /> <span class="hidden sm:inline">{{ __('Export list (CSV)') }}</span>
                </button>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              @foreach ([
                '' => [__('All'), $stats['recipients']],
                'opened' => [__('Opened'), $stats['viewed']],
                'not_opened' => [__('Not opened yet'), $stats['recipients'] - $stats['viewed']],
                'paused' => [__('Paused'), $stats['paused']],
              ] as $value => [$label, $count])
                <button type="button" wire:click="$set('guestFilter', '{{ $value }}')" wire:key="gfilter-{{ $value ?: 'all' }}"
                  @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset transition',
                    'bg-brand-600 text-white ring-brand-600' => $guestFilter === $value,
                    'bg-white text-plum-600 ring-plum-200 hover:ring-brand-400 dark:bg-plum-900 dark:text-plum-300 dark:ring-plum-700' => $guestFilter !== $value,
                  ])>
                  {{ $label }}
                  <span @class(['rounded-full px-1.5 text-[10px] font-bold', 'bg-white/20' => $guestFilter === $value, 'bg-plum-100 dark:bg-plum-800' => $guestFilter !== $value])>{{ $count }}</span>
                </button>
              @endforeach
              @if ($hasGuestFilters)
                <button type="button" wire:click="clearGuestFilters" class="inline-flex items-center gap-1 px-2 text-xs font-semibold text-plum-500 hover:text-brand-600 dark:text-plum-400">
                  <x-app-icon name="x-mark" class="h-3.5 w-3.5" /> {{ __('Clear filters') }}
                </button>
              @endif
            </div>
          </div>
        @endif

        <div class="mt-4" wire:loading.class="pointer-events-none opacity-50"
          wire:target="guestSearch,guestFilter,guestSort,guestPerPage,clearGuestFilters,gotoPage,nextPage,previousPage">
          @if ($stats['recipients'] === 0)
            <p class="rounded-xl border border-dashed border-plum-300 py-10 text-center text-sm text-plum-500 dark:border-plum-700 dark:text-plum-400">{{ __('No guests yet — add one above.') }}</p>
          @elseif ($guestList->isEmpty())
            <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-plum-300 py-10 text-center dark:border-plum-700">
              <p class="text-sm text-plum-500 dark:text-plum-400">{{ __('No guests match your search.') }}</p>
              <button type="button" wire:click="clearGuestFilters" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Clear filters') }}</button>
            </div>
          @else
            @php $cols = 'md:grid md:grid-cols-[minmax(0,2.2fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,0.9fr)_auto] md:items-center md:gap-4'; @endphp
            <div class="rounded-2xl border border-plum-200 bg-white shadow-sm dark:border-plum-800 dark:bg-plum-900">
              {{-- Column headings (desktop) --}}
              <div class="hidden rounded-t-2xl border-b border-plum-200 bg-plum-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-plum-500 dark:border-plum-800 dark:bg-plum-800/50 dark:text-plum-400 {{ $cols }}">
                <span>{{ __('Guest') }}</span>
                <span>{{ __('Link') }}</span>
                <span>{{ __('RSVP') }}</span>
                <span>{{ __('Added') }}</span>
                <span class="w-[8.5rem] text-right">{{ __('Actions') }}</span>
              </div>
            <ul class="divide-y divide-plum-100 dark:divide-plum-800">
              @foreach ($guestList as $recipient)
                @php $link = route('invitation.show', [$invitation, $recipient]); @endphp
                <li wire:key="recipient-{{ $recipient->id }}" @class(['flex flex-wrap items-center gap-3 px-5 py-4 transition last:rounded-b-2xl hover:bg-plum-50/70 dark:hover:bg-plum-800/40', $cols, 'opacity-60' => $recipient->revoked_at])>
                  {{-- Guest --}}
                  <div class="flex min-w-0 flex-1 items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-50 text-base font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                      {{ mb_substr($recipient->recipient_name, 0, 1) }}
                    </span>
                    <div class="min-w-0">
                      <p class="truncate text-[15px] font-semibold text-plum-900 dark:text-white">{{ $recipient->recipient_name }}</p>
                      <p class="truncate text-xs text-plum-400" title="{{ $link }}">{{ \Illuminate\Support\Str::after($link, '://') }}</p>
                    </div>
                  </div>

                  {{-- Link status --}}
                  <div class="order-last basis-full md:order-none md:basis-auto">
                    @if ($recipient->revoked_at)
                      <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <x-app-icon name="pause" class="h-3.5 w-3.5" /> {{ __('Paused') }}
                      </span>
                    @elseif ($recipient->viewed_at)
                      <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400" title="{{ $recipient->viewed_at->format('d M Y H:i') }}">
                        <x-app-icon name="eye" class="h-3.5 w-3.5" /> {{ __('Opened') }} · {{ $recipient->viewed_at->diffForHumans(short: true) }}
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1.5 rounded-full bg-plum-100 px-2.5 py-1 text-xs font-semibold text-plum-500 dark:bg-plum-800 dark:text-plum-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-plum-400"></span> {{ __('Not opened yet') }}
                      </span>
                    @endif
                    {{-- RSVP + date inline on phones --}}
                    <span class="ml-1 md:hidden">
                      @if ($recipient->rsvp_status === 'yes')
                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400">{{ __('Attending') }}</span>
                      @elseif ($recipient->rsvp_status === 'no')
                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-400">{{ __('Not attending') }}</span>
                      @endif
                    </span>
                  </div>

                  {{-- RSVP --}}
                  <div class="hidden md:block">
                    @if ($recipient->rsvp_status === 'yes')
                      <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-green-700 dark:text-green-400"><x-app-icon name="check-circle" class="h-4 w-4" /> {{ __('Attending') }}</span>
                    @elseif ($recipient->rsvp_status === 'no')
                      <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-600 dark:text-red-400"><x-app-icon name="x-circle" class="h-4 w-4" /> {{ __('Not attending') }}</span>
                    @else
                      <span class="text-sm text-plum-400">—</span>
                    @endif
                  </div>

                  {{-- Added --}}
                  <div class="hidden text-sm text-plum-500 dark:text-plum-400 md:block" title="{{ $recipient->created_at?->format('d M Y H:i') }}">
                    {{ $recipient->created_at?->format('d M Y') }}
                  </div>

                  <div class="flex shrink-0 items-center justify-end gap-1 md:w-[8.5rem]">
                    <button type="button" x-data="{ copied: false }"
                      @click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($this->shareCaption($recipient->recipient_name, $link)) }})
                        .then(() => { copied = true; setTimeout(() => copied = false, 1500); window.toast('success', @js(__("Copied «:name»'s link — you can send it now.", ['name' => $recipient->recipient_name]))) })
                        .catch(() => window.toast('error', @js(__("Couldn't copy — press and hold the link and copy it by hand."))))"
                      class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-brand-700">
                      <x-app-icon name="link" class="h-3.5 w-3.5" />
                      <span x-show="!copied">{{ __('Copy link') }}</span>
                      <span x-cloak x-show="copied" style="display: none">{{ __('Copied!') }}</span>
                    </button>

                    {{-- Less common actions live in a menu, so each row stays calm --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                      <button type="button" @click="open = !open" aria-label="{{ __('More actions') }}"
                        class="grid h-8 w-8 place-items-center rounded-full text-plum-500 hover:bg-plum-100 hover:text-plum-800 dark:text-plum-400 dark:hover:bg-plum-800 dark:hover:text-white">
                        <x-app-icon name="ellipsis" class="h-5 w-5" />
                      </button>
                      <div x-show="open" x-transition.origin.top.right style="display: none"
                        class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-plum-200 bg-white py-1 text-sm shadow-lg dark:border-plum-700 dark:bg-plum-900">
                        <a href="{{ $link }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-plum-700 hover:bg-plum-50 dark:text-plum-200 dark:hover:bg-plum-800">
                          <x-app-icon name="external" class="h-4 w-4" /> {{ __('Open link') }}
                        </a>
                        <button type="button" wire:click="toggleRecipient({{ $recipient->id }})" @click="open = false"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-plum-700 hover:bg-plum-50 dark:text-plum-200 dark:hover:bg-plum-800">
                          <x-app-icon :name="$recipient->revoked_at ? 'play' : 'pause'" class="h-4 w-4" />
                          {{ $recipient->revoked_at ? __('Restore link') : __('Pause link') }}
                        </button>
                        <button type="button" wire:click="rotateRecipient({{ $recipient->id }})" @click="open = false"
                          wire:confirm="{{ __('Create a new link for :name? The old link will stop working.', ['name' => $recipient->recipient_name]) }}"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-plum-700 hover:bg-plum-50 dark:text-plum-200 dark:hover:bg-plum-800">
                          <x-app-icon name="arrow-path" class="h-4 w-4" /> {{ __('New link') }}
                        </button>
                        <div class="my-1 border-t border-plum-100 dark:border-plum-800"></div>
                        <button type="button" wire:click="removeRecipient({{ $recipient->id }})" @click="open = false"
                          wire:confirm="{{ __('Remove :name? Their link will stop working.', ['name' => $recipient->recipient_name]) }}"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                          <x-app-icon name="trash" class="h-4 w-4" /> {{ __('Remove') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </li>
              @endforeach
            </ul>
            </div>

            @include('livewire.pages.dashboard.partials.pagination', [
              'paginator' => $guestList,
              'pageName' => 'guestsPage',
              'perPageModel' => 'guestPerPage',
              'perPageOptions' => \App\Livewire\Pages\Dashboard\InvitationManagePage::GUEST_PER_PAGE,
            ])
          @endif
        </div>

      @elseif ($section === 'responses')
        @unless ($invitation->fieldUnlocked('rsvp_enabled'))
          <p class="mt-4 flex items-center gap-2 rounded-lg bg-gold-500/10 px-4 py-3 text-sm text-gold-700 dark:text-gold-400">
            <x-app-icon name="lock-closed" class="h-4 w-4" /> {{ __("RSVP isn't included in your plan, so guests can't respond yet.") }}
          </p>
        @endunless

        {{-- The tiles double as filters --}}
        <div class="mt-6 grid grid-cols-3 gap-3">
          @foreach ([
            ['filter' => 'yes', 'label' => __('Attending'), 'value' => $stats['attending'], 'class' => 'text-green-600 dark:text-green-400'],
            ['filter' => 'no', 'label' => __('Not attending'), 'value' => $stats['declined'], 'class' => 'text-red-600 dark:text-red-400'],
            ['filter' => 'none', 'label' => __('No answer yet'), 'value' => $stats['recipients'] - $stats['attending'] - $stats['declined'], 'class' => 'text-plum-900 dark:text-white'],
          ] as $tile)
            <button type="button" wire:click="$set('responseFilter', '{{ $responseFilter === $tile['filter'] ? '' : $tile['filter'] }}')"
              @class([
                'rounded-xl border bg-white p-4 text-left transition hover:border-brand-400 dark:bg-plum-900',
                'border-brand-500 ring-2 ring-brand-500/20' => $responseFilter === $tile['filter'],
                'border-plum-200 dark:border-plum-800' => $responseFilter !== $tile['filter'],
              ])>
              <p class="text-xs font-semibold text-plum-500 dark:text-plum-400">{{ $tile['label'] }}</p>
              <p class="mt-1 text-2xl font-extrabold {{ $tile['class'] }}">{{ $tile['value'] }}</p>
            </button>
          @endforeach
        </div>

        @if ($stats['recipients'] > 0)
          <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative flex-1">
              <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
              <input type="search" wire:model.live.debounce.350ms="guestSearch" placeholder="{{ __('Search names or notes…') }}"
                class="w-full rounded-xl border border-plum-200 bg-white py-2.5 pl-10 pr-3 text-sm text-plum-800 placeholder:text-plum-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-100" />
            </div>
            <select wire:model.live="guestSort" aria-label="{{ __('Sort by') }}"
              class="rounded-xl border border-plum-200 bg-white py-2.5 pl-3 pr-8 text-sm font-medium text-plum-700 focus:border-brand-500 focus:outline-none dark:border-plum-700 dark:bg-plum-900 dark:text-plum-200">
              <option value="newest">{{ __('Newest first') }}</option>
              <option value="oldest">{{ __('Oldest first') }}</option>
              <option value="name">{{ __('Name A–Z') }}</option>
              <option value="opened">{{ __('Recently opened') }}</option>
            </select>
            @if ($hasGuestFilters)
              <button type="button" wire:click="clearGuestFilters" class="inline-flex items-center gap-1 px-2 text-xs font-semibold text-plum-500 hover:text-brand-600 dark:text-plum-400">
                <x-app-icon name="x-mark" class="h-3.5 w-3.5" /> {{ __('Clear filters') }}
              </button>
            @endif
          </div>
        @endif

        <div class="mt-4" wire:loading.class="pointer-events-none opacity-50"
          wire:target="guestSearch,responseFilter,guestSort,guestPerPage,clearGuestFilters,gotoPage,nextPage,previousPage">
          <div class="overflow-hidden rounded-xl border border-plum-200 bg-white dark:border-plum-800 dark:bg-plum-900">
            @forelse ($responseList as $recipient)
              <div wire:key="response-{{ $recipient->id }}" class="flex items-start justify-between gap-3 border-b border-plum-100 p-4 last:border-0 dark:border-plum-800">
                <div class="flex min-w-0 items-start gap-3">
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                    {{ mb_substr($recipient->recipient_name, 0, 1) }}
                  </span>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $recipient->recipient_name }}</p>
                    @if ($recipient->rsvp_note)
                      <p class="mt-1 text-sm text-plum-600 dark:text-plum-300">“{{ $recipient->rsvp_note }}”</p>
                    @endif
                  </div>
                </div>
                @if ($recipient->rsvp_status === 'yes')
                  <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ __('Attending') }}</span>
                @elseif ($recipient->rsvp_status === 'no')
                  <span class="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Not attending') }}</span>
                @else
                  <span class="shrink-0 rounded-full bg-plum-100 px-2.5 py-1 text-[11px] font-bold text-plum-500 dark:bg-plum-800 dark:text-plum-400">{{ __('No answer yet') }}</span>
                @endif
              </div>
            @empty
              <p class="py-10 text-center text-sm text-plum-500 dark:text-plum-400">{{ $stats['recipients'] === 0 ? __('No guests yet.') : __('No guests match your search.') }}</p>
            @endforelse
          </div>

          @if ($responseList->total() > 0)
            @include('livewire.pages.dashboard.partials.pagination', [
              'paginator' => $responseList,
              'pageName' => 'responsesPage',
              'perPageModel' => 'guestPerPage',
              'perPageOptions' => \App\Livewire\Pages\Dashboard\InvitationManagePage::GUEST_PER_PAGE,
            ])
          @endif
        </div>
      @endif
    </div>
  </main>
</div>
