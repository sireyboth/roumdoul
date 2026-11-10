<div>
    <section class="bg-brand-950 py-16 sm:py-24">
      <div class="mx-auto flex max-w-4xl flex-col items-center gap-5 px-4 text-center sm:gap-6 sm:px-6 lg:px-8">
        <span class="rounded-full border border-gold-400/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300">
          {{ __('Contact ROUMDOUL') }}
        </span>
        <h1 class="text-2xl font-bold leading-tight text-white drop-shadow-sm sm:text-3xl md:text-4xl">
          {{ __('Have a question? We are here to help you') }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-brand-100 sm:text-base">
          {{ __('Whether your question is about an order, a payment or a product, our team is ready to help you at any time.') }}
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
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">{{ __('Email') }}</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">we don't have EMAIL yet</p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="phone" class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">{{ __('Phone / Telegram') }}</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">+855 15 57 87 07</p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-plum-800 dark:text-brand-300">
                <x-app-icon name="clock" class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-white">{{ __('Support hours') }}</h3>
                <p class="mt-1 text-sm text-plum-600 dark:text-plum-400">{{ __('Customer support: 24 hours / 7 days') }}</p>
              </div>
            </div>

            <div class="mt-2 rounded-lg border border-plum-200 bg-plum-50 p-5 dark:border-plum-800 dark:bg-plum-900">
              <h3 class="mb-3 text-sm font-bold text-brand-800 dark:text-white">{{ __('Request a callback') }}</h3>
              <livewire:components.contact-form />
            </div>
          </div>

          <div class="rounded-lg border border-plum-200 bg-white p-6 shadow-sm transition-colors dark:border-plum-800 dark:bg-plum-900 sm:p-8 lg:col-span-2">
            <h2 class="mb-1 text-lg font-bold text-plum-900 dark:text-white">{{ __('Send us a message') }}</h2>
            <p class="mb-6 text-sm text-plum-500 dark:text-plum-400">{{ __('Fill in the form below and we will get back to you shortly') }}</p>
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
          <h2 class="text-2xl font-bold text-plum-900 dark:text-white sm:text-3xl">{{ __('Frequently asked questions') }}</h2>
        </div>

        <div class="mt-10 flex flex-col gap-3">
          @foreach ([
              ['q' => __('How long does it take to receive my order after purchasing?'), 'a' => __('Usually from a few minutes to a few hours after our team confirms your payment.')],
              ['q' => __('Which payment methods can I use?'), 'a' => __('We currently accept payment via ABA PAY, Wing and QR Code. Our team will send you the details right after you order.')],
              ['q' => __('Is there a guarantee if an account has a problem?'), 'a' => __('Yes. Every purchase comes with a replacement or refund guarantee if the account has a problem during the warranty period.')],
              ['q' => __('Can I buy several accounts at the same time?'), 'a' => __('Yes. You can order several items or several types of services in a single cart.')],
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
