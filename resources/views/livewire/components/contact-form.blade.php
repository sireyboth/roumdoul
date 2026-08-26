<div>
    @if ($submitted)
        <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
            <x-icon name="check-circle" class="h-4 w-4 shrink-0" />
            អរគុណសម្រាប់សំណើរបស់អ្នក! យើងនឹងទាក់ទងអ្នកឆាប់ៗនេះ។
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-3">
            <div>
                <input type="email" wire:model="email" placeholder="អីមែលរបស់អ្នក"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="tel" wire:model="phone" placeholder="លេខទូរស័ព្ទរបស់អ្នក"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('phone')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="text" wire:model="address" placeholder="Telegram / Facebook (មិនចាំបាច់)"
                    class="w-full rounded-lg border border-plum-200 bg-white px-3 py-2 text-sm text-plum-900 outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100 dark:border-plum-700 dark:bg-plum-800 dark:text-white" />
                @error('address')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="w-full rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">ស្នើសុំទូរស័ព្ទមកវិញ</span>
                <span wire:loading wire:target="submit">កំពុងផ្ញើ...</span>
            </button>
        </form>
    @endif
</div>
