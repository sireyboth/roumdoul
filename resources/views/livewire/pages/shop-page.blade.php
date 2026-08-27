<div>
  <div class="border-b border-plum-100 bg-brand-50/40 dark:border-plum-800 dark:bg-plum-800/20">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <p class="text-xs font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-300">ហាង</p>
      <h1 class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">
        {{ $category ? $category->name_km : 'គ្រប់សេវាកម្មទាំងអស់' }}
      </h1>

      <form action="{{ $category ? '/shop/'.$category->slug : '/shop' }}" method="GET" class="mt-5 max-w-xl">
        <div class="relative">
          <x-app-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
          <input type="text" name="search" value="{{ $search }}" placeholder="ស្វែងរកសេវាកម្ម..."
            class="w-full rounded-full border border-plum-200 bg-white py-2.5 pl-10 pr-4 text-sm outline-none transition-colors placeholder:text-plum-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-900" />
        </div>
      </form>
    </div>
  </div>

  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-8 lg:flex-row">
      <x-shop.sidebar :categories="$categories" :active="$category" />

      <div class="flex-1">
        @if ($search !== '')
          <p class="mb-4 text-sm text-plum-500 dark:text-plum-400">
            លទ្ធផលសម្រាប់ &ldquo;<span class="font-semibold text-plum-800 dark:text-plum-100">{{ $search }}</span>&rdquo; &mdash; {{ $services->total() }} ផលិតផល
          </p>
        @endif

        @if ($services->isEmpty())
          <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-plum-300 py-20 text-center dark:border-plum-700">
            <x-app-icon name="search" class="h-10 w-10 text-plum-300" />
            <p class="text-sm font-medium text-plum-500 dark:text-plum-400">រកមិនឃើញសេវាកម្មដែលត្រូវនឹងលក្ខខណ្ឌនេះទេ</p>
            <a href="/shop" wire:navigate class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">សម្អាតតម្រង</a>
          </div>
        @else
          <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-50">
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
