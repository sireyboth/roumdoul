@props(['service'])

@php
$fromPrice = (float) ($service->plans->min('price') ?? $service->base_price);
$discountedFromPrice = $service->discountedPrice($fromPrice);
$discountBadge = $service->discount_type === 'percentage'
  ? '-'.rtrim(rtrim(number_format($service->discount_value, 2), '0'), '.').'%'
  : '-$'.number_format($service->discount_value, 2);
@endphp

<a href="/service/{{ $service->slug }}" wire:navigate data-reveal
  class="group relative flex flex-col overflow-hidden rounded-lg border border-plum-200 bg-white transition-colors hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
  @if ($service->is_featured)
    <span class="absolute left-3 top-3 z-10 rounded bg-gold-500 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-950">
      Featured
    </span>
  @endif
  @if ($service->hasDiscount())
    <span class="absolute right-3 top-3 z-10 rounded bg-red-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">
      {{ $discountBadge }}
    </span>
  @endif

  <div class="relative flex aspect-[4/3] items-center justify-center bg-plum-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300 {{ $service->in_stock ? '' : 'opacity-50 grayscale' }}">
    @if ($service->image_path)
      <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($service->image_path) }}" alt="{{ $service->name_en }}" loading="lazy" decoding="async" class="h-full w-full object-cover" />
    @else
      <x-app-icon name="{{ $service->category->icon }}" class="h-12 w-12" />
    @endif
    @unless ($service->in_stock)
      <div class="absolute inset-0 flex items-center justify-center bg-plum-950/40">
        <span class="rounded-full bg-plum-950/80 px-4 py-1.5 text-xs font-extrabold uppercase tracking-widest text-white">
          Out of Stock
        </span>
      </div>
    @endunless
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
        @if ($service->hasDiscount())
          <p class="flex items-baseline gap-1.5">
            <span class="text-lg font-bold text-red-600 dark:text-red-400">${{ number_format($discountedFromPrice, 2) }}</span>
            <span class="text-xs text-plum-400 line-through">${{ number_format($fromPrice, 2) }}</span>
          </p>
        @else
          <p class="text-lg font-bold text-brand-700 dark:text-brand-300">${{ number_format($fromPrice, 2) }}</p>
        @endif
      </div>
      <x-app-icon name="chevron-right" class="h-4.5 w-4.5 text-plum-300 transition-colors group-hover:text-brand-600" />
    </div>
  </div>
</a>
