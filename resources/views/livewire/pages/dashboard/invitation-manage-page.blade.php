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

<div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
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
    <div class="mx-auto max-w-3xl">
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
        <form wire:submit="save" class="mt-6"
          x-data="{ uploading: 0 }"
          x-on:photo-upload-start="uploading++"
          x-on:photo-upload-end="uploading = Math.max(0, uploading - 1)">
          <div class="flex flex-col gap-5 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
            @foreach ($this->sectionFields($section) as $key)
              @php $catalogField = $fieldCatalog[$key]; @endphp
              <div wire:key="field-{{ $key }}">
                <div class="mb-1.5 flex items-center gap-2">
                  <label class="block text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $catalogField['label'] }}</label>
                  @unless ($invitation->fieldUnlocked($key))
                    <span title="Not included in your plan — this won't show to your guests"
                      class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-gold-600 dark:text-gold-400">
                      <x-app-icon name="lock-closed" class="h-3 w-3" /> Premium
                    </span>
                  @endunless
                </div>
                @if (! empty($catalogField['hint']))
                  <p class="-mt-0.5 mb-2 text-xs text-plum-500 dark:text-plum-400">{{ $catalogField['hint'] }}</p>
                @endif
                @include('livewire.pages.dashboard.partials.field-input')
              </div>
            @endforeach
          </div>

          <div class="sticky bottom-0 mt-4 flex items-center gap-3 bg-plum-50/90 py-4 backdrop-blur dark:bg-plum-950/90">
            <button type="submit" :disabled="uploading > 0" wire:loading.attr="disabled" wire:target="save"
              class="flex items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60">
              <span x-show="uploading > 0" style="display: none">Wait for photos&hellip;</span>
              <span x-show="uploading === 0">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
              </span>
            </button>
            @if ($saved)
              <p class="text-sm font-semibold text-green-600 dark:text-green-400">Saved!</p>
            @endif
          </div>
        </form>

      @elseif ($section === 'guests')
        @php $usage = $invitation->max_recipients ? min(100, $stats['recipients'] / $invitation->max_recipients * 100) : 0; @endphp
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
                  @click="navigator.clipboard.writeText('{{ $link }}'); copied = true; setTimeout(() => copied = false, 1500)"
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
