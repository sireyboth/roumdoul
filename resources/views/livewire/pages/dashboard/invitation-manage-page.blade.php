@php
  $navItems = [
    'overview' => ['label' => 'ទិដ្ឋភាពទូទៅ', 'icon' => 'squares'],
    'details' => ['label' => 'ព័ត៌មានលម្អិត', 'icon' => 'pencil'],
    'media' => ['label' => 'រូបភាព', 'icon' => 'photo'],
    'program' => ['label' => 'កម្មវិធី', 'icon' => 'calendar'],
    'guests' => ['label' => 'ភ្ញៀវ', 'icon' => 'users'],
    'responses' => ['label' => 'ការឆ្លើយតប', 'icon' => 'chat'],
    'guide' => ['label' => 'ការណែនាំ', 'icon' => 'question'],
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
    <button type="button" @click="sidebarOpen = true" aria-label="Open menu" class="text-plum-600 dark:text-plum-300">
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
      <button type="button" @click="sidebarOpen = false" aria-label="Close menu" class="text-plum-400 lg:hidden">
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
          <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">ផុតកំណត់</span>
        @elseif (! $isPaid)
          <span class="rounded-full bg-gold-500/15 px-2.5 py-0.5 text-[11px] font-bold text-gold-600 dark:text-gold-400">រង់ចាំការទូទាត់</span>
        @else
          <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-[11px] font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">សកម្ម</span>
        @endif
        @if ($invitation->expires_at)
          <span class="text-[11px] text-plum-500 dark:text-plum-400">ផុតកំណត់ {{ $invitation->expires_at->format('d M Y') }}</span>
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
          <span class="flex-1">មើលជាមុន</span>
          <x-app-icon name="external" class="h-4 w-4 text-plum-400" />
        </a>
      @endif
    </nav>

    <div class="border-t border-plum-200 p-3 dark:border-plum-800">
      <a href="/dashboard" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-plum-600 hover:bg-plum-50 dark:text-plum-300 dark:hover:bg-plum-800">
        <x-app-icon name="chevron-left" class="h-5 w-5" /> ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង
      </a>
      <div class="flex items-center justify-between px-3 py-2">
        <form method="POST" action="/logout">
          @csrf
          <button type="submit" class="flex items-center gap-2 text-sm font-semibold text-plum-500 hover:text-red-600 dark:text-plum-400 dark:hover:text-red-400">
            <x-app-icon name="logout" class="h-5 w-5" /> ចាកចេញ
          </button>
        </form>
        <x-theme-toggle class="flex" />
      </div>
    </div>
  </aside>

  {{-- Main content --}}
  <main class="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-12 lg:py-10">
    @php $wide = $section === 'guide' || ($isPaid && in_array($section, ['details', 'media', 'program'], true)); @endphp
    <div @class(['mx-auto', 'max-w-none' => $wide, 'max-w-3xl' => ! $wide])>
      <h1 class="text-2xl font-extrabold text-plum-900 dark:text-white">{{ $navItems[$section]['label'] }}</h1>

      @if ($section === 'guide')
        @include('livewire.pages.dashboard.partials.guide')

      @elseif (! $isPaid)
        <div class="mt-8 flex flex-col items-center gap-3 rounded-xl border border-dashed border-gold-400 bg-gold-500/10 py-16 text-center">
          <x-app-icon name="clock" class="h-10 w-10 text-gold-600" />
          <p class="text-base font-semibold text-plum-800 dark:text-plum-100">Waiting for payment confirmation</p>
          <p class="max-w-sm text-sm text-plum-500 dark:text-plum-400">
            You'll be able to fill in details and add recipients as soon as your order is confirmed as paid.
          </p>
          <button type="button" wire:click="showSection('guide')" class="mt-2 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
            ស្វែងយល់ពីរបៀបប្រើប្រាស់ →
          </button>
        </div>

      @elseif ($section === 'overview')
        <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ $invitation->template->name }}</p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
          @foreach ([
            ['label' => 'ភ្ញៀវ', 'value' => $stats['recipients'].' / '.$invitation->max_recipients],
            ['label' => 'បានបើកមើល', 'value' => $stats['viewed']],
            ['label' => 'ចូលរួម', 'value' => $stats['attending']],
            ['label' => 'ថ្ងៃនៅសល់', 'value' => $daysLeft ?? '—'],
          ] as $tile)
            <div class="rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
              <p class="text-xs font-semibold text-plum-500 dark:text-plum-400">{{ $tile['label'] }}</p>
              <p class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white">{{ $tile['value'] }}</p>
            </div>
          @endforeach
        </div>

        @php
          $steps = [
            ['done' => $stats['filled'] > 0, 'title' => 'បំពេញព័ត៌មានកម្មវត្ថុអញ្ជើញ', 'hint' => $stats['filled'].' / '.$stats['fields'].' fields filled', 'section' => $sections[1] ?? null],
            ['done' => $stats['recipients'] > 0, 'title' => 'បន្ថែមភ្ញៀវ', 'hint' => 'Each guest gets their own personal link', 'section' => 'guests'],
            ['done' => $stats['viewed'] > 0, 'title' => 'ចែករំលែកតំណភ្ជាប់', 'hint' => 'Copy each link and send it by Telegram, Messenger…', 'section' => 'guests'],
          ];
        @endphp
        <div class="mt-6 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-bold text-plum-900 dark:text-white">ជំហានរៀបចំ</h2>
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
              class="flex items-center gap-1.5 rounded-full border border-plum-200 px-3.5 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
              <x-app-icon name="eye" class="h-4 w-4" /> មើលជាមុន
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
            <span class="block text-sm font-semibold text-plum-900 dark:text-white">លើកដំបូងប្រើប្រាស់?</span>
            <span class="block text-xs text-plum-500 dark:text-plum-400">អានការណែនាំពីរបៀបប្រើប្រាស់ និងអត្ថន័យនៃប្រអប់នីមួយៗ</span>
          </span>
          <x-app-icon name="chevron-right" class="h-4 w-4 text-plum-300" />
        </button>

      @elseif (in_array($section, ['details', 'media', 'program'], true))
        @php
          $formFields = $this->sectionFields($section);
          $isFilled = fn (string $key) => filled($fieldValues[$key] ?? null) || ! empty($imageUploads[$key]) || ! empty($galleryUploads[$key]);
          $initialFocus = in_array($focusField, $formFields, true) ? $focusField : ($formFields[0] ?? null);
        @endphp
        <p class="mt-2 flex items-start gap-2 rounded-xl bg-gold-500/10 px-4 py-3 text-base text-plum-700 dark:text-plum-200">
          <x-app-icon name="light-bulb" class="mt-0.5 h-5 w-5 shrink-0 text-gold-500" />
          ចុចលើប្រអប់ណាមួយ ដើម្បីមើលថាវាបង្ហាញនៅកន្លែងណាលើធៀប។ ប្រអប់ទទេនឹងមិនបង្ហាញទេ។
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
              <p class="px-3 pb-2 pt-1 text-xs font-bold uppercase tracking-wide text-plum-400">ប្រអប់ក្នុងផ្ទាំងនេះ</p>
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
              <span x-show="uploading > 0" style="display: none">កំពុងបង្ហោះរូប&hellip;</span>
              <span x-show="uploading === 0" class="flex items-center gap-2">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                  <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> កំពុងរក្សាទុក&hellip;
                </span>
              </span>
            </button>
            <p wire:loading.remove wire:target="save" class="text-sm font-semibold">
              <span x-show="uploading > 0" style="display: none" class="text-plum-500 dark:text-plum-400">រង់ចាំរូបបង្ហោះរួចសិន ទើប Save បាន</span>
              <span x-show="uploading === 0 && dirty" style="display: none" class="flex items-center gap-1.5 text-gold-600 dark:text-gold-400">
                <span class="h-2 w-2 rounded-full bg-gold-500"></span> មានការកែប្រែមិនទាន់រក្សាទុក
              </span>
              <span x-show="uploading === 0 && ! dirty && justSaved" style="display: none" class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                <x-app-icon name="check-circle" class="h-4 w-4" /> បានរក្សាទុក
              </span>
            </p>
          </div>
          </div>
        </form>

      @elseif ($section === 'guests')
        @php $usage = $invitation->max_recipients ? min(100, $stats['recipients'] / $invitation->max_recipients * 100) : 0; @endphp
        @include('livewire.pages.dashboard.partials.flow-steps', ['flow' => [
          ['icon' => 'user-plus', 'title' => 'វាយឈ្មោះភ្ញៀវ', 'text' => 'ម្នាក់ម្ដងៗ'],
          ['icon' => 'link', 'title' => 'ចុច Copy link', 'text' => 'តំណមានឈ្មោះភ្ញៀវនោះ'],
          ['icon' => 'paper-airplane', 'title' => 'ផ្ញើទៅភ្ញៀវ', 'text' => 'Telegram · Messenger · SMS'],
          ['icon' => 'eye', 'title' => 'មើល «Opened»', 'text' => 'ដឹងថាភ្ញៀវបានបើកហើយ'],
        ]])
        <div class="mt-6 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
          <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-plum-700 dark:text-plum-200">{{ $stats['recipients'] }} / {{ $invitation->max_recipients }} recipients</span>
            <span class="text-plum-500 dark:text-plum-400">{{ $stats['viewed'] }} opened</span>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-plum-100 dark:bg-plum-800">
            <div class="h-full rounded-full bg-brand-500" style="width: {{ $usage }}%"></div>
          </div>

          @if ($invitation->recipients->count() < $invitation->max_recipients)
            <form wire:submit="addRecipient" class="mt-5 flex gap-2">
              <input type="text" wire:model="newRecipientName" placeholder="Recipient's name"
                wire:loading.attr="disabled" wire:target="addRecipient"
                class="flex-1 rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none disabled:opacity-50 dark:border-plum-700" />
              <button type="submit" wire:loading.attr="disabled" wire:target="addRecipient"
                class="shrink-0 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="addRecipient">Add</span>
                <span wire:loading wire:target="addRecipient">Adding...</span>
              </button>
            </form>
            @error('newRecipientName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
          @else
            <p class="mt-5 text-sm text-plum-500 dark:text-plum-400">You've used all {{ $invitation->max_recipients }} recipient links on this plan.</p>
          @endif
        </div>

        <div class="mt-4 flex flex-col gap-2">
          @forelse ($invitation->recipients as $recipient)
            @php $link = route('invitation.show', [$invitation, $recipient]); @endphp
            <div wire:key="recipient-{{ $recipient->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $recipient->recipient_name }}</p>
                  @if ($recipient->revoked_at)
                    <span class="text-[11px] font-bold text-red-600 dark:text-red-400">Paused</span>
                  @endif
                  @if ($recipient->viewed_at)
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-brand-600 dark:text-brand-400">
                      <x-app-icon name="eye" class="h-3.5 w-3.5" /> Opened
                    </span>
                  @endif
                </div>
                <p class="truncate text-xs text-plum-400">{{ $link }}</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <button type="button" x-data="{ copied: false }"
                  @click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($this->shareCaption($recipient->recipient_name, $link)) }})
                    .then(() => { copied = true; setTimeout(() => copied = false, 1500); window.toast('success', 'បានចម្លងតំណរបស់ «' + {{ \Illuminate\Support\Js::from($recipient->recipient_name) }} + '» — ឥឡូវផ្ញើទៅគេបាន។') })
                    .catch(() => window.toast('error', 'ចម្លងមិនបាន — សូមចុចសង្កត់លើតំណ ហើយចម្លងដោយដៃ។'))"
                  class="rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
                  <span x-show="!copied">Copy link</span>
                  <span x-cloak x-show="copied" class="text-green-600 dark:text-green-400">Copied!</span>
                </button>
                <button type="button" wire:click="toggleRecipient({{ $recipient->id }})"
                  class="rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
                  {{ $recipient->revoked_at ? 'Restore link' : 'Pause link' }}
                </button>
                <button type="button" wire:click="rotateRecipient({{ $recipient->id }})" wire:confirm="Create a new link for {{ $recipient->recipient_name }}? The old link will stop working."
                  class="rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">
                  New link
                </button>
                <button type="button" wire:click="removeRecipient({{ $recipient->id }})" wire:confirm="Remove {{ $recipient->recipient_name }}? Their link will stop working."
                  class="text-plum-400 hover:text-red-600">
                  <x-app-icon name="trash" class="h-4 w-4" />
                </button>
              </div>
            </div>
          @empty
            <p class="rounded-xl border border-dashed border-plum-300 py-10 text-center text-sm text-plum-500 dark:border-plum-700 dark:text-plum-400">No recipients yet — add one above.</p>
          @endforelse
        </div>

      @elseif ($section === 'responses')
        @unless ($invitation->fieldUnlocked('rsvp_enabled'))
          <p class="mt-4 flex items-center gap-2 rounded-lg bg-gold-500/10 px-4 py-3 text-sm text-gold-700 dark:text-gold-400">
            <x-app-icon name="lock-closed" class="h-4 w-4" /> RSVP isn't included in your plan, so guests can't respond yet.
          </p>
        @endunless

        <div class="mt-6 grid grid-cols-3 gap-3">
          @foreach ([
            ['label' => 'ចូលរួម', 'value' => $stats['attending'], 'class' => 'text-green-600 dark:text-green-400'],
            ['label' => 'មិនចូលរួម', 'value' => $stats['declined'], 'class' => 'text-red-600 dark:text-red-400'],
            ['label' => 'មិនទាន់ឆ្លើយ', 'value' => $stats['recipients'] - $stats['attending'] - $stats['declined'], 'class' => 'text-plum-900 dark:text-white'],
          ] as $tile)
            <div class="rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
              <p class="text-xs font-semibold text-plum-500 dark:text-plum-400">{{ $tile['label'] }}</p>
              <p class="mt-1 text-2xl font-extrabold {{ $tile['class'] }}">{{ $tile['value'] }}</p>
            </div>
          @endforeach
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-plum-200 bg-white dark:border-plum-800 dark:bg-plum-900">
          @forelse ($invitation->recipients as $recipient)
            <div wire:key="response-{{ $recipient->id }}" class="flex items-start justify-between gap-3 border-b border-plum-100 p-4 last:border-0 dark:border-plum-800">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $recipient->recipient_name }}</p>
                @if ($recipient->rsvp_note)
                  <p class="mt-1 text-sm text-plum-600 dark:text-plum-300">“{{ $recipient->rsvp_note }}”</p>
                @endif
              </div>
              @if ($recipient->rsvp_status === 'yes')
                <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">ចូលរួម</span>
              @elseif ($recipient->rsvp_status === 'no')
                <span class="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">មិនចូលរួម</span>
              @else
                <span class="shrink-0 rounded-full bg-plum-100 px-2.5 py-1 text-[11px] font-bold text-plum-500 dark:bg-plum-800 dark:text-plum-400">មិនទាន់ឆ្លើយ</span>
              @endif
            </div>
          @empty
            <p class="py-10 text-center text-sm text-plum-500 dark:text-plum-400">No guests yet.</p>
          @endforelse
        </div>
      @endif
    </div>
  </main>
</div>
