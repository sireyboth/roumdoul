<div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-6">
  <div class="rounded-lg border border-plum-200 bg-white p-8 dark:border-plum-800 dark:bg-plum-900">
    <div class="mb-6 text-center">
      <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
        <x-app-icon name="lock-closed" class="h-6 w-6" />
      </span>
      <h1 class="mt-4 text-xl font-extrabold text-plum-900 dark:text-white">ចូលគណនី</h1>
      <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">ចូលទៅកាន់ផ្ទាំងគ្រប់គ្រងកម្មវត្ថុអញ្ជើញរបស់អ្នក</p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-4">
      <div>
        <label class="mb-1.5 block text-sm font-semibold text-plum-800 dark:text-plum-100">អ៊ីមែល</label>
        <input type="email" wire:model="email"
          class="w-full rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
        @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="mb-1.5 block text-sm font-semibold text-plum-800 dark:text-plum-100">ពាក្យសម្ងាត់</label>
        <input type="password" wire:model="password"
          class="w-full rounded-lg border border-plum-200 bg-transparent px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700" />
        @error('password') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <label class="flex items-center gap-2 text-sm text-plum-600 dark:text-plum-300">
        <input type="checkbox" wire:model="remember" class="rounded border-plum-300" />
        ចងចាំខ្ញុំ
      </label>

      <button type="submit"
        class="mt-2 flex items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-700">
        <span wire:loading.remove wire:target="login">ចូលគណនី</span>
        <span wire:loading wire:target="login">កំពុងចូល...</span>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-plum-500 dark:text-plum-400">
      មិនទាន់មានគណនី?
      <a href="/register" wire:navigate class="font-semibold text-brand-700 hover:underline dark:text-brand-300">បង្កើតគណនី</a>
    </p>
  </div>
</div>
