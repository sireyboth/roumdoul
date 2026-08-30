@php
    $accent = $fields['accent_color'] ?? '#e0709f';
    $senderName = $fields['sender_name'] ?? '';
    $headline = $fields['headline'] ?? 'Will you go out with me?';
    $message = $fields['message'] ?? '';
    $coverUrl = ! empty($fields['cover_image'])
        ? \Illuminate\Support\Facades\Storage::disk('s3')->url($fields['cover_image'])
        : null;

    $decorations = ['💕', '✨', '🌸', '🩷', '💌', '🥹', '🌷', '💫'];
    $floaters = collect(range(1, 14))->map(fn ($i) => [
        'emoji' => $decorations[$i % count($decorations)],
        'top' => rand(2, 92),
        'left' => rand(2, 94),
        'size' => rand(14, 30),
        'duration' => rand(5, 9),
        'delay' => rand(0, 4) + (rand(0, 9) / 10),
        'rotate' => rand(-15, 15),
    ]);
@endphp

<div
  x-data="{
    noAttempts: 0,
    noX: 0,
    noY: 0,
    answered: false,
    hearts: [],
    dodge() {
      if (this.noAttempts >= 6) return;
      this.noAttempts++;
      // Only ever drifts right/down, away from the Yes button on its left —
      // never overlaps it, however many times it's dodged.
      this.noX = 30 + Math.random() * 55;
      this.noY = Math.random() * 30;
    },
    sayYes() {
      this.answered = true;
      const emojis = ['💖','💕','✨','🎉','🩷','💘'];
      for (let i = 0; i < 24; i++) {
        this.hearts.push({
          id: Date.now() + i,
          emoji: emojis[Math.floor(Math.random() * emojis.length)],
          left: Math.random() * 100,
          x: (Math.random() * 240 - 120) + 'px',
          y: -(Math.random() * 200 + 160) + 'px',
          rotate: (Math.random() * 180 - 90) + 'deg',
          delay: (Math.random() * 0.3) + 's',
        });
      }
      setTimeout(() => { this.hearts = []; }, 1400);
    },
  }"
  style="--accent: {{ $accent }}"
  class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-pink-100 via-rose-50 to-purple-100 px-4 py-16 animate-invite-gradient dark:from-plum-950 dark:via-plum-900 dark:to-plum-950"
>
  {{-- Floating background decorations --}}
  @foreach ($floaters as $f)
    <span
      class="pointer-events-none absolute select-none animate-invite-float"
      style="top: {{ $f['top'] }}%; left: {{ $f['left'] }}%; font-size: {{ $f['size'] }}px; --drift-duration: {{ $f['duration'] }}s; --drift-delay: {{ $f['delay'] }}s; --drift-rotate: {{ $f['rotate'] }}deg; opacity: 0.55;"
    >{{ $f['emoji'] }}</span>
  @endforeach

  {{-- Confetti hearts burst, spawned on "Yes" --}}
  <template x-for="heart in hearts" :key="heart.id">
    <span
      class="pointer-events-none absolute bottom-1/3 select-none text-3xl animate-invite-burst"
      :style="`left: ${heart.left}%; --burst-x: ${heart.x}; --burst-y: ${heart.y}; --burst-rotate: ${heart.rotate}; animation-delay: ${heart.delay};`"
      x-text="heart.emoji"
    ></span>
  </template>

  <div class="relative z-10 w-full max-w-md animate-invite-pop-in rounded-3xl border border-white/60 bg-white/80 p-8 text-center shadow-2xl shadow-pink-900/10 backdrop-blur-md dark:border-plum-800 dark:bg-plum-900/80">
    <span class="inline-flex items-center gap-1.5 rounded-full bg-[color:var(--accent)]/10 px-3.5 py-1 text-[11px] font-bold uppercase tracking-widest text-[color:var(--accent)]">
      💌 A special question for {{ $recipientName }}
    </span>

    @if ($coverUrl)
      <div class="mx-auto mt-6 w-48 rotate-[-4deg] rounded-lg border-8 border-white bg-white p-1 shadow-xl transition-transform duration-300 hover:rotate-0 dark:border-plum-100">
        <img src="{{ $coverUrl }}" alt="" class="aspect-square w-full rounded object-cover" />
      </div>
    @else
      <div class="mx-auto mt-6 text-6xl">🥹💗</div>
    @endif

    <h1 class="mt-6 text-2xl font-extrabold leading-snug text-plum-900 dark:text-white sm:text-3xl">
      {{ $headline }}
    </h1>

    @if ($message)
      <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">{{ $message }}</p>
    @endif

    @if ($senderName)
      <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-plum-400 dark:text-plum-500">&mdash; {{ $senderName }}</p>
    @endif

    <div x-show="!answered" class="relative mt-8 flex h-20 items-center justify-center gap-4">
      <button
        type="button"
        @click="sayYes()"
        :style="`transform: scale(${1 + Math.min(noAttempts, 6) * 0.08})`"
        class="z-10 rounded-full bg-[color:var(--accent)] px-7 py-3 text-sm font-extrabold text-white shadow-lg transition-transform duration-300 hover:brightness-110 active:scale-95"
      >
        Yes! 😍
      </button>

      <button
        type="button"
        @mouseenter="dodge()"
        @touchstart.prevent="dodge()"
        @click="if (noAttempts >= 6) sayYes()"
        :style="`transform: translate(${noX}px, ${noY}px)`"
        class="rounded-full border-2 border-plum-300 px-6 py-3 text-sm font-bold text-plum-500 transition-transform duration-300 ease-out dark:border-plum-700 dark:text-plum-400"
      >
        No 😭
      </button>
    </div>

    <div x-show="answered" x-cloak x-transition.scale class="mt-8">
      <p class="text-xl font-extrabold text-[color:var(--accent)]">YESSS! 🎉💘</p>
      <p class="mt-1.5 text-sm text-plum-500 dark:text-plum-400">
        @if ($senderName) {{ $senderName }} will @else We'll @endif be in touch to pick the perfect time~
      </p>
    </div>
  </div>
</div>
