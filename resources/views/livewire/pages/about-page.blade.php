<div>
    {{-- Hero + stats --}}
    <section class="bg-brand-950 py-16 sm:py-24">
      <div class="relative mx-auto flex max-w-4xl flex-col items-center gap-5 px-4 text-center sm:gap-6 sm:px-6 lg:px-8">
        <span class="rounded-full border border-gold-400/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300">
          រំដួល &mdash; ROUMDOUL
        </span>
        <h1 class="text-2xl font-bold leading-tight text-white drop-shadow-sm sm:text-3xl md:text-4xl">
          ស្វែងយល់អំពី ROUMDOUL
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-brand-100 sm:text-base">
          ហាងសេវាកម្មឌីជីថលកម្រិតខ្ពស់ដែលនាំយកកម្មវិធីល្បីៗពិភពលោក មកកាន់អ្នកប្រើប្រាស់កម្ពុជា ដោយសុវត្ថិភាព រហ័ស និងតម្លៃសមរម្យ។
        </p>
      </div>

      <div class="relative mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-6 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div class="flex flex-col items-center gap-1 text-center">
          <span class="text-2xl font-bold text-gold-400 sm:text-3xl">៥.០០០+</span>
          <span class="text-xs text-brand-200 sm:text-sm">អតិថិជនពេញចិត្ត</span>
        </div>
        <div class="flex flex-col items-center gap-1 text-center">
          <span class="text-2xl font-bold text-gold-400 sm:text-3xl">២០+</span>
          <span class="text-xs text-brand-200 sm:text-sm">សេវាកម្មឌីជីថល</span>
        </div>
        <div class="flex flex-col items-center gap-1 text-center">
          <span class="text-2xl font-bold text-gold-400 sm:text-3xl">២៤/៧</span>
          <span class="text-xs text-brand-200 sm:text-sm">គាំទ្រអតិថិជន</span>
        </div>
        <div class="flex flex-col items-center gap-1 text-center">
          <span class="text-2xl font-bold text-gold-400 sm:text-3xl">១០០%</span>
          <span class="text-xs text-brand-200 sm:text-sm">ដឹកជញ្ជូនភ្លាមៗ</span>
        </div>
      </div>
    </section>

    {{-- Mission & Vision --}}
    <section class="bg-white py-16 transition-colors dark:bg-plum-950 sm:py-20">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-2xl font-bold text-brand-800 dark:text-white sm:text-3xl">បេសកកម្ម និងចក្ខុវិស័យ</h2>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div class="flex flex-col gap-3 rounded-lg border border-plum-200 p-6 transition-colors dark:border-plum-800">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
              <x-app-icon name="sparkles" class="h-6 w-6" />
            </span>
            <h3 class="text-lg font-bold text-brand-800 dark:text-white">បេសកកម្ម</h3>
            <p class="text-sm text-plum-600 dark:text-plum-400">
              ផ្តល់ជូនសេវាកម្មឌីជីថលកម្រិតខ្ពស់ ដោយភាពស្មោះត្រង់ តម្លាភាព និងការគាំទ្រយ៉ាងឆាប់រហ័ស
              ដើម្បីអោយអតិថិជនគ្រប់រូបទទួលបានបទពិសោធន៍ទិញឥវ៉ាន់ដ៏រលូនបំផុត។
            </p>
          </div>

          <div class="flex flex-col gap-3 rounded-lg border border-plum-200 p-6 transition-colors dark:border-plum-800">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
              <x-app-icon name="star" class="h-6 w-6" />
            </span>
            <h3 class="text-lg font-bold text-brand-800 dark:text-white">ចក្ខុវិស័យ</h3>
            <p class="text-sm text-plum-600 dark:text-plum-400">
              ក្លាយជាហាងសេវាកម្មឌីជីថលដែលគួរឱ្យទុកចិត្តបំផុតនៅកម្ពុជា ជាកន្លែងទីមួយដែលអតិថិជននឹកឃើញ
              នៅពេលចង់ទិញកម្មវិធី ឬសមាជិកភាពឌីជីថលណាមួយ។
            </p>
          </div>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-6 lg:grid-cols-4">
          @foreach ([
              ['icon' => 'shield-check', 'label' => 'ភាពស្មោះត្រង់'],
              ['icon' => 'bolt', 'label' => 'ល្បឿនលឿន'],
              ['icon' => 'heart', 'label' => 'ចិត្តគំនិតអតិថិជន'],
              ['icon' => 'sparkles', 'label' => 'គុណភាពខ្ពស់'],
          ] as $value)
            <div class="flex flex-col items-center gap-3 text-center">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="{{ $value['icon'] }}" class="h-6 w-6" />
              </div>
              <h4 class="text-sm font-bold text-brand-800 dark:text-white">{{ $value['label'] }}</h4>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- How we operate --}}
    <section class="bg-plum-50 py-16 transition-colors dark:bg-plum-900 sm:py-20">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-2xl font-bold text-brand-800 dark:text-white sm:text-3xl">ហេតុអ្វីជាទីទុកចិត្ត</h2>
          <p class="mt-3 text-sm text-plum-600 dark:text-plum-400 sm:text-base">
            រាល់សេវាកម្មដែលយើងលក់ សុទ្ធតែឆ្លងកាត់ការត្រួតពិនិត្យគុណភាព និងភាពស្របច្បាប់ដ៏តឹងរឹង
            ដើម្បីធានាបាននូវបទពិសោធន៍ល្អបំផុតសម្រាប់អតិថិជន។
          </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2">
          @foreach ([
              ['icon' => 'shield-check', 'title' => 'គណនីស្របច្បាប់ ១០០%', 'desc' => 'រាល់គណនី/លេខកូដដែលយើងផ្តល់ជូន សុទ្ធតែជាកម្មសិទ្ធិស្របច្បាប់ និងធានាឱ្យដំណើរការបានយូរអង្វែង។'],
              ['icon' => 'bolt', 'title' => 'ដឹកជញ្ជូនភ្លាមៗ', 'desc' => 'ប្រព័ន្ធរបស់យើងត្រូវបានរៀបចំដើម្បីឱ្យអតិថិជនទទួលបានទំនិញក្នុងរយៈពេលខ្លីបំផុតបន្ទាប់ពីការទូទាត់។'],
              ['icon' => 'chat', 'title' => 'គាំទ្រ ២៤/៧', 'desc' => 'ក្រុមការងាររបស់យើងរួចរាល់ឆ្លើយតបរាល់សំណួរ និងដោះស្រាយបញ្ហាបានគ្រប់ពេលវេលា។'],
              ['icon' => 'heart', 'title' => 'តម្លៃដ៏សមរម្យ', 'desc' => 'យើងធានាតម្លៃប្រកួតប្រជែងបំផុត ដោយមិនសម្របសម្រួលគុណភាពសេវាកម្មឡើយ។'],
          ] as $item)
            <div class="flex flex-col gap-3 rounded-lg bg-white p-6 shadow-sm transition-colors dark:bg-plum-950">
              <div class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="{{ $item['icon'] }}" class="h-6 w-6" />
              </div>
              <h3 class="text-base font-bold text-brand-800 dark:text-white">{{ $item['title'] }}</h3>
              <p class="text-sm text-plum-600 dark:text-plum-400">{{ $item['desc'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    {{-- CTA banner --}}
    <section class="bg-brand-900 py-16 transition-colors sm:py-20">
      <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-white sm:text-3xl">ត្រៀមខ្លួនរួចរាល់ចាប់ផ្តើមទិញហើយឬនៅ?</h2>
        <p class="mt-3 text-sm text-brand-100 sm:text-base">
          ស្វែងរកសេវាកម្មឌីជីថលដែលអ្នកត្រូវការ ហើយទទួលបានវាភ្លាមៗ ដោយសុវត្ថិភាព និងតម្លៃសមរម្យ។
        </p>
        <a href="/shop" wire:navigate
          class="mt-6 inline-block rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-brand-950 transition-colors hover:bg-gold-400 sm:text-base">
          ចាប់ផ្តើមទិញឥឡូវនេះ
        </a>
      </div>
    </section>
</div>
