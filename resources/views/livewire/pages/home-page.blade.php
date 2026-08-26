<div>
  {{-- Hero promo carousel --}}
  <section id="hero-slider" class="relative h-104 overflow-hidden sm:h-120">
    <div class="hero-slide absolute inset-0 opacity-100 transition-opacity duration-700 bg-linear-to-br from-brand-700 via-brand-800 to-plum-950"></div>
    <div class="hero-slide absolute inset-0 opacity-0 transition-opacity duration-700 bg-linear-to-br from-brand-900 via-plum-900 to-brand-600"></div>
    <div class="hero-slide absolute inset-0 opacity-0 transition-opacity duration-700 bg-linear-to-br from-plum-950 via-brand-950 to-gold-600/40"></div>

    <div class="pointer-events-none absolute -right-16 top-10 h-72 w-72 animate-float-slow rounded-full bg-gold-400/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-10 bottom-0 h-56 w-56 animate-float-slow rounded-full bg-brand-300/10 blur-3xl"></div>

    <div class="relative mx-auto flex h-full max-w-7xl flex-col items-center justify-center px-4 text-center sm:px-6 lg:px-8">
      <span class="mb-4 flex items-center gap-2 rounded-full border border-gold-400/40 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300 backdrop-blur">
        <x-icon name="sparkles" class="h-4 w-4" />
        ហាងសេវាកម្មឌីជីថលកម្រិតខ្ពស់
      </span>
      <h1 class="max-w-3xl text-3xl font-extrabold leading-tight text-white sm:text-5xl">
        ROUMDOUL &mdash; សេវាកម្មឌីជីថលពិសេស <span class="text-gold-400">តម្លៃសមរម្យ</span>
      </h1>
      <p class="mt-4 max-w-xl text-sm text-brand-100 sm:text-base">
        Gemini Pro, ChatGPT Plus, Envato, Netflix, Canva Pro និងកម្មវិធីល្បីៗជាច្រើនទៀត ដឹកជញ្ជូនភ្លាមៗ ដោយសុវត្ថិភាព ១០០%។
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="/shop" wire:navigate
          class="rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-brand-950 shadow-lg shadow-gold-900/20 transition-transform hover:-translate-y-0.5 hover:bg-gold-400">
          ចាប់ផ្តើមទិញឥឡូវនេះ
        </a>
        <a href="#categories"
          class="rounded-full border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition-colors hover:bg-white/20">
          មើលប្រភេទទាំងអស់
        </a>
      </div>
    </div>

    <div class="absolute inset-x-0 bottom-5 flex items-center justify-center gap-4">
      <button id="hero-prev" type="button" aria-label="Previous"
        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20">
        <x-icon name="chevron-left" class="h-5 w-5" />
      </button>
      <div id="hero-dots" class="flex items-center gap-2"></div>
      <button id="hero-next" type="button" aria-label="Next"
        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20">
        <x-icon name="chevron-right" class="h-5 w-5" />
      </button>
    </div>
  </section>

  {{-- Marquee trust strip --}}
  <div class="overflow-hidden border-y border-brand-100 bg-brand-50 py-3 dark:border-plum-800 dark:bg-plum-800/40">
    <div class="flex w-max animate-marquee gap-10 whitespace-nowrap text-sm font-semibold text-brand-700 dark:text-brand-300">
      @for ($i = 0; $i < 2; $i++)
        <span class="flex items-center gap-2"><x-icon name="sparkles" class="h-4 w-4" /> Gemini Pro</span>
        <span class="flex items-center gap-2"><x-icon name="paint-brush" class="h-4 w-4" /> Envato Elements</span>
        <span class="flex items-center gap-2"><x-icon name="play" class="h-4 w-4" /> Netflix Premium</span>
        <span class="flex items-center gap-2"><x-icon name="sparkles" class="h-4 w-4" /> ChatGPT Plus</span>
        <span class="flex items-center gap-2"><x-icon name="paint-brush" class="h-4 w-4" /> Canva Pro</span>
        <span class="flex items-center gap-2"><x-icon name="cpu-chip" class="h-4 w-4" /> Windows 11 Pro</span>
        <span class="flex items-center gap-2"><x-icon name="puzzle-piece" class="h-4 w-4" /> Steam Wallet</span>
      @endfor
    </div>
  </div>

  {{-- Category quick links --}}
  <section id="categories" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div data-reveal class="mb-8 text-center">
      <h2 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">ស្វែងរកតាមប្រភេទ</h2>
      <p class="mt-2 text-sm text-plum-500 dark:text-plum-400">ជ្រើសរើសប្រភេទសេវាកម្មដែលអ្នកត្រូវការ</p>
    </div>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
      @foreach ($categories as $category)
        <a href="/shop/{{ $category->slug }}" wire:navigate data-reveal
          class="hover-lift group flex flex-col items-center gap-3 rounded-2xl border border-plum-200 bg-white p-6 text-center shadow-sm transition-shadow hover:shadow-lg hover:shadow-brand-900/10 dark:border-plum-800 dark:bg-plum-900">
          <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white dark:bg-plum-800 dark:text-brand-300">
            <x-icon name="{{ $category->icon }}" class="h-7 w-7" />
          </span>
          <span class="text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $category->name_km }}</span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- Featured services --}}
  @if ($featuredServices->isNotEmpty())
    <section class="bg-brand-50/50 py-14 dark:bg-plum-800/20">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div data-reveal class="mb-8 flex items-end justify-between">
          <div>
            <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gold-600 dark:text-gold-400">
              <x-icon name="star" class="h-4 w-4" /> ពេញនិយមបំផុត
            </span>
            <h2 class="mt-1 text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">សេវាកម្មពិសេស</h2>
          </div>
          <a href="/shop" wire:navigate class="hidden text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300 sm:block">មើលទាំងអស់ &rarr;</a>
        </div>
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
          @foreach ($featuredServices as $service)
            <x-shop.product-card :service="$service" />
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Tabbed browse by category --}}
  <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" x-data="{ tab: '{{ $categories->first()?->slug }}' }">
    <div data-reveal class="mb-8 text-center">
      <h2 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">រកមើលងាយស្រួល</h2>
      <p class="mt-2 text-sm text-plum-500 dark:text-plum-400">ជ្រើសរើសផ្ទាំងខាងក្រោមដើម្បីមើលសេវាកម្មតាមប្រភេទ</p>
    </div>

    <div class="mb-8 flex flex-wrap justify-center gap-2">
      @foreach ($categories as $category)
        <button type="button" @click="tab = '{{ $category->slug }}'"
          :class="tab === '{{ $category->slug }}' ? 'bg-brand-600 text-white shadow-md shadow-brand-900/20' : 'bg-white text-plum-600 border border-plum-200 hover:border-brand-300 dark:bg-plum-900 dark:text-plum-300 dark:border-plum-700'"
          class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors">
          <x-icon name="{{ $category->icon }}" class="h-4 w-4" />
          {{ $category->name_km }}
        </button>
      @endforeach
    </div>

    @foreach ($categories as $category)
      <div x-show="tab === '{{ $category->slug }}'" x-cloak x-transition.opacity
        class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($servicesByCategory->get($category->id, collect()) as $service)
          <x-shop.product-card :service="$service" />
        @endforeach
      </div>
    @endforeach
  </section>

  {{-- Why choose us --}}
  <section class="bg-linear-to-br from-brand-900 via-brand-950 to-plum-950 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div data-reveal class="mb-10 text-center">
        <h2 class="text-2xl font-extrabold text-white sm:text-3xl">ហេតុអ្វីជ្រើសរើស ROUMDOUL?</h2>
      </div>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['icon' => 'bolt', 'title' => 'ដឹកជញ្ជូនភ្លាមៗ', 'desc' => 'ទទួលបានគណនី/លេខកូដភ្លាមៗក្រោយបញ្ជាទិញជោគជ័យ'],
            ['icon' => 'shield-check', 'title' => 'សុវត្ថិភាព ១០០%', 'desc' => 'គណនីស្របច្បាប់ ធានាដោយក្រុមការងាររបស់យើង'],
            ['icon' => 'chat', 'title' => 'គាំទ្រ ២៤/៧', 'desc' => 'ក្រុមការងារយើងខ្ញុំរួចរាល់ជួយឆ្លើយរាល់ពេលវេលា'],
            ['icon' => 'heart', 'title' => 'តម្លៃសមរម្យបំផុត', 'desc' => 'តម្លៃប្រកួតប្រជែង ជាមួយការធានាគុណភាព'],
        ] as $item)
          <div data-reveal class="hover-lift rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gold-400/15 text-gold-400">
              <x-icon name="{{ $item['icon'] }}" class="h-6 w-6" />
            </span>
            <h3 class="mt-4 text-base font-bold text-white">{{ $item['title'] }}</h3>
            <p class="mt-1.5 text-sm text-brand-200">{{ $item['desc'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Testimonials --}}
  <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div data-reveal class="mb-10 text-center">
      <h2 class="text-2xl font-extrabold text-plum-900 dark:text-white sm:text-3xl">អតិថិជនរបស់យើងនិយាយអ្វី</h2>
    </div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ([
          ['name' => 'សុខា', 'text' => 'ទិញ Gemini Pro នៅទីនេះ ដឹកជញ្ជូនលឿនណាស់ ក្នុងរយៈពេលតែប៉ុន្មាននាទី!', 'role' => 'អតិថិជនទៀងទាត់'],
          ['name' => 'ដារា', 'text' => 'តម្លៃសមរម្យ សេវាកម្មល្អ ខ្ញុំណែនាំមិត្តភក្តិទាំងអស់ឱ្យមកទិញនៅទីនេះ។', 'role' => 'Freelancer'],
          ['name' => 'ចន្ទថា', 'text' => 'ក្រុមការងារឆ្លើយតបលឿន និងមានភាពរួសរាយ។ ជឿជាក់បាន ១០០%!', 'role' => 'Business Owner'],
      ] as $t)
        <div data-reveal class="hover-lift rounded-2xl border border-plum-200 bg-white p-6 shadow-sm dark:border-plum-800 dark:bg-plum-900">
          <div class="flex gap-0.5 text-gold-500">
            @for ($i = 0; $i < 5; $i++)
              <x-icon name="star" class="h-4 w-4 fill-current" />
            @endfor
          </div>
          <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $t['text'] }}&rdquo;</p>
          <div class="mt-4 flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">{{ mb_substr($t['name'], 0, 1) }}</span>
            <div>
              <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $t['name'] }}</p>
              <p class="text-xs text-plum-400">{{ $t['role'] }}</p>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- CTA banner --}}
  <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div data-reveal class="flex flex-col items-center gap-5 rounded-3xl bg-linear-to-r from-brand-600 to-brand-800 px-6 py-12 text-center shadow-xl shadow-brand-900/20 sm:flex-row sm:justify-between sm:text-left">
      <div>
        <h2 class="text-2xl font-extrabold text-white">ត្រៀមខ្លួនរួចរាល់ហើយឬនៅ?</h2>
        <p class="mt-1 text-sm text-brand-100">ចាប់ផ្តើមទិញសេវាកម្មឌីជីថលដែលអ្នកចង់បានឥឡូវនេះ</p>
      </div>
      <a href="/shop" wire:navigate
        class="shrink-0 rounded-full bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition-transform hover:-translate-y-0.5">
        ចាប់ផ្តើមទិញ
      </a>
    </div>
  </section>
</div>
