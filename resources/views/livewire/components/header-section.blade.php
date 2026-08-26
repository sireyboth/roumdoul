
  <div class="contents">
    <div class="bg-green-950 text-gray-200 transition-colors dark:bg-black">
      <div class="mx-auto flex max-w-6xl items-center justify-center gap-2 px-4 py-1.5 text-center text-xs sm:justify-between sm:px-6 lg:px-8">
        <a href="tel:+855123456780" class="flex items-center gap-1.5 font-medium transition-colors hover:text-amber-400 hover:underline">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" class="h-3.5 w-3.5 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
          </svg>
          <span>ខ្សែទូរស័ព្ទបន្ទាន់៖ +855 12 345 678</span>
        </a>
        <span class="hidden font-semibold uppercase tracking-wide text-amber-400 sm:inline">បម្រើសេវាកម្ម ២៤/៧</span>
      </div>
    </div>

    <header class="sticky top-0 z-50 bg-white shadow-md transition-colors dark:bg-gray-900">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
      <a href="/" wire:navigate class="flex items-center gap-2">
        <img src="/images/SJL Logo-01.png" alt="JingLong Security" class="h-9 w-9 object-contain sm:h-10 sm:w-10" />
        <span class="text-lg font-bold uppercase text-green-800 dark:text-white sm:text-xl">JINGLONG SECURITY CO.,LTD</span>
      </a>

      <nav class="hidden items-center gap-6 md:flex">
        <a href="/" wire:navigate data-nav-link data-nav-exact
          class="font-medium transition-colors text-gray-600 hover:text-green-800 dark:text-gray-300 dark:hover:text-white">ទំព័រដើម</a>
        <a href="/services" wire:navigate data-nav-link
          class="font-medium transition-colors text-gray-600 hover:text-green-800 dark:text-gray-300 dark:hover:text-white">សេវាកម្ម</a>
        <a href="/about" wire:navigate data-nav-link
          class="font-medium transition-colors text-gray-600 hover:text-green-800 dark:text-gray-300 dark:hover:text-white">អំពីយើង</a>
        <a href="/contact" wire:navigate data-nav-link
          class="font-medium transition-colors text-gray-600 hover:text-green-800 dark:text-gray-300 dark:hover:text-white">ទំនាក់ទំនង</a>
      </nav>

      <div class="flex items-center gap-2">
        <a href="/services#pricing" wire:navigate
          class="hidden shrink-0 rounded-md bg-green-800 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700 md:inline-block">
          តម្លៃនៃសេវាកម្ម
        </a>

        {{-- Single-click theme toggle: no menu state needed, both this and the
             mobile instance below read/write the same global Alpine store,
             so they stay in sync for free. --}}
        <button type="button" @click="$store.theme.set($store.theme.current === 'dark' ? 'light' : 'dark')"
          role="switch" :aria-checked="($store.theme.current === 'dark').toString()"
          aria-label="ប្តូររបៀបងងឹត/ភ្លឺ"
          class="relative hidden h-7 w-14 shrink-0 items-center rounded-full p-1 transition-colors duration-300 ease-in-out md:inline-flex"
          :class="$store.theme.current === 'dark' ? 'bg-indigo-950' : 'bg-amber-100'">
          <span class="pointer-events-none flex h-5 w-5 items-center justify-center rounded-full shadow-sm transition-transform duration-300 ease-in-out"
            :class="$store.theme.current === 'dark' ? 'translate-x-7 bg-gray-900' : 'translate-x-0 bg-white'">
            <svg x-cloak x-show="$store.theme.current !== 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
              fill="currentColor" class="h-3.5 w-3.5 text-amber-400">
              <path fill-rule="evenodd"
                d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"
                clip-rule="evenodd" />
            </svg>
            <svg x-cloak x-show="$store.theme.current === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
              fill="currentColor" class="h-3.5 w-3.5 text-purple-400">
              <path fill-rule="evenodd"
                d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                clip-rule="evenodd" />
            </svg>
          </span>
        </button>

        <div class="relative" data-lang-dropdown>
          <button type="button" data-lang-toggle aria-haspopup="menu" aria-expanded="false" aria-label="ជ្រើសរើសភាសា"
            class="flex h-9 items-center gap-1 rounded-full px-2.5 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
              <path d="M2 12h20"></path>
            </svg>
            <span data-lang-label class="text-xs font-semibold uppercase">ខ្មែរ</span>
          </button>

          <div data-lang-menu role="menu"
            class="pointer-events-none absolute right-0 z-50 mt-2 w-32 origin-top-right scale-95 rounded-md border border-gray-200 bg-white py-1 opacity-0 shadow-lg transition-[opacity,transform] duration-150 ease-out dark:border-gray-700 dark:bg-gray-800">
            <button type="button" role="menuitem" data-lang-option="km"
              class="flex w-full items-center justify-between px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
              ខ្មែរ
              <svg data-lang-check-km xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="h-4 w-4 text-green-800 dark:text-amber-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
            </button>
            <button type="button" role="menuitem" data-lang-option="en"
              class="flex w-full items-center justify-between px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
              English
              <svg data-lang-check-en xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="hidden h-4 w-4 text-green-800 dark:text-amber-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
            </button>
          </div>
        </div>

        <button type="button" id="menu-toggle" aria-label="បើកម៉ឺនុយ" aria-expanded="false" aria-controls="mobile-menu"
          class="flex h-9 w-9 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 md:hidden">
          <svg id="menu-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" class="h-6 w-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
          </svg>
          <svg id="menu-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" class="hidden h-6 w-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <div id="mobile-menu-wrapper"
      class="grid grid-rows-[0fr] opacity-0 transition-[grid-template-rows,opacity] duration-300 ease-in-out md:hidden">
      <div class="overflow-hidden">
        <nav id="mobile-menu" class="flex flex-col gap-1 border-t border-gray-200 px-4 pb-3 pt-2 dark:border-gray-800">
          <a href="/" wire:navigate data-nav-link data-nav-exact
            class="rounded-md px-3 py-2 font-medium transition-colors text-gray-600 hover:bg-gray-100 hover:text-green-800 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            ទំព័រដើម
          </a>
          <a href="/services" wire:navigate data-nav-link
            class="rounded-md px-3 py-2 font-medium transition-colors text-gray-600 hover:bg-gray-100 hover:text-green-800 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            សេវាកម្ម
          </a>
          <a href="/about" wire:navigate data-nav-link
            class="rounded-md px-3 py-2 font-medium transition-colors text-gray-600 hover:bg-gray-100 hover:text-green-800 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            អំពីយើង
          </a>
          <a href="/contact" wire:navigate data-nav-link
            class="rounded-md px-3 py-2 font-medium transition-colors text-gray-600 hover:bg-gray-100 hover:text-green-800 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
            ទំនាក់ទំនង
          </a>

          <a href="/contact" wire:navigate
            class="mt-2 rounded-md bg-green-800 px-3 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-green-700">
            ស្នើសុំសម្រង់តម្លៃ
          </a>

          <div class="mt-1 flex items-center justify-between border-t border-gray-200 px-3 pb-2 pt-3 dark:border-gray-800">
            <span class="flex items-center gap-2.5 font-medium text-gray-600 dark:text-gray-300">
              <svg x-cloak x-show="$store.theme.current !== 'dark'" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-amber-400">
                <path fill-rule="evenodd"
                  d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"
                  clip-rule="evenodd" />
              </svg>
              <svg x-cloak x-show="$store.theme.current === 'dark'" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-purple-400">
                <path fill-rule="evenodd"
                  d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                  clip-rule="evenodd" />
              </svg>
              <span x-text="$store.theme.current === 'dark' ? 'ងងឹត' : 'ភ្លឺ'"></span>
            </span>

            <button type="button" @click="$store.theme.set($store.theme.current === 'dark' ? 'light' : 'dark')"
              role="switch" :aria-checked="($store.theme.current === 'dark').toString()"
              aria-label="ប្តូររបៀបងងឹត/ភ្លឺ"
              class="relative inline-flex h-7 w-14 shrink-0 items-center rounded-full p-1 transition-colors duration-300 ease-in-out"
              :class="$store.theme.current === 'dark' ? 'bg-indigo-950' : 'bg-amber-100'">
              <span class="pointer-events-none flex h-5 w-5 items-center justify-center rounded-full shadow-sm transition-transform duration-300 ease-in-out"
                :class="$store.theme.current === 'dark' ? 'translate-x-7 bg-gray-900' : 'translate-x-0 bg-white'">
                <svg x-cloak x-show="$store.theme.current !== 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                  fill="currentColor" class="h-3.5 w-3.5 text-amber-400">
                  <path fill-rule="evenodd"
                    d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"
                    clip-rule="evenodd" />
                </svg>
                <svg x-cloak x-show="$store.theme.current === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                  fill="currentColor" class="h-3.5 w-3.5 text-purple-400">
                  <path fill-rule="evenodd"
                    d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                    clip-rule="evenodd" />
                </svg>
              </span>
            </button>
          </div>
        </nav>
      </div>
    </div>
    </header>
  </div>
