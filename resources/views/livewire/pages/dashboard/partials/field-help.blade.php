{{-- Visual help for one field: where it shows on the invitation, what it's for, an example.
     Expects $key, $catalogField, $invitation, $filled. $size: 'inline' (under the field on
     narrow screens), 'panel' (side panel, default) or 'full' (the full-screen view). The
     "full screen" button sets `zoom` on the nearest Alpine scope that has one. --}}
@php $size = $size ?? 'panel'; @endphp

<div @class([
  'flex gap-4',
  'flex-col items-center text-center' => $size === 'panel',
  'flex-col items-center gap-6 text-center sm:flex-row sm:items-center sm:text-left' => $size === 'full',
])>
  <x-invite-diagram :field="$key" @class([
    'shrink-0',
    'w-24 sm:w-28' => $size === 'inline',
    'w-48' => $size === 'panel',
    'max-h-[60vh] w-auto sm:max-h-[78vh]' => $size === 'full',
  ]) />

  <div @class(['min-w-0', 'flex-1' => $size !== 'panel', 'w-full' => $size === 'panel'])>
    @unless ($size === 'inline')
      <p @class(['font-bold text-plum-900 dark:text-white', 'text-lg' => $size === 'panel', 'text-2xl' => $size === 'full'])>{{ $catalogField['label'] }}</p>
    @endunless

    <div @class(['flex flex-wrap items-center gap-2', 'mt-2 justify-center' => $size === 'panel', 'mt-3 justify-center sm:justify-start' => $size === 'full'])>
      @if (! $invitation->fieldUnlocked($key))
        <span class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2.5 py-1 text-xs font-bold text-gold-600 dark:text-gold-400">
          <x-app-icon name="lock-closed" class="h-3.5 w-3.5" /> ភ្ញៀវមើលមិនឃើញ
        </span>
      @elseif ($filled)
        <span class="flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700 dark:bg-green-900/30 dark:text-green-400">
          <x-app-icon name="check-circle" class="h-3.5 w-3.5" /> បានបំពេញ
        </span>
      @else
        <span class="rounded-full bg-plum-100 px-2.5 py-1 text-xs font-bold text-plum-500 dark:bg-plum-800 dark:text-plum-400">ទទេ — មិនបង្ហាញ</span>
      @endif
      <span class="flex items-center gap-1.5 text-xs font-semibold text-brand-600 dark:text-brand-400">
        <span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span> បង្ហាញត្រង់នេះ
      </span>
    </div>

    @if (! empty($catalogField['hint']))
      <p @class([
        'leading-relaxed text-plum-700 dark:text-plum-200',
        'mt-2 text-sm' => $size === 'inline',
        'mt-4 text-[15px]' => $size === 'panel',
        'mt-5 text-lg sm:text-xl' => $size === 'full',
      ])>{{ $catalogField['hint'] }}</p>
    @endif

    @if (! empty($catalogField['example']))
      <p @class([
        'mt-3 inline-flex max-w-full items-center gap-2 rounded-lg bg-plum-50 px-3 py-1.5 dark:bg-plum-800/60',
        'text-sm' => $size !== 'full',
        'text-lg' => $size === 'full',
      ])>
        <span class="font-bold text-plum-400">ឧ.</span>
        <span class="truncate font-semibold text-plum-800 dark:text-plum-100">{{ $catalogField['example'] }}</span>
      </p>
    @endif

    @unless ($size === 'full')
      <div @class(['mt-3', 'flex justify-center' => $size === 'panel'])>
        <button type="button" @click.stop="zoom = '{{ $key }}'"
          class="flex items-center gap-1.5 rounded-full border border-brand-200 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:border-brand-400 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-300 dark:hover:bg-brand-500/10">
          <x-app-icon name="arrows-expand" class="h-4 w-4" /> មើលពេញអេក្រង់
        </button>
      </div>
    @endunless
  </div>
</div>
