{{-- One form card (a FORM_GROUPS group). Expects $groupKey, $group, plus the form's $fieldCatalog,
     $invitation, $initialFocus, $isFilled. Short fields pair up two per row; $group['full'] take a
     whole row. An either/or group sits side by side with a vertical "or" once the card is wide. --}}
<section wire:key="group-{{ $groupKey }}" class="@container rounded-2xl border border-plum-200 bg-white p-4 sm:p-6 dark:border-plum-800 dark:bg-plum-900">
  <h2 class="mb-4 flex items-center gap-3 text-lg font-bold text-plum-900 dark:text-white">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
      <x-app-icon :name="$group['icon']" class="h-5 w-5" />
    </span>
    {{ $group['label'] }}
  </h2>

  <div @class([
    'grid items-start gap-4',
    '@lg:grid-cols-2' => ! $group['or'],
    '@3xl:grid-cols-3' => ! $group['or'] && ($group['cols'] ?? 2) === 3,
    '@4xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] @4xl:items-stretch' => $group['or'],
  ])>
    @foreach ($group['fields'] as $i => $key)
      @php
        $catalogField = $fieldCatalog[$key];
        $isToggle = $catalogField['type'] === 'boolean';
      @endphp

      @if ($group['or'] && $i > 0)
        <div class="flex items-center gap-3 @4xl:flex-col">
          <span class="h-px flex-1 bg-plum-200 @4xl:h-auto @4xl:w-px dark:bg-plum-700"></span>
          <span class="whitespace-nowrap rounded-full bg-gold-500 px-4 py-1.5 text-sm font-bold text-white">ឬ</span>
          <span class="h-px flex-1 bg-plum-200 @4xl:h-auto @4xl:w-px dark:bg-plum-700"></span>
        </div>
      @endif

      <div wire:key="field-{{ $key }}" id="field-{{ $key }}"
        x-on:focusin="focus = '{{ $key }}'" x-on:click="focus = '{{ $key }}'"
        :class="focus === '{{ $key }}' && 'bg-brand-50/60 ring-2 ring-brand-300 dark:bg-brand-500/5 dark:ring-brand-500/40'"
        @class([
          'min-w-0 scroll-mt-24 rounded-xl border p-4 transition-colors',
          'border-plum-100 dark:border-plum-800' => ! $group['or'],
          'border-dashed border-plum-300 dark:border-plum-700' => $group['or'],
          'col-span-full' => ! $group['or'] && in_array($key, $group['full'], true),
          'bg-plum-50 dark:bg-plum-800/40' => $isToggle,
        ])>
        <div @class(['flex items-center gap-2', 'mb-2' => ! $isToggle, 'justify-between' => $isToggle])>
          <span class="flex flex-wrap items-center gap-2">
            <label for="input-{{ $key }}" class="block text-base font-semibold text-plum-800 dark:text-plum-100">{{ $catalogField['label'] }}</label>
            @unless ($invitation->fieldUnlocked($key))
              <span title="Not included in your plan — this won't show to your guests"
                class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-gold-600 dark:text-gold-400">
                <x-app-icon name="lock-closed" class="h-3 w-3" /> Premium
              </span>
            @endunless
          </span>
          @if ($isToggle)
            @include('livewire.pages.dashboard.partials.field-input')
          @endif
        </div>

        {{-- Help card (narrow screens): opens under the field being edited --}}
        <div x-show="focus === '{{ $key }}'" @if ($key !== $initialFocus) style="display: none" @endif
          class="mb-3 mt-2 rounded-lg border border-brand-200 bg-white p-4 xl:hidden dark:border-brand-500/30 dark:bg-plum-900">
          @include('livewire.pages.dashboard.partials.field-help', ['filled' => $isFilled($key), 'size' => 'inline'])
        </div>

        @unless ($isToggle)
          @include('livewire.pages.dashboard.partials.field-input')
        @endunless
      </div>
    @endforeach
  </div>
</section>
