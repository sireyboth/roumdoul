@php
    $accent = $fields['accent_color'] ?? '#c9a34e';
    $senderName = $fields['sender_name'] ?? '';
    $headline = $fields['headline'] ?? "I have a surprise for you, but first, let's play a game.";
    $message = $fields['message'] ?? '';

    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : \Illuminate\Support\Facades\Storage::disk('s3')->url($path);

    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : null;

    $galleryUnlocked = $invitation ? $invitation->fieldUnlocked('photo_gallery') : true;
    $galleryUrls = collect($galleryUnlocked ? ($fields['photo_gallery'] ?? []) : [])
        ->map($resolveUrl)
        ->take(12)
        ->values();
    if ($galleryUrls->isEmpty() && $coverUrl) {
        $galleryUrls = collect([$coverUrl]);
    }

    // Deterministic per-image styling derived purely from index, so re-renders never jitter —
    // each of the 8 drift keyframe variants gives a distinct flight path (arc, sweep, wobble...).
    $galleryItems = $galleryUrls->values()->map(function ($url, $i) {
        return [
            'url' => $url,
            'drift' => $i % 8,
            'depth' => 8 + ($i % 5) * 6,
            'soft' => $i % 3 === 1,
            'size' => 140 + (($i * 47) % 110),
            'top' => ($i * 53 + 7) % 78,
            'left' => ($i * 71 + 11) % 76,
            'delay' => ($i % 6) * 1.4,
            'duration' => 20 + ($i % 5) * 3,
            'rotate' => (($i * 29) % 16) - 8,
        ];
    });

    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if ($musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $ctaLabel = $fields['cta_label'] ?? null;
    $ctaUrl = $fields['cta_url'] ?? null;
    $showCta = $ctaLabel && $ctaUrl && ($invitation ? $invitation->fieldUnlocked('cta_label') : true);

    $stars = [
        ['I', 18, 15],
        ['II', 10, 70],
        ['III', 42, 42],
        ['IV', 58, 80],
        ['V', 70, 14],
    ];
@endphp

<div
  x-data="{
    step: 0,
    total: 5,
    solved: false,
    revealed: false,
    wrongPulse: false,
    px: 0,
    py: 0,
    hit(i) {
      if (this.solved) return;
      if (i === this.step) {
        this.step++;
        if (this.step >= this.total) {
          this.solved = true;
          this.playMusic();
          setTimeout(() => { this.revealed = true; }, 1100);
        }
      } else {
        this.wrongPulse = true;
        this.step = 0;
        setTimeout(() => { this.wrongPulse = false; }, 450);
      }
    },
    parallax(e) {
      const point = e.touches ? e.touches[0] : e;
      this.px = (point.clientX / window.innerWidth - 0.5) * 2;
      this.py = (point.clientY / window.innerHeight - 0.5) * 2;
    },
    playMusic() {
      const player = this.$refs.musicFrame?.contentWindow;
      if (!player) return;
      const post = (func) => player.postMessage(JSON.stringify({ event: 'command', func, args: [] }), '*');
      post('unMute');
      post('playVideo');
    },
  }"
  @mousemove.window="parallax($event)"
  @touchmove.window="parallax($event)"
  style="--accent: {{ $accent }}"
  :style="`--px: ${px}; --py: ${py}`"
  class="admire relative min-h-screen overflow-hidden bg-[#0b0710] text-white"
>
  {{-- Stage 1: sleek minimalist puzzle gate --}}
  <section
    x-show="!revealed"
    x-transition:leave="transition ease-in duration-700"
    x-transition:leave-end="opacity-0"
    class="relative z-20 flex min-h-screen flex-col items-center justify-center px-6 text-center"
  >
    <div class="pointer-events-none absolute inset-0" style="background: radial-gradient(ellipse at center, color-mix(in srgb, var(--accent) 14%, transparent), transparent 60%);"></div>

    <p class="relative text-[11px] font-semibold uppercase tracking-[0.35em] text-[color:var(--accent)]">
      A little something for {{ $recipientName }}
    </p>

    <h1 class="relative mt-5 max-w-sm font-serif text-2xl font-light leading-relaxed text-white/90 sm:text-3xl">
      {{ $headline }}
    </h1>

    <p class="relative mt-4 text-xs tracking-widest text-white/40" x-show="!solved">Tap the stars in order</p>
    <p class="relative mt-4 text-xs tracking-widest text-[color:var(--accent)]" x-show="solved" x-cloak>&#10022; found you &#10022;</p>

    {{-- constellation puzzle --}}
    <div class="relative mt-10 h-72 w-full max-w-sm">
      @foreach ($stars as $i => [$label, $top, $left])
        <button
          type="button"
          @click="hit({{ $i }})"
          class="absolute flex h-12 w-12 -translate-x-1/2 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border-0 bg-transparent p-0 transition-transform duration-500"
          :class="step > {{ $i }} ? 'scale-90 opacity-40' : (wrongPulse && step === 0 ? 'animate-invite-wiggle' : '')"
          style="top: {{ $top }}%; left: {{ $left }}%;"
        >
          <span
            class="absolute inset-0 rounded-full blur-md"
            style="background: color-mix(in srgb, var(--accent) 25%, transparent);"
            :class="step === {{ $i }} ? 'animate-invite-ring-pulse' : ''"
          ></span>
          <span class="relative font-serif text-[10px] tracking-widest text-[color:var(--accent)]" x-text="step > {{ $i }} ? '&#10022;' : '{{ $label }}'"></span>
        </button>
      @endforeach
    </div>

    <div class="relative mt-8 flex items-center gap-2">
      <template x-for="i in total" :key="i">
        <span class="h-1.5 w-1.5 rounded-full transition-colors duration-300" :class="i <= step ? 'bg-[color:var(--accent)]' : 'bg-white/20'"></span>
      </template>
    </div>
  </section>

  {{-- Stage 2: cinematic Admire gallery --}}
  <section
    x-show="revealed"
    x-cloak
    x-transition:enter="transition ease-out duration-1000"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="relative z-10 min-h-screen"
  >
    {{-- ambient light leaks --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
      <span class="admire-leak absolute h-72 w-72 rounded-full" style="top:8%; left:-6%; background: radial-gradient(circle, color-mix(in srgb, var(--accent) 55%, transparent), transparent 70%); animation-duration: 26s;"></span>
      <span class="admire-leak absolute h-96 w-96 rounded-full" style="bottom:4%; right:-10%; background: radial-gradient(circle, color-mix(in srgb, var(--accent) 40%, transparent), transparent 70%); animation-duration: 32s; animation-delay: -6s;"></span>
      <span class="admire-leak absolute h-60 w-60 rounded-full" style="top:45%; left:60%; background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 70%); animation-duration: 22s; animation-delay: -12s;"></span>
    </div>

    {{-- floating, drifting photos with depth-based parallax --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
      @foreach ($galleryItems as $item)
        <div class="admire-depth absolute" style="top: {{ $item['top'] }}%; left: {{ $item['left'] }}%; --depth: {{ $item['depth'] }};">
          <img
            src="{{ $item['url'] }}"
            alt=""
            class="admire-drift admire-drift-{{ $item['drift'] }} rounded-2xl object-cover"
            style="width: {{ $item['size'] }}px; aspect-ratio: 3/4; filter: blur({{ $item['soft'] ? 3 : 0 }}px); animation-delay: {{ $item['delay'] }}s; animation-duration: {{ $item['duration'] }}s; --rot: {{ $item['rotate'] }}deg; box-shadow: 0 20px 60px rgba(0,0,0,0.55);"
          />
        </div>
      @endforeach
    </div>

    {{-- emotional caption --}}
    <div class="relative z-20 flex min-h-screen flex-col items-center justify-end gap-4 px-6 pb-16 pt-24 text-center">
      <div class="admire-caption max-w-sm rounded-2xl bg-black/30 px-6 py-5 backdrop-blur-sm">
        @if ($message)
          <p class="font-serif text-base italic leading-relaxed text-white/90">&ldquo;{{ $message }}&rdquo;</p>
        @endif
        @if ($senderName)
          <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-[color:var(--accent)]">&mdash; {{ $senderName }}</p>
        @endif
        @if ($showCta)
          <a href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer"
            class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-[color:var(--accent)] px-5 py-2 text-xs font-bold text-[color:var(--accent)] transition-colors hover:bg-[color:var(--accent)] hover:text-[#0b0710]"
          >
            {{ $ctaLabel }} &#8599;
          </a>
        @endif
      </div>
    </div>
  </section>

  {{-- ambient music, primed muted, unlocked the instant the puzzle is solved --}}
  @if ($youtubeId)
    <div class="fixed h-0 w-0 overflow-hidden opacity-0">
      <iframe
        x-ref="musicFrame"
        src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
        allow="autoplay; encrypted-media" class="h-0 w-0"></iframe>
    </div>
  @elseif ($musicUrl)
    <a x-show="revealed" x-cloak href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer"
      class="fixed bottom-5 right-5 z-30 whitespace-nowrap rounded-full bg-black/60 px-4 py-2.5 text-xs font-semibold text-white shadow-lg backdrop-blur">
      &#127925; Our song &#8599;
    </a>
  @endif
</div>

<style>
  .admire .font-serif { font-family: 'Playfair Display', ui-serif, Georgia, serif; }

  .admire-depth {
    transform: translate(calc(var(--px, 0) * var(--depth, 10) * 1px), calc(var(--py, 0) * var(--depth, 10) * 1px));
    transition: transform 0.25s ease-out;
  }

  .admire-drift {
    animation-timing-function: cubic-bezier(0.45, 0, 0.15, 1);
    animation-iteration-count: infinite;
    animation-direction: alternate;
    will-change: transform;
  }

  @keyframes admire-drift-0 { 0% { transform: translate(-2vw, 2vh) rotate(calc(var(--rot, 0deg) * -0.4)) scale(0.97); } 100% { transform: translate(2vw, -3vh) rotate(var(--rot, 0deg)) scale(1.02); } }
  @keyframes admire-drift-1 { 0% { transform: translate(-6vw, 4vh) rotate(calc(var(--rot, 0deg) * -1)) scale(0.92); } 50% { transform: translate(2vw, -3vh) rotate(var(--rot, 0deg)) scale(1.03); } 100% { transform: translate(6vw, -6vh) rotate(calc(var(--rot, 0deg) * 1.4)) scale(0.98); } }
  @keyframes admire-drift-2 { 0% { transform: translate(6vw, -4vh) rotate(var(--rot, 0deg)) scale(0.94); } 50% { transform: translate(-2vw, 3vh) rotate(calc(var(--rot, 0deg) * -0.6)) scale(1.02); } 100% { transform: translate(-6vw, 6vh) rotate(calc(var(--rot, 0deg) * -1.3)) scale(0.96); } }
  @keyframes admire-drift-3 { 0% { transform: translate(-3vw, 5vh) scale(0.95); } 40% { transform: translate(1vw, -5vh) rotate(var(--rot, 0deg)) scale(1.05); } 100% { transform: translate(4vw, 1vh) rotate(calc(var(--rot, 0deg) * 0.5)) scale(0.99); } }
  @keyframes admire-drift-4 { 0% { transform: rotate(calc(var(--rot, 0deg) * -1)) translate(-1vw, -1vh) scale(0.96); } 50% { transform: rotate(var(--rot, 0deg)) translate(1vw, 1vh) scale(1.01); } 100% { transform: rotate(calc(var(--rot, 0deg) * -0.6)) translate(-2vw, 2vh) scale(0.97); } }
  @keyframes admire-drift-5 { 0% { transform: translate(-5vw, 0) scale(0.96); } 50% { transform: translate(0, -2vh) scale(1.06); } 100% { transform: translate(5vw, 0) scale(0.98); } }
  @keyframes admire-drift-6 { 0% { transform: translate(-3vw, -5vh) scale(0.9); } 100% { transform: translate(4vw, 5vh) rotate(var(--rot, 0deg)) scale(1.08); } }
  @keyframes admire-drift-7 { 0% { transform: translate(3vw, 5vh) scale(1.06); } 100% { transform: translate(-4vw, -5vh) rotate(calc(var(--rot, 0deg) * -1)) scale(0.92); } }

  .admire-drift-0 { animation-name: admire-drift-0; }
  .admire-drift-1 { animation-name: admire-drift-1; }
  .admire-drift-2 { animation-name: admire-drift-2; }
  .admire-drift-3 { animation-name: admire-drift-3; }
  .admire-drift-4 { animation-name: admire-drift-4; }
  .admire-drift-5 { animation-name: admire-drift-5; }
  .admire-drift-6 { animation-name: admire-drift-6; }
  .admire-drift-7 { animation-name: admire-drift-7; }

  @keyframes admire-leak { 0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; } 50% { transform: translate(4vw, -3vh) scale(1.15); opacity: 0.8; } }
  .admire-leak { animation-name: admire-leak; animation-timing-function: ease-in-out; animation-iteration-count: infinite; filter: blur(50px); }

  @keyframes admire-caption-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .admire-caption { animation: admire-caption-in 1.2s cubic-bezier(0.22, 1, 0.36, 1) both; animation-delay: 1.6s; }

  @media (prefers-reduced-motion: reduce) {
    .admire-drift, .admire-leak, .admire-caption, .admire-depth {
      animation: none !important;
      transition: none !important;
      transform: none !important;
    }
  }
</style>
