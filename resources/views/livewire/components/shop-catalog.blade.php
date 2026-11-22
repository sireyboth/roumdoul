<section id="shop" class="scroll-mt-32 border-b border-plum-100 dark:border-plum-800">
  <div class="relative overflow-clip bg-gradient-to-br from-brand-50 via-white to-plum-50 dark:from-plum-900 dark:via-plum-950 dark:to-plum-900">
    <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-brand-400/10 blur-3xl dark:bg-brand-500/10"></div>
    <div class="pointer-events-none absolute -left-10 bottom-0 h-48 w-48 rounded-full bg-gold-400/10 blur-3xl"></div>

    <div class="relative">
      <x-shop.catalog :services="$services" :categories="$categories" :active="$category" :search="trim($search)" inline>
        {{-- the section heading rides along in the sticky sidebar --}}
        <x-slot:heading>
          <div class="px-1">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-300">
              <x-app-icon name="cart" class="h-3.5 w-3.5" />
              {{ __('Shop') }}
            </span>
            <h2 class="mt-1 text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">
              {{ $category ? $category->name_km : __('All services') }}
            </h2>
            <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">
              {{ __(':count digital products, ready to use instantly', ['count' => $services->total()]) }}
            </p>
          </div>
        </x-slot:heading>
      </x-shop.catalog>
    </div>
  </div>
</section>
