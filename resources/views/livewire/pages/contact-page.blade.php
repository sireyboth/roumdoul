<div>
    <section class="bg-brand-950 py-16 sm:py-24">
      <div class="mx-auto flex max-w-4xl flex-col items-center gap-5 px-4 text-center sm:gap-6 sm:px-6 lg:px-8">
        <span class="rounded-full border border-gold-400/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300">
          ទំនាក់ទំនងមកកាន់ ROUMDOUL
        </span>
        <h1 class="text-2xl font-bold leading-tight text-white drop-shadow-sm sm:text-3xl md:text-4xl">
          មានសំណួរ? យើងនៅទីនេះដើម្បីជួយអ្នក
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-brand-100 sm:text-base">
          មិនថាបញ្ហាអំពីការបញ្ជាទិញ ការទូទាត់ ឬចង់សួរអំពីផលិតផលទេ ក្រុមការងាររបស់យើងរួចរាល់ជួយអ្នកគ្រប់ពេលវេលា។
        </p>
      </div>
    </section>

    {{-- Contact info + form --}}
    <section class="bg-white py-16 transition-colors dark:bg-plum-950 sm:py-20">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
          <div class="flex flex-col gap-6 lg:col-span-1">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="envelope" class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">អ៊ីមែល</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">we don't have EMAIL yet</p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="phone" class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">ទូរស័ព្ទ / Telegram</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">+855 15 57 87 07</p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="clock" class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">ម៉ោងគាំទ្រ</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">គាំទ្រអតិថិជន៖ ២៤ម៉ោង / ៧ថ្ងៃ</p>
              </div>
            </div>

            <div class="mt-2 rounded-lg border border-plum-200 bg-plum-50 p-5 dark:border-plum-800 dark:bg-plum-900">
              <h3 class="mb-3 text-sm font-bold text-brand-800 dark:text-white">ស្នើសុំទូរស័ព្ទមកវិញ</h3>
              <livewire:components.contact-form />
            </div>
          </div>

          <div class="rounded-lg border border-plum-200 bg-white p-6 shadow-sm transition-colors dark:border-plum-800 dark:bg-plum-900 sm:p-8 lg:col-span-2">
            <h2 class="mb-1 text-lg font-bold text-plum-900 dark:text-white">ផ្ញើសាររបស់អ្នក</h2>
            <p class="mb-6 text-sm text-plum-500 dark:text-plum-400">បំពេញទម្រង់ខាងក្រោម ហើយយើងនឹងឆ្លើយតបក្នុងរយៈពេលឆាប់ៗនេះ</p>
            <livewire:components.contact-page-form
              :initial-service="request()->query('service', '')"
              :initial-message="request()->query('message', '')" />
          </div>
        </div>
      </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-plum-50 py-16 transition-colors dark:bg-plum-900 sm:py-20">
      <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-2xl font-bold text-plum-900 dark:text-white sm:text-3xl">សំណួរដែលសួរញឹកញាប់</h2>
        </div>

        <div class="mt-10 flex flex-col gap-3">
          @foreach ([
              ['q' => 'តើត្រូវចំណាយពេលប៉ុន្មានដើម្បីទទួលបានទំនិញក្រោយបញ្ជាទិញ?', 'a' => 'ជាធម្មតាក្នុងរយៈពេលពីរបីនាទីរហូតដល់ពីរបីម៉ោង ក្រោយពេលក្រុមការងារបញ្ជាក់ការទូទាត់របស់អ្នក។'],
              ['q' => 'តើវិធីទូទាត់អ្វីខ្លះដែលអាចប្រើបាន?', 'a' => 'បច្ចុប្បន្នយើងទទួលការទូទាត់តាមរយៈ ABA PAY, Wing, និង QR Code។ ក្រុមការងារនឹងផ្ញើព័ត៌មានលម្អិតជូនអ្នកភ្លាមៗក្រោយបញ្ជាទិញ។'],
              ['q' => 'តើមានការធានាឬអត់ បើគណនីមានបញ្ហា?', 'a' => 'បាទ/ចាស រាល់ការទិញទាំងអស់មានការធានាជំនួស ឬសងប្រាក់វិញ ប្រសិនបើគណនីមានបញ្ហាក្នុងកំឡុងពេលធានា។'],
              ['q' => 'តើខ្ញុំអាចទិញច្រើនគណនីក្នុងពេលតែមួយបានទេ?', 'a' => 'បាទ/ចាស អ្នកអាចបញ្ជាទិញច្រើនចំនួន ឬច្រើនប្រភេទសេវាកម្មក្នុងកន្ត្រកតែមួយបាន។'],
          ] as $faq)
            <div class="rounded-xl border border-plum-200 bg-white transition-colors dark:border-plum-800 dark:bg-plum-950" x-data="{ open: false }">
              <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                <span class="font-semibold text-plum-800 dark:text-white">{{ $faq['q'] }}</span>
                <x-app-icon name="chevron-down" class="h-5 w-5 shrink-0 text-plum-400 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
              </button>
              <div class="grid overflow-hidden transition-[grid-template-rows] duration-200 ease-in-out"
                :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="min-h-0">
                  <p class="px-5 pb-4 text-sm text-plum-600 dark:text-plum-400">{{ $faq['a'] }}</p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
</div>
