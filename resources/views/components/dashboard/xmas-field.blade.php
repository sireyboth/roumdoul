{{-- One labelled field of the Christmas editor (partials/christmas-editor.blade.php): label with a
     required star / Premium badge, the input (slot), an optional hint, and its validation error
     shown the same way as the rest of the dashboard's forms. English-only, like the template. --}}
@props(['key', 'label', 'for' => null, 'required' => false, 'locked' => false, 'hint' => null, 'error' => null])

<div id="xm-field-{{ $key }}" {{ $attributes->class(['min-w-0 scroll-mt-24']) }}>
  <div class="mb-1.5 flex items-end justify-between gap-2">
    <label for="{{ $for ?? 'xm-input-'.$key }}" class="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-plum-800 dark:text-plum-100">
      {{ $label }}
      @if ($required)
        <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
      @endif
      @if ($locked)
        <span title="{{ \App\Support\Invitations\ChristmasTemplate::t('editor.premium_hint') }}"
          class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-gold-600 dark:text-gold-400">
          <x-app-icon name="lock-closed" class="h-3 w-3" /> {{ \App\Support\Invitations\ChristmasTemplate::t('editor.premium') }}
        </span>
      @endif
    </label>
    {{ $aside ?? '' }}
  </div>

  {{ $slot }}

  @if ($hint)
    <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">{{ $hint }}</p>
  @endif
  @if ($error)
    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $error }}</p>
  @endif
</div>
