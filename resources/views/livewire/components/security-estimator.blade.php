<div class="mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-colors dark:border-gray-800 dark:bg-gray-900 sm:p-8">
    {{-- Progress dots --}}
    <div class="mb-6 flex items-center justify-center gap-2">
        @for ($i = 1; $i <= 3; $i++)
            <span class="h-2 rounded-full transition-all {{ $step === $i ? 'w-8 bg-amber-400' : ($step > $i ? 'w-2 bg-green-800 dark:bg-amber-400' : 'w-2 bg-gray-200 dark:bg-gray-700') }}"></span>
        @endfor
    </div>

    {{-- Step 1: Property type --}}
    @if ($step === 1)
        <div wire:key="step-1">
            <h3 class="text-center text-lg font-bold text-green-800 dark:text-white">តើទ្រព្យសម្បត្តិរបស់អ្នកជាប្រភេទណា?</h3>
            <div class="mt-6 grid grid-cols-2 gap-3">
                @foreach ($this->propertyTypes() as $value => $label)
                    <button type="button" wire:click="selectPropertyType('{{ $value }}')"
                        class="flex flex-col items-center gap-2 rounded-lg border p-4 text-sm font-medium transition-colors
                        {{ $propertyType === $value
                            ? 'border-green-800 bg-green-50 text-green-800 dark:border-amber-400 dark:bg-amber-400/10 dark:text-amber-400'
                            : 'border-gray-200 text-gray-600 hover:border-green-800 hover:text-green-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-amber-400 dark:hover:text-amber-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Step 2: Services --}}
    @if ($step === 2)
        <div wire:key="step-2">
            <h3 class="text-center text-lg font-bold text-green-800 dark:text-white">តើអ្នកត្រូវការសេវាកម្មអ្វីខ្លះ?</h3>
            <p class="mt-1 text-center text-xs text-gray-500 dark:text-gray-400">អាចជ្រើសរើសបានច្រើន</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                @foreach ($this->serviceOptions() as $value => $label)
                    <button type="button" wire:click="toggleService('{{ $value }}')"
                        class="flex items-center gap-2 rounded-lg border p-4 text-sm font-medium transition-colors
                        {{ in_array($value, $services, true)
                            ? 'border-green-800 bg-green-50 text-green-800 dark:border-amber-400 dark:bg-amber-400/10 dark:text-amber-400'
                            : 'border-gray-200 text-gray-600 hover:border-green-800 hover:text-green-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-amber-400 dark:hover:text-amber-400' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" class="h-4 w-4 shrink-0">
                            @if (in_array($value, $services, true))
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            @else
                                <rect x="4.5" y="4.5" width="15" height="15" rx="3" />
                            @endif
                        </svg>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="back"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    ថយក្រោយ
                </button>
                <button type="button" wire:click="goToCoverageStep" @disabled(empty($services))
                    class="rounded-md bg-green-800 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                    បន្ទាប់
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: Coverage hours --}}
    @if ($step === 3)
        <div wire:key="step-3">
            <h3 class="text-center text-lg font-bold text-green-800 dark:text-white">តើអ្នកត្រូវការការគ្របដណ្តប់រយៈពេលប៉ុន្មាន?</h3>
            <div class="mt-6 grid grid-cols-3 gap-3">
                @foreach ($this->coverageOptions() as $value => $label)
                    <button type="button" wire:click="selectCoverageHours('{{ $value }}')"
                        class="flex flex-col items-center gap-2 rounded-lg border p-4 text-sm font-medium transition-colors
                        {{ $coverageHours === $value
                            ? 'border-green-800 bg-green-50 text-green-800 dark:border-amber-400 dark:bg-amber-400/10 dark:text-amber-400'
                            : 'border-gray-200 text-gray-600 hover:border-green-800 hover:text-green-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-amber-400 dark:hover:text-amber-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($coverageHours)
                <div class="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-600 transition-colors dark:bg-gray-800 dark:text-gray-400">
                    <p><span class="font-semibold text-green-800 dark:text-amber-400">ប្រភេទទ្រព្យសម្បត្តិ៖</span> {{ $this->propertyTypes()[$propertyType] ?? '' }}</p>
                    <p class="mt-1"><span class="font-semibold text-green-800 dark:text-amber-400">សេវាកម្ម៖</span>
                        {{ implode(', ', array_map(fn ($key) => $this->serviceOptions()[$key] ?? $key, $services)) }}
                    </p>
                    <p class="mt-1"><span class="font-semibold text-green-800 dark:text-amber-400">ម៉ោងគ្របដណ្តប់៖</span> {{ $this->coverageOptions()[$coverageHours] ?? '' }}</p>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="back"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    ថយក្រោយ
                </button>
                <button type="button" wire:click="getRecommendation" @disabled(! $coverageHours)
                    class="rounded-md bg-green-800 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                    ទទួលបានការណែនាំ
                </button>
            </div>
        </div>
    @endif
</div>
