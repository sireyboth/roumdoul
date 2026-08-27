
  <div class="contents">
    <header class="sticky top-0 z-40 border-b border-plum-200 bg-white/95 shadow-sm backdrop-blur transition-colors dark:border-plum-800 dark:bg-plum-900/95">
      <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="/" wire:navigate class="flex shrink-0 items-center gap-2.5">
          <img src="/images/Roumdoul_Logo.png" alt="ROUMDOUL" class="h-10 w-10 shrink-0 rounded-full object-contain" />
          <span class="flex flex-col leading-tight">
            <span class="text-lg font-extrabold tracking-tight text-brand-800 dark:text-white sm:text-xl">ROUMDOUL</span>
            <span class="hidden text-[11px] font-medium text-plum-500 dark:text-plum-400 sm:block">រំដួល &middot; Premium Digital Services</span>
          </span>
        </a>

        <form action="/shop" method="GET" class="hidden flex-1 items-center md:flex">
          <div class="relative w-full max-w-lg">
            <x-app-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
            <input type="text" name="search" placeholder="ស្វែងរកសេវាកម្ម ឬផលិតផលឌីជីថល..."
              class="w-full rounded-full border border-plum-200 bg-plum-50 py-2 pl-10 pr-4 text-sm text-plum-900 outline-none transition-colors placeholder:text-plum-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white dark:placeholder:text-plum-400 dark:focus:bg-plum-800" />
          </div>
        </form>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
          <button type="button" @click="$store.cartDrawer.show()"
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-plum-600 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
            <x-app-icon name="cart" class="h-5.5 w-5.5" />
            @if ($cartCount > 0)
              <span class="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white">
                {{ $cartCount }}
              </span>
            @endif
          </button>

          <x-theme-toggle class="hidden md:inline-flex" />

          <div class="relative" data-lang-dropdown>
            <button type="button" data-lang-toggle aria-haspopup="menu" aria-expanded="false" aria-label="ជ្រើសរើសភាសា"
              class="flex h-9 items-center gap-1 rounded-full px-2.5 text-plum-600 transition-colors hover:bg-brand-50 dark:text-plum-300 dark:hover:bg-plum-800">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                <path d="M2 12h20"></path>
              </svg>
              <span data-lang-label class="text-xs font-semibold uppercase">ខ្មែរ</span>
            </button>

            <div data-lang-menu role="menu"
              class="pointer-events-none absolute right-0 z-50 mt-2 w-32 origin-top-right scale-95 rounded-md border border-plum-200 bg-white py-1 opacity-0 shadow-lg transition-[opacity,transform] duration-150 ease-out dark:border-plum-700 dark:bg-plum-800">
              <button type="button" role="menuitem" data-lang-option="km"
                class="flex w-full items-center justify-between px-3 py-2 text-sm text-plum-700 transition-colors hover:bg-brand-50 dark:text-plum-200 dark:hover:bg-plum-700">
                ខ្មែរ
                <x-app-icon name="check-circle" data-lang-check-km class="h-4 w-4 text-brand-700 dark:text-gold-400" />
              </button>
              <button type="button" role="menuitem" data-lang-option="en"
                class="flex w-full items-center justify-between px-3 py-2 text-sm text-plum-700 transition-colors hover:bg-brand-50 dark:text-plum-200 dark:hover:bg-plum-700">
                English
                <x-app-icon name="check-circle" data-lang-check-en class="hidden h-4 w-4 text-brand-700 dark:text-gold-400" />
              </button>
            </div>
          </div>

          <button type="button" id="menu-toggle" aria-label="បើកម៉ឺនុយ" aria-expanded="false" aria-controls="mobile-menu"
            class="flex h-9 w-9 items-center justify-center rounded-full text-plum-600 transition-colors hover:bg-brand-50 dark:text-plum-300 dark:hover:bg-plum-800 md:hidden">
            <x-app-icon id="menu-icon-open" name="bars" class="h-6 w-6" />
            <x-app-icon id="menu-icon-close" name="x-mark" class="hidden h-6 w-6" />
          </button>
        </div>
      </div>

      {{-- Top category bar --}}
      <nav class="hidden border-t border-plum-100 bg-plum-50 dark:border-plum-800 dark:bg-plum-900 md:block">
        <div class="mx-auto flex max-w-7xl items-center gap-4 overflow-x-auto px-4 py-2.5 sm:px-6 lg:px-8">
          <a href="/shop" wire:navigate data-nav-link
            class="shrink-0 text-sm font-semibold transition-colors text-plum-600 hover:text-brand-700 dark:text-plum-300 dark:hover:text-white">
            គ្រប់ប្រភេទទាំងអស់
          </a>
          @foreach ($categories as $category)
            <a href="/shop/{{ $category->slug }}" wire:navigate data-nav-link
              class="shrink-0 text-sm font-medium transition-colors text-plum-500 hover:text-brand-700 dark:text-plum-400 dark:hover:text-white">
              {{ $category->name_km }}
            </a>
          @endforeach
        </div>
      </nav>

      <div id="mobile-menu-wrapper"
        class="grid grid-rows-[0fr] opacity-0 transition-[grid-template-rows,opacity] duration-300 ease-in-out md:hidden">
        <div class="overflow-hidden">
          <nav id="mobile-menu" class="flex flex-col gap-1 border-t border-plum-200 px-4 pb-3 pt-2 dark:border-plum-800">
            <form action="/shop" method="GET" class="mb-1">
              <div class="relative">
                <x-app-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-plum-400" />
                <input type="text" name="search" placeholder="ស្វែងរកសេវាកម្ម..."
                  class="w-full rounded-full border border-plum-200 bg-plum-50 py-2 pl-10 pr-4 text-sm outline-none focus:border-brand-500 dark:border-plum-700 dark:bg-plum-800" />
              </div>
            </form>

            <a href="/" wire:navigate data-nav-link data-nav-exact
              class="rounded-md px-3 py-2 font-medium transition-colors text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
              ទំព័រដើម
            </a>
            <a href="/shop" wire:navigate data-nav-link
              class="rounded-md px-3 py-2 font-medium transition-colors text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
              ហាង
            </a>
            @foreach ($categories as $category)
              <a href="/shop/{{ $category->slug }}" wire:navigate data-nav-link
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
                <x-app-icon name="{{ $category->icon }}" class="h-4 w-4" />
                {{ $category->name_km }}
              </a>
            @endforeach
            <a href="/about" wire:navigate data-nav-link
              class="rounded-md px-3 py-2 font-medium transition-colors text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
              អំពីយើង
            </a>
            <a href="/contact" wire:navigate data-nav-link
              class="rounded-md px-3 py-2 font-medium transition-colors text-plum-600 hover:bg-brand-50 hover:text-brand-700 dark:text-plum-300 dark:hover:bg-plum-800 dark:hover:text-white">
              ទំនាក់ទំនង
            </a>

            <div class="mt-1 flex items-center justify-between border-t border-plum-200 px-3 pb-2 pt-3 dark:border-plum-800">
              <span class="flex items-center gap-2.5 font-medium text-plum-600 dark:text-plum-300">
                <svg x-cloak x-show="$store.theme.current !== 'dark'" xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-gold-500">
                  <path fill-rule="evenodd"
                    d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"
                    clip-rule="evenodd" />
                </svg>
                <svg x-cloak x-show="$store.theme.current === 'dark'" xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-brand-400">
                  <path fill-rule="evenodd"
                    d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                    clip-rule="evenodd" />
                </svg>
                <span x-text="$store.theme.current === 'dark' ? 'ងងឹត' : 'ភ្លឺ'"></span>
              </span>

              <x-theme-toggle />
            </div>
          </nav>
        </div>
      </div>
    </header>
  </div>
