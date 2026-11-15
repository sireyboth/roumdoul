@php
  $input = 'w-full rounded-lg border border-plum-200 bg-white px-3.5 py-2.5 text-sm focus:border-brand-500 focus:outline-none dark:border-plum-700 dark:bg-plum-900';
  $label = 'mb-1.5 block text-sm font-semibold text-plum-800 dark:text-plum-100';
  $error = 'mt-1 text-xs text-red-600 dark:text-red-400';
@endphp

<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-10">
  <a href="/dashboard" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">
    <x-app-icon name="chevron-left" class="h-4 w-4" /> {{ __('Back to dashboard') }}
  </a>
  <h1 class="mt-4 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">{{ __('My account') }}</h1>

  {{-- ───────── Details ───────── --}}
  <form wire:submit="saveProfile" class="relative mt-6 overflow-hidden rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <x-kbach-decor />

    <div class="relative">
    <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-plum-900 dark:text-white">
      <x-app-icon name="user" class="h-5 w-5 text-brand-600" /> {{ __('Your details') }}
    </h2>

    <div class="flex flex-col gap-4">
      <div>
        <label for="profile-name" class="{{ $label }}">{{ __('Name') }}</label>
        <input id="profile-name" type="text" wire:model="name" autocomplete="name" class="{{ $input }}" />
        @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="profile-email" class="{{ $label }}">{{ __('Email') }}</label>
          <input id="profile-email" type="email" wire:model="email" autocomplete="email" class="{{ $input }}" />
          @error('email') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
        <div>
          <label for="profile-phone" class="{{ $label }}">{{ __('Phone number / Telegram') }}</label>
          <input id="profile-phone" type="tel" wire:model="phone" autocomplete="tel" class="{{ $input }}" />
          @error('phone') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-3">
      <button type="submit"
        class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-700">
        <span wire:loading.remove wire:target="saveProfile">{{ __('Save') }}</span>
        <span wire:loading wire:target="saveProfile">{{ __('Saving…') }}</span>
      </button>
      @if ($profileSaved)
        <p class="flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
          <x-app-icon name="check-circle" class="h-4 w-4" /> {{ $profileSaved }}
        </p>
      @endif
    </div>
    </div>
  </form>

  {{-- ───────── Password ───────── --}}
  <form wire:submit="updatePassword" class="relative mt-6 overflow-hidden rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <x-kbach-decor />

    <div class="relative">
    <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-plum-900 dark:text-white">
      <x-app-icon name="lock-closed" class="h-5 w-5 text-brand-600" /> {{ __('Change password') }}
    </h2>

    <div class="flex flex-col gap-4">
      <div>
        <label for="current-password" class="{{ $label }}">{{ __('Current password') }}</label>
        <input id="current-password" type="password" wire:model="current_password" autocomplete="current-password" class="{{ $input }}" />
        @error('current_password') <p class="{{ $error }}">{{ $message }}</p> @enderror
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="new-password" class="{{ $label }}">{{ __('New password') }}</label>
          <input id="new-password" type="password" wire:model="password" autocomplete="new-password" class="{{ $input }}" />
          @error('password') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
        <div>
          <label for="new-password-confirm" class="{{ $label }}">{{ __('Confirm password') }}</label>
          <input id="new-password-confirm" type="password" wire:model="password_confirmation" autocomplete="new-password" class="{{ $input }}" />
        </div>
      </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-3">
      <button type="submit"
        class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-700">
        <span wire:loading.remove wire:target="updatePassword">{{ __('Change password') }}</span>
        <span wire:loading wire:target="updatePassword">{{ __('Saving…') }}</span>
      </button>
      @if ($passwordSaved)
        <p class="flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
          <x-app-icon name="check-circle" class="h-4 w-4" /> {{ $passwordSaved }}
        </p>
      @endif
    </div>
    </div>
  </form>
</div>
