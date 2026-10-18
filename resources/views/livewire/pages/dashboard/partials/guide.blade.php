{{-- "How to use" guide for the invitation workspace. Expects $invitation, $navItems, $fieldCatalog, $isPaid. --}}
@php
  $flow = [
    ['icon' => 'pencil', 'title' => 'បំពេញព័ត៌មាន', 'text' => 'ឈ្មោះ ថ្ងៃ ទីកន្លែង → Save'],
    ['icon' => 'photo', 'title' => 'ដាក់រូបភាព', 'text' => 'ចុចប្រអប់ ជ្រើសរូប'],
    ['icon' => 'eye', 'title' => 'មើលជាមុន', 'text' => 'ឃើញដូចភ្ញៀវឃើញ'],
    ['icon' => 'user-plus', 'title' => 'បន្ថែមភ្ញៀវ', 'text' => 'ម្នាក់ = តំណមួយ'],
    ['icon' => 'paper-airplane', 'title' => 'ផ្ញើតំណ', 'text' => 'Telegram · Messenger'],
    ['icon' => 'chat', 'title' => 'មើលចម្លើយ', 'text' => 'អ្នកណាចូលរួម'],
  ];

  $sectionHelp = [
    'overview' => 'ស្ថិតិ និងជំហានបន្ទាប់',
    'details' => 'ឈ្មោះ សារ ថ្ងៃ ទីកន្លែង',
    'media' => 'រូបគម្រប និងរូបថត',
    'program' => 'របៀបវារៈ និងរឿងរ៉ាវ',
    'guests' => 'បន្ថែមភ្ញៀវ និង Copy link',
    'responses' => 'អ្នកចូលរួម / មិនចូលរួម',
  ];

  $fieldGroups = collect(['details', 'media', 'program'])
    ->mapWithKeys(fn ($s) => [$s => $this->sectionFields($s)])
    ->filter();
  $open = $this->visibleSections();
  $isFilled = fn (string $key) => filled($fieldValues[$key] ?? null);
@endphp

<div x-data="{ zoom: null }">
@include('livewire.pages.dashboard.partials.field-help-zoom', ['zoomFields' => $fieldGroups->flatten()->all()])

<p class="mt-1 text-base text-plum-500 dark:text-plum-400">របៀបប្រើប្រាស់ ក្នុង ៦ ជំហាន</p>

{{-- Step by step, as icons --}}
@include('livewire.pages.dashboard.partials.flow-steps', ['flow' => $flow])

{{-- The sidebar menu, as tiles that go straight there --}}
<h2 class="mt-10 text-xl font-bold text-plum-900 dark:text-white">ម៉ឺនុយនៅខាងឆ្វេង</h2>
<div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
  @foreach ($this->availableSections() as $key)
    @continue(! isset($sectionHelp[$key]))
    <button type="button" wire:key="guide-nav-{{ $key }}" wire:click="showSection('{{ $key }}')" @disabled(! in_array($key, $open, true))
      class="flex items-center gap-3 rounded-xl border border-plum-200 bg-white p-4 text-left transition-colors enabled:hover:border-brand-400 disabled:cursor-default dark:border-plum-800 dark:bg-plum-900">
      <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
        <x-app-icon :name="$navItems[$key]['icon']" class="h-5 w-5" />
      </span>
      <span class="min-w-0">
        <span class="block text-base font-semibold text-plum-900 dark:text-white">{{ $navItems[$key]['label'] }}</span>
        <span class="block text-sm text-plum-500 dark:text-plum-400">{{ $sectionHelp[$key] }}</span>
      </span>
    </button>
  @endforeach
</div>

{{-- Every field on this template, with where it shows on the invitation --}}
@if ($fieldGroups->isNotEmpty())
  <h2 class="mt-10 text-xl font-bold text-plum-900 dark:text-white">ប្រអប់នីមួយៗ បង្ហាញនៅកន្លែងណា?</h2>
  <p class="mt-2 flex items-center gap-2 text-sm text-plum-600 dark:text-plum-300">
    <span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span> ពណ៌ផ្កាឈូក = កន្លែងដែលប្រអប់នោះបង្ហាញលើធៀប · ប្រអប់ទទេមិនបង្ហាញ
  </p>

  @foreach ($fieldGroups as $group => $keys)
    <h3 class="mt-6 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-plum-400">
      <x-app-icon :name="$navItems[$group]['icon']" class="h-4 w-4" /> {{ $navItems[$group]['label'] }}
    </h3>
    <div class="mt-3 grid gap-3 lg:grid-cols-2">
      @foreach ($keys as $key)
        <div wire:key="guide-{{ $key }}" class="flex gap-4 rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
          <button type="button" @click="zoom = '{{ $key }}'" title="មើលពេញអេក្រង់" class="shrink-0 self-start rounded-lg transition hover:ring-2 hover:ring-brand-300"><x-invite-diagram :field="$key" class="w-24" /></button>
          <div class="flex min-w-0 flex-1 flex-col">
            <p class="flex flex-wrap items-center gap-1.5 text-base font-semibold text-plum-900 dark:text-white">
              {{ $fieldCatalog[$key]['label'] }}
              @unless ($invitation->fieldUnlocked($key))
                <span class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-gold-600 dark:text-gold-400">
                  <x-app-icon name="lock-closed" class="h-3 w-3" /> Premium
                </span>
              @endunless
            </p>
            <p class="mt-1.5 text-sm leading-relaxed text-plum-700 dark:text-plum-200">{{ $fieldCatalog[$key]['hint'] ?? '' }}</p>
            @if (! empty($fieldCatalog[$key]['example']))
              <p class="mt-2 truncate text-sm text-plum-500 dark:text-plum-400"><span class="font-bold">ឧ.</span> {{ $fieldCatalog[$key]['example'] }}</p>
            @endif
            <div class="mt-auto flex flex-wrap gap-2 pt-3">
              <button type="button" @click="zoom = '{{ $key }}'" class="rounded-full border border-plum-200 px-3 py-1.5 text-xs font-semibold text-plum-600 hover:border-brand-400 dark:border-plum-700 dark:text-plum-300">មើលពេញអេក្រង់</button>
              @if ($isPaid)
                <button type="button" wire:click="editField('{{ $key }}')" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">បំពេញឥឡូវ →</button>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
@endif

{{-- Good to know, one icon per point --}}
<h2 class="mt-10 text-xl font-bold text-plum-900 dark:text-white">គួរដឹង</h2>
<div class="mt-3 grid gap-2 sm:grid-cols-2">
  @foreach (array_filter([
    ['icon' => 'lock-closed', 'text' => 'ផ្លាក Premium = មិននៅក្នុងគម្រោងរបស់អ្នក ភ្ញៀវមើលមិនឃើញ។'],
    ['icon' => 'check-circle', 'text' => 'ចុច Save ទើបរក្សាទុក។ ភ្ញៀវឃើញការកែភ្លាមៗ មិនបាច់ផ្ញើតំណថ្មី។'],
    ['icon' => 'users', 'text' => 'ភ្ញៀវបានរហូតដល់ '.$invitation->max_recipients.' នាក់។ លុបភ្ញៀវ = តំណគេលែងដំណើរការ។'],
    $invitation->expires_at ? ['icon' => 'clock', 'text' => 'ដំណើរការដល់ '.$invitation->expires_at->format('d M Y').'។ ក្រោយនោះតំណបិទ។'] : null,
    ['icon' => 'photo', 'text' => 'រូប JPG/PNG/WebP ≤ 20 MB · GIF ≤ 5 MB។'],
  ]) as $note)
    <div class="flex items-start gap-3 rounded-xl border border-plum-200 bg-white p-4 dark:border-plum-800 dark:bg-plum-900">
      <x-app-icon :name="$note['icon']" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600 dark:text-brand-400" />
      <p class="text-base text-plum-700 dark:text-plum-300">{!! str_replace('Premium', '<strong class="text-gold-600 dark:text-gold-400">Premium</strong>', e($note['text'])) !!}</p>
    </div>
  @endforeach
</div>
</div>
