@props(['categories', 'active' => null])

{{-- Desktop rail --}}
<aside class="hidden shrink-0 lg:block lg:w-64">
  @include('components.shop.sidebar-content', ['categories' => $categories, 'active' => $active])
</aside>

{{-- Mobile: trigger + slide-over drawer --}}
<div x-data="{ open: false }" class="lg:hidden">
  <button type="button" @click="open = true"
    class="flex items-center gap-2 rounded-full border border-plum-300 px-4 py-2 text-sm font-semibold text-plum-700 transition-colors hover:bg-brand-50 dark:border-plum-600 dark:text-plum-200 dark:hover:bg-plum-800">
    <x-app-icon name="filter" class="h-4.5 w-4.5" />
    តម្រង & ប្រភេទ
  </button>

  <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-plum-950/60" @click="open = false"></div>

  <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-y-0 left-0 z-50 w-full max-w-xs overflow-y-auto bg-white p-5 shadow-2xl dark:bg-plum-900">
    <div class="mb-4 flex items-center justify-between">
      <span class="text-base font-bold text-plum-900 dark:text-white">តម្រង & ប្រភេទ</span>
      <button type="button" @click="open = false"
        class="flex h-8 w-8 items-center justify-center rounded-full text-plum-500 hover:bg-plum-100 dark:hover:bg-plum-800">
        <x-app-icon name="x-mark" class="h-5 w-5" />
      </button>
    </div>
    @include('components.shop.sidebar-content', ['categories' => $categories, 'active' => $active])
  </div>
</div>
