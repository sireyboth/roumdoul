{{-- "How to use" guide for the invitation workspace. Expects $invitation, $navItems, $fieldCatalog, $isPaid. --}}
@php
  $steps = [
    ['title' => 'បំពេញព័ត៌មាន', 'body' => 'ចូលទៅផ្ទាំង «ព័ត៌មានលម្អិត» ហើយបំពេញឈ្មោះ សារ ថ្ងៃ និងទីកន្លែងកម្មវិធី។ កុំភ្លេចចុច Save មុនប្ដូរទៅផ្ទាំងផ្សេង។'],
    ['title' => 'ដាក់រូបភាព', 'body' => 'នៅផ្ទាំង «រូបភាព» ចុចលើប្រអប់ដើម្បីជ្រើសរូបពីទូរស័ព្ទ ឬកុំព្យូទ័រ។ រូបធំៗនឹងត្រូវបង្រួមដោយស្វ័យប្រវត្តិ។'],
    ['title' => 'មើលជាមុន', 'body' => 'ចុច «មើលជាមុន» ដើម្បីឃើញធៀបដូចភ្ញៀវឃើញ។ ការមើលជាមុនមិនរាប់ថាភ្ញៀវបានបើកមើលទេ។'],
    ['title' => 'បន្ថែមភ្ញៀវ', 'body' => 'នៅផ្ទាំង «ភ្ញៀវ» វាយឈ្មោះភ្ញៀវម្នាក់ៗ។ ភ្ញៀវម្នាក់ៗទទួលបានតំណផ្ទាល់ខ្លួន ដែលមានឈ្មោះរបស់គេនៅលើធៀប។'],
    ['title' => 'ផ្ញើតំណ', 'body' => 'ចុច «Copy link» ហើយផ្ញើទៅភ្ញៀវតាម Telegram, Messenger ឬ SMS។ កុំផ្ញើតំណរបស់ភ្ញៀវម្នាក់ទៅអ្នកផ្សេង ព្រោះវាមានឈ្មោះគេ។'],
    ['title' => 'តាមដានការឆ្លើយតប', 'body' => 'មើលថាភ្ញៀវណាបានបើកធៀប (Opened) និងអ្នកណាឆ្លើយថាចូលរួម ឬមិនចូលរួម។'],
  ];

  $sectionHelp = [
    'overview' => 'ស្ថិតិសង្ខេប — ចំនួនភ្ញៀវ ចំនួនអ្នកបានបើកមើល ចំនួនអ្នកចូលរួម និងចំនួនថ្ងៃដែលធៀបនៅដំណើរការ។',
    'details' => 'អក្សរ និងព័ត៌មានរបស់ធៀប — ឈ្មោះ សារ ថ្ងៃខែ ទីកន្លែង ពណ៌ និងមុខងារបើក/បិទ។',
    'media' => 'រូបភាពទាំងអស់ — រូបគម្រប រូបថតបុគ្គល និងវិចិត្រសាលរូបថត។',
    'program' => 'កម្មវិធីលម្អិត — របៀបវារៈតាមម៉ោង និងរឿងរ៉ាវរបស់អ្នក។',
    'guests' => 'បន្ថែម ឬលុបភ្ញៀវ និងចម្លងតំណផ្ទាល់ខ្លួនរបស់ភ្ញៀវម្នាក់ៗ។',
    'responses' => 'បញ្ជីអ្នកឆ្លើយថាចូលរួម ឬមិនចូលរួម រួមទាំងសារដែលភ្ញៀវសរសេរមក។',
  ];

  $fieldGroups = collect(['details', 'media', 'program'])
    ->mapWithKeys(fn ($s) => [$s => $this->sectionFields($s)])
    ->filter();
@endphp

<p class="mt-1 text-sm text-plum-500 dark:text-plum-400">របៀបប្រើប្រាស់ផ្ទាំងនេះ និងអត្ថន័យនៃប្រអប់នីមួយៗ</p>

{{-- Step by step --}}
<div class="mt-6 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
  <h2 class="text-base font-bold text-plum-900 dark:text-white">ជំហានប្រើប្រាស់</h2>
  <ol class="mt-4 flex flex-col gap-4">
    @foreach ($steps as $i => $step)
      <li class="flex gap-3">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
        <div>
          <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $step['title'] }}</p>
          <p class="mt-0.5 text-sm text-plum-600 dark:text-plum-300">{{ $step['body'] }}</p>
        </div>
      </li>
    @endforeach
  </ol>
</div>

{{-- What each sidebar item is for --}}
<div class="mt-4 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
  <h2 class="text-base font-bold text-plum-900 dark:text-white">ម៉ឺនុយនៅខាងឆ្វេង</h2>
  <dl class="mt-4 flex flex-col gap-3">
    @foreach ($this->availableSections() as $key)
      @continue(! isset($sectionHelp[$key]))
      <div class="flex gap-3">
        <x-app-icon :name="$navItems[$key]['icon']" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600 dark:text-brand-400" />
        <div>
          <dt class="text-sm font-semibold text-plum-900 dark:text-white">{{ $navItems[$key]['label'] }}</dt>
          <dd class="mt-0.5 text-sm text-plum-600 dark:text-plum-300">{{ $sectionHelp[$key] }}</dd>
        </div>
      </div>
    @endforeach
  </dl>
</div>

{{-- What every field on this template means --}}
@if ($fieldGroups->isNotEmpty())
  <div class="mt-4 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
    <h2 class="text-base font-bold text-plum-900 dark:text-white">អត្ថន័យនៃប្រអប់នីមួយៗ</h2>
    <p class="mt-1 text-xs text-plum-500 dark:text-plum-400">ប្រអប់ទាំងអស់មិនតម្រូវឲ្យបំពេញទេ — ប្រអប់ដែលទុកទទេនឹងមិនបង្ហាញលើធៀបឡើយ។</p>

    @foreach ($fieldGroups as $group => $keys)
      <h3 class="mt-5 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-plum-400">
        <x-app-icon :name="$navItems[$group]['icon']" class="h-4 w-4" /> {{ $navItems[$group]['label'] }}
      </h3>
      <dl class="mt-2 divide-y divide-plum-100 dark:divide-plum-800">
        @foreach ($keys as $key)
          <div wire:key="guide-{{ $key }}" class="py-3">
            <dt class="flex items-center gap-2 text-sm font-semibold text-plum-900 dark:text-white">
              {{ $fieldCatalog[$key]['label'] }}
              @unless ($invitation->fieldUnlocked($key))
                <span class="flex items-center gap-1 rounded-full bg-gold-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-gold-600 dark:text-gold-400">
                  <x-app-icon name="lock-closed" class="h-3 w-3" /> Premium
                </span>
              @endunless
            </dt>
            <dd class="mt-0.5 text-sm text-plum-600 dark:text-plum-300">{{ $fieldCatalog[$key]['hint'] ?? '' }}</dd>
          </div>
        @endforeach
      </dl>
    @endforeach
  </div>
@endif

{{-- Good to know --}}
<div class="mt-4 rounded-xl border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
  <h2 class="text-base font-bold text-plum-900 dark:text-white">គួរដឹង</h2>
  <ul class="mt-3 flex list-disc flex-col gap-2 pl-5 text-sm text-plum-600 dark:text-plum-300">
    <li>ផ្លាក <span class="font-bold text-gold-600 dark:text-gold-400">Premium</span> មានន័យថាប្រអប់នោះមិនរួមបញ្ចូលក្នុងគម្រោងរបស់អ្នកទេ — អ្នកអាចបំពេញបាន ប៉ុន្តែភ្ញៀវនឹងមិនឃើញវាឡើយ។</li>
    <li>ការកែប្រែនីមួយៗត្រូវចុច <strong>Save</strong> ទើបរក្សាទុក។ ភ្ញៀវដែលមានតំណរួចហើយនឹងឃើញការកែប្រែភ្លាមៗ មិនចាំបាច់ផ្ញើតំណថ្មីទេ។</li>
    <li>គម្រោងរបស់អ្នកអនុញ្ញាតភ្ញៀវបានរហូតដល់ <strong>{{ $invitation->max_recipients }}</strong> នាក់។ ការលុបភ្ញៀវចេញនឹងធ្វើឲ្យតំណរបស់គេលែងដំណើរការ។</li>
    @if ($invitation->expires_at)
      <li>ធៀបនេះដំណើរការរហូតដល់ថ្ងៃ <strong>{{ $invitation->expires_at->format('d M Y') }}</strong>។ ក្រោយថ្ងៃនោះ តំណទាំងអស់នឹងបិទ។</li>
    @endif
    <li>រូបភាព៖ JPG, PNG ឬ WebP រហូតដល់ 20 MB ក្នុងមួយរូប, GIF រហូតដល់ 5 MB។</li>
  </ul>
</div>
