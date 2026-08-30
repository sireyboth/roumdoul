@php
    $accent = $fields['accent_color'] ?? '#e0709f';
    $senderName = $fields['sender_name'] ?? '';
    $headline = $fields['headline'] ?? 'Will you go out with me?';
    $message = $fields['message'] ?? '';
    $coverUrl = ! empty($fields['cover_image'])
        ? \Illuminate\Support\Facades\Storage::disk('s3')->url($fields['cover_image'])
        : null;

    $eventDate = ! empty($fields['event_date']) ? \Illuminate\Support\Carbon::parse($fields['event_date']) : null;
    $venueName = $fields['venue_name'] ?? null;
    $venueAddress = $fields['venue_address'] ?? null;
    $musicUrl = $fields['music_url'] ?? null;
    $ctaLabel = $fields['cta_label'] ?? null;
    $ctaUrl = $fields['cta_url'] ?? null;
    $wantsRsvpNote = ! empty($fields['rsvp_enabled']);
    $wantsCountdown = ! empty($fields['countdown_enabled']) && $eventDate;

    // Optional extras are gated by which plan the invitation was bought under — the same
    // catalog keys ('map', 'countdown', 'rsvp', 'music') set in Pricing plan > Unlocked features.
    // On the unpurchased demo page $invitation is null, so every extra previews unlocked.
    $showMap = $venueAddress && ($invitation ? $invitation->hasFeature('map') : true);
    $showCountdown = $wantsCountdown && ($invitation ? $invitation->hasFeature('countdown') : true);
    $showMusic = $musicUrl && ($invitation ? $invitation->hasFeature('music') : true);
    $trackRsvp = $invitation ? $invitation->hasFeature('rsvp') : true;

    $youtubeId = null;
    if ($musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $rsvpUrl = ($invitation && $recipient) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    $decorations = ['💕', '✨', '🌸', '🩷', '💌', '🥹', '🌷', '💫', '⭐', '🎀'];
    $floaters = collect(range(1, 20))->map(fn ($i) => [
        'emoji' => $decorations[$i % count($decorations)],
        'top' => rand(2, 92),
        'left' => rand(2, 94),
        'size' => rand(14, 32),
        'duration' => rand(4, 9),
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
    rsvpNote: '',
    countdown: { d: 0, h: 0, m: 0, s: 0 },
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
      const emojis = ['💖','💕','✨','🎉','🩷','💘','🌟','🎊'];
      for (let i = 0; i < 36; i++) {
        this.hearts.push({
          id: Date.now() + i,
          emoji: emojis[Math.floor(Math.random() * emojis.length)],
          left: Math.random() * 100,
          x: (Math.random() * 320 - 160) + 'px',
          y: -(Math.random() * 260 + 180) + 'px',
          rotate: (Math.random() * 260 - 130) + 'deg',
          delay: (Math.random() * 0.4) + 's',
        });
      }
      setTimeout(() => { this.hearts = []; }, 1600);
      this.sendRsvp('yes');
      this.playMusic();
    },
    sendRsvp(status) {
      @if ($trackRsvp && $rsvpUrl)
        fetch('{{ $rsvpUrl }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ status, note: this.rsvpNote || null }),
        }).catch(() => {});
      @endif
    },
    playMusic() {
      // Clicking Yes is a real user gesture, so this is the first moment the browser
      // will actually let audio play — the iframe itself has been sitting there muted
      // since page load just so it is already buffered and ready to go the instant this fires.
      const player = this.$refs.musicFrame?.contentWindow;
      if (!player) return;
      const post = (func) => player.postMessage(JSON.stringify({ event: 'command', func, args: [] }), '*');
      post('unMute');
      post('playVideo');
    },
    @if ($showCountdown)
    tickCountdown() {
      const target = new Date('{{ $eventDate->toIso8601String() }}').getTime();
      const diff = Math.max(0, target - Date.now());
      this.countdown = {
        d: Math.floor(diff / 86400000),
        h: Math.floor(diff / 3600000) % 24,
        m: Math.floor(diff / 60000) % 60,
        s: Math.floor(diff / 1000) % 60,
      };
    },
    @endif
  }"
  @if ($showCountdown) x-init="tickCountdown(); setInterval(() => tickCountdown(), 1000)" @endif
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

  {{-- Background music — silently primed on load, only actually starts once they click Yes
       (that click is the user gesture browsers require before audio is allowed to play) --}}
  @if ($showMusic)
    @if ($youtubeId)
      <div class="fixed h-0 w-0 overflow-hidden opacity-0">
        <iframe
          x-ref="musicFrame"
          src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
          allow="autoplay; encrypted-media" class="h-0 w-0"></iframe>
      </div>
    @else
      <a x-show="answered" x-cloak href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer"
        class="fixed bottom-5 right-5 z-20 whitespace-nowrap rounded-full bg-plum-900 px-4 py-2.5 text-xs font-semibold text-white shadow-lg animate-invite-wiggle">
        🎵 Tap for our song ↗
      </a>
    @endif
  @endif

  <div class="relative z-10 w-full max-w-md animate-invite-pop-in animate-invite-breathe rounded-3xl border border-white/60 bg-white/80 p-8 text-center shadow-2xl shadow-pink-900/10 backdrop-blur-md animate-invite-glow dark:border-plum-800 dark:bg-plum-900/80">
    <span class="inline-flex items-center gap-1.5 rounded-full bg-[color:var(--accent)]/10 px-3.5 py-1 text-[11px] font-bold uppercase tracking-widest text-[color:var(--accent)]">
      💌 A special question for {{ $recipientName }}
    </span>

    @if ($coverUrl)
      <div class="relative mx-auto mt-6 w-48">
        <div class="absolute inset-[-10px] rounded-lg animate-invite-spin-slow" style="background: conic-gradient(from 0deg, transparent, {{ $accent }}, transparent 30%);"></div>
        <div class="relative rotate-[-4deg] rounded-lg border-8 border-white bg-white p-1 shadow-xl transition-transform duration-300 hover:rotate-0 dark:border-plum-100">
          <img src="{{ $coverUrl }}" alt="" class="aspect-square w-full rounded object-cover" />
        </div>
      </div>
    @else
      <div class="mx-auto mt-6 text-6xl">🥹💗</div>
    @endif

    <h1 class="mt-6 text-2xl font-extrabold leading-snug text-plum-900 animate-invite-text-glow dark:text-white sm:text-3xl">
      {{ $headline }}
    </h1>

    @if ($message)
      <p class="mt-3 text-sm leading-relaxed text-plum-600 dark:text-plum-300">{{ $message }}</p>
    @endif

    @if ($senderName)
      <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-plum-400 dark:text-plum-500">&mdash; {{ $senderName }}</p>
    @endif

    @if ($eventDate)
      <div class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-plum-100 px-3 py-1 text-xs font-semibold text-plum-600 dark:bg-plum-800 dark:text-plum-300">
        📅 {{ $eventDate->format('l, d M Y \a\t g:i A') }}
      </div>
    @endif

    @if ($showCountdown)
      <div class="mt-4 grid grid-cols-4 gap-2">
        <template x-for="unit in [['countdown.d','D'],['countdown.h','H'],['countdown.m','M'],['countdown.s','S']]" :key="unit[0]">
          <div class="rounded-xl bg-[color:var(--accent)]/10 py-2">
            <p class="text-lg font-extrabold text-[color:var(--accent)]" x-text="(unit[0] === 'countdown.d' ? countdown.d : unit[0] === 'countdown.h' ? countdown.h : unit[0] === 'countdown.m' ? countdown.m : countdown.s).toString().padStart(2,'0')"></p>
            <p class="text-[10px] font-bold uppercase text-plum-400" x-text="unit[1]"></p>
          </div>
        </template>
      </div>
    @endif

    @if ($venueName || $showMap)
      <div class="mt-4 rounded-xl border border-plum-200 p-3 text-left dark:border-plum-700">
        <p class="text-xs font-bold uppercase tracking-wide text-plum-400">📍 Where</p>
        @if ($venueName)
          <p class="mt-0.5 text-sm font-semibold text-plum-800 dark:text-plum-100">{{ $venueName }}</p>
        @endif
        @if ($showMap)
          <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($venueAddress) }}" target="_blank" rel="noopener noreferrer"
            class="mt-1 inline-block text-xs font-bold text-[color:var(--accent)] hover:underline">
            Get directions ↗
          </a>
        @endif
      </div>
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

    @if ($wantsRsvpNote)
      <div x-show="!answered" x-cloak class="mt-3">
        <input type="text" x-model="rsvpNote" placeholder="Leave a little note (optional)"
          class="w-full rounded-full border border-plum-200 bg-transparent px-4 py-2 text-center text-xs focus:border-[color:var(--accent)] focus:outline-none dark:border-plum-700" />
      </div>
    @endif

    <div x-show="answered" x-cloak x-transition.scale class="mt-8">
      <p class="text-xl font-extrabold text-[color:var(--accent)]">YESSS! 🎉💘</p>
      <p class="mt-1.5 text-sm text-plum-500 dark:text-plum-400">
        @if ($senderName) {{ $senderName }} will @else We'll @endif be in touch to pick the perfect time~
      </p>
    </div>

    @if ($ctaLabel && $ctaUrl)
      <a href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer"
        class="mt-4 inline-flex items-center gap-1.5 rounded-full border-2 border-[color:var(--accent)] px-5 py-2 text-xs font-bold text-[color:var(--accent)] transition-colors hover:bg-[color:var(--accent)] hover:text-white">
        {{ $ctaLabel }} ↗
      </a>
    @endif
  </div>
</div>
