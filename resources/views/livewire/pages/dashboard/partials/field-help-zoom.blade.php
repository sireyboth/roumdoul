{{-- Full-screen field help, opened by setting `zoom` to a field key in the enclosing Alpine scope.
     Expects $zoomFields (keys), $fieldCatalog, $invitation, $isFilled (fn). --}}
<template x-teleport="body">
  <div x-cloak x-show="zoom" x-transition.opacity @keydown.escape.window="zoom = null"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-plum-950/70 p-3 backdrop-blur-sm sm:p-6"
    @click.self="zoom = null">
    <div class="relative max-h-full w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:p-10 dark:bg-plum-900">
      <button type="button" @click="zoom = null" aria-label="Close"
        class="absolute right-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-plum-100 text-plum-600 hover:bg-plum-200 dark:bg-plum-800 dark:text-plum-300">
        <x-app-icon name="x-mark" class="h-5 w-5" />
      </button>
      @foreach ($zoomFields as $key)
        <div x-show="zoom === '{{ $key }}'" style="display: none" wire:key="zoom-{{ $key }}">
          @include('livewire.pages.dashboard.partials.field-help', ['catalogField' => $fieldCatalog[$key], 'filled' => $isFilled($key), 'size' => 'full'])
        </div>
      @endforeach
    </div>
  </div>
</template>
