@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Rose Bloom — anniversary / love-letter gift page, collage look.
    // 3D roses bloom, burst into petals that form a beating heart, then an envelope opens
    // into a scrolling collage: editorial poster → handwritten letter with a ribboned polaroid →
    // "now playing" music card → scratch-off stamps → polaroid strip → reasons deck → memory map
    // → «keep this forever?» finale.
    //
    // Everything personal comes from $fields (catalog keys, docs/invitation-templates.md):
    //   recipient name  → the big script name (poster + letter) and the «To» lines (the guest link's name)
    //   sender_name     → signature            headline      → text inside the petal heart
    //   message         → the letter (blank line = new paragraph)
    //   together_since  → «together since» date on the poster + days-together count (optional; an older
    //                     invitation's event_date stands in when it's empty)
    //   venue_name      → «where it began» line on the poster (optional)
    //   cover_image     → poster photo (default: photo 1 of the gallery)
    //   photo_gallery   → poster (1), polaroid (2), music card (3–4), stamps (1–4), strip (5–9), map (after that)
    //   photo_captions  → one line per photo in the same order; strip lines «Title | small line»
    //   story_chapters  → memory map pins (title «Place | when», text = the memory); no chapters = no map
    //   love_reasons    → the reasons deck, one per line
    //   song_title + music_file / music_url → the music card (no music = the background piano)
    //   closing_line + thanks_message → the finale     accent_color → flowers/hearts/pins/play button
    //   background_image → full-screen background (default: a soft pink gradient that flows in an endless loop)
    // Hidden hearts hunt: 5 .hh buttons (poster, letter, music card, strip polaroid 3, reasons deck) + a
    // counter; finding all opens #secret (template wording, no field).
    // Empty fields fall back to gentle generic wording; missing photos become generated
    // film-style placeholders. On demo pages $invitation is null → every premium field unlocked.
    // Behaviour: public/js/invitations/rose-bloom.js, styles: public/css/invitations/rose-bloom.css
    // ------------------------------------------------------------------------------------
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $resolveUrl = fn (string $path) => preg_match('#^(https?:)?//#i', $path) ? $path : Storage::disk('s3')->url($path);
    $text = fn (string $key) => trim((string) ($fields[$key] ?? ''));
    $lines = fn (string $key) => collect(preg_split('/\R/', $text($key)))->map(fn ($l) => trim($l))->filter()->values()->all();
    $rbAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));

    $dir = 'images/invitations/anniversary-category/rose-bloom/';

    $guest = trim((string) ($recipientName ?? ''));
    $to = $guest !== '' ? rtrim($guest, ',').',' : 'My love,';
    $name = rtrim($guest !== '' ? $guest : 'My love', ', ');
    $from = $text('sender_name') ?: 'Me';

    $paragraphs = collect(preg_split('/\R\s*\R/', $text('message')))->map(fn ($p) => trim(preg_replace('/\s+/', ' ', $p)))->filter()->values()->all();
    if (! $paragraphs) {
        $paragraphs = [
            'I made this just for you.',
            'Go slowly. Scratch the stamps, press play, open every little heart. Each part is something I love about you, and there were far too many to fit, so think of this as the short version.',
            'Thank you for every ordinary day that somehow turned into my favorite one.',
        ];
    }

    $photos = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter(fn ($p) => is_string($p) && trim($p) !== '')
        ->map(fn ($p) => $resolveUrl(trim($p)))
        ->values()->all();
    $photoAt = fn (int $i) => $photos ? $photos[$i % count($photos)] : null;
    $coverPhoto = (! empty($fields['cover_image']) && $isUnlocked('cover_image')) ? $resolveUrl($fields['cover_image']) : $photoAt(0);
    $captions = $lines('photo_captions');
    $splitCaption = function (?string $line, string $title, string $sub) {
        if ($line === null || $line === '') {
            return [$title, $sub];
        }
        [$a, $b] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

        return [$a !== '' ? $a : $title, $b];
    };

    // «together since»: the poster's date column + a days-together count (only for a past date).
    // together_since is the start date; invitations made before it existed used event_date for it.
    $since = ($isUnlocked('together_since') ? \App\Support\Invitations\EventDate::parse($text('together_since')) : null)
        ?? ($isUnlocked('event_date') ? \App\Support\Invitations\EventDate::parse($text('event_date')) : null);
    $days = $since && $since->isPast() ? (int) $since->startOfDay()->diffInDays(\App\Support\Invitations\EventDate::now()->startOfDay()) : null;
    $venue = $isUnlocked('venue_name') ? $text('venue_name') : '';
    $dateLines = $since ? [$since->format('l'), $since->format('j F'), $since->format('Y')] : ['Always', '& forever', '♡'];

    $momentDefaults = [
        ['my favorite kind of day', 'golden'], ['right here, with you', 'blossom'],
        ['that night we laughed', 'night'], ['us, always', 'dusk'],
    ];
    $moments = collect($momentDefaults)->map(fn ($d, $i) => [
        'caption' => $captions[$i] ?? $d[0],
        'theme' => $d[1],
        'photo' => $photoAt($i),
    ])->all();

    $stripDefaults = [
        ['First photo together', 'the one you hate, I love', 'blossom'],
        ['Late night drives', 'windows down, our song on', 'night'],
        ['Sunday mornings', 'coffee and nowhere to be', 'golden'],
        ['Little adventures', 'anywhere, as long as it’s you', 'sea'],
        ['Just us', 'my favorite place', 'dusk'],
    ];
    $strip = collect($stripDefaults)->map(function ($d, $i) use ($captions, $splitCaption, $photoAt) {
        [$title, $sub] = $splitCaption($captions[4 + $i] ?? null, $d[0], $d[1]);

        return ['title' => $title, 'date' => $sub, 'theme' => $d[2], 'photo' => $photoAt(4 + $i)];
    })->all();

    // memory map: each story chapter is a pin; pin positions follow a fixed winding route
    $pinSpots = [[22, 60], [43, 30], [68, 64], [82, 26], [58, 84], [30, 22]];
    $placeThemes = ['golden', 'dusk', 'sea', 'night', 'blossom', 'golden'];
    $places = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($c) => is_array($c) && trim(($c['title'] ?? '').($c['text'] ?? '')) !== '')
        ->take(count($pinSpots))
        ->values()
        ->map(function ($c, $i) use ($splitCaption, $pinSpots, $placeThemes, $photoAt) {
            [$name, $when] = $splitCaption(trim((string) ($c['title'] ?? '')), 'A memory', '');

            return [
                'name' => $name, 'date' => $when, 'note' => trim((string) ($c['text'] ?? '')),
                'x' => $pinSpots[$i][0], 'y' => $pinSpots[$i][1], 'theme' => $placeThemes[$i],
                'photo' => $photoAt(9 + $i),
            ];
        })->all();

    $reasons = $lines('love_reasons') ?: [
        'The way you say my name.',
        'You remember the small things I only mention once.',
        'Your laugh, especially the one you try to hold in.',
        'You make every plan feel like an adventure.',
        'You’re my calm on loud days.',
        'You believe in me, even when I forget to.',
        'Because you’re you. That one covers the rest.',
    ];

    // an uploaded song wins over a link; nothing downloads until the music card's Play is tapped
    $musicFile = (! empty($fields['music_file']) && $isUnlocked('music_file')) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $isUnlocked('music_url') ? $text('music_url') : '';
    $youtubeId = null;
    if (! $musicFile && $musicUrl !== '') {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
            $youtubeId = $m[1];
        } elseif (preg_match('#^https?://\S+\.(mp3|m4a|aac|ogg|wav)(\?\S*)?$#i', $musicUrl)) {
            $musicFile = $musicUrl;
        }
    }

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $text('accent_color')) ? $text('accent_color') : '#d65f7f';

    $background = ! empty($fields['background_image']) && $isUnlocked('background_image') ? $resolveUrl($fields['background_image']) : null;
    if (! $background) {
        foreach (['background.jpg', 'background.jpeg', 'background.webp', 'background.png'] as $file) {
            if (is_file(public_path($dir.$file))) {
                $background = $rbAsset($dir.$file);
                break;
            }
        }
    }

    $config = [
        'heartLine' => $text('headline') ?: 'You’re my favorite person',
        'to' => $to,
        'from' => $from,
        'letter' => $paragraphs,
        'moments' => $moments,
        'strip' => $strip,
        'songTitle' => $text('song_title') ?: 'Our song',
        'music' => ['file' => $musicFile, 'youtube' => $youtubeId],
        // the template's own background piano (after the opening chime); the music card plays the customer's song
        'bgMusic' => $rbAsset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
        'reasons' => $reasons,
        'places' => $places,
        'finaleBig' => $text('closing_line') ?: 'I love you, always.',
        'finaleSmall' => $text('thanks_message') ?: 'Today, tomorrow, and on every ordinary day in between.',
        'accent' => $accent,
        // shared sound effects (trimmed copies in images/invitations/assets/sfx/); sparkle = the chime, pitched up
        'sfx' => collect([
            'chime' => 'chime', 'sparkle' => 'chime', 'envelope' => 'envelope', 'scratch' => 'scratch-loop',
            'cassette' => 'cassette', 'card' => 'card', 'pop' => 'pop', 'finale' => 'reveal',
        ])->map(fn ($f) => $rbAsset("images/invitations/assets/sfx/{$f}.mp3"))->all(),
    ];

    // a hidden heart for the hearts hunt (rose-bloom.js counts them; the number shown is however many are on the page)
    $hiddenHeart = fn (string $class) => '<button type="button" class="hh '.$class.'" aria-label="A hidden heart"><svg aria-hidden="true"><use href="#rb-heart"/></svg></button>';

    // one flower/petal/daisy, drawn from the shared SVG symbols below
    $flower = fn (string $kind, string $class, string $style = '') => '<svg class="fl '.$class.'" style="'.$style.'" aria-hidden="true"><use href="#rb-'.$kind.'"/></svg>';
@endphp

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..700;1,6..96,400..700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Homemade+Apple&family=Pinyon+Script&display=swap">
<link rel="stylesheet" href="{{ $rbAsset('css/invitations/rose-bloom.css') }}">

<div class="rb" style="--rose: {{ $accent }}; --name-len: {{ max(5, mb_strlen($name)) }};@if ($background) --wall: url('{{ $background }}');@endif" @if ($background) data-photo-bg @endif>

  {{-- shared flower artwork (tinted by the accent colour) --}}
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      <radialGradient id="rbPhloxG" cx="0" cy="0" r="48" gradientUnits="userSpaceOnUse">
        <stop offset="0" style="stop-color:color-mix(in srgb, var(--rose) 70%, #4a0018)"/>
        <stop offset=".22" style="stop-color:var(--rose)"/>
        <stop offset=".6" style="stop-color:color-mix(in srgb, var(--rose) 55%, #fff)"/>
        <stop offset="1" style="stop-color:color-mix(in srgb, var(--rose) 30%, #fff)"/>
      </radialGradient>
      <linearGradient id="rbLilyG" x1="0" y1="0" x2="0" y2="-58" gradientUnits="userSpaceOnUse">
        <stop offset="0" style="stop-color:color-mix(in srgb, var(--rose) 75%, #3a0016)"/>
        <stop offset=".45" style="stop-color:color-mix(in srgb, var(--rose) 85%, #fff)"/>
        <stop offset=".85" style="stop-color:color-mix(in srgb, var(--rose) 22%, #fff)"/>
        <stop offset="1" stop-color="#fff"/>
      </linearGradient>
      <linearGradient id="rbSatin" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#050505"/><stop offset=".35" stop-color="#3b3b3b"/>
        <stop offset=".55" stop-color="#0c0c0c"/><stop offset=".8" stop-color="#2c2c2c"/><stop offset="1" stop-color="#070707"/>
      </linearGradient>
      <path id="rbPhloxPetal" d="M0 -5 C -11 -13 -23 -30 -17 -42 C -12 -49 -4 -48 0 -40 C 4 -48 12 -49 17 -42 C 23 -30 11 -13 0 -5Z"/>
      <path id="rbLilyPetal" d="M0 -2 C -13 -16 -14 -38 -2 -56 Q 0 -60 2 -56 C 14 -38 13 -16 0 -2Z"/>
    </defs>
    <symbol id="rb-phlox" viewBox="-50 -50 100 100">
      <g fill="url(#rbPhloxG)">
        <use href="#rbPhloxPetal"/><use href="#rbPhloxPetal" transform="rotate(72)"/><use href="#rbPhloxPetal" transform="rotate(144)"/>
        <use href="#rbPhloxPetal" transform="rotate(216)"/><use href="#rbPhloxPetal" transform="rotate(288)"/>
      </g>
      <g stroke="color-mix(in srgb, var(--rose) 60%, #5a0020)" stroke-width="1.1" stroke-linecap="round" opacity=".55" fill="none">
        <path d="M0 -6 L0 -20 M-3 -7 L-6 -17 M3 -7 L6 -17" /><path d="M0 -6 L0 -20 M-3 -7 L-6 -17 M3 -7 L6 -17" transform="rotate(72)"/>
        <path d="M0 -6 L0 -20 M-3 -7 L-6 -17 M3 -7 L6 -17" transform="rotate(144)"/><path d="M0 -6 L0 -20 M-3 -7 L-6 -17 M3 -7 L6 -17" transform="rotate(216)"/>
        <path d="M0 -6 L0 -20 M-3 -7 L-6 -17 M3 -7 L6 -17" transform="rotate(288)"/>
      </g>
      <circle r="6.5" fill="#fff6f2" opacity=".85"/><circle r="3.6" fill="color-mix(in srgb, var(--rose) 55%, #400010)"/><circle r="1.4" fill="#f6d77a"/>
    </symbol>
    <symbol id="rb-petal" viewBox="-22 -50 44 50"><use href="#rbPhloxPetal" fill="url(#rbPhloxG)"/></symbol>
    <symbol id="rb-lily" viewBox="-62 -62 124 124">
      <g fill="url(#rbLilyG)" stroke="rgba(255,255,255,.7)" stroke-width=".8">
        <use href="#rbLilyPetal" transform="rotate(-8)"/><use href="#rbLilyPetal" transform="rotate(54) scale(.95)"/><use href="#rbLilyPetal" transform="rotate(116)"/>
        <use href="#rbLilyPetal" transform="rotate(178) scale(.92)"/><use href="#rbLilyPetal" transform="rotate(236)"/><use href="#rbLilyPetal" transform="rotate(298) scale(.96)"/>
      </g>
      <g fill="color-mix(in srgb, var(--rose) 55%, #3a0016)" opacity=".75">
        <circle cx="-3" cy="-14" r="1.3"/><circle cx="2" cy="-20" r="1.1"/><circle cx="-2" cy="-25" r=".9"/>
        <circle cx="13" cy="-6" r="1.2"/><circle cx="18" cy="-11" r="1"/><circle cx="-13" cy="-7" r="1.2"/><circle cx="-18" cy="-12" r="1"/>
        <circle cx="10" cy="11" r="1.2"/><circle cx="-10" cy="11" r="1.2"/><circle cx="1" cy="15" r="1"/>
      </g>
      <g stroke="#9cb36a" stroke-width="1.2" fill="none" stroke-linecap="round">
        <path d="M0 0 Q -6 -14 -12 -30"/><path d="M0 0 Q 7 -13 16 -28"/><path d="M0 0 Q 14 -4 30 -12"/><path d="M0 0 Q -14 -2 -29 -8"/><path d="M0 0 Q 3 -18 2 -34"/>
      </g>
      <g fill="#8a3b1c"><ellipse cx="-12" cy="-31" rx="2" ry="4" transform="rotate(-20 -12 -31)"/><ellipse cx="16" cy="-29" rx="2" ry="4" transform="rotate(28 16 -29)"/>
        <ellipse cx="30" cy="-12" rx="2" ry="4" transform="rotate(68 30 -12)"/><ellipse cx="-29" cy="-9" rx="2" ry="4" transform="rotate(-70 -29 -9)"/></g>
      <circle cx="2" cy="-35" r="2.4" fill="#cfe3a0"/>
    </symbol>
    <symbol id="rb-heart" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/></symbol>
    <symbol id="rb-daisy" viewBox="-20 -20 40 40">
      <g fill="#fff" stroke="#e6e1d6" stroke-width=".5">
        @for ($a = 0; $a < 360; $a += 22.5)<ellipse cy="-10.5" rx="2.6" ry="8" transform="rotate({{ $a }})"/>@endfor
      </g>
      <circle r="4.6" fill="#f2c230"/><circle r="4.6" fill="none" stroke="#c99212" stroke-width="1"/>
    </symbol>
    <symbol id="rb-bow" viewBox="-80 -40 160 140">
      <g fill="url(#rbSatin)">
        <path d="M-6 6 C -14 34 -28 64 -44 92 L -30 86 L -24 98 C -10 68 0 38 4 8Z"/>
        <path d="M6 6 C 16 30 30 58 48 84 L 34 82 L 30 95 C 14 66 2 38 -4 8Z"/>
        <path d="M-4 0 C -24 -34 -72 -40 -74 -10 C -76 16 -34 18 -4 4Z"/>
        <path d="M4 0 C 26 -32 72 -36 72 -8 C 72 18 32 18 4 4Z"/>
        <ellipse cx="0" cy="2" rx="10" ry="12"/>
      </g>
      <g fill="none" stroke="rgba(255,255,255,.22)" stroke-width="2" stroke-linecap="round">
        <path d="M-12 -6 C -30 -24 -56 -26 -62 -12"/><path d="M12 -6 C 30 -22 54 -24 60 -10"/><path d="M-3 -6 Q 2 2 -2 10"/>
      </g>
    </symbol>
  </svg>

  <div id="wall" aria-hidden="true"></div>
  <div id="introBg" aria-hidden="true"></div>
  <canvas id="gl" aria-hidden="true"></canvas>

  <section class="scene" id="intro" role="button" tabindex="0" aria-label="Open your gift">
    <p class="whisper" id="introLine">For you<small>Someone left these here.</small></p>
  </section>

  <div id="heartText" aria-live="polite"></div>
  <section class="scene" id="envScene">
    <h1 class="title">I made something<br>just for you</h1>
    <p class="sub">Open it slowly</p>
    <button class="env" id="env" type="button" aria-label="Open the letter">
      <div class="env-back"></div>
      <div class="env-letter"><b id="envTo"></b><i></i><i></i><i></i></div>
      <div class="env-front"></div>
      <div class="flapw"><div class="flap"></div><div class="seal"><span>&#9829;</span></div></div>
    </button>
    <p class="hint">Tap the seal</p>
  </section>

  <main id="book">
    {{-- 1. editorial poster --}}
    <section class="sec poster-sec">
      <div class="poster-wrap">
        <article class="poster" id="poster">
          <header class="p-top"><span>{{ $from }}</span><i></i><span>{{ $name }}</span></header>
          <p class="p-kicker">Happy anniversary</p>
          <h1 class="p-name">{{ $name }}</h1>
          <div class="p-photo">
            <img class="zoom" data-cap="{{ $name }}" alt="" data-ph="golden" data-seed="5" @if ($coverPhoto) src="{{ $coverPhoto }}" @endif>
            <p class="p-date">@foreach ($dateLines as $line)<span>{{ $line }}</span>@endforeach</p>
            {!! $flower('phlox', 'p-ghost') !!}
          </div>
          <footer class="p-info">
            <p>
              @if ($days !== null)
                <span>{{ number_format($days) }} days of us</span><span>Together since {{ $since->format('Y') }}</span>
              @else
                <span>Every day</span><span>with you</span>
              @endif
              @if ($venue !== '')<span>{{ $venue }}</span>@endif
            </p>
            <p class="r"><span>From {{ $from }}</span><span>with all my love</span><span>&#9825;</span></p>
          </footer>
          <div class="p-foot"><span>for {{ $name }}</span><span>made by {{ $from }}</span></div>
        </article>
        {!! $flower('phlox', 'p-f1') !!}{!! $flower('phlox', 'p-f2') !!}{!! $flower('phlox', 'p-f3') !!}
        {!! $flower('petal', 'p-pt1') !!}{!! $flower('petal', 'p-pt2') !!}{!! $flower('petal', 'p-pt3') !!}
        {!! $hiddenHeart('hh-poster') !!}
      </div>
      <p class="scroll-cue">scroll</p>
    </section>

    {{-- 2. the letter --}}
    <section class="sec letter-sec">
      <p class="giant dear" aria-hidden="true">Dear</p>
      <div class="letter-wrap">
        <figure class="lpol" id="lpol">
          <img class="zoom" data-cap="{{ $from }} &#9825; {{ $name }}" alt="" data-ph="blossom" data-seed="17" @if ($photoAt(1)) src="{{ $photoAt(1) }}" @endif>
          <figcaption>{{ $from }} &#9825; {{ $name }}</figcaption>
          <svg class="bow" aria-hidden="true"><use href="#rb-bow"/></svg>
        </figure>
        {!! $flower('lily', 'lily') !!}
        <article class="letter paper" id="letter">
          <p class="to" id="letterTo"></p>
          <div id="letterBody"></div>
          <p class="sign">Forever yours,<span id="letterFrom"></span></p>
          {!! $hiddenHeart('hh-letter') !!}
        </article>
        <span class="pill" aria-hidden="true">Always &#9825;</span>
        {!! $flower('phlox', 'l-f1') !!}
      </div>
      <p class="giant gname" aria-hidden="true">{{ $name }}</p>
    </section>

    {{-- 3. music --}}
    <section class="sec">
      <h2 class="title rv">Every love song sounds like you</h2>
      <p class="sub rv">Press play</p>
      <div class="player" id="cassette">
        <div class="pl-top">
          <img class="pl-av" alt="" data-ph="dusk" data-seed="29" @if ($photoAt(3)) src="{{ $photoAt(3) }}" @endif>
          <div><b>Now playing</b><small>for {{ $name }}</small></div>
          <span class="pl-ic"><span>&#9829;</span><span class="pk">&#9733;</span></span>
        </div>
        <div class="pl-art">
          <img class="zoom" data-cap="{{ $text('song_title') ?: 'Our song' }}" alt="" data-ph="dusk" data-seed="23" @if ($photoAt(2)) src="{{ $photoAt(2) }}" @endif>
          {!! $hiddenHeart('hh-music') !!}
          <div class="pl-glass"><span class="pl-song" id="songTitle"></span><span class="pl-meta">&#9829; {{ $from }} &rarr; {{ $name }}</span></div>
          <button class="pl-play" id="playBtn" type="button" aria-pressed="false"><span id="playIcon" aria-hidden="true">&#9654;</span><span id="playTxt" class="sr">Play</span></button>
        </div>
        <div class="pl-bar"><i></i></div>
        <div class="pl-row"><span>Side A</span><span class="eq" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span><span>on repeat</span></div>
        <div class="pl-chips"><span>Our song</span><span class="o">Love</span><span class="o">&#9825; Forever</span></div>
      </div>
    </section>

    {{-- 4. scratch stamps --}}
    <section class="sec">
      <h2 class="title rv">If I could live a moment twice…</h2>
      <p class="sub rv">Scratch each stamp to see</p>
      <div class="stamps" id="stamps"></div>
    </section>

    {{-- 5. polaroid strip --}}
    <section class="sec strip" id="strip">
      <div class="head"><h2 class="title">Little pieces of us</h2><p class="sub">Keep scrolling</p></div>
      <div class="track" id="track"></div>
    </section>

    {{-- 6. reasons --}}
    <section class="sec">
      <h2 class="title rv">Reasons, in no particular order</h2>
      <p class="sub rv">There were too many to fit</p>
      <div class="deck" id="deck" role="button" tabindex="0" aria-label="Show the next reason">
        <div class="rcard paper base"></div>
        <div class="rcard paper next" id="rNext"><p class="n"></p><p class="t"></p></div>
        <div class="rcard paper" id="rFront"><p class="n"></p><p class="t"></p></div>
        {!! $hiddenHeart('hh-deck') !!}
        {!! $flower('phlox', 'd-f1') !!}{!! $flower('petal', 'd-f2') !!}
      </div>
      <p class="deck-hint">Tap the card for the next one</p>
    </section>

    @if ($places)
      <section class="sec" id="mapSec">
        <h2 class="title rv">Everywhere feels like home with you</h2>
        <p class="sub rv">Tap a heart to open a memory</p>
        <div class="map paper" id="map">
          <canvas id="mapCanvas"></canvas>
          <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path id="route" fill="none" stroke="var(--rose)" stroke-opacity=".8" stroke-width="2.2" stroke-dasharray="1 6" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
          <div class="pola" id="pola"><img class="zoom" alt=""><h3></h3><p class="d"></p><p class="nt"></p></div>
        </div>
      </section>
    @endif

    <section class="sec">
      <h2 class="title rv">One last thing…</h2>
      <p class="sub rv" style="margin-bottom:28px">Will you keep this forever?</p>
      <div class="choices rv">
        <button class="btn" type="button" data-yes>Yes</button>
        <button class="btn" type="button" data-yes>Yes, obviously</button>
      </div>
      <p class="hh-left" id="hhLeft"></p>
    </section>
    {{-- footer (shared component, every template ends with one) --}}
    <x-invitation-footer prefix="rb" :names="$from.' ♡ '.$name" :date="$since?->format('d · m · Y')">
      <x-slot:ornament><svg><use href="#rb-phlox"/></svg></x-slot:ornament>
      <button class="linkbtn" id="replay" type="button">Start again from the roses</button>
    </x-invitation-footer>
  </main>

  <button class="sound" id="soundBtn" type="button" aria-pressed="false" aria-label="Mute sound">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path class="on" d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path class="off" d="M16.5 9.5l5 5m0-5l-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
  </button>

  {{-- hearts hunt: counter, tip, and the secret it unlocks --}}
  <button class="hh-count" id="hhCount" type="button" aria-label="Hidden hearts found"><svg aria-hidden="true"><use href="#rb-heart"/></svg><span id="hhN">0</span>/<span id="hhTotal">5</span></button>
  <div class="hh-toast" id="hhToast" role="status">
    <span class="hh-toast-ic" aria-hidden="true"><svg><use href="#rb-heart"/></svg></span>
    <p class="hh-toast-t" id="hhToastT"></p>
    <p class="hh-toast-m" id="hhToastM"></p>
    <span class="hh-dots" id="hhDots"></span>
    <span class="hh-toast-x">tap to close</span>
  </div>
  <div id="secret" role="dialog" aria-modal="true" aria-label="A secret">
    <div class="secret-card">
      <figure class="secret-pol"><img alt="" data-ph="golden" data-seed="5" @if ($coverPhoto) src="{{ $coverPhoto }}" @endif><figcaption>{{ $from }} &#9825; {{ $name }}</figcaption></figure>
      <p class="secret-k">Secret unlocked</p>
      <p class="secret-big">You found every heart.</p>
      <p class="secret-small">Just like you found mine.</p>
      <button class="btn" id="secretClose" type="button">Keep reading</button>
    </div>
  </div>

  {{-- photo preview (tap any photo) --}}
  <div id="lb" role="dialog" aria-modal="true" aria-label="Photo preview">
    <div class="lb-bg"></div>
    <figure class="lb-pol" id="lbPol">
      <span class="lb-tape" aria-hidden="true"></span>
      <img id="lbImg" alt="">
      <figcaption id="lbCap"></figcaption>
    </figure>
    <button class="lb-x" id="lbX" type="button" aria-label="Close">&#10005;</button>
    <div class="lb-bar">
      <button class="lb-nav" id="lbPrev" type="button" aria-label="Previous photo">&#8249;</button>
      <span class="lb-count"><svg aria-hidden="true"><use href="#rb-heart"/></svg><span id="lbCount"></span></span>
      <button class="lb-nav" id="lbNext" type="button" aria-label="Next photo">&#8250;</button>
    </div>
  </div>

  <div id="finale" role="dialog" aria-modal="true" aria-label="A last note">
    <div class="fin-card paper">
      {!! $flower('phlox', 'f-f1') !!}{!! $flower('phlox', 'f-f2') !!}
      <img id="finaleRose" alt="">
      <p class="big" id="finaleBig"></p>
      <p class="small" id="finaleSmall"></p>
      <button class="btn" id="again" type="button">Read it again</button>
    </div>
  </div>
</div>

<script id="rb-config" type="application/json">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="https://cdn.jsdelivr.net/npm/three@0.149.0/build/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="{{ $rbAsset('js/invitations/rose-bloom.js') }}"></script>
