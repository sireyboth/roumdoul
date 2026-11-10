<div>
    <section class="bg-brand-950 py-16 sm:py-24">
      <div class="mx-auto flex max-w-3xl flex-col items-center gap-5 px-4 text-center sm:gap-6 sm:px-6 lg:px-8">
        <img src="/images/Roumdoul_Logo.png" alt="ROUMDOUL" class="h-16 w-16 rounded-full object-contain shadow-lg sm:h-20 sm:w-20" />
        <span class="rounded-full border border-gold-400/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300">
          ROUMDOUL
        </span>
        <h1 class="text-2xl font-bold leading-tight text-white drop-shadow-sm sm:text-3xl md:text-4xl">
          {{ __('Your feedback matters to us') }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-brand-100 sm:text-base">
          {{ __('Please take a few minutes to share your experience with ROUMDOUL. No need to sign up or log in — just fill in the form below.') }}
        </p>
      </div>
    </section>

    <section class="bg-white py-16 transition-colors dark:bg-plum-950 sm:py-20">
      <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-plum-200 bg-white p-6 shadow-sm transition-colors dark:border-plum-800 dark:bg-plum-900 sm:p-8">
          <h2 class="mb-1 text-lg font-bold text-plum-900 dark:text-white">{{ __('Send your feedback') }}</h2>
          <p class="mb-6 text-sm text-plum-500 dark:text-plum-400">
            {{ __('Approved feedback may be shown publicly on our home page.') }}
          </p>
          <livewire:components.public-feedback-form />
        </div>
      </div>
    </section>
</div>
