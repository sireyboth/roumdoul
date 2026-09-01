<div>
  <div class="relative overflow-hidden border-b border-plum-100 bg-gradient-to-br from-brand-50 via-white to-plum-50 dark:border-plum-800 dark:from-plum-900 dark:via-plum-950 dark:to-plum-900">
    <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-brand-400/10 blur-3xl dark:bg-brand-500/10"></div>
    <div class="pointer-events-none absolute -left-10 bottom-0 h-48 w-48 rounded-full bg-gold-400/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <nav class="flex items-center gap-1.5 text-xs font-medium text-plum-400 dark:text-plum-500">
        <a href="/" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-300">ទំព័រដើម</a>
        <x-app-icon name="chevron-right" class="h-3 w-3" />
        @if ($category)
          <a href="/shop" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-300">ហាង</a>
          <x-app-icon name="chevron-right" class="h-3 w-3" />
          <span class="text-plum-600 dark:text-plum-300">{{ $category->name_km }}</span>
        @else
          <span class="text-plum-600 dark:text-plum-300">ហាង</span>
        @endif
      </nav>

      <div data-reveal class="mt-3 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-300">ហាង</p>
          <h1 class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">
            {{ $category ? $category->name_km : 'គ្រប់សេវាកម្មទាំងអស់' }}
          </h1>
          <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">
            {{ $services->total() }} ផលិតផលឌីជីថល ត្រៀមឱ្យប្រើប្រាស់ភ្លាមៗ
          </p>
        </div>
      </div>

      <form action="{{ $category ? '/shop/'.$category->slug : '/shop' }}" method="GET" class="mt-6 max-w-xl">
        <div class="group relative">
          <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400 transition-colors group-focus-within:text-brand-500" />
          <input type="text" name="search" value="{{ $search }}" placeholder="ស្វែងរកសេវាកម្ម..."
            class="w-full rounded-full border border-plum-200 bg-white/90 py-3 pl-10 pr-4 text-sm shadow-sm outline-none backdrop-blur transition-all placeholder:text-plum-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-900/90 dark:focus:ring-brand-900/40" />
        </div>
      </form>
    </div>
  </div>

  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-8 lg:flex-row">
      <div class="lg:sticky lg:top-24 lg:h-fit lg:w-64 lg:shrink-0">
        <x-shop.sidebar :categories="$categories" :active="$category" />
      </div>

      <div class="min-w-0 flex-1">
        @if ($search !== '')
          <p data-reveal class="mb-4 text-sm text-plum-500 dark:text-plum-400">
            លទ្ធផលសម្រាប់ &ldquo;<span class="font-semibold text-plum-800 dark:text-plum-100">{{ $search }}</span>&rdquo; &mdash; {{ $services->total() }} ផលិតផល
          </p>
        @endif

        @if ($services->isEmpty())
          <div data-reveal class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-plum-300 bg-plum-50/50 py-24 text-center dark:border-plum-700 dark:bg-plum-900/30">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-plum-300 shadow-sm dark:bg-plum-800">
              <x-app-icon name="search" class="h-8 w-8" />
            </span>
            <div>
              <p class="text-sm font-semibold text-plum-700 dark:text-plum-200">រកមិនឃើញសេវាកម្មដែលត្រូវនឹងលក្ខខណ្ឌនេះទេ</p>
              <p class="mt-1 text-xs text-plum-400 dark:text-plum-500">សាកល្បងពាក្យគន្លឹះផ្សេង ឬសម្អាតតម្រង</p>
            </div>
            <a href="/shop" wire:navigate
              class="mt-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md">
              សម្អាតតម្រង
            </a>
          </div>
        @else
          <div class="grid grid-cols-2 gap-5 transition-opacity duration-200 sm:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-50">
            @foreach ($services as $service)
              <x-shop.product-card :service="$service" wire:key="service-{{ $service->id }}" />
            @endforeach
          </div>

          <div class="mt-10">
            {{ $services->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
