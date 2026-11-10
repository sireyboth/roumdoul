<div>
    @if ($submitted)
        <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
            <x-app-icon name="check-circle" class="h-5 w-5 shrink-0" />
            {{ __('Thank you so much for your feedback! It will appear on the website after review.') }}
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            {{-- Honeypot — hidden from real users, left empty by them --}}
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <input type="text" tabindex="-1" autocomplete="off" wire:model="website" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    {{ __('Email') }} <span class="normal-case text-plum-400">{{ __('(optional · never shown publicly)') }}</span>
                </label>
                <input type="email" wire:model="email" placeholder="{{ __('Your email (optional)') }}"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    {{ __('Rating') }}
                </label>
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="$set('rating', {{ $i }})" aria-label="{{ __(':count star', ['count' => $i]) }}">
                            <x-app-icon name="star"
                                class="h-8 w-8 transition-colors {{ $i <= $rating ? 'fill-current text-gold-500' : 'text-plum-200 dark:text-plum-700' }}" />
                        </button>
                    @endfor
                </div>
                @error('rating')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    {{ __('Your feedback') }}
                </label>
                <textarea wire:model="comment" rows="4" placeholder="{{ __('Share your experience with us...') }}"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white"></textarea>
                @error('comment')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="w-full rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">{{ __('Send feedback') }}</span>
                <span wire:loading wire:target="submit">{{ __('Sending...') }}</span>
            </button>
        </form>
    @endif
</div>
