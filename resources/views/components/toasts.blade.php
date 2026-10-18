{{-- Toast stack, driven by the `toasts` Alpine store (resources/js/modules/toast.js).
     Top-right on desktop, top and full-width on phones. Hovering pauses the timer. --}}
<div id="toast-root" x-data aria-live="polite" aria-atomic="false"
  class="pointer-events-none fixed inset-x-0 top-0 z-[200] flex flex-col items-center gap-2 p-3 sm:inset-x-auto sm:right-0 sm:items-end sm:p-5">
  <template x-for="toast in $store.toasts.items" :key="toast.id">
    <div x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
      x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
      @mouseenter="$store.toasts.pause(toast)" @mouseleave="$store.toasts.arm(toast)"
      :role="toast.type === 'error' ? 'alert' : 'status'"
      :class="{
        'border-green-200 dark:border-green-500/30': toast.type === 'success',
        'border-red-200 dark:border-red-500/30': toast.type === 'error',
        'border-gold-300 dark:border-gold-500/30': toast.type === 'warning',
        'border-plum-200 dark:border-plum-700': toast.type === 'info',
      }"
      class="pointer-events-auto relative flex w-full max-w-md items-start gap-3 overflow-hidden rounded-xl border bg-white p-4 pr-10 shadow-lg dark:bg-plum-900">
      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
        :class="{
          'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': toast.type === 'success',
          'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': toast.type === 'error',
          'bg-gold-500/15 text-gold-600 dark:text-gold-400': toast.type === 'warning',
          'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400': toast.type === 'info',
        }">
        <x-app-icon x-show="toast.type === 'success'" name="check-circle" class="h-5 w-5" />
        <x-app-icon x-show="toast.type === 'error'" name="x-circle" class="h-5 w-5" />
        <x-app-icon x-show="toast.type === 'warning'" name="clock" class="h-5 w-5" />
        <x-app-icon x-show="toast.type === 'info'" name="light-bulb" class="h-5 w-5" />
      </span>

      <div class="min-w-0 flex-1 pt-1">
        <p class="text-sm font-semibold leading-relaxed text-plum-900 dark:text-white" x-text="toast.message"></p>
        <template x-if="toast.action">
          <button type="button" @click="toast.action.run(); $store.toasts.dismiss(toast.id)"
            class="mt-2 rounded-full bg-brand-600 px-3 py-1 text-xs font-bold text-white hover:bg-brand-700" x-text="toast.action.label"></button>
        </template>
      </div>

      <button type="button" @click="$store.toasts.dismiss(toast.id)" aria-label="Close"
        class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full text-plum-400 hover:bg-plum-100 hover:text-plum-700 dark:hover:bg-plum-800 dark:hover:text-plum-200">
        <x-app-icon name="x-mark" class="h-4 w-4" />
      </button>

      {{-- time left --}}
      <template x-for="round in [toast.round]" :key="round">
      <span class="absolute inset-x-0 bottom-0 h-0.5 origin-left"
        :class="{ 'bg-green-500': toast.type === 'success', 'bg-red-500': toast.type === 'error', 'bg-gold-500': toast.type === 'warning', 'bg-brand-500': toast.type === 'info' }"
        :style="`animation: toast-timer ${toast.duration}ms linear forwards; animation-play-state: ${toast.paused ? 'paused' : 'running'}`"></span>
      </template>
    </div>
  </template>
</div>
