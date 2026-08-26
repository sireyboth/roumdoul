<div>
    @if ($submitted)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
            អរគុណសម្រាប់សំណើរបស់អ្នក! ក្រុមការងាររបស់យើងនឹងទាក់ទងអ្នកមកវិញឆាប់ៗនេះ។
        </div>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    ឈ្មោះពេញ
                </label>
                <input type="text" wire:model="full_name" placeholder="ឈ្មោះរបស់អ្នក"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                @error('full_name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        លេខទូរស័ព្ទ
                    </label>
                    <input type="tel" wire:model="phone" placeholder="លេខទូរស័ព្ទរបស់អ្នក"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        អ៊ីមែល
                    </label>
                    <input type="email" wire:model="email" placeholder="អីមែលរបស់អ្នក"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    សេវាកម្មដែលត្រូវការ
                </label>
                <select wire:model="service_needed"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">-- សូមជ្រើសរើសសេវាកម្ម --</option>
                    @foreach ($this->services() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('service_needed')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    សារ
                </label>
                <textarea wire:model="message" rows="4" placeholder="ប្រាប់យើងអំពីតម្រូវការសន្តិសុខរបស់អ្នក..."
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-green-800 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                @error('message')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="w-full rounded-md bg-green-800 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">ផ្ញើសារ</span>
                <span wire:loading wire:target="submit">កំពុងផ្ញើ...</span>
            </button>
        </form>
    @endif
</div>
