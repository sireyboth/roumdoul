{{-- A row of numbered icon steps — "how this works" at a glance. Expects $flow: [['icon', 'title', 'text'], ...]. --}}
<ol @class(['mt-6 grid grid-cols-2 gap-2', 'sm:grid-cols-3' => count($flow) % 3 === 0, 'lg:grid-cols-6' => count($flow) === 6, 'sm:grid-cols-4' => count($flow) % 3 !== 0])>
  @foreach ($flow as $i => $step)
    <li class="relative flex flex-col items-center gap-1.5 rounded-xl border border-plum-200 bg-white px-3 py-4 text-center dark:border-plum-800 dark:bg-plum-900">
      <span class="absolute left-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white">{{ $i + 1 }}</span>
      <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
        <x-app-icon :name="$step['icon']" class="h-5 w-5" />
      </span>
      <span class="text-xs font-bold text-plum-900 dark:text-white">{{ $step['title'] }}</span>
      @if (! empty($step['text']))
        <span class="text-[11px] leading-snug text-plum-500 dark:text-plum-400">{{ $step['text'] }}</span>
      @endif
    </li>
  @endforeach
</ol>
