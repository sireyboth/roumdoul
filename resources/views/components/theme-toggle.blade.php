@props(['class' => ''])

<button type="button" @click="$store.theme.set($store.theme.current === 'dark' ? 'light' : 'dark')" role="switch"
  :aria-checked="($store.theme.current === 'dark').toString()" aria-label="Toggle dark mode"
  {{ $attributes->merge(['class' => 'relative h-7 w-14 shrink-0 items-center rounded-full p-1 transition-colors duration-300 ease-in-out '.$class]) }}
  :class="$store.theme.current === 'dark' ? 'bg-plum-800' : 'bg-brand-100'">
  <span
    class="pointer-events-none flex h-5 w-5 items-center justify-center rounded-full shadow-sm transition-transform duration-300 ease-in-out"
    :class="$store.theme.current === 'dark' ? 'translate-x-7 bg-plum-950' : 'translate-x-0 bg-white'">
    <svg x-cloak x-show="$store.theme.current !== 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
      fill="currentColor" class="h-3.5 w-3.5 text-gold-500">
      <path fill-rule="evenodd"
        d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"
        clip-rule="evenodd" />
    </svg>
    <svg x-cloak x-show="$store.theme.current === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
      fill="currentColor" class="h-3.5 w-3.5 text-brand-400">
      <path fill-rule="evenodd"
        d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
        clip-rule="evenodd" />
    </svg>
  </span>
</button>
