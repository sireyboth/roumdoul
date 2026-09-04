<div>
    @if ($submitted)
        <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
            <x-app-icon name="check-circle" class="h-5 w-5 shrink-0" />
            អរគុណច្រើនសម្រាប់មតិយោបល់របស់អ្នក! មតិរបស់អ្នកនឹងបង្ហាញនៅលើគេហទំព័រក្រោយពេលត្រួតពិនិត្យ។
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            {{-- Honeypot — hidden from real users, left empty by them --}}
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <input type="text" tabindex="-1" autocomplete="off" wire:model="website" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    ឈ្មោះរបស់អ្នក
                </label>
                <input type="text" wire:model="customer_name" placeholder="ឈ្មោះរបស់អ្នក"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('customer_name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    អ៊ីមែល <span class="normal-case text-plum-400">(មិនបង្ហាញជាសាធារណៈទេ)</span>
                </label>
                <input type="email" wire:model="email" placeholder="អីមែលរបស់អ្នក (ស្រេចចិត្ត)"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-plum-500 dark:text-plum-400">
                    ការវាយតម្លៃ
                </label>
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="$set('rating', {{ $i }})" aria-label="{{ $i }} star">
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
                    មតិយោបល់របស់អ្នក
                </label>
                <textarea wire:model="comment" rows="4" placeholder="ចែករំលែកបទពិសោធន៍របស់អ្នកជាមួយយើង..."
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white"></textarea>
                @error('comment')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="w-full rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">ផ្ញើមតិយោបល់</span>
                <span wire:loading wire:target="submit">កំពុងផ្ញើ...</span>
            </button>
        </form>
    @endif
</div>
