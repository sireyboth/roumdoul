<div class="about-experience relative bg-plum-50 text-plum-900 transition-colors duration-700 dark:bg-[#050308] dark:text-white">

  {{-- Fixed fullscreen Three.js particle field — behind everything, drifts and
       reacts subtly to cursor position. Runs for as long as this page is open;
       torn down on livewire:navigate (see script block) so it never keeps
       rendering in the background after leaving the page. Dimmed in light mode
       via CSS opacity only (colors stay brand pink/gold in both themes). --}}
  <canvas id="about-particles" class="pointer-events-none fixed inset-0 z-0 opacity-25 transition-opacity duration-700 dark:opacity-100" aria-hidden="true"></canvas>

  {{-- Ambient falling petals — cheap CSS-only decoration, negative animation-delay
       so the layer already looks "in progress" on first paint instead of every
       petal starting from the top at once. Disabled under reduced motion. --}}
  <div class="about-petals pointer-events-none fixed inset-0 z-[1] overflow-hidden" aria-hidden="true">
    @for ($p = 0; $p < 10; $p++)
      <span class="about-petal" style="--x:{{ rand(2, 96) }}%; --size:{{ rand(8, 16) }}px; --dur:{{ rand(16, 28) }}s; --delay:-{{ rand(0, 24) }}s;"></span>
    @endfor
  </div>

  <div class="relative z-10">

  {{-- ============ HERO — kinetic 3D wordmark ============ --}}
  <section class="about-hero relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 text-center">
    <div class="pointer-events-none absolute inset-0 about-mesh opacity-40 dark:opacity-100"></div>
    <div class="pointer-events-none absolute -left-24 top-1/4 h-96 w-96 rounded-full bg-brand-600/10 blur-[100px] dark:bg-brand-600/30"></div>
    <div class="pointer-events-none absolute -right-24 bottom-1/4 h-96 w-96 rounded-full bg-gold-500/10 blur-[100px] dark:bg-gold-500/20"></div>

    <span translate="no" class="about-eyebrow notranslate relative text-[11px] font-semibold uppercase tracking-[0.5em] text-gold-600 dark:text-gold-300">
      រំដួល
    </span>

    <h1 translate="no" class="about-title notranslate relative mt-6 flex select-none flex-wrap items-center justify-center text-[15vw] font-black leading-[0.85] tracking-tight sm:text-[11vw] lg:text-[9vw]">
      <span class="about-letter inline-block">R</span><span class="about-letter inline-block">O</span><span class="about-letter inline-block">U</span><span class="about-letter inline-block">M</span><span class="about-letter inline-block">D</span><span class="about-letter inline-block">O</span><span class="about-letter inline-block">U</span><span class="about-letter inline-block">L</span>
    </h1>

    <p class="about-tagline relative mt-8 max-w-xl text-sm leading-relaxed text-plum-600 dark:text-plum-300 sm:text-base">
      យើងមិនគ្រាន់តែជាហាងឌីជីថលទេ — យើងជាស្ពានតភ្ជាប់អ្នកទៅកាន់ពិភពឌីជីថលដ៏ធំទូលាយ
      <span class="block text-plum-500 dark:text-plum-400">We're not just a digital shop — we're the bridge to the wider digital world.</span>
    </p>

    <div class="about-scrollcue relative mt-16 flex flex-col items-center gap-2 text-plum-500 dark:text-plum-500">
      <span class="text-[10px] font-semibold uppercase tracking-[0.3em]">Scroll</span>
      <span class="h-10 w-px bg-gradient-to-b from-gold-400 to-transparent"></span>
    </div>
  </section>

  {{-- ============ THE NAME — meaning behind "Roumdoul" + 3D bloom ============ --}}
  <section class="about-name relative overflow-hidden px-4 py-28 sm:px-6 lg:px-8">
    <div class="pointer-events-none absolute inset-0 about-mesh opacity-30 dark:opacity-60"></div>

    <div class="relative mx-auto grid max-w-6xl items-center gap-16 lg:grid-cols-2">
      <div class="about-reveal order-2 lg:order-1">
        <img src="/images/Roumdoul_Logo.png" alt="" aria-hidden="true"
          class="pointer-events-none absolute -left-10 top-0 -z-10 w-64 opacity-[0.04] dark:opacity-[0.06] lg:w-80" />

        <span class="text-[11px] font-semibold uppercase tracking-[0.4em] text-gold-600 dark:text-gold-300">The story behind the name</span>
        <h2 class="mt-4 text-3xl font-extrabold sm:text-5xl">ហេតុអ្វីជា "រំដួល"?</h2>

        <p class="mt-6 max-w-lg text-sm leading-relaxed text-plum-600 dark:text-plum-300 sm:text-base">
          <span class="font-semibold text-plum-900 dark:text-white">រំដួល</span> គឺជាផ្កាជាតិរបស់កម្ពុជា — ផ្កាតូចមួយដែលរីកចេញយឺតៗ ស្ងាត់ស្ងៀម ក៏ប៉ុន្តែក្រអូបនិងមិនងាយភ្លេច។ យើងជ្រើសរើសឈ្មោះនេះ ព្រោះនោះជាវិធីដែលយើងចង់លូតលាស់៖ មិនប្រញាប់ មិនក្លែងបន្លំ ហើយចាក់ឬសនៅលើទំនុកចិត្ត។
          <span class="mt-4 block text-plum-500 dark:text-plum-400">Roumdoul is Cambodia's national flower — small, unhurried in bloom, quiet, yet impossible to forget. We chose the name because that is exactly how we want to grow: unrushed, genuine, and rooted in trust.</span>
        </p>

        <p class="mt-6 max-w-lg text-sm leading-relaxed text-plum-600 dark:text-plum-300 sm:text-base">
          សូម្បីតែនិមិត្តសញ្ញារបស់យើង ក៏ជាកាយវិការនៃដៃដែលកំពុងបើកចេញយឺតៗ ដូចផ្កាកំពុងចាប់ផ្ដើមរីក។
          <span class="mt-2 block text-plum-500 dark:text-plum-400">Even our mark is a hand caught mid-gesture — opening the way a flower does.</span>
        </p>
      </div>

      <div class="order-1 lg:order-2 lg:sticky lg:top-32 lg:self-start">
        <div class="about-bloom-stage relative mx-auto aspect-square w-full max-w-md">
          <div class="pointer-events-none absolute inset-[-15%] rounded-full blur-3xl"
            style="background: radial-gradient(circle, color-mix(in srgb, var(--color-brand-500, #cc3d78) 30%, transparent), transparent 70%);"></div>
          <canvas id="about-bloom" class="relative z-10 h-full w-full" aria-hidden="true"></canvas>
        </div>
        <p class="mt-4 text-center text-[11px] font-semibold uppercase tracking-[0.3em] text-plum-400 dark:text-plum-500">
          Scroll to watch it bloom
        </p>
      </div>
    </div>
  </section>

  {{-- ============ MANIFESTO — massive pinned line-by-line reveal ============ --}}
  <section class="about-manifesto-pin relative flex h-screen flex-col items-center justify-center gap-4 px-4 text-center sm:gap-6">
    <p class="about-manifesto-line text-[9vw] font-black uppercase leading-[1.05] tracking-tight sm:text-[6vw] lg:text-[5vw]">
      We don't just sell software.
    </p>
    <p class="about-manifesto-line about-glow text-[9vw] font-black uppercase leading-[1.05] tracking-tight text-transparent sm:text-[6vw] lg:text-[5vw]">
      We engineer trust.
    </p>
    <p class="about-manifesto-line text-[9vw] font-black uppercase leading-[1.05] tracking-tight sm:text-[6vw] lg:text-[5vw]">
      At the speed of one click.
    </p>
  </section>

  {{-- ============ STORY — pinned horizontal-scroll beats ============ --}}
  <section class="about-story-pin relative h-screen overflow-hidden">
    <div class="about-story-track flex h-full items-center gap-10 pl-[10vw] will-change-transform">
      @foreach ([
          ['no' => '01', 'kh' => 'ចាប់ផ្តើម', 'en' => 'The Beginning', 'desc' => 'ចាប់ផ្តើមពីគំនិតសាមញ្ញមួយ៖ នាំកម្មវិធីឌីជីថលកម្រិតពិភពលោក មកកាន់អ្នកប្រើប្រាស់កម្ពុជា ដោយមិនចាំបាច់ស្មុគស្មាញ។'],
          ['no' => '02', 'kh' => 'កំណើន', 'en' => 'Growth', 'desc' => 'ពីអតិថិជនដំបូងម្នាក់ រហូតដល់ពាន់នាក់ដែលទុកចិត្ត — ការលក់រាល់ដងគឺជាការសន្យាថ្មីមួយដែលយើងគោរព។'],
          ['no' => '03', 'kh' => 'ជំនឿទុកចិត្ត', 'en' => 'Trust', 'desc' => 'រាល់គណនីត្រូវបានផ្ទៀងផ្ទាត់។ រាល់ការទូទាត់ត្រូវបានការពារ។ រាល់ការបញ្ជូនគឺភ្លាមៗ — គ្មានករណីលើកលែង។'],
          ['no' => '04', 'kh' => 'អនាគត', 'en' => 'What\'s Next', 'desc' => 'យើងកំពុងពង្រីកជានិច្ច — នាំមកនូវឧបករណ៍ថ្មីៗ បទពិសោធន៍ថ្មីៗ ដើម្បីអោយអ្នកនៅមុខគេជានិច្ច។'],
          ['no' => '05', 'kh' => 'សហគមន៍', 'en' => 'Community', 'desc' => 'ថ្ងៃនេះ យើងលែងជាគ្រាន់តែហាងទៀតហើយ — យើងជាសហគមន៍អតិថិជនរាប់ពាន់នាក់ដែលរីកចម្រើនទៅជាមួយគ្នា។'],
      ] as $beat)
        <article class="about-beat flex h-[70vh] w-[85vw] shrink-0 flex-col justify-center rounded-3xl border border-plum-200 bg-white p-10 shadow-sm backdrop-blur-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none sm:w-[60vw] sm:p-16 lg:w-[38vw]">
          <span class="text-8xl font-black text-plum-900/5 dark:text-white/5 sm:text-9xl">{{ $beat['no'] }}</span>
          <h2 class="-mt-8 text-3xl font-extrabold text-plum-900 dark:text-white sm:text-4xl">{{ $beat['kh'] }}</h2>
          <p class="mt-1 text-xs font-semibold uppercase tracking-[0.3em] text-gold-600 dark:text-gold-400">{{ $beat['en'] }}</p>
          <p class="mt-5 max-w-md text-sm leading-relaxed text-plum-600 dark:text-plum-300 sm:text-base">{{ $beat['desc'] }}</p>
        </article>
      @endforeach
      <div class="w-[10vw] shrink-0"></div>
    </div>
  </section>

  {{-- ============ PROCESS — from click to delivery ============ --}}
  <section class="about-process relative px-4 py-28 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl text-center">
      <span class="about-reveal text-[11px] font-semibold uppercase tracking-[0.4em] text-gold-600 dark:text-gold-300">From click to delivery</span>
      <h2 class="about-reveal mt-4 text-3xl font-extrabold sm:text-5xl">របៀបដែលយើងធ្វើការ</h2>
      <p class="about-reveal mx-auto mt-3 max-w-md text-sm text-plum-500 dark:text-plum-400">How we work — four steps, zero friction.</p>
    </div>

    <div class="relative mx-auto mt-20 max-w-5xl">
      <div class="absolute left-[12.5%] right-[12.5%] top-7 hidden h-0.5 overflow-hidden rounded-full bg-plum-200 dark:bg-white/10 sm:block">
        <div class="process-fill h-full w-full origin-left scale-x-0 bg-gradient-to-r from-brand-500 via-gold-400 to-brand-500"></div>
      </div>

      <div class="grid grid-cols-1 gap-12 sm:grid-cols-4 sm:gap-6">
        @foreach ([
            ['no' => '01', 'kh' => 'ជ្រើសរើស', 'en' => 'Choose', 'desc' => 'ជ្រើសរើសសេវាកម្មដែលអ្នកត្រូវការ ក្នុងប៉ុន្មានវិនាទី។', 'desc_en' => 'Pick the service you need in seconds.'],
            ['no' => '02', 'kh' => 'ទូទាត់', 'en' => 'Pay', 'desc' => 'ទូទាត់ដោយសុវត្ថិភាព តាមវិធីដែលអ្នកទុកចិត្ត។', 'desc_en' => 'Pay securely, your way.'],
            ['no' => '03', 'kh' => 'ផ្ទៀងផ្ទាត់', 'en' => 'Verify', 'desc' => 'ក្រុមរបស់យើងផ្ទៀងផ្ទាត់ និងរៀបចំការបញ្ជូនភ្លាមៗ។', 'desc_en' => 'Our team verifies and prepares delivery.'],
            ['no' => '04', 'kh' => 'ទទួល', 'en' => 'Receive', 'desc' => 'ទទួលបានក្នុងប៉ុន្មាននាទី មិនមែនប៉ុន្មានថ្ងៃ។', 'desc_en' => 'Receive in minutes, not days.'],
        ] as $step)
          <div class="about-reveal relative flex flex-col items-center text-center">
            <span class="relative z-10 flex h-14 w-14 items-center justify-center rounded-full border border-plum-200 bg-plum-50 text-sm font-black text-brand-600 shadow-sm dark:border-white/10 dark:bg-[#0a0610] dark:text-brand-300">{{ $step['no'] }}</span>
            <h3 class="mt-5 text-lg font-bold">{{ $step['kh'] }}</h3>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-plum-500 dark:text-plum-500">{{ $step['en'] }}</p>
            <p class="mt-3 max-w-[24ch] text-sm leading-relaxed text-plum-600 dark:text-plum-300">
              {{ $step['desc'] }}
              <span class="mt-1 block text-xs text-plum-500 dark:text-plum-500">{{ $step['desc_en'] }}</span>
            </p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============ VALUES — 3D flip cards ============ --}}
  <section class="relative px-4 py-32 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl text-center">
      <span class="text-[11px] font-semibold uppercase tracking-[0.4em] text-gold-600 dark:text-gold-300">What we stand for</span>
      <h2 class="about-reveal mt-4 text-3xl font-extrabold sm:text-5xl">ហេតុអ្វីជាទីទុកចិត្ត</h2>
    </div>

    <div class="mx-auto mt-16 grid max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" style="perspective: 1200px;">
      @foreach ([
          ['icon' => 'bolt', 'title' => 'ល្បឿនលឿន', 'en' => 'Speed'],
          ['icon' => 'shield-check', 'title' => 'សុវត្ថិភាព', 'en' => 'Security'],
          ['icon' => 'chat', 'title' => 'គាំទ្រ ២៤/៧', 'en' => 'Support'],
          ['icon' => 'heart', 'title' => 'តម្លៃសមរម្យ', 'en' => 'Fair Pricing'],
          ['icon' => 'sparkles', 'title' => 'គុណភាព', 'en' => 'Quality'],
          ['icon' => 'lock-closed', 'title' => 'ឯកជនភាព', 'en' => 'Privacy'],
      ] as $value)
        <div class="about-flip-card group relative rounded-2xl border border-plum-200 bg-white p-8 text-center shadow-sm dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-gold-500 text-white">
            <x-app-icon name="{{ $value['icon'] }}" class="h-7 w-7" />
          </div>
          <h3 class="mt-5 text-lg font-bold">{{ $value['title'] }}</h3>
          <p class="mt-1 text-xs font-semibold uppercase tracking-[0.25em] text-plum-500 dark:text-plum-500">{{ $value['en'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ============ MARQUEE — decorative ghost-text ribbon ============ --}}
  <section class="about-marquee relative overflow-hidden border-y border-plum-200 bg-white py-8 dark:border-white/10 dark:bg-white/[0.02] sm:py-10" aria-hidden="true">
    <div class="-rotate-1 sm:-rotate-2">
      <div class="flex w-max animate-marquee gap-8 whitespace-nowrap">
        @for ($r = 0; $r < 2; $r++)
          <div class="flex items-center gap-8 pr-8">
            @foreach (['ទំនុកចិត្ត · TRUST', 'លឿន · SPEED', 'សុវត្ថិភាព · SECURITY', '២៤/៧ · SUPPORT', 'កម្ពុជា · CAMBODIA', 'រំដួល · ROUMDOUL'] as $word)
              <span class="flex items-center gap-8 text-2xl font-black uppercase tracking-tight text-plum-200 dark:text-white/10 sm:text-4xl">
                {{ $word }}
                <span class="h-2 w-2 shrink-0 rounded-full bg-gradient-to-br from-brand-500 to-gold-500"></span>
              </span>
            @endforeach
          </div>
        @endfor
      </div>
    </div>
  </section>

  {{-- ============ STATS — kinetic count-up ============ --}}
  <section class="relative overflow-hidden px-4 py-32 sm:px-6 lg:px-8">
    <div class="pointer-events-none absolute inset-0 about-mesh opacity-20 dark:opacity-40"></div>
    <div class="relative mx-auto grid max-w-5xl grid-cols-2 gap-10 text-center lg:grid-cols-4">
      @foreach ([
          ['target' => 5000, 'suffix' => '+', 'label' => 'អតិថិជនពេញចិត្ត'],
          ['target' => 20, 'suffix' => '+', 'label' => 'សេវាកម្មឌីជីថល'],
          ['target' => 24, 'suffix' => '/7', 'label' => 'គាំទ្រអតិថិជន'],
          ['target' => 100, 'suffix' => '%', 'label' => 'ដឹកជញ្ជូនភ្លាមៗ'],
      ] as $stat)
        <div>
          <p class="about-stat-number bg-gradient-to-br from-plum-900 to-brand-600 bg-clip-text text-4xl font-black text-transparent dark:from-white dark:to-gold-300 sm:text-6xl"
            data-target="{{ $stat['target'] }}" data-suffix="{{ $stat['suffix'] }}">0</p>
          <p class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-plum-500 dark:text-plum-400 sm:text-sm">{{ $stat['label'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ============ QUOTE — spotlight pull-quote ============ --}}
  <section class="about-reveal relative px-4 py-28 text-center sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
      <x-app-icon name="sparkles" class="mx-auto h-8 w-8 text-gold-600 dark:text-gold-400" />
      <blockquote class="mt-6 text-2xl font-light leading-snug text-plum-800 dark:text-white/90 sm:text-4xl">
        &ldquo;រាល់ការទិញមួយដង គឺជាការចាប់ផ្តើមនៃទំនុកចិត្តមួយថ្មី។&rdquo;
        <span class="mt-3 block text-lg text-plum-500 dark:text-plum-400 sm:text-xl">&ldquo;Every purchase is the start of a new trust.&rdquo;</span>
      </blockquote>
      <p class="mt-6 text-xs font-semibold uppercase tracking-[0.3em] text-plum-400 dark:text-plum-500">— ក្រុមរំដួល · The Roumdoul Team</p>
    </div>
  </section>

  {{-- ============ CLOSING — quiet, editorial, not a shouty ecommerce banner ============ --}}
  <section class="about-reveal relative flex min-h-[70vh] flex-col items-center justify-center px-4 text-center">
    <p class="max-w-2xl text-2xl font-light leading-relaxed text-plum-800 dark:text-white/90 sm:text-4xl">
      រាល់ចុចមួយដង គឺជាការសន្យាមួយ។
      <span class="mt-2 block text-plum-500 dark:text-plum-400">Every click is a promise we intend to keep.</span>
    </p>
    <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
      <a href="/shop" wire:navigate
        class="group inline-flex items-center gap-2 rounded-full bg-gradient-to-br from-brand-600 to-brand-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition-transform hover:scale-[1.03]">
        មើលសេវាកម្មរបស់យើង
        <x-app-icon name="chevron-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
      </a>
      <a href="/contact" wire:navigate
        class="group inline-flex items-center gap-2 border-b border-gold-500/40 pb-1 text-sm font-semibold uppercase tracking-[0.2em] text-gold-600 transition-colors hover:border-gold-600 hover:text-gold-700 dark:border-gold-400/40 dark:text-gold-300 dark:hover:border-gold-300 dark:hover:text-gold-200">
        ទាក់ទងមកយើង
        <x-app-icon name="chevron-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
      </a>
    </div>
  </section>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
  // Clear any ScrollTriggers left over from a previous visit to this page within
  // the same SPA session (wire:navigate swaps this markup back in fresh each
  // time, but GSAP's global registry persists across navigations). This must run
  // before ANY ScrollTrigger.create() below — including the bloom flower's —
  // otherwise a trigger created earlier in this same script gets wiped out by a
  // cleanup call meant for the *previous* page instance, and the flower never
  // receives another onUpdate after its very first (frozen-closed) one.
  if (window.ScrollTrigger) {
    ScrollTrigger.getAll().forEach((t) => t.kill());
  }

  // Fullscreen fixed 3D particle field — brand-colored points drifting slowly in
  // space, with a subtle camera shift toward the cursor. Independent of the
  // homepage hero's particle field (resources/js/modules/heroParticles.js,
  // which is npm-bundled) — this one is CDN-only and page-scoped on purpose,
  // per the "no npm install, CDN only, keep it light" brief for this page.
  (function () {
    if (typeof THREE === 'undefined') return;

    const canvas = document.getElementById('about-particles');
    if (!canvas) return;

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const PARTICLE_COUNT = 1400;
    const BOUND = 9;

    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 40);
    camera.position.z = 10;

    // Soft radial-gradient sprite (drawn once to a small canvas) instead of
    // Three.js's default hard-edged square points — this plus additive blending
    // is what makes the field read as glowing light rather than flat dots.
    function createGlowTexture() {
      const size = 64;
      const c = document.createElement('canvas');
      c.width = c.height = size;
      const ctx = c.getContext('2d');
      const grad = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
      grad.addColorStop(0, 'rgba(255,255,255,1)');
      grad.addColorStop(0.4, 'rgba(255,255,255,0.6)');
      grad.addColorStop(1, 'rgba(255,255,255,0)');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, size, size);
      return new THREE.CanvasTexture(c);
    }
    const glowTexture = createGlowTexture();

    // Two brand-colored point clouds (rosewood pink + champagne gold) — priority
    // stays on ROUMDOUL's own palette rather than generic neon cyan/white.
    function buildCloud(color, count, size) {
      const geometry = new THREE.BufferGeometry();
      const positions = new Float32Array(count * 3);
      for (let i = 0; i < count * 3; i++) positions[i] = (Math.random() - 0.5) * BOUND * 2;
      geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
      const material = new THREE.PointsMaterial({
        color,
        size,
        map: glowTexture,
        transparent: true,
        opacity: 0.9,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
      });
      return new THREE.Points(geometry, material);
    }

    const pink = buildCloud(0xe0709f, Math.round(PARTICLE_COUNT * 0.6), 0.09);
    const gold = buildCloud(0xd9b66a, Math.round(PARTICLE_COUNT * 0.4), 0.1);
    scene.add(pink, gold);

    const pointer = { x: 0, y: 0 };
    const pointerTarget = { x: 0, y: 0 };

    function onPointerMove(e) {
      pointerTarget.x = (e.clientX / window.innerWidth - 0.5) * 2;
      pointerTarget.y = -(e.clientY / window.innerHeight - 0.5) * 2;
    }
    window.addEventListener('pointermove', onPointerMove, { passive: true });

    function onResize() {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    }
    window.addEventListener('resize', onResize);

    let rafId = null;
    let lastTime = 0;

    function renderStaticFrame() {
      renderer.render(scene, camera);
    }

    function animate(now) {
      const dt = lastTime ? Math.min((now - lastTime) / 1000, 0.05) : 1 / 60;
      lastTime = now;

      pointer.x += (pointerTarget.x - pointer.x) * 0.03;
      pointer.y += (pointerTarget.y - pointer.y) * 0.03;
      // Deliberately small magnitudes — "reacts slightly", not a full parallax swing.
      camera.position.x = pointer.x * 0.6;
      camera.position.y = pointer.y * 0.4;
      camera.lookAt(0, 0, 0);

      pink.rotation.y += dt * 0.02;
      gold.rotation.y -= dt * 0.015;
      pink.rotation.x += dt * 0.005;

      renderer.render(scene, camera);
      rafId = requestAnimationFrame(animate);
    }

    if (motionQuery.matches) {
      renderStaticFrame();
    } else {
      rafId = requestAnimationFrame(animate);
    }

    function dispose() {
      if (rafId !== null) cancelAnimationFrame(rafId);
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('resize', onResize);
      [pink, gold].forEach((points) => { points.geometry.dispose(); points.material.dispose(); });
      glowTexture.dispose();
      renderer.dispose();
      document.removeEventListener('livewire:navigate', dispose);
    }

    // Livewire fires this right as SPA navigation away from this page begins —
    // the correct moment to tear down the render loop so it doesn't keep
    // running detached in the background after the user leaves /about.
    document.addEventListener('livewire:navigate', dispose, { once: true });
  })();

  // Procedural 3D bloom — a stylized flower built from elongated "petal" blobs,
  // not an image. Doubles as a nod to Roumdoul the flower and to the hand-gesture
  // logo (a hand opening the way a flower does). Petals start folded (a bud) and
  // open as the "The Name" section scrolls through view; idles with a slow spin
  // and a light pointer-tilt the rest of the time. Colors track the site's
  // light/dark theme via a MutationObserver on <html class="dark">, since the
  // Alpine theme store can flip mid-visit without a page reload.
  //
  // Each petal is a squashed sphere (never goes edge-on/invisible from any
  // viewing angle, unlike a flat extruded plane) hinged through TWO nested
  // groups: an outer "facing" group fixes its position around the flower, and
  // an inner "hinge" group — a child of facing, so its own rotation composes
  // on top of facing's — tilts it outward for bloom. Putting both rotations on
  // one object doesn't reliably tip each petal in its own radial direction;
  // nesting them does, unambiguously.
  (function () {
    if (typeof THREE === 'undefined') return;

    const canvas = document.getElementById('about-bloom');
    const stage = document.querySelector('.about-bloom-stage');
    if (!canvas || !stage) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function isDark() {
      return document.documentElement.classList.contains('dark');
    }

    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 20);
    camera.position.set(0, 0.95, 5.4);
    camera.lookAt(0, 0.6, 0);

    const ambient = new THREE.AmbientLight(0xffffff, 0.55);
    const key = new THREE.DirectionalLight(0xffffff, 0.75);
    key.position.set(2, 3, 4);
    const glowLight = new THREE.PointLight(0xe0709f, 1.4, 8);
    glowLight.position.set(0, 0.8, 1.6);
    scene.add(ambient, key, glowLight);

    const petalGeo = new THREE.SphereGeometry(1, 16, 12);
    petalGeo.scale(0.22, 0.9, 0.14);
    petalGeo.translate(0, 0.82, 0); // base sits at the hinge's origin, tip extends outward

    function petalColor() {
      return isDark() ? 0xe0709f : 0xcc3d78;
    }

    const petals = [];
    const flower = new THREE.Group();
    scene.add(flower);

    function buildRing(count, scale, radiusOffset, raised, openAngle, delayOffset) {
      for (let i = 0; i < count; i++) {
        const facing = new THREE.Group();
        facing.rotation.y = (i / count) * Math.PI * 2 + (raised ? Math.PI / count : 0);
        facing.position.y = raised ? 0.05 : 0;
        flower.add(facing);

        const hinge = new THREE.Group();
        hinge.position.set(0, 0, radiusOffset);
        facing.add(hinge);

        const mat = new THREE.MeshStandardMaterial({
          color: petalColor(),
          roughness: 0.42,
          metalness: 0.12,
          side: THREE.DoubleSide,
          emissive: 0x3a0f26,
          emissiveIntensity: 0.3,
        });
        const mesh = new THREE.Mesh(petalGeo, mat);
        mesh.scale.setScalar(scale);
        hinge.add(mesh);

        petals.push({ pivot: hinge, mat, openAngle, delay: (i / count) * 0.18 + delayOffset });
      }
    }

    buildRing(6, 1, 0.05, false, Math.PI * 0.44, 0);
    buildRing(5, 0.62, 0.03, true, Math.PI * 0.34, 0.12);

    const coreGeo = new THREE.SphereGeometry(0.2, 24, 24);
    const coreMat = new THREE.MeshStandardMaterial({
      color: 0xd9b66a,
      emissive: 0x8a5a1a,
      emissiveIntensity: 0.6,
      roughness: 0.3,
      metalness: 0.4,
    });
    const core = new THREE.Mesh(coreGeo, coreMat);
    flower.add(core);

    function resize() {
      const size = stage.clientWidth || 320;
      renderer.setSize(size, size, false);
      camera.aspect = 1;
      camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);

    function applyTheme() {
      const color = petalColor();
      petals.forEach(({ mat }) => mat.color.set(color));
      glowLight.color.set(color);
    }
    applyTheme();

    const themeObserver = new MutationObserver(applyTheme);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    const pointer = { x: 0, y: 0 };
    const pointerTarget = { x: 0, y: 0 };

    function onStageMove(e) {
      const rect = stage.getBoundingClientRect();
      pointerTarget.x = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
      pointerTarget.y = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
    }
    stage.addEventListener('pointermove', onStageMove);

    let bloomProgress = reduceMotion ? 1 : 0;
    let rafId = null;
    let lastTime = 0;

    function applyBloom() {
      petals.forEach(({ pivot, openAngle, delay }) => {
        const local = Math.max(0, Math.min(1, (bloomProgress - delay) / (1 - delay)));
        pivot.rotation.x = local * openAngle;
      });
      core.scale.setScalar(0.85 + bloomProgress * 0.3);
    }

    function render(now) {
      const dt = lastTime ? Math.min((now - lastTime) / 1000, 0.05) : 1 / 60;
      lastTime = now;

      pointer.x += (pointerTarget.x - pointer.x) * 0.04;
      pointer.y += (pointerTarget.y - pointer.y) * 0.04;

      flower.rotation.y += dt * 0.12 + pointer.x * dt * 0.15;
      flower.rotation.x = pointer.y * 0.15;

      applyBloom();
      renderer.render(scene, camera);
      rafId = requestAnimationFrame(render);
    }

    if (reduceMotion) {
      applyBloom();
      renderer.render(scene, camera);
    } else {
      rafId = requestAnimationFrame(render);
    }

    let bloomTrigger = null;
    if (!reduceMotion && typeof gsap !== 'undefined' && window.ScrollTrigger) {
      bloomTrigger = ScrollTrigger.create({
        // Viewport-relative, not tied to the section's full scroll distance —
        // the bloom finishes shortly after the section enters view (while its
        // top travels from 80% down the viewport to 25%) and then just stays
        // open for the rest of the (much longer, text-driven) scroll through
        // the section, instead of finishing right as you scroll past it.
        trigger: '.about-name',
        start: 'top 80%',
        end: 'top 25%',
        scrub: 0.6,
        onUpdate: (self) => { bloomProgress = self.progress; },
      });
    }

    function dispose() {
      if (rafId !== null) cancelAnimationFrame(rafId);
      window.removeEventListener('resize', resize);
      stage.removeEventListener('pointermove', onStageMove);
      themeObserver.disconnect();
      if (bloomTrigger) bloomTrigger.kill();
      petals.forEach(({ mat }) => mat.dispose());
      petalGeo.dispose();
      coreGeo.dispose();
      coreMat.dispose();
      renderer.dispose();
      document.removeEventListener('livewire:navigate', dispose);
    }

    document.addEventListener('livewire:navigate', dispose, { once: true });
  })();

  (function () {
    if (typeof gsap === 'undefined') return;

    // Leftover-trigger cleanup for SPA re-navigation now happens once at the very
    // top of this script block (see comment there) — before this IIFE, before the
    // particle field, and before the bloom flower's own ScrollTrigger.
    gsap.registerPlugin(ScrollTrigger);

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const letters = gsap.utils.toArray('.about-letter');
    const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });

    if (reduceMotion) {
      gsap.set(['.about-eyebrow', '.about-letter', '.about-tagline', '.about-scrollcue', '.about-manifesto-line'], { opacity: 1, clearProps: 'all' });
      gsap.set('.process-fill', { scaleX: 1 });
    } else {
      gsap.set(letters, { transformPerspective: 600 });
      tl.from('.about-eyebrow', { opacity: 0, y: -20, duration: 0.6 })
        .from(letters, {
          opacity: 0,
          rotateX: -100,
          y: 60,
          stagger: 0.05,
          duration: 0.9,
        }, '-=0.3')
        .from('.about-tagline', { opacity: 0, y: 20, duration: 0.8 }, '-=0.4')
        .from('.about-scrollcue', { opacity: 0, duration: 0.6 }, '-=0.3');

      gsap.to('.about-scrollcue', { y: 10, opacity: 0.4, duration: 1.2, repeat: -1, yoyo: true, ease: 'sine.inOut' });

      // Manifesto: pinned for the section's full height, each massive line dims in
      // and out as scroll progress passes it — the classic "one statement at a
      // time" agency-manifesto reveal, driven entirely by scrub (no autoplay).
      const manifestoLines = gsap.utils.toArray('.about-manifesto-line');
      if (manifestoLines.length) {
        gsap.set(manifestoLines, { opacity: 0.08 });
        const manifestoTl = gsap.timeline({
          scrollTrigger: {
            trigger: '.about-manifesto-pin',
            start: 'top top',
            end: '+=150%',
            scrub: 0.5,
            pin: true,
          },
        });
        manifestoLines.forEach((line, i) => {
          manifestoTl
            .to(line, { opacity: 1, scale: 1.04, duration: 0.5 })
            .to(line, { opacity: 0.08, scale: 1, duration: 0.5 }, i === manifestoLines.length - 1 ? '+=0.3' : '+=0.15');
        });
      }

      // Horizontal-scroll story: the pinned section's height IS the scroll runway —
      // vertical scroll progress drives horizontal translation of the track inside it.
      const track = document.querySelector('.about-story-track');
      if (track) {
        const distance = () => track.scrollWidth - window.innerWidth;
        const horizontalTween = gsap.to(track, {
          x: () => -distance(),
          ease: 'none',
          scrollTrigger: {
            trigger: '.about-story-pin',
            start: 'top top',
            end: () => '+=' + distance(),
            scrub: 0.6,
            pin: true,
            invalidateOnRefresh: true,
          },
        });

        gsap.utils.toArray('.about-beat').forEach((beat) => {
          gsap.from(beat, {
            opacity: 0.25,
            scale: 0.92,
            scrollTrigger: {
              trigger: beat,
              containerAnimation: horizontalTween,
              start: 'left 90%',
              toggleActions: 'play none none reverse',
            },
          });
        });
      }

      // Connecting line for the "how we work" process steps — fills left to right
      // as the section scrolls through, echoing the story track's scrub pattern
      // at a much smaller scale.
      const processFill = document.querySelector('.process-fill');
      if (processFill) {
        gsap.to(processFill, {
          scaleX: 1,
          ease: 'none',
          scrollTrigger: {
            trigger: '.about-process',
            start: 'top 70%',
            end: 'bottom 60%',
            scrub: 0.6,
          },
        });
      }

      // 3D flip-in cards.
      gsap.utils.toArray('.about-flip-card').forEach((card, i) => {
        gsap.from(card, {
          opacity: 0,
          rotateY: 90,
          duration: 0.8,
          delay: i * 0.08,
          ease: 'power3.out',
          scrollTrigger: { trigger: card, start: 'top 85%', toggleActions: 'play none none reverse' },
        });
      });

      // Generic fade/rise reveals.
      gsap.utils.toArray('.about-reveal').forEach((el) => {
        gsap.from(el, {
          opacity: 0,
          y: 40,
          duration: 0.9,
          scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none reverse' },
        });
      });

      // Kinetic count-up stats.
      gsap.utils.toArray('.about-stat-number').forEach((el) => {
        const target = parseFloat(el.dataset.target);
        const suffix = el.dataset.suffix || '';
        const counter = { val: 0 };
        ScrollTrigger.create({
          trigger: el,
          start: 'top 90%',
          once: true,
          onEnter: () => {
            gsap.to(counter, {
              val: target,
              duration: 1.6,
              ease: 'power2.out',
              onUpdate: () => { el.textContent = Math.round(counter.val).toLocaleString() + suffix; },
            });
          },
        });
      });
    }

    ScrollTrigger.refresh();
  })();
</script>

<style>
  .about-mesh {
    background:
      radial-gradient(ellipse 60% 50% at 20% 20%, color-mix(in srgb, var(--color-brand-600, #b02361) 35%, transparent), transparent 60%),
      radial-gradient(ellipse 50% 60% at 80% 70%, color-mix(in srgb, var(--color-gold-500, #c79a44) 25%, transparent), transparent 60%);
  }

  /* Brand-color neon glow — rosewood pink into champagne gold. Lighter/more
     saturated in light mode so it reads against a pale background; brighter and
     glowier once .dark is present, matching the rest of the page's dark palette. */
  .about-glow {
    background-image: linear-gradient(90deg, var(--color-brand-500, #cc3d78), var(--color-gold-500, #c79a44));
    -webkit-background-clip: text;
    background-clip: text;
    filter: drop-shadow(0 0 14px color-mix(in srgb, var(--color-brand-500, #cc3d78) 35%, transparent));
  }

  .dark .about-glow {
    background-image: linear-gradient(90deg, var(--color-brand-400, #e0709f), var(--color-gold-400, #d9b66a));
    filter: drop-shadow(0 0 25px color-mix(in srgb, var(--color-brand-500, #cc3d78) 55%, transparent))
      drop-shadow(0 0 45px color-mix(in srgb, var(--color-gold-500, #c79a44) 35%, transparent));
  }

  /* Ambient falling petals — small brand-gradient blobs drifting down slowly
     behind the content, in front of the particle canvas. Purely decorative. */
  .about-petal {
    position: absolute;
    top: -5%;
    left: var(--x, 50%);
    width: var(--size, 10px);
    height: var(--size, 10px);
    background: linear-gradient(135deg, var(--color-brand-400, #e0709f), var(--color-gold-400, #d9b66a));
    border-radius: 0% 70% 0% 70%;
    opacity: 0.28;
    animation: about-petal-fall var(--dur, 20s) linear var(--delay, 0s) infinite;
  }

  @keyframes about-petal-fall {
    0% { transform: translateY(-10vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.28; }
    90% { opacity: 0.28; }
    100% { transform: translateY(110vh) rotate(360deg); opacity: 0; }
  }

  @media (prefers-reduced-motion: reduce) {
    .about-petal {
      display: none;
    }
  }
</style>
