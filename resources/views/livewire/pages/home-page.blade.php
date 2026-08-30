<div>
  {{-- Hero promo carousel --}}
  <section id="hero-slider" class="relative h-96 overflow-hidden sm:h-104">
    <div class="hero-slide absolute inset-0 bg-cover bg-center opacity-100 transition-opacity duration-700"
      style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.85), rgb(12 22 34 / 0.85)), url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1600&q=80')"></div>
    <div class="hero-slide absolute inset-0 bg-cover bg-center opacity-0 transition-opacity duration-700"
      style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.85), rgb(12 22 34 / 0.85)), url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1600&q=80')"></div>
    <div class="hero-slide absolute inset-0 bg-cover bg-center opacity-0 transition-opacity duration-700"
      style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.85), rgb(12 22 34 / 0.85)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1600&q=80')"></div>

    <div class="relative mx-auto flex h-full max-w-7xl flex-col items-center justify-center px-4 text-center sm:px-6 lg:px-8">
      <span class="mb-4 flex items-center gap-2 rounded-full border border-gold-400/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300">
        ហាងសេវាកម្មឌីជីថលកម្រិតខ្ពស់
      </span>
      <h1 class="max-w-3xl text-3xl font-extrabold leading-tight text-white sm:text-5xl">
        ROUMDOUL &mdash; សេវាកម្មឌីជីថលពិសេស <span class="text-gold-400">តម្លៃសមរម្យ</span>
      </h1>
      <p class="mt-4 max-w-xl text-sm text-plum-300 sm:text-base">
        Gemini Pro, ChatGPT Plus, Envato, Netflix, Canva Pro និងកម្មវិធីល្បីៗជាច្រើនទៀត ដឹកជញ្ជូនភ្លាមៗ ដោយសុវត្ថិភាព ១០០%។
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="/shop" wire:navigate
          class="rounded-md bg-gold-500 px-6 py-3 text-sm font-bold text-brand-950 transition-colors hover:bg-gold-400">
          ចាប់ផ្តើមទិញឥឡូវនេះ
        </a>
        <a href="#categories"
          class="rounded-md border border-white/25 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/10">
          មើលប្រភេទទាំងអស់
        </a>
      </div>
    </div>

    <div class="absolute inset-x-0 bottom-5 flex items-center justify-center gap-4">
      <button id="hero-prev" type="button" aria-label="Previous"
        class="flex h-9 w-9 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white">
        <x-app-icon name="chevron-left" class="h-5 w-5" />
      </button>
      <div id="hero-dots" class="flex items-center gap-2"></div>
      <button id="hero-next" type="button" aria-label="Next"
        class="flex h-9 w-9 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white">
        <x-app-icon name="chevron-right" class="h-5 w-5" />
      </button>
    </div>
  </section>

  {{-- Featured services --}}
  @if ($featuredServices->isNotEmpty())
    <section class="border-y border-plum-100 bg-plum-50 py-14 dark:border-plum-800 dark:bg-plum-900/40">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div data-reveal class="mb-8 flex items-end justify-between">
          <div>
            <span class="text-xs font-bold uppercase tracking-widest text-gold-600 dark:text-gold-400">ពេញនិយមបំផុត</span>
            <h2 class="mt-1 text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">សេវាកម្មពិសេស</h2>
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

  {{-- Category quick links --}}
  <section id="categories" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div data-reveal class="mb-8">
      <h2 class="text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">ស្វែងរកតាមប្រភេទ</h2>
      <p class="mt-1 text-sm text-plum-500 dark:text-plum-400">ជ្រើសរើសប្រភេទសេវាកម្មដែលអ្នកត្រូវការ</p>
    </div>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
      @foreach ($categories as $category)
        <a href="/shop/{{ $category->slug }}" wire:navigate data-reveal
          class="group flex flex-col items-center gap-3 rounded-lg border border-plum-200 bg-white p-6 text-center transition-colors hover:border-brand-400 dark:border-plum-800 dark:bg-plum-900">
          <span class="flex h-12 w-12 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white dark:bg-plum-800 dark:text-brand-300">
            <x-app-icon name="{{ $category->icon }}" class="h-6 w-6" />
          </span>
          <span class="text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $category->name_km }}</span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- Why choose us --}}
  <section class="bg-brand-950 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div data-reveal class="mb-10 text-center">
        <h2 class="text-xl font-bold text-white sm:text-2xl">ហេតុអ្វីជ្រើសរើស ROUMDOUL?</h2>
      </div>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['icon' => 'bolt', 'title' => 'ដឹកជញ្ជូនភ្លាមៗ', 'desc' => 'ទទួលបានគណនី/លេខកូដភ្លាមៗក្រោយបញ្ជាទិញជោគជ័យ'],
            ['icon' => 'shield-check', 'title' => 'សុវត្ថិភាព ១០០%', 'desc' => 'គណនីស្របច្បាប់ ធានាដោយក្រុមការងាររបស់យើង'],
            ['icon' => 'chat', 'title' => 'គាំទ្រ ២៤/៧', 'desc' => 'ក្រុមការងារយើងខ្ញុំរួចរាល់ជួយឆ្លើយរាល់ពេលវេលា'],
            ['icon' => 'heart', 'title' => 'តម្លៃសមរម្យបំផុត', 'desc' => 'តម្លៃប្រកួតប្រជែង ជាមួយការធានាគុណភាព'],
        ] as $item)
          <div data-reveal class="rounded-lg border border-white/10 p-6 text-center">
            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white/5 text-gold-400">
              <x-app-icon name="{{ $item['icon'] }}" class="h-5.5 w-5.5" />
            </span>
            <h3 class="mt-4 text-sm font-bold text-white">{{ $item['title'] }}</h3>
            <p class="mt-1.5 text-sm text-plum-400">{{ $item['desc'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Testimonials --}}
  <section class="py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div data-reveal class="mb-10 text-center">
        <h2 class="text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">អតិថិជនរបស់យើងនិយាយអ្វី</h2>
      </div>
    </div>

    @if ($reviews->isEmpty())
      {{-- No real reviews yet — sample testimonials so the section isn't empty pre-launch --}}
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          @foreach ([
              ['name' => 'សុខា', 'text' => 'ទិញ Gemini Pro នៅទីនេះ ដឹកជញ្ជូនលឿនណាស់ ក្នុងរយៈពេលតែប៉ុន្មាននាទី!'],
              ['name' => 'ដារា', 'text' => 'តម្លៃសមរម្យ សេវាកម្មល្អ ខ្ញុំណែនាំមិត្តភក្តិទាំងអស់ឱ្យមកទិញនៅទីនេះ។'],
              ['name' => 'ចន្ទថា', 'text' => 'ក្រុមការងារឆ្លើយតបលឿន និងមានភាពរួសរាយ។ ជឿជាក់បាន ១០០%!'],
          ] as $t)
            <div data-reveal class="rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
              <div class="flex gap-0.5 text-gold-500">
                @for ($i = 0; $i < 5; $i++)
                  <x-app-icon name="star" class="h-4 w-4 fill-current" />
                @endfor
              </div>
              <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $t['text'] }}&rdquo;</p>
              <div class="mt-4 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">{{ mb_substr($t['name'], 0, 1) }}</span>
                <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $t['name'] }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @elseif ($reviews->count() > 4)
      {{-- Many real reviews — auto-scrolling marquee, duplicated once for a seamless loop --}}
      <div class="overflow-hidden">
        <div class="flex w-max animate-marquee gap-5">
          @foreach ($reviews->concat($reviews) as $review)
            <div wire:key="review-marquee-{{ $loop->index }}" class="w-80 shrink-0 rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
              <div class="flex gap-0.5 text-gold-500">
                @for ($i = 0; $i < 5; $i++)
                  <x-app-icon name="star" class="h-4 w-4 {{ $i < $review->rating ? 'fill-current' : 'text-plum-200 dark:text-plum-700' }}" />
                @endfor
              </div>
              <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $review->comment }}&rdquo;</p>
              <div class="mt-4 flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">{{ mb_substr($review->customer_name, 0, 1) }}</span>
                <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $review->customer_name }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @else
      {{-- A handful of real reviews — static grid --}}
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          @foreach ($reviews as $review)
            <div data-reveal wire:key="review-{{ $review->id }}" class="rounded-lg border border-plum-200 bg-white p-6 dark:border-plum-800 dark:bg-plum-900">
              <div class="flex gap-0.5 text-gold-500">
                @for ($i = 0; $i < 5; $i++)
                  <x-app-icon name="star" class="h-4 w-4 {{ $i < $review->rating ? 'fill-current' : 'text-plum-200 dark:text-plum-700' }}" />
                @endfor
              </div>
              <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $review->comment }}&rdquo;</p>
              <div class="mt-4 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 dark:bg-plum-800 dark:text-brand-300">{{ mb_substr($review->customer_name, 0, 1) }}</span>
                <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $review->customer_name }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </section>

  {{-- CTA banner --}}
  <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div data-reveal class="flex flex-col items-center gap-5 rounded-lg bg-brand-700 px-6 py-12 text-center sm:flex-row sm:justify-between sm:text-left">
      <div>
        <h2 class="text-xl font-bold text-white sm:text-2xl">ត្រៀមខ្លួនរួចរាល់ហើយឬនៅ?</h2>
        <p class="mt-1 text-sm text-brand-100">ចាប់ផ្តើមទិញសេវាកម្មឌីជីថលដែលអ្នកចង់បានឥឡូវនេះ</p>
      </div>
      <a href="/shop" wire:navigate
        class="shrink-0 rounded-md bg-white px-6 py-3 text-sm font-bold text-brand-700 transition-colors hover:bg-plum-100">
        ចាប់ផ្តើមទិញ
      </a>
    </div>
  </section>
</div>
