@props(['services', 'categories', 'active' => null, 'search' => '', 'inline' => false, 'heading' => null])

{{-- The shop grid: category sidebar + sort, product cards, pagination. Used by /shop (links to /shop/{category})
     and the homepage #shop section (inline: filters in place through the ShopCatalog component). --}}
  <div @class(["mx-auto max-w-7xl px-4 sm:px-6 lg:px-8", "py-14" => $inline, "py-10" => ! $inline])>
    <div class="flex flex-col gap-8 lg:flex-row">
      {{-- stays in view while the products scroll, just below the header (logo row + menu bar ≈ 7.3rem);
           sticky needs no overflow-hidden on any parent --}}
      <div class="flex flex-col gap-4 lg:sticky lg:top-32 lg:max-h-[calc(100vh-9rem)] lg:w-64 lg:shrink-0 lg:self-start lg:overflow-y-auto lg:overscroll-contain lg:p-1 lg:pb-2">
        {{ $heading }}
        @if ($inline)
          <div class="group relative">
            <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400 transition-colors group-focus-within:text-brand-500" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search services...') }}" aria-label="{{ __('Search services...') }}"
              class="w-full rounded-full border border-plum-200 bg-white/90 py-2.5 pl-10 pr-4 text-sm shadow-sm outline-none backdrop-blur transition-all placeholder:text-plum-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-900/90 dark:focus:ring-brand-900/40" />
          </div>
        @endif
        <x-shop.sidebar :categories="$categories" :active="$active" :inline="$inline" />
      </div>

      <div class="min-w-0 flex-1">
        @if ($search !== '')
          <p data-reveal class="mb-4 text-sm text-plum-500 dark:text-plum-400">
            {{ __('Results for') }} &ldquo;<span class="font-semibold text-plum-800 dark:text-plum-100">{{ $search }}</span>&rdquo; &mdash; {{ __(':count products', ['count' => $services->total()]) }}
          </p>
        @endif

        @if ($services->isEmpty())
          <div data-reveal class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-plum-300 bg-plum-50/50 py-24 text-center dark:border-plum-700 dark:bg-plum-900/30">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-plum-300 shadow-sm dark:bg-plum-800">
              <x-app-icon name="search" class="h-8 w-8" />
            </span>
            <div>
              <p class="text-sm font-semibold text-plum-700 dark:text-plum-200">{{ __('No services match these filters') }}</p>
              <p class="mt-1 text-xs text-plum-400 dark:text-plum-500">{{ __('Try a different keyword or clear the filters') }}</p>
            </div>
            @if ($inline)
              <button type="button" wire:click="clearFilters"
                class="mt-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md">
                {{ __('Clear filters') }}
              </button>
            @else
              <a href="/shop" wire:navigate
                class="mt-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md">
                {{ __('Clear filters') }}
              </a>
            @endif
          </div>
        @else
          <div class="grid grid-cols-2 gap-5 transition-opacity duration-200 sm:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-50">
            @foreach ($services as $service)
              <x-shop.product-card :service="$service" wire:key="service-{{ $service->id }}" />
            @endforeach
          </div>

          <div class="mt-10">
            {{ $inline ? $services->links(data: ['scrollTo' => '#shop']) : $services->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
