<div>
  {{-- Hero promo carousel --}}
  <section id="hero-slider" class="relative h-[26rem] overflow-hidden sm:h-[30rem] lg:h-[34rem]">
    <div class="hero-slide absolute inset-0 overflow-hidden opacity-100 transition-opacity duration-700">
      <div class="hero-slide-bg absolute inset-0 bg-cover bg-center"
        style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.88), rgb(12 22 34 / 0.88)), url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1600&q=80'); --kb-tx: 0%; --kb-ty: -1.5%; --kb-duration: 9s;"></div>
    </div>
    <div class="hero-slide absolute inset-0 overflow-hidden opacity-0 transition-opacity duration-700">
      <div class="hero-slide-bg absolute inset-0 bg-cover bg-center"
        style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.88), rgb(12 22 34 / 0.88)), url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1600&q=80'); --kb-tx: -2.5%; --kb-ty: 0.5%; --kb-duration: 11s;"></div>
    </div>
    <div class="hero-slide absolute inset-0 overflow-hidden opacity-0 transition-opacity duration-700">
      <div class="hero-slide-bg absolute inset-0 bg-cover bg-center"
        style="background-image: linear-gradient(to bottom right, rgb(53 10 30 / 0.88), rgb(12 22 34 / 0.88)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1600&q=80'); --kb-tx: 2%; --kb-ty: 1.5%; --kb-duration: 10s;"></div>
    </div>

    {{-- Decorative floating glow orbs --}}
    <div class="pointer-events-none absolute -left-16 top-10 h-72 w-72 rounded-full bg-brand-500/30 blur-3xl animate-invite-float" style="--drift-duration:9s"></div>
    <div class="pointer-events-none absolute -right-10 bottom-0 h-80 w-80 rounded-full bg-gold-400/20 blur-3xl animate-invite-float" style="--drift-duration:11s; --drift-delay:1.5s"></div>

    {{-- 3D particle field (Three.js, wired up in resources/js/modules/heroParticles.js) --}}
    <div id="hero-particles" class="pointer-events-none absolute inset-0" aria-hidden="true"></div>

    <div class="relative mx-auto flex h-full max-w-7xl flex-col items-center justify-center px-4 text-center sm:px-6 lg:px-8">
      {{-- Per-slide headline/subtext — crossfades independently of the CTA below via
           heroSlider.js's applyContent(), so the shared CTA never has to duplicate
           across slides (avoids stacking invisible/inactive buttons on top of each
           other, which would otherwise steal clicks and tab focus). --}}
      <div class="relative flex min-h-[13rem] w-full flex-col items-center justify-center sm:min-h-[15rem]">
        <div class="hero-slide-content absolute inset-0 flex flex-col items-center justify-center">
          <span class="mb-5 flex items-center gap-2 rounded-full border border-gold-400/40 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300 backdrop-blur animate-invite-glow" style="--accent:#d9b66a">
            <x-app-icon name="bolt" class="h-3.5 w-3.5" />
            ហាងសេវាកម្មឌីជីថលកម្រិតខ្ពស់
          </span>
          <h1 class="max-w-3xl text-4xl font-extrabold leading-[1.1] text-white sm:text-5xl lg:text-6xl">
            ROUMDOUL &mdash; សេវាកម្មឌីជីថលពិសេស
            <span class="block bg-gradient-to-r from-gold-300 via-gold-400 to-gold-300 bg-clip-text text-transparent">តម្លៃសមរម្យ</span>
          </h1>
          <p class="mt-5 max-w-xl text-sm text-plum-300 sm:text-base">
            Gemini Pro, ChatGPT Plus, Envato, Netflix, Canva Pro និងកម្មវិធីល្បីៗជាច្រើនទៀត ដឹកជញ្ជូនភ្លាមៗ ដោយសុវត្ថិភាព ១០០%។
          </p>
        </div>
        <div class="hero-slide-content absolute inset-0 flex flex-col items-center justify-center">
          <span class="mb-5 flex items-center gap-2 rounded-full border border-gold-400/40 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300 backdrop-blur animate-invite-glow" style="--accent:#d9b66a">
            <x-app-icon name="bolt" class="h-3.5 w-3.5" />
            ដឹកជញ្ជូនភ្លាមៗ
          </span>
          <h1 class="max-w-3xl text-4xl font-extrabold leading-[1.1] text-white sm:text-5xl lg:text-6xl">
            បញ្ជាទិញរួច ទទួលបានគណនីភ្លាមៗ
            <span class="block bg-gradient-to-r from-gold-300 via-gold-400 to-gold-300 bg-clip-text text-transparent">មិនចាំបាច់រង់ចាំយូរឡើយ</span>
          </h1>
          <p class="mt-5 max-w-xl text-sm text-plum-300 sm:text-base">
            ទទួលបានគណនី/លេខកូដភ្លាមៗក្រោយបញ្ជាទិញជោគជ័យ — សម្រាប់សេវាកម្មឌីជីថលគ្រប់ប្រភេទ។
          </p>
        </div>
        <div class="hero-slide-content absolute inset-0 flex flex-col items-center justify-center">
          <span class="mb-5 flex items-center gap-2 rounded-full border border-gold-400/40 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300 backdrop-blur animate-invite-glow" style="--accent:#d9b66a">
            <x-app-icon name="shield-check" class="h-3.5 w-3.5" />
            សុវត្ថិភាព ១០០%
          </span>
          <h1 class="max-w-3xl text-4xl font-extrabold leading-[1.1] text-white sm:text-5xl lg:text-6xl">
            គណនីស្របច្បាប់ ធានាដោយ
            <span class="block bg-gradient-to-r from-gold-300 via-gold-400 to-gold-300 bg-clip-text text-transparent">ក្រុមការងាររបស់យើង</span>
          </h1>
          <p class="mt-5 max-w-xl text-sm text-plum-300 sm:text-base">
            គាំទ្រ ២៤/៧ ជាមួយតម្លៃប្រកួតប្រជែង និងការធានាគុណភាពគ្រប់ការបញ្ជាទិញ។
          </p>
        </div>
      </div>
      <div data-reveal class="mt-9 flex flex-wrap items-center justify-center gap-3">
        <a href="/shop" wire:navigate
          class="rounded-md bg-gold-500 px-7 py-3.5 text-sm font-bold text-brand-950 shadow-lg shadow-gold-500/20 transition-all hover:-translate-y-0.5 hover:bg-gold-400 hover:shadow-xl hover:shadow-gold-500/30">
          ចាប់ផ្តើមទិញឥឡូវនេះ
        </a>
        <a href="#categories"
          class="rounded-md border border-white/25 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition-all hover:-translate-y-0.5 hover:border-white/40 hover:bg-white/10">
          មើលប្រភេទទាំងអស់
        </a>
      </div>
    </div>

    <a href="#stats" aria-label="Scroll down"
      class="absolute inset-x-0 bottom-16 mx-auto flex h-9 w-9 animate-bounce items-center justify-center rounded-full border border-white/20 text-white/60 transition-colors hover:text-white sm:bottom-20">
      <x-app-icon name="chevron-down" class="h-4 w-4" />
    </a>

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

  {{-- Trust stats strip — real counts, floated over the hero for a layered look --}}
  <section id="stats" class="relative z-10 -mt-8 px-4 sm:-mt-12 sm:px-6 lg:px-8">
    <div data-reveal class="mx-auto grid max-w-6xl grid-cols-2 gap-4 rounded-2xl border border-plum-200/70 bg-white/90 p-5 shadow-xl shadow-plum-900/10 backdrop-blur-md dark:border-plum-800/70 dark:bg-plum-900/90 sm:grid-cols-4 sm:gap-6 sm:p-7">
      <div class="flex flex-col items-center gap-1 text-center sm:items-start sm:text-left">
        <span class="text-2xl font-extrabold text-brand-700 dark:text-brand-300 sm:text-3xl">
          <span data-counter data-counter-target="{{ $categories->count() }}">0</span><span>+</span>
        </span>
        <span class="text-xs font-medium text-plum-500 dark:text-plum-400">ប្រភេទសេវាកម្ម</span>
      </div>
      <div class="flex flex-col items-center gap-1 text-center sm:items-start sm:text-left">
        <span class="text-2xl font-extrabold text-brand-700 dark:text-brand-300 sm:text-3xl">
          <span data-counter data-counter-target="{{ $totalActiveServices }}">0</span><span>+</span>
        </span>
        <span class="text-xs font-medium text-plum-500 dark:text-plum-400">ផលិតផលឌីជីថល</span>
      </div>
      <div class="flex flex-col items-center gap-1.5 text-center sm:items-start sm:text-left">
        <x-app-icon name="truck" class="h-6 w-6 text-brand-700 dark:text-brand-300" />
        <span class="text-xs font-medium text-plum-500 dark:text-plum-400">ដឹកជញ្ជូនភ្លាមៗ</span>
      </div>
      <div class="flex flex-col items-center gap-1.5 text-center sm:items-start sm:text-left">
        <x-app-icon name="clock" class="h-6 w-6 text-brand-700 dark:text-brand-300" />
        <span class="text-xs font-medium text-plum-500 dark:text-plum-400">គាំទ្រ ២៤/៧</span>
      </div>
    </div>
  </section>

  {{-- Trusted brands marquee --}}
  @php
    $trustedBrands = ['Gemini Pro', 'ChatGPT Plus', 'Netflix', 'Canva Pro', 'Envato'];
  @endphp
  <section class="mt-10 overflow-hidden border-y border-plum-100 bg-plum-50/60 py-4 dark:border-plum-800 dark:bg-plum-900/20">
    <div class="flex w-max animate-marquee gap-10">
      @foreach ([...$trustedBrands, ...$trustedBrands] as $brand)
        <span class="shrink-0 text-sm font-semibold uppercase tracking-wide text-plum-400 dark:text-plum-500">
          {{ $brand }}
        </span>
      @endforeach
    </div>
  </section>

  {{-- Featured services --}}
  @if ($featuredServices->isNotEmpty())
    <section class="border-b border-plum-100 bg-plum-50 py-14 dark:border-plum-800 dark:bg-plum-900/40">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div data-reveal class="mb-8 flex items-end justify-between">
          <div>
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gold-600 dark:text-gold-400">
              <x-app-icon name="sparkles" class="h-3.5 w-3.5" />
              ពេញនិយមបំផុត
            </span>
            <h2 class="mt-1 text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">សេវាកម្មពិសេស</h2>
          </div>
          <a href="/shop" wire:navigate class="group hidden items-center gap-1 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300 sm:flex">
            មើលទាំងអស់
            <x-app-icon name="chevron-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
          </a>
        </div>
        {{-- Bento grid: first featured product runs large (2x2), the rest fill in around it --}}
        <div class="grid grid-cols-2 gap-5 sm:auto-rows-[1fr] sm:grid-cols-3 lg:grid-cols-4">
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
      {{-- Staggered offsets (nth position, wraps every 3) on a wrapper div — the
           cards themselves carry data-tilt, whose JS sets an inline transform
           on pointer interaction that would otherwise stomp a class-based
           translate on the same element. --}}
      @foreach ($categories as $category)
        <div @class([
          'sm:-translate-y-3' => $loop->iteration % 3 === 2,
          'sm:translate-y-2' => $loop->iteration % 3 === 0,
        ])>
          <a href="/shop/{{ $category->slug }}" wire:navigate data-reveal data-tilt
            class="group flex flex-col items-center gap-3 rounded-lg border border-plum-200 bg-white p-6 text-center transition-colors duration-300 hover:border-brand-400 hover:shadow-lg hover:shadow-brand-500/10 dark:border-plum-800 dark:bg-plum-900">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-plum-100 text-brand-700 transition-all duration-300 group-hover:scale-110 group-hover:bg-brand-600 group-hover:text-white dark:bg-plum-800 dark:text-brand-300">
              <x-app-icon name="{{ $category->icon }}" class="h-6 w-6" />
            </span>
            <span class="text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $category->name_km }}</span>
          </a>
        </div>
      @endforeach
    </div>
  </section>

  {{-- Why choose us --}}
  <section class="relative overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-plum-950 bg-[length:200%_200%] py-16 animate-invite-gradient">
    <div class="pointer-events-none absolute -left-24 top-0 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl animate-invite-float" style="--drift-duration:10s"></div>
    <div class="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-gold-400/10 blur-3xl animate-invite-float" style="--drift-duration:13s; --drift-delay:2s"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div data-reveal class="mb-10 text-center">
        <h2 class="text-xl font-bold text-white sm:text-2xl">ហេតុអ្វីជ្រើសរើស ROUMDOUL?</h2>
      </div>
      {{-- Bento: the first reason runs as a wide banner, the rest sit three-up below it --}}
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
        @foreach ([
            ['icon' => 'bolt', 'title' => 'ដឹកជញ្ជូនភ្លាមៗ', 'desc' => 'ទទួលបានគណនី/លេខកូដភ្លាមៗក្រោយបញ្ជាទិញជោគជ័យ — មិនចាំបាច់រង់ចាំយូរឡើយ'],
            ['icon' => 'shield-check', 'title' => 'សុវត្ថិភាព ១០០%', 'desc' => 'គណនីស្របច្បាប់ ធានាដោយក្រុមការងាររបស់យើង'],
            ['icon' => 'chat', 'title' => 'គាំទ្រ ២៤/៧', 'desc' => 'ក្រុមការងារយើងខ្ញុំរួចរាល់ជួយឆ្លើយរាល់ពេលវេលា'],
            ['icon' => 'heart', 'title' => 'តម្លៃសមរម្យបំផុត', 'desc' => 'តម្លៃប្រកួតប្រជែង ជាមួយការធានាគុណភាព'],
        ] as $item)
          <div data-reveal @class([
            'group col-span-2 rounded-xl border border-white/10 p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-gold-400/30 hover:bg-white/5',
            'sm:col-span-6 sm:flex sm:items-center sm:gap-6 sm:py-8 sm:text-left' => $loop->first,
          ])>
            <span @class([
              'mx-auto flex shrink-0 items-center justify-center rounded-full bg-white/5 text-gold-400 transition-all duration-300 group-hover:scale-110 group-hover:bg-gold-500 group-hover:text-brand-950',
              'h-11 w-11' => ! $loop->first,
              'h-16 w-16' => $loop->first,
            ])>
              <x-app-icon name="{{ $item['icon'] }}" class="{{ $loop->first ? 'h-8 w-8' : 'h-5.5 w-5.5' }}" />
            </span>
            <div @class(['mt-4' => ! $loop->first])>
              <h3 @class(['font-bold text-white', 'text-sm' => ! $loop->first, 'text-lg sm:text-xl' => $loop->first])>{{ $item['title'] }}</h3>
              <p @class(['mt-1.5 text-sm text-plum-400', 'sm:max-w-md' => $loop->first])>{{ $item['desc'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Testimonials --}}
  <section class="py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div data-reveal class="mb-10 text-center">
        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gold-600 dark:text-gold-400">
          <x-app-icon name="sparkles" class="h-3.5 w-3.5" />
          ការវាយតម្លៃ
        </span>
        <h2 class="mt-1 text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">អតិថិជនរបស់យើងនិយាយអ្វី</h2>
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
            <div @class(['sm:-translate-y-3' => $loop->iteration === 2])>
              <div data-reveal class="h-full rounded-lg border border-plum-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-plum-900/5 dark:border-plum-800 dark:bg-plum-900">
                <div class="flex gap-0.5 text-gold-500">
                  @for ($i = 0; $i < 5; $i++)
                    <x-app-icon name="star" class="h-4 w-4 fill-current" />
                  @endfor
                </div>
                <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $t['text'] }}&rdquo;</p>
                <div class="mt-4 flex items-center gap-3">
                  <span class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 ring-2 ring-plum-100 dark:bg-plum-800 dark:text-brand-300 dark:ring-plum-800">{{ mb_substr($t['name'], 0, 1) }}</span>
                  <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $t['name'] }}</p>
                </div>
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
            <div wire:key="review-marquee-{{ $loop->index }}" class="w-80 shrink-0 rounded-lg border border-plum-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-plum-900/5 dark:border-plum-800 dark:bg-plum-900">
              <div class="flex gap-0.5 text-gold-500">
                @for ($i = 0; $i < 5; $i++)
                  <x-app-icon name="star" class="h-4 w-4 {{ $i < $review->rating ? 'fill-current' : 'text-plum-200 dark:text-plum-700' }}" />
                @endfor
              </div>
              <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $review->comment }}&rdquo;</p>
              <div class="mt-4 flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 ring-2 ring-plum-100 dark:bg-plum-800 dark:text-brand-300 dark:ring-plum-800">{{ mb_substr($review->customer_name, 0, 1) }}</span>
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
            <div wire:key="review-wrap-{{ $review->id }}" @class(['sm:-translate-y-3' => $loop->iteration === 2])>
              <div data-reveal class="h-full rounded-lg border border-plum-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-plum-900/5 dark:border-plum-800 dark:bg-plum-900">
                <div class="flex gap-0.5 text-gold-500">
                  @for ($i = 0; $i < 5; $i++)
                    <x-app-icon name="star" class="h-4 w-4 {{ $i < $review->rating ? 'fill-current' : 'text-plum-200 dark:text-plum-700' }}" />
                  @endfor
                </div>
                <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">&ldquo;{{ $review->comment }}&rdquo;</p>
                <div class="mt-4 flex items-center gap-3">
                  <span class="flex h-9 w-9 items-center justify-center rounded-full bg-plum-100 text-sm font-bold text-brand-700 ring-2 ring-plum-100 dark:bg-plum-800 dark:text-brand-300 dark:ring-plum-800">{{ mb_substr($review->customer_name, 0, 1) }}</span>
                  <p class="text-sm font-semibold text-plum-900 dark:text-white">{{ $review->customer_name }}</p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </section>

  {{-- CTA banner --}}
  <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div data-reveal class="relative flex flex-col items-center gap-5 overflow-hidden rounded-lg bg-gradient-to-r from-brand-700 via-brand-600 to-brand-700 bg-[length:200%_200%] px-6 py-12 text-center shadow-lg shadow-brand-700/20 animate-invite-gradient sm:flex-row sm:justify-between sm:text-left">
      <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gold-400/20 blur-3xl"></div>
      <div class="relative">
        <h2 class="text-xl font-bold text-white sm:text-2xl">ត្រៀមខ្លួនរួចរាល់ហើយឬនៅ?</h2>
        <p class="mt-1 text-sm text-brand-100">ចាប់ផ្តើមទិញសេវាកម្មឌីជីថលដែលអ្នកចង់បានឥឡូវនេះ</p>
      </div>
      <a href="/shop" wire:navigate
        class="relative shrink-0 rounded-md bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition-all hover:-translate-y-0.5 hover:bg-plum-100 hover:shadow-xl">
        ចាប់ផ្តើមទិញ
      </a>
    </div>
  </section>
</div>
