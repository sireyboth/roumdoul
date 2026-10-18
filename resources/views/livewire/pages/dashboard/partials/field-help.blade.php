{{-- Visual help for one field: where it shows on the invitation, what it's for, an example.
     Expects $key, $catalogField, $invitation, $filled; $compact for the small inline card. --}}
@php $compact = $compact ?? false; @endphp

<div @class(['flex gap-3', 'flex-col items-center text-center' => ! $compact])>
  <x-invite-diagram :field="$key" @class(['shrink-0', 'w-14' => $compact, 'w-28' => ! $compact]) />

  <div @class(['min-w-0', 'flex-1' => $compact])>
    @unless ($compact)
      <p class="text-sm font-bold text-plum-900 dark:text-white">{{ $catalogField['label'] }}</p>
    @endunless

    <div @class(['flex flex-wrap items-center gap-1.5', 'mt-1.5 justify-center' => ! $compact])>
      @if (! $invitation->fieldUnlocked($key))
        <span class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold text-gold-600 dark:text-gold-400">
          <x-app-icon name="lock-closed" class="h-3 w-3" /> ភ្ញៀវមើលមិនឃើញ
        </span>
      @elseif ($filled)
        <span class="flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">
          <x-app-icon name="check-circle" class="h-3 w-3" /> បានបំពេញ
        </span>
      @else
        <span class="rounded-full bg-plum-100 px-2 py-0.5 text-[10px] font-bold text-plum-500 dark:bg-plum-800 dark:text-plum-400">ទទេ — មិនបង្ហាញ</span>
      @endif
      <span class="flex items-center gap-1 text-[10px] font-semibold text-brand-600 dark:text-brand-400">
        <span class="h-2 w-2 rounded-sm bg-brand-500"></span> បង្ហាញត្រង់នេះ
      </span>
    </div>

    @if (! empty($catalogField['hint']))
      <p @class(['text-xs leading-relaxed text-plum-600 dark:text-plum-300', 'mt-1.5' => $compact, 'mt-3' => ! $compact])>{{ $catalogField['hint'] }}</p>
    @endif

    @if (! empty($catalogField['example']))
      <p @class(['mt-2 inline-flex max-w-full items-center gap-1.5 rounded-lg bg-plum-50 px-2 py-1 text-xs dark:bg-plum-800/60'])>
        <span class="font-bold text-plum-400">ឧ.</span>
        <span class="truncate font-semibold text-plum-800 dark:text-plum-100">{{ $catalogField['example'] }}</span>
      </p>
    @endif
  </div>
</div>
