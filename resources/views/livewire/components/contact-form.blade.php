<div>
    @if ($submitted)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
            អរគុណសម្រាប់សំណើរបស់អ្នក! យើងនឹងទាក់ទងអ្នកឆាប់ៗនេះ។
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-3">
            <div>
                <input type="email" wire:model="email" placeholder="អីមែលរបស់អ្នក"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="tel" wire:model="phone" placeholder="លេខទូរស័ព្ទរបស់អ្នក"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                @error('phone')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="text" wire:model="address" placeholder="អាស័យដ្ឋានរបស់អ្នក"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                @error('address')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="w-full rounded-md bg-green-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">ផ្ញើសំណើ</span>
                <span wire:loading wire:target="submit">កំពុងផ្ញើ...</span>
            </button>
        </form>
    @endif
</div>
