    <section id="hero-slider" class="relative h-100 w-full overflow-hidden sm:h-125 md:h-150">
      <div class="hero-slide absolute inset-0 bg-cover bg-center bg-no-repeat opacity-100 transition-opacity duration-700 ease-in-out"
        style="background-image: url('{{ asset('images/hero-images/hero1.jpg') }}')">
      </div>
      <div class="hero-slide absolute inset-0 bg-cover bg-center bg-no-repeat opacity-0 transition-opacity duration-700 ease-in-out"
        style="background-image: url('{{ asset('images/hero-images/hero2.jpg') }}')">
      </div>
      <div class="hero-slide absolute inset-0 bg-cover bg-center bg-no-repeat opacity-0 transition-opacity duration-700 ease-in-out"
        style="background-image: url('{{ asset('images/hero-images/hero3.jpg') }}')">
      </div>

      <div class="pointer-events-none absolute inset-0 bg-linear-to-r from-black/80 via-black/60 to-black/30"></div>

      <div class="pointer-events-none relative z-10 flex h-full w-full min-w-0 flex-col items-center justify-center gap-5 px-4 text-center sm:gap-6 sm:px-6 lg:px-8">
        <h1 class="max-w-3xl wrap-break-word text-2xl font-bold leading-tight text-white drop-shadow-sm sm:text-3xl md:text-4xl lg:text-5xl">
          ភាពស្ងប់ចិត្តរបស់អ្នក គឺជាបេសកកម្មចម្បងរបស់យើង
        </h1>
        <p class="max-w-2xl wrap-break-word text-sm text-gray-200 sm:text-base md:text-lg">
          JINGLONG SECURITY CO.,LTD ផ្តល់នូវសេវាកម្មសន្តិសុខអាជីពសម្រាប់អាជីវកម្ម លំនៅដ្ឋាន
          និងព្រឹត្តិការណ៍នានាទូទាំងកម្ពុជា។ ក្រុមអ្នកយាមសន្តិសុខរបស់យើងត្រូវបានបណ្តុះបណ្តាលយ៉ាងម៉ត់ចត់
          និងត្រៀមខ្លួនជានិច្ច ដើម្បីការពារអ្វីដែលសំខាន់បំផុតសម្រាប់អ្នក។
        </p>
        <div class="pointer-events-auto flex w-full max-w-xs flex-col gap-3 sm:w-auto sm:max-w-none sm:flex-row">
          <a href="#contact"
            class="wrap-break-word rounded-md bg-amber-400 px-6 py-3 text-sm font-semibold text-green-950 transition-colors hover:bg-amber-300 sm:text-base">
            ស្នើសុំពិគ្រោះយោបល់ដោយឥតគិតថ្លៃ
          </a>
          <a href="#services"
            class="wrap-break-word rounded-md border border-white/70 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/10 sm:text-base">
            ស្វែងយល់សេវាកម្មរបស់យើង
          </a>
        </div>
      </div>

      <button type="button" id="hero-prev" aria-label="មុន"
        class="absolute left-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition-colors hover:bg-white/30">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <button type="button" id="hero-next" aria-label="បន្ទាប់"
        class="absolute right-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition-colors hover:bg-white/30">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <div id="hero-dots" class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2"></div>
    </section>
