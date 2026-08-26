@props(['service'])

@php
$fromPrice = $service->plans->min('price') ?? $service->base_price;
@endphp

<a href="/service/{{ $service->slug }}" wire:navigate data-reveal
  class="hover-lift group relative flex flex-col overflow-hidden rounded-2xl border border-plum-200 bg-white shadow-sm transition-shadow hover:shadow-xl hover:shadow-brand-900/10 dark:border-plum-800 dark:bg-plum-900">
  @if ($service->is_featured)
    <span class="absolute left-3 top-3 z-10 flex items-center gap-1 rounded-full bg-gold-500 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-brand-950 shadow">
      <x-icon name="star" class="h-3 w-3" />
      Featured
    </span>
  @endif

  <div class="flex aspect-[4/3] items-center justify-center bg-linear-to-br from-brand-50 to-brand-100 text-brand-700 transition-colors group-hover:from-brand-100 group-hover:to-brand-200 dark:from-plum-800 dark:to-plum-950 dark:text-brand-300">
    <x-icon name="{{ $service->category->icon }}" class="h-14 w-14" />
  </div>

  <div class="flex flex-1 flex-col gap-1.5 p-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-300">{{ $service->category->name_km }}</span>
    <h3 class="text-base font-bold text-plum-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
      {{ $service->name_en }}
    </h3>
    <p class="line-clamp-2 flex-1 text-sm text-plum-500 dark:text-plum-400">{{ $service->short_description }}</p>

    <div class="mt-2 flex items-center justify-between border-t border-plum-100 pt-3 dark:border-plum-800">
      <div>
        <span class="text-[11px] uppercase text-plum-400">ចាប់ផ្តើមពី</span>
        <p class="text-lg font-extrabold text-brand-700 dark:text-brand-300">${{ number_format($fromPrice, 2) }}</p>
      </div>
      <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white dark:bg-plum-800 dark:text-brand-300">
        <x-icon name="chevron-right" class="h-4.5 w-4.5" />
      </span>
    </div>
  </div>
</a>
