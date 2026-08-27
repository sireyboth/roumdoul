<div>
  <div class="border-b border-plum-100 bg-brand-50/40 dark:border-plum-800 dark:bg-plum-800/20">
    <div class="mx-auto max-w-7xl px-4 py-4 text-sm sm:px-6 lg:px-8">
      <nav class="flex items-center gap-1.5 text-plum-500 dark:text-plum-400">
        <a href="/shop" wire:navigate class="hover:text-brand-700 dark:hover:text-white">ហាង</a>
        <x-app-icon name="chevron-right" class="h-3.5 w-3.5" />
        <a href="/shop/{{ $service->category->slug }}" wire:navigate class="hover:text-brand-700 dark:hover:text-white">{{ $service->category->name_km }}</a>
        <x-app-icon name="chevron-right" class="h-3.5 w-3.5" />
        <span class="text-plum-800 dark:text-plum-100">{{ $service->name_en }}</span>
      </nav>
    </div>
  </div>

  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
      <div>
        <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-lg bg-plum-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
          @if ($service->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($service->image_path) }}" alt="{{ $service->name_en }}" class="h-full w-full object-cover" />
          @else
            <x-app-icon name="{{ $service->category->icon }}" class="h-28 w-28" />
          @endif
        </div>
      </div>

      <div>
        <div class="mb-3 flex flex-wrap items-center gap-2">
          @if ($service->is_featured)
            <span class="inline-flex items-center gap-1 rounded-full bg-gold-500 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-brand-950">
              <x-app-icon name="star" class="h-3 w-3" /> Featured
            </span>
          @endif
          @if ($service->in_stock)
            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-green-700 dark:bg-green-900/30 dark:text-green-400">
              <x-app-icon name="check-circle" class="h-3 w-3" /> In Stock
            </span>
          @else
            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-red-700 dark:bg-red-900/30 dark:text-red-400">
              <x-app-icon name="x-circle" class="h-3 w-3" /> Out of Stock
            </span>
          @endif
        </div>
        <span class="block text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-300">{{ $service->category->name_km }}</span>
        <h1 class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">{{ $service->name_en }}</h1>
        <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">{{ $service->name_km }}</p>
        <p class="mt-4 text-sm leading-relaxed text-plum-600 dark:text-plum-300">{{ $service->short_description }}</p>

        @if ($service->plans->isNotEmpty())
          <div class="mt-6">
            <p class="mb-2.5 text-sm font-semibold text-plum-800 dark:text-plum-100">ជ្រើសរើសគម្រោង</p>
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
              @foreach ($service->plans as $plan)
                <label wire:key="plan-{{ $plan->id }}"
                  class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 px-3 py-2.5 text-center transition-colors {{ $selectedPlanId === $plan->id ? 'border-brand-600 bg-brand-50 dark:bg-plum-800' : 'border-plum-200 hover:border-brand-300 dark:border-plum-700' }}">
                  <input type="radio" wire:model.live="selectedPlanId" value="{{ $plan->id }}" class="sr-only" />
                  <span class="text-xs font-semibold text-plum-700 dark:text-plum-200">{{ $plan->label }}</span>
                  <span class="text-sm font-bold text-brand-700 dark:text-brand-300">${{ number_format($plan->price, 2) }}</span>
                </label>
              @endforeach
            </div>
          </div>
        @endif

        <div class="mt-6 flex items-end gap-4">
          <div>
            <span class="text-xs uppercase text-plum-400">តម្លៃ</span>
            <p class="text-3xl font-extrabold text-brand-700 dark:text-brand-300">
              ${{ number_format($selectedPlan?->price ?? $service->base_price, 2) }}
            </p>
          </div>

          <div class="flex items-center rounded-full border border-plum-200 dark:border-plum-700">
            <button type="button" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})" @disabled(! $service->in_stock)
              class="flex h-10 w-10 items-center justify-center text-plum-500 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40">&minus;</button>
            <span class="w-8 text-center text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $quantity }}</span>
            <button type="button" wire:click="$set('quantity', {{ $quantity + 1 }})" @disabled(! $service->in_stock)
              class="flex h-10 w-10 items-center justify-center text-plum-500 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40">+</button>
          </div>
        </div>

        @if ($service->in_stock)
          <button type="button" wire:click="addToCart"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700 sm:w-auto sm:px-10">
            <x-app-icon name="cart" class="h-5 w-5" />
            <span wire:loading.remove wire:target="addToCart">បន្ថែមទៅកន្ត្រក</span>
            <span wire:loading wire:target="addToCart">កំពុងបន្ថែម...</span>
          </button>
        @else
          <button type="button" disabled
            class="mt-6 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-full bg-plum-200 px-6 py-3.5 text-sm font-bold text-plum-500 sm:w-auto sm:px-10 dark:bg-plum-800 dark:text-plum-500">
            <x-app-icon name="x-circle" class="h-5 w-5" />
            អស់ពីស្តុក
          </button>
        @endif
        @if ($added)
          <p class="mt-2 flex items-center gap-1.5 text-sm font-semibold text-green-600 dark:text-green-400">
            <x-app-icon name="check-circle" class="h-4 w-4" /> បានបន្ថែមទៅកន្ត្រកដោយជោគជ័យ!
          </p>
        @endif

        <div class="mt-6 grid grid-cols-3 gap-2 rounded-xl border border-plum-200 p-3 text-center dark:border-plum-800">
          <div class="flex flex-col items-center gap-1">
            <x-app-icon name="bolt" class="h-5 w-5 text-brand-600 dark:text-brand-300" />
            <span class="text-[11px] font-medium text-plum-500 dark:text-plum-400">ដឹកជញ្ជូនភ្លាមៗ</span>
          </div>
          <div class="flex flex-col items-center gap-1">
            <x-app-icon name="shield-check" class="h-5 w-5 text-brand-600 dark:text-brand-300" />
            <span class="text-[11px] font-medium text-plum-500 dark:text-plum-400">សុវត្ថិភាព ១០០%</span>
          </div>
          <div class="flex flex-col items-center gap-1">
            <x-app-icon name="chat" class="h-5 w-5 text-brand-600 dark:text-brand-300" />
            <span class="text-[11px] font-medium text-plum-500 dark:text-plum-400">គាំទ្រ ២៤/៧</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabbed description --}}
    <div class="mt-14" x-data="{ tab: 'details' }">
      <div class="flex gap-2 border-b border-plum-200 dark:border-plum-800">
        @foreach (['details' => 'ព័ត៌មានលម្អិត', 'how' => 'របៀបប្រើប្រាស់', 'faq' => 'សំណួរញឹកញាប់'] as $key => $label)
          <button type="button" @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-plum-500 hover:text-brand-700 dark:text-plum-400'"
            class="border-b-2 px-4 py-3 text-sm font-semibold transition-colors">
            {{ $label }}
          </button>
        @endforeach
      </div>

      <div x-show="tab === 'details'" class="max-w-3xl py-6 text-sm leading-relaxed text-plum-600 dark:text-plum-300">
        {{ $service->description }}
      </div>
      <div x-show="tab === 'how'" x-cloak class="max-w-3xl py-6 text-sm leading-relaxed text-plum-600 dark:text-plum-300">
        <ol class="flex flex-col gap-3">
          <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">1</span> ជ្រើសរើសគម្រោងសមស្របនិងបន្ថែមទៅកន្ត្រក</li>
          <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">2</span> បំពេញព័ត៌មានទំនាក់ទំនងហើយដាក់ការបញ្ជាទិញ</li>
          <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">3</span> ក្រុមការងារយើងខ្ញុំនឹងទាក់ទងអ្នកដើម្បីបញ្ជាក់ការទូទាត់</li>
          <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">4</span> ទទួលបានគណនី/លេខកូដភ្លាមៗ</li>
        </ol>
      </div>
      <div x-show="tab === 'faq'" x-cloak class="max-w-3xl py-6 text-sm leading-relaxed text-plum-600 dark:text-plum-300">
        <p class="font-semibold text-plum-800 dark:text-plum-100">តើត្រូវចំណាយពេលប៉ុន្មានដើម្បីទទួលបានទំនិញ?</p>
        <p class="mt-1 mb-4">ជាធម្មតាក្នុងរយៈពេលពីរបីនាទីក្រោយបញ្ជាក់ការទូទាត់។</p>
        <p class="font-semibold text-plum-800 dark:text-plum-100">តើមានការធានាឬទេ?</p>
        <p class="mt-1">បាទ/ចាស មានការគាំទ្របន្ទាប់ពីការទិញរាល់ពេលដែលអ្នកត្រូវការ។</p>
      </div>
    </div>

    {{-- Related services --}}
    @if ($relatedServices->isNotEmpty())
      <div class="mt-16">
        <h2 class="mb-6 text-xl font-extrabold text-plum-900 dark:text-white">ផលិតផលពាក់ព័ន្ធ</h2>
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
          @foreach ($relatedServices as $related)
            <x-shop.product-card :service="$related" wire:key="related-{{ $related->id }}" />
          @endforeach
        </div>
      </div>
    @endif
  </div>
</div>
