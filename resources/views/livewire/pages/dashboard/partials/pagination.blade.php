{{--
  Branded pager for the dashboard lists.
  Expects: $paginator (LengthAwarePaginator), $pageName (string), $perPageModel (wire:model name),
  $perPageOptions (int[]), $label (e.g. "invitations").
--}}
@php
  $current = $paginator->currentPage();
  $last = $paginator->lastPage();
  // Compact window: 1 … 4 5 [6] 7 8 … 20
  $pages = collect([1, $last, ...range(max(1, $current - 2), min($last, $current + 2))])->unique()->sort()->values();
@endphp

<div class="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
  <div class="flex items-center gap-3 text-sm text-plum-500 dark:text-plum-400">
    <span>
      {!! __('Showing :from–:to of :total', [
        'from' => '<b class="font-semibold text-plum-800 dark:text-plum-100">'.($paginator->firstItem() ?? 0).'</b>',
        'to' => '<b class="font-semibold text-plum-800 dark:text-plum-100">'.($paginator->lastItem() ?? 0).'</b>',
        'total' => '<b class="font-semibold text-plum-800 dark:text-plum-100">'.$paginator->total().'</b>',
      ]) !!}
    </span>
    <label class="flex items-center gap-1.5">
      <span class="sr-only">{{ __('Per page') }}</span>
      <select wire:model.live="{{ $perPageModel }}"
        class="rounded-lg border border-plum-200 bg-white py-1 pl-2 pr-7 text-sm text-plum-700 focus:border-brand-500 focus:ring-brand-500 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-200">
        @foreach ($perPageOptions as $n)
          <option value="{{ $n }}">{{ __(':count / page', ['count' => $n]) }}</option>
        @endforeach
      </select>
    </label>
  </div>

  @if ($last > 1)
    <nav class="flex items-center gap-1" aria-label="{{ __('Pagination') }}">
      <button type="button" wire:click="previousPage('{{ $pageName }}')" @disabled($paginator->onFirstPage())
        aria-label="{{ __('Previous') }}"
        class="grid h-9 w-9 place-items-center rounded-lg border border-plum-200 bg-white text-plum-600 transition hover:border-brand-400 hover:text-brand-600 disabled:pointer-events-none disabled:opacity-40 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-300">
        <x-app-icon name="chevron-left" class="h-4 w-4" />
      </button>

      @foreach ($pages as $i => $page)
        @if ($i > 0 && $page - $pages[$i - 1] > 1)
          <span class="px-1 text-plum-400">…</span>
        @endif
        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:key="{{ $pageName }}-{{ $page }}"
          @if ($page === $current) aria-current="page" @endif
          @class([
            'h-9 min-w-9 rounded-lg px-2 text-sm font-semibold transition',
            'bg-brand-600 text-white shadow-sm shadow-brand-600/30' => $page === $current,
            'border border-plum-200 bg-white text-plum-600 hover:border-brand-400 hover:text-brand-600 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-300' => $page !== $current,
          ])>
          {{ $page }}
        </button>
      @endforeach

      <button type="button" wire:click="nextPage('{{ $pageName }}')" @disabled(! $paginator->hasMorePages())
        aria-label="{{ __('Next') }}"
        class="grid h-9 w-9 place-items-center rounded-lg border border-plum-200 bg-white text-plum-600 transition hover:border-brand-400 hover:text-brand-600 disabled:pointer-events-none disabled:opacity-40 dark:border-plum-700 dark:bg-plum-900 dark:text-plum-300">
        <x-app-icon name="chevron-right" class="h-4 w-4" />
      </button>
    </nav>
  @endif
</div>
