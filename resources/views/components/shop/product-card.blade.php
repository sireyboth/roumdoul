@props(['service'])

@php
$fromPrice = $service->plans->min('price') ?? $service->base_price;
@endphp

<a href="/service/{{ $service->slug }}" wire:navigate data-reveal
  class="group relative flex flex-col overflow-hidden rounded-lg border border-plum-200 bg-white transition-colors hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
  @if ($service->is_featured)
    <span class="absolute left-3 top-3 z-10 rounded bg-gold-500 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-950">
      Featured
    </span>
  @endif

  <div class="flex aspect-[4/3] items-center justify-center bg-plum-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
    @if ($service->image_path)
      <img src="{{ \Illuminate\Support\Facades\Storage::url($service->image_path) }}" alt="{{ $service->name_en }}" class="h-full w-full object-cover" />
    @else
      <x-app-icon name="{{ $service->category->icon }}" class="h-12 w-12" />
    @endif
  </div>

  <div class="flex flex-1 flex-col gap-1.5 p-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-plum-400 dark:text-plum-500">{{ $service->category->name_km }}</span>
    <h3 class="text-base font-bold text-plum-900 transition-colors group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
      {{ $service->name_en }}
    </h3>
    <p class="line-clamp-2 flex-1 text-sm text-plum-500 dark:text-plum-400">{{ $service->short_description }}</p>

    <div class="mt-2 flex items-center justify-between border-t border-plum-100 pt-3 dark:border-plum-800">
      <div>
        <span class="text-[11px] uppercase text-plum-400">ចាប់ផ្តើមពី</span>
        <p class="text-lg font-bold text-brand-700 dark:text-brand-300">${{ number_format($fromPrice, 2) }}</p>
      </div>
      <x-app-icon name="chevron-right" class="h-4.5 w-4.5 text-plum-300 transition-colors group-hover:text-brand-600" />
    </div>
  </div>
</a>
