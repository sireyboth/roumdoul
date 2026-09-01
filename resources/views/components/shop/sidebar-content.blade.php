<div class="flex items-center justify-between px-1">
  <h3 class="text-sm font-bold uppercase tracking-wide text-brand-800 dark:text-brand-300">ប្រភេទផលិតផល</h3>
</div>
<ul class="mt-3 flex flex-col gap-1">
  <li>
    <a href="/shop" wire:navigate
      class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold transition-colors {{ $active ? 'text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800' : 'bg-brand-600 text-white' }}">
      គ្រប់ប្រភេទទាំងអស់
    </a>
  </li>
  @foreach ($categories as $category)
    <li>
      <a href="/shop/{{ $category->slug }}" wire:navigate
        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-all {{ $active && $active->id === $category->id ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/30' : 'text-plum-600 hover:translate-x-0.5 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800' }}">
        <x-app-icon name="{{ $category->icon }}" class="h-4.5 w-4.5 shrink-0" />
        {{ $category->name_km }}
      </a>
    </li>
  @endforeach
</ul>

<div class="mt-6 border-t border-plum-200 px-1 pt-5 dark:border-plum-800">
  <h3 class="text-sm font-bold uppercase tracking-wide text-brand-800 dark:text-brand-300">តម្រៀបតាម</h3>
  <select wire:model.live="sort"
    class="mt-3 w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-700 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-plum-200">
    <option value="popular">ពេញនិយម</option>
    <option value="price_asc">តម្លៃ៖ ទាបទៅខ្ពស់</option>
    <option value="price_desc">តម្លៃ៖ ខ្ពស់ទៅទាប</option>
    <option value="name">ឈ្មោះ A-Z</option>
  </select>
</div>

<div class="mt-6 rounded-lg border border-brand-900/20 bg-brand-950 p-4 text-center text-white">
  <x-app-icon name="shield-check" class="mx-auto h-7 w-7 text-gold-400" />
  <p class="mt-2 text-sm font-semibold">ការទូទាត់ដោយសុវត្ថិភាព ១០០%</p>
  <p class="mt-1 text-xs text-brand-100">ដឹកជញ្ជូនភ្លាមៗក្រោយបញ្ជាទិញ</p>
</div>
