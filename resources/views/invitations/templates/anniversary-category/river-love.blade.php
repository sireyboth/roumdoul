@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // River Love — romantic anniversary / love page.
    // Cover: a full-screen looping video of a blossom lake (cropped, never shrunk, on phones),
    // the words in the middle rise softly out of a blur, and petals and leaves fall forever over the
    // whole page. Below: the letter → scratch-off photos → our photos → paper boats of reasons →
    // our places → now playing → together since (a live count-up) → the lantern finale.
    //
    // Everything personal comes from $fields (catalog keys, docs/invitation-templates.md):
    //   recipient name   → «For …» on the cover and «Dear …» on the letter (the guest link's name)
    //   sender_name      → signature + top bar   headline → the big cover line (« | » = line break)
    //   message          → the letter (blank line = new paragraph); its first sentence is the cover subline
    //   together_since   → «Together since …» + a live days/hours/minutes/seconds count-up (a past event_date stands in;
    //                    there is no countdown — a future event_date isn't shown)
    //   photo_gallery + photo_captions (caption N = gallery photo N) → scratch cards (photos 1–4, title = caption 1–4),
    //                    letter prints (photos 5–7; 1–3 in a gallery under 7), the photo reels (led by photos 5+; caption
    //                    «Title | small line» = the card's film labels, the small line replaces the year). Scratch cards: x-scratch-card
    //   story_chapters   → «Our places» memory map pins (shared x-memory-map; title «Place | when», text = the memory,
    //                    photo = gallery photo 8+); no chapters = no map
    //   cover_image      → first photo of the photos section (default: gallery photo 1)
    // The background is always the river-love video (not a customer field): background_image and
    // cover_image never replace it — cover_image only leads the photos section.
    //   love_reasons     → «Paper boats»: one folded paper boat per line (max 9) bobbing on the river; a tap unfolds
    //                    it into a note with that reason (gentle generic reasons when empty)
    //   music_file / music_url + song_title → plays by itself; «Play our song» on the cover and the «Now playing»
    //                    card (record, title, from/for, progress) both pause/play the same song, always in sync
    //   closing_line + thanks_message → the lantern finale: «Send a lantern» lets a paper lantern rise, then they show
    //   accent_color → petals, buttons, hearts
    // Hidden hearts hunt: a small heart hides in the letter, scratch cards, photo reels, map and boats (sections
    // that aren't shown take their heart with them); a counter appears after the first, all found = a secret card.
    // Empty fields fall back to gentle generic wording. On demo pages $invitation is null → all unlocked.
    // Video: images/invitations/assets/videos/river-love-loop.mp4 (landscape) and river-love-portrait-loop.mp4
    // (portrait screens), each a seamless loop made with ffmpeg from river-love.mp4 / river-love-portrait.mp4,
    // with a matching *-poster.jpg. Behaviour: public/js/invitations/river-love.js,
    // styles: public/css/invitations/river-love.css
    // ------------------------------------------------------------------------------------
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $resolveUrl = fn (string $path) => preg_match('#^(https?:)?//#i', $path) ? $path : Storage::disk('s3')->url($path);
    $text = fn (string $key) => trim((string) ($fields[$key] ?? ''));
    $lines = fn (string $key) => collect(preg_split('/\R/', $text($key)))->map(fn ($l) => trim($l))->filter()->values()->all();
    $rlAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));

    $guest = trim((string) ($recipientName ?? ''));
    $name = rtrim($guest !== '' ? $guest : 'My love', ', ');
    $from = $text('sender_name') ?: 'Me';

    // the big cover line: « | » or a line break splits it; otherwise it's split near the middle
    $headline = $text('headline') ?: 'The best part of my life | is in that letter';
    $headLines = collect(preg_split('/\s*(\||\R)\s*/', $headline))->filter()->values()->all();
    if (count($headLines) === 1) {
        $words = preg_split('/\s+/', $headLines[0]);
        if (count($words) >= 4) {
            $half = (int) ceil(count($words) / 2);
            $headLines = [implode(' ', array_slice($words, 0, $half)), implode(' ', array_slice($words, $half))];
        }
    }

    $paragraphs = collect(preg_split('/\R\s*\R/', $text('message')))->map(fn ($p) => trim(preg_replace('/\s+/', ' ', $p)))->filter()->values()->all();
    if (! $paragraphs) {
        $paragraphs = [
            'Some days I still can’t believe I get to love you.',
            'You turned ordinary mornings into my favorite part of the day, and every quiet evening into a place I want to stay. Like a river, I keep finding my way back to you.',
            'Thank you for choosing me, again and again.',
        ];
    }
    // the cover's small line: the first sentence of the letter, kept short
    $subline = preg_split('/(?<=[.!?។])\s+/u', $paragraphs[0])[0] ?? $paragraphs[0];
    $subline = \Illuminate\Support\Str::limit($subline, 120);

    $photos = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter(fn ($p) => is_string($p) && trim($p) !== '')
        ->map(fn ($p) => $resolveUrl(trim($p)))
        ->values();
    // captions belong to the gallery photos (caption 1 = gallery photo 1), never to the cover
    $captions = $lines('photo_captions');
    $galleryItems = $photos->map(function ($src, $i) use ($captions) {
        [$title, $sub] = array_pad(array_map('trim', explode('|', $captions[$i] ?? '', 2)), 2, '');

        return ['src' => $src, 'title' => $title, 'sub' => $sub];
    })->values();
    // cover_image only leads the photo reels — the letter prints and scratch cards use gallery photos
    // (a lone cover still shows everywhere when there's no gallery)
    $cover = (! empty($fields['cover_image']) && $isUnlocked('cover_image')) ? $resolveUrl($fields['cover_image']) : null;
    // the photo strip leads with gallery photos 5+ (captions 5–9 are its «Title | small line» labels), then
    // photos 1–4 (the scratch cards); the cover goes first of all
    $stripOrder = $galleryItems->slice(4)->concat($galleryItems->take(4))->values();
    $reelItems = $cover
        ? $stripOrder->reject(fn ($g) => $g['src'] === $cover)->prepend(['src' => $cover, 'title' => '', 'sub' => ''])->values()
        : $stripOrder;
    $gallery = $reelItems->take(18)->all();
    $ownPhotos = $galleryItems->isNotEmpty() ? $galleryItems->all() : $gallery;

    // scratch cards: gallery photos 1–4 with captions 1–4 (as the Photo captions help says); a smaller gallery repeats
    $scratchDefaults = ['my favorite kind of day', 'right here, with you', 'that night we laughed', 'us, always'];
    $scratchCards = $ownPhotos ? collect(range(0, min(4, count($ownPhotos)) - 1))->map(function ($k) use ($ownPhotos, $scratchDefaults) {
        $g = $ownPhotos[$k];

        return ['src' => $g['src'], 'caption' => $g['title'] !== '' ? $g['title'] : $scratchDefaults[$k]];
    })->all() : [];

    // memory map (shared x-memory-map): one pin per story chapter, title «Place | when», text = the memory;
    // pin photos are gallery photos 8+ (after the prints and scratch cards), starting over when the gallery runs out
    $places = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($c) => is_array($c) && trim(($c['title'] ?? '').($c['text'] ?? '')) !== '')
        ->take(6)->values()
        ->map(function ($c, $i) use ($ownPhotos) {
            [$place, $when] = array_pad(array_map('trim', explode('|', (string) ($c['title'] ?? ''), 2)), 2, '');

            return [
                'name' => $place !== '' ? $place : 'A memory', 'date' => $when, 'note' => trim((string) ($c['text'] ?? '')),
                'photo' => $ownPhotos ? $ownPhotos[(7 + $i) % count($ownPhotos)]['src'] : null,
            ];
        })->all();

    // together_since → the live «together for» count-up (read as Cambodia time, EventDate). There's no countdown:
    // invitations made before the field existed used a past event_date as the start, so that still counts.
    $now = \App\Support\Invitations\EventDate::now();
    $since = $isUnlocked('together_since') ? \App\Support\Invitations\EventDate::parse($text('together_since')) : null;
    if (! $since && $isUnlocked('event_date')) {
        $eventDate = \App\Support\Invitations\EventDate::parse($text('event_date'));
        $since = $eventDate?->isPast() ? $eventDate : null;
    }
    if ($since?->isFuture()) {
        $since = null; // a start date can't be ahead (the dashboard refuses it too)
    }
    $days = $since ? (int) $since->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : null;
    // the together-for count as the page first shows it (river-love.js keeps it ticking)
    $together = null;
    if ($since) {
        $secs = max(0, $now->getTimestamp() - $since->getTimestamp());
        $together = ['d' => intdiv($secs, 86400), 'h' => intdiv($secs, 3600) % 24, 'm' => intdiv($secs, 60) % 60, 's' => $secs % 60];
    }
    // the stamp, film labels and footer show the day it all began
    $date = $since;

    // an uploaded song wins over a link; it plays by itself (on open, or on the first tap when the browser blocks sound)
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
    $hasMusic = $musicFile || $youtubeId;
    $songTitle = $text('song_title') ?: 'Our song';

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $text('accent_color')) ? $text('accent_color') : '#e8789a';

    $video = 'images/invitations/assets/videos/river-love-loop.mp4';
    $poster = 'images/invitations/assets/videos/river-love-poster.jpg';
    $videoPortrait = 'images/invitations/assets/videos/river-love-portrait-loop.mp4';
    $posterPortrait = 'images/invitations/assets/videos/river-love-portrait-poster.jpg';

    // paper boats: one reason per line, at most 9 (three rows of three on a phone)
    $reasons = array_slice($lines('love_reasons') ?: [
        'The way you make ordinary days feel like a holiday.',
        'You listen, even to my longest stories.',
        'Your laugh. I would cross any river for it.',
        'You feel like home.',
        'Because every day with you becomes my favourite one.',
    ], 0, 9);

    // hidden hearts: one per shown section (letter + boats always; scratch, reels and map when they're on the page)
    $heartCount = 2 + ($scratchCards ? 1 : 0) + ($gallery ? 1 : 0) + ($places ? 1 : 0);
    $hiddenHeart = fn (string $where) => '<button type="button" class="rl-hh rl-hh-'.$where.'" aria-label="A little hidden heart"><svg aria-hidden="true"><use href="#rl-heart"/></svg></button>';

    $closing = $text('closing_line') ?: 'I love you, always.';
    $thanks = $text('thanks_message') ?: 'Today, tomorrow, and on every ordinary day in between.';

    $config = [
        'music' => ['file' => $musicFile, 'youtube' => $youtubeId],
        'since' => $since?->toIso8601String(),
        // template ambience: a seamless 40 s birdsong loop (made with ffmpeg from soundreality-birds-forest-nature-445379.mp3)
        'birds' => $rlAsset('images/invitations/assets/sfx/birds-loop.mp3'),
        // short taps: a boat unfolds (paper), folds back (card), a hidden heart (pop), a lantern (chime), the secret (reveal)
        'sfx' => collect(['paper' => 'papercrackle1', 'card' => 'card', 'pop' => 'pop', 'chime' => 'chime', 'reveal' => 'reveal'])
            ->map(fn ($f) => $rlAsset("images/invitations/assets/sfx/{$f}.mp3"))->all(),
    ];
@endphp

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Pinyon+Script&family=DM+Serif+Display&family=Special+Elite&display=swap">
<link rel="stylesheet" href="{{ $rlAsset('css/invitations/river-love.css') }}">

<div class="rl" style="--rl-accent: {{ $accent }};">

  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="rl-heart" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/></symbol>
    <symbol id="rl-blossom" viewBox="-50 -50 100 100">
      <g fill="currentColor">
        @for ($a = 0; $a < 360; $a += 72)<path transform="rotate({{ $a }})" d="M0 -6 C -12 -14 -20 -30 -14 -42 C -9 -48 -3 -46 0 -40 C 3 -46 9 -48 14 -42 C 20 -30 12 -14 0 -6Z"/>@endfor
      </g>
      <circle r="7" fill="#fff" opacity=".85"/><circle r="3" fill="#f3c969"/>
    </symbol>
  </svg>

  {{-- falling petals & leaves, forever (fixed over the whole page) --}}
  <canvas class="rl-fall" id="rlFall" aria-hidden="true"></canvas>

  {{-- one video background behind the whole page (fixed; every section sits on it) --}}
  <div class="rl-media" aria-hidden="true">
    {{-- portrait screens (phones) get the portrait cut; river-love.js swaps it when the phone is rotated --}}
    <video class="rl-video" id="rlVideo" autoplay muted loop playsinline preload="auto" disablepictureinpicture
      poster="{{ $rlAsset($poster) }}"
      data-landscape="{{ $rlAsset($video) }}" data-landscape-poster="{{ $rlAsset($poster) }}"
      data-portrait="{{ $rlAsset($videoPortrait) }}" data-portrait-poster="{{ $rlAsset($posterPortrait) }}">
      <source src="{{ $rlAsset($videoPortrait) }}" type="video/mp4" media="(orientation: portrait)">
      <source src="{{ $rlAsset($video) }}" type="video/mp4">
    </video>
    <div class="rl-shade"></div>
  </div>

  {{-- ─────────── cover ─────────── --}}
  <section class="rl-cover" id="rlCover">

    <header class="rl-bar">
      <span class="rl-logo"><svg aria-hidden="true"><use href="#rl-blossom"/></svg></span>
      <nav class="rl-nav" aria-label="Sections">
        <a href="#rlLetter">Letter</a>
        @if ($gallery)<a href="#rlPhotos">Moments</a>@endif
        <a href="#rlReasons">Reasons</a>
        @if ($places)<a href="#rlMap">Places</a>@endif
        @if ($since)<a href="#rlTime">Us</a>@endif
      </nav>
      <a class="rl-chip" href="#rlLetter">For {{ $name }} <svg aria-hidden="true"><use href="#rl-heart"/></svg></a>
    </header>

    <div class="rl-center">
      <p class="rl-pill rl-in" style="--d:.2s">
        <b>{{ $since ? 'Since '.$since->format('Y') : 'With love' }}</b>
        <span>from {{ $from }}, for {{ $name }}</span>
      </p>
      <h1 class="rl-title" aria-label="{{ implode(' ', $headLines) }}">
        @php $wi = 0; @endphp
        @foreach ($headLines as $line)
          <span class="rl-line" aria-hidden="true">
            @foreach (preg_split('/\s+/', $line) as $word)
              <span class="rl-word" style="--d:{{ number_format(.45 + $wi++ * .12, 2) }}s">{{ $word }}</span>
            @endforeach
          </span>
        @endforeach
      </h1>
      <p class="rl-sub rl-in" style="--d:{{ number_format(.7 + $wi * .12, 2) }}s">{{ $subline }}</p>
      <div class="rl-actions rl-in" style="--d:{{ number_format(.9 + $wi * .12, 2) }}s">
        <a class="rl-btn" href="#rlLetter">Open my letter <span aria-hidden="true">&#8595;</span></a>
        @if ($hasMusic)
          <button class="rl-btn rl-btn-ghost" id="rlPlay" type="button" aria-pressed="false">
            <span class="rl-eq" aria-hidden="true"><i></i><i></i><i></i></span>
            <span id="rlPlayTxt">Play {{ $songTitle }}</span>
          </button>
        @endif
      </div>
    </div>

    <div class="rl-strip rl-in" style="--d:{{ number_format(1.15 + $wi * .12, 2) }}s">
      <p class="rl-strip-k">Written with every heartbeat</p>
      <ul>
        <li>{{ $from }} <svg aria-hidden="true"><use href="#rl-heart"/></svg> {{ $name }}</li>
        @if ($days !== null)<li>{{ number_format($days) }} days</li>@endif
        <li>Always</li>
      </ul>
    </div>
  </section>

  {{-- ─────────── letter: a keepsake card on frosted glass over the video (every screen size) ───────────
       round date stamp, the letter in small centred capitals, tilted prints with a green wax seal,
       drawn thyme and a white chrysanthemum. Everything comes from the customer's fields
       (date, message, photos). --}}
  @php
      $stamp = $date
          ? ['top' => mb_strtoupper($date->format('l')), 'mid' => $date->format('d'), 'bottom' => mb_strtoupper($date->format('F'))]
          : ['top' => 'ALWAYS', 'mid' => '♡', 'bottom' => '& FOREVER'];
      // the letter's prints: photos 5–7 when there are that many (1–4 hide under the scratch cards), else 1–3
      $prints = collect($ownPhotos)->slice(count($ownPhotos) >= 7 ? 4 : 0, 3)->pluck('src')->all();
      // green wax seal with a leaf; $id keeps gradient ids unique when it appears twice
      $seal = fn (string $id, string $class) => '<svg class="'.$class.'" viewBox="0 0 100 100" aria-hidden="true"><defs><radialGradient id="'.$id.'" cx="38%" cy="32%" r="70%"><stop offset="0" stop-color="#7fa36a"/><stop offset=".55" stop-color="#4f7a43"/><stop offset="1" stop-color="#355a2e"/></radialGradient></defs>'
          .'<path d="M50 4 C62 3 70 8 79 14 C89 21 96 33 96 47 C97 60 93 71 84 81 C75 91 63 96 50 96 C37 97 25 92 16 83 C7 74 3 62 4 49 C4 36 9 25 18 16 C27 8 38 4 50 4Z" fill="url(#'.$id.')"/>'
          .'<circle cx="50" cy="50" r="33" fill="none" stroke="#3b6233" stroke-width="2.5" opacity=".7"/><circle cx="50" cy="50" r="31" fill="none" stroke="#9dc187" stroke-width="1" opacity=".5"/>'
          .'<g fill="none" stroke="#2f5228" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M33 66 C 30 44 44 30 66 32 C 68 54 54 68 33 66Z"/><path d="M33 66 L 60 38 M42 57 L 41 47 M42 57 L 52 58 M50 49 L 49 41 M50 49 L 59 50"/></g>'
          .'<g fill="none" stroke="#a9cc92" stroke-width="1" stroke-linecap="round" opacity=".55" transform="translate(-1 -1)"><path d="M33 66 C 30 44 44 30 66 32 C 68 54 54 68 33 66Z"/></g></svg>';
      // one thyme sprig: a gently curved stem with pairs of small leaves (x, y, rotation, scale, flip)
      $sprig = function (float $len, float $bend) {
          $out = '<path d="M0 0 Q '.($bend).' '.(-$len / 2).' '.($bend * .4).' '.(-$len).'" fill="none" stroke="#6f8f5b" stroke-width="1.6" stroke-linecap="round"/>';
          for ($i = 1; $i <= 18; $i++) {
              $t = $i / 19;
              $x = 2 * (1 - $t) * $t * $bend + $t * $t * $bend * .4;
              $y = -$len * $t;
              $sz = 1 - $t * .5;
              foreach ([-1, 1] as $side) {
                  $out .= '<ellipse cx="'.round($x + $side * 6 * $sz, 1).'" cy="'.round($y - 2, 1).'" rx="'.round(5.5 * $sz, 1).'" ry="'.round(2.4 * $sz, 1).'" transform="rotate('.($side * -38).' '.round($x + $side * 6 * $sz, 1).' '.round($y - 2, 1).')" fill="'.($i % 3 ? '#7fa36a' : '#93b87c').'"/>';
              }
          }

          return $out;
      };
  @endphp
  <div id="rlLetter" class="rl-letter-host">
  <section class="rl-letter">
    <div class="rl-l-wrap">
      {!! $hiddenHeart('letter') !!}
      <div class="rl-l-text">
        <div class="rl-stamp rl-rv" aria-label="{{ $date ? $date->format('l, j F Y') : 'Always and forever' }}">
          <p class="rl-stamp-day" aria-hidden="true">{{ $stamp['top'] }}</p>
          <div class="rl-stamp-ring" aria-hidden="true">
            <svg viewBox="0 0 200 200">
              <defs><path id="rlStampPath" d="M100 100 m-74 0 a74 74 0 1 1 148 0 a74 74 0 1 1 -148 0"/></defs>
              <text><textPath href="#rlStampPath" textLength="455">FOR THE MOMENTS THAT MATTER MOST ·</textPath></text>
            </svg>
            <b>{{ $stamp['mid'] }}</b>
          </div>
          <p class="rl-stamp-month" aria-hidden="true">{{ $stamp['bottom'] }}</p>
        </div>

        <article class="rl-l-body rl-rv">
          <p class="rl-l-dear">Dear {{ $name }},</p>
          @foreach ($paragraphs as $p)
            <p>{{ $p }}</p>
          @endforeach
          <p class="rl-l-sign">Forever yours,<span>{{ $from }}</span></p>
        </article>
      </div>

      @if ($prints)
        {{-- the prints fly in and land when this comes into view (river-love.js adds .is-flown) --}}
        <div class="rl-l-prints">
          @foreach ($prints as $i => $src)
            <figure class="rl-print rl-print-{{ $i + 1 }}" style="--fd:{{ $i * .28 }}s">
              <button type="button" class="rl-ph-btn" data-full="{{ $src }}" aria-label="Open photo {{ $i + 1 }}">
                <img src="{{ $src }}" alt="" loading="lazy" decoding="async">
              </button>
              @if ($i === 1 || count($prints) === 1)<figcaption class="rl-print-script">Happy anniversary</figcaption>@endif
              @if ($i === 0){!! $seal('rlSealP', 'rl-seal') !!}@endif
            </figure>
          @endforeach
        </div>
      @endif
    </div>

    {{-- thyme sprigs (bottom left) and a white chrysanthemum (bottom right), swaying a little --}}
    <svg class="rl-l-thyme" viewBox="-130 -270 280 280" aria-hidden="true">
      <g class="rl-sway" style="--sw:-2.5deg;animation-delay:0s">{!! '<g transform="translate(-95 0) rotate(-22)">'.$sprig(190, 20).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:2deg;animation-delay:-2s">{!! '<g transform="translate(-70 0) rotate(-14)">'.$sprig(240, 26).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:-2deg;animation-delay:-4s">{!! '<g transform="translate(-45 0) rotate(-8)">'.$sprig(210, -16).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:2.2deg;animation-delay:-1s">{!! '<g transform="translate(-20 0) rotate(-3)">'.$sprig(260, -18).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:-2deg;animation-delay:-3s">{!! '<g transform="translate(5 0) rotate(3)">'.$sprig(225, 20).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:2.5deg;animation-delay:-5s">{!! '<g transform="translate(30 0) rotate(8)">'.$sprig(200, 22).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:-2deg;animation-delay:-2.5s">{!! '<g transform="translate(55 0) rotate(14)">'.$sprig(170, -14).'</g>' !!}</g>
      <g class="rl-sway" style="--sw:2deg;animation-delay:-1.5s">{!! '<g transform="translate(80 0) rotate(20)">'.$sprig(140, 12).'</g>' !!}</g>
    </svg>
    <svg class="rl-l-mum" viewBox="-80 -80 160 220" aria-hidden="true">
      <g class="rl-sway" style="--sw:2deg">
        <path d="M2 18 C 6 60 -4 100 4 140" fill="none" stroke="#6f8f5b" stroke-width="3" stroke-linecap="round"/>
        <path d="M3 70 C 24 58 40 64 52 76 C 34 82 18 80 3 70Z" fill="#7fa36a"/>
        <path d="M2 100 C -18 88 -36 92 -46 104 C -28 112 -12 110 2 100Z" fill="#6f9a5c"/>
        @for ($ring = 0; $ring < 3; $ring++)
          @for ($a = 0; $a < 360; $a += (14 - $ring * 2))
            <ellipse cx="0" cy="{{ -(28 - $ring * 8) }}" rx="{{ 3.6 - $ring * .6 }}" ry="{{ 26 - $ring * 7 }}" transform="rotate({{ $a + $ring * 7 }})" fill="{{ $ring ? '#fffdf6' : '#f4f1e6' }}" stroke="#e2dccb" stroke-width=".5"/>
          @endfor
        @endfor
        <circle r="7" fill="#efe6c6"/><circle r="4" fill="#d9c98f"/>
      </g>
    </svg>
  </section>

  </div>

  {{-- ─────────── scratch to remember: the shared scratch-card component (up to 4) ───────────
       photos 4–7 of the gallery (the first three are the letter's prints; a small gallery starts over),
       their caption titles under each card. Revealed cards open in the photo preview and drop petals. --}}
  @if ($scratchCards)
    <section class="rl-sec rl-scratch" id="rlScratch">
      {!! $hiddenHeart('scratch') !!}
      <p class="rl-kicker rl-rv">Scratch to remember</p>
      <h2 class="rl-h2 rl-rv">If I could live a moment twice…</h2>
      <div class="rl-scratch-grid" style="--n: {{ count($scratchCards) }}">
        @foreach ($scratchCards as $i => $card)
          <x-scratch-card class="rl-scratch-card rl-rv" style="--d: {{ $i * .12 }}s; --tilt: {{ [-2.5, 2, -1.5, 2.5][$i % 4] }}deg"
            :caption="$card['caption']" cover="scratch me" hint="scratch to see" label="Scratch to reveal photo {{ $i + 1 }}">
            <button type="button" class="rl-ph-btn" data-full="{{ $card['src'] }}" data-cap="{{ $card['caption'] }}" aria-label="Open photo {{ $i + 1 }}">
              <img src="{{ $card['src'] }}" alt="" loading="lazy" decoding="async" draggable="false">
            </button>
          </x-scratch-card>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ─────────── photos: tilted film reels sliding forever ───────────
       three white bands of framed photos on a pale sky, tilted, each row sliding the other way.
       Every row is its card set twice in a row; sliding by exactly one set (-50%) loops with no seam.
       Small film labels come from the customer's own data: ▶ date (MMDD), names / photo captions,
       the days-together count (or the year), and a soft tagline. --}}
  @if ($gallery)
    @php
        $reelCode = $date ? $date->format('md') : '0214';
        $reelNum = $days !== null ? (string) $days : ($date ? $date->format('Y') : (string) now()->year);
        $reelNames = mb_strtoupper($from.' & '.$name);
        $reelTags = ['FOREVER US', 'ALWAYS YOU', 'JUST US TWO', 'MY FAVORITE'];
        $ratios = ['4/3', '3/4', '16/10', '4/5', '1/1', '3/2'];
        $perRow = max(6, count($gallery));
        $reelRows = collect([0, 1, 2])->map(fn ($r) => collect(range(0, $perRow - 1))->map(function ($k) use ($r, $gallery, $ratios, $reelNames, $reelCode, $reelNum, $reelTags) {
            $i = ($k + $r * 3) % count($gallery);
            $g = $gallery[$i];
            $title = $g['title'] !== '' ? mb_strtoupper($g['title']) : $reelNames;
            // a caption's small line («Title | 2022») always wins over the event year / days count / tagline
            $bottom = match (true) {
                $g['sub'] !== '' => ['tag', mb_strtoupper($g['sub'])],
                (bool) (($k + $r) % 2) => ['tag', $reelTags[($k + $r) % count($reelTags)]],
                default => ['num', $reelNum.' ▶'],
            };

            return [
                'src' => $g['src'], 'cap' => $g['title'], 'n' => $i + 1,
                'ratio' => $ratios[($k + $r * 2) % count($ratios)],
                'top' => ($k + $r) % 2 ? ['code', '▶ '.$reelCode] : ['name', $title],
                'bottom' => $bottom,
            ];
        })->all())->all();
    @endphp
    <section class="rl-reel" id="rlPhotos">
      <p class="rl-reel-ghost" aria-hidden="true">{{ $name }}</p>
      <header class="rl-reel-head">
        <p class="rl-kicker rl-rv">Little pieces of us</p>
        <h2 class="rl-reel-title rl-rv">Moments<span>I keep</span></h2>
        {!! $hiddenHeart('reel') !!}
      </header>
      <div class="rl-reel-glow" aria-hidden="true"></div>
      <div class="rl-reel-grain" aria-hidden="true"></div>
      <div class="rl-reel-tilt">
        @foreach ($reelRows as $r => $cards)
          <div class="rl-row {{ $r % 2 ? 'is-right' : '' }}" style="--dur: {{ count($cards) * ($r === 1 ? 18 : 15) }}s">
            <div class="rl-track">
              @foreach ([0, 1] as $copy)
                @foreach ($cards as $c)
                  <figure class="rl-card" style="--ratio: {{ $c['ratio'] }}" @if ($copy) aria-hidden="true" @endif>
                    <span class="rl-lab rl-lab-top rl-lab-{{ $c['top'][0] }}">{{ $c['top'][1] }}</span>
                    <button type="button" class="rl-ph-btn" data-full="{{ $c['src'] }}" data-cap="{{ $c['cap'] }}"
                      @if ($copy) tabindex="-1" @else aria-label="Open photo {{ $c['n'] }}" @endif>
                      <img src="{{ $c['src'] }}" alt="" loading="lazy" decoding="async" draggable="false">
                    </button>
                    <span class="rl-lab rl-lab-bottom rl-lab-{{ $c['bottom'][0] }}">{{ $c['bottom'][1] }}</span>
                  </figure>
                @endforeach
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
      <p class="rl-reel-note rl-rv">Every frame, still you.<small>{{ $from }} &#9825; {{ $name }}</small></p>
    </section>
  @endif

  {{-- ─────────── paper boats of reasons: love_reasons, one folded boat per line ───────────
       the boats bob on a strip of the river; a tap unfolds one into a small note with its reason (one at a time,
       tap again / anywhere else to fold it back); a boat that's been read keeps a little heart on its sail. --}}
  <section class="rl-sec rl-boats" id="rlReasons">
    <p class="rl-kicker rl-rv">Paper boats</p>
    <h2 class="rl-h2 rl-rv">Reasons I love you, sent down the river</h2>
    {{-- --cols: boats per row on wide screens (up to 7 in one row, more = two even rows) --}}
    <div class="rl-river rl-rv" id="rlRiver" style="--cols: {{ count($reasons) <= 7 ? count($reasons) : (int) ceil(count($reasons) / 2) }}">
      <div class="rl-fleet">
      @foreach ($reasons as $i => $reason)
        <button type="button" class="rl-boat" data-reason="{{ $reason }}" aria-expanded="false" aria-controls="rlNote"
          aria-label="Unfold reason {{ $i + 1 }}"
          style="--bd: {{ 4.2 + ($i * 7 % 5) * .45 }}s; --bdl: -{{ ($i * 3 % 7) * .6 }}s; --by: {{ [0, 18, 6, 22, 2, 14, 10, 20, 4][$i] }}px; --bt: {{ [-4, 3, -2, 5, -3, 2, -5, 4, -1][$i] }}deg">
          <svg viewBox="0 0 120 84" aria-hidden="true">
            <g stroke="rgba(90,62,74,.22)" stroke-width=".8" stroke-linejoin="round">
              <path d="M4 50 L116 50 L97 74 L23 74Z" fill="#fbf9f5"/>
              <path d="M23 74 L60 57 L97 74Z" fill="#ebe5dd"/>
              <path d="M14 50 L36 27 L45 50Z" fill="#f1ede6"/>
              <path d="M106 50 L84 27 L75 50Z" fill="#e2dcd2"/>
              <path d="M60 5 L31 50 L60 50Z" fill="#fff"/>
              <path d="M60 5 L89 50 L60 50Z" fill="#ece6de"/>
            </g>
            <use class="rl-boat-heart" href="#rl-heart" x="52" y="31" width="12" height="12"/>
          </svg>
        </button>
      @endforeach
      </div>
      <div class="rl-note" id="rlNote" hidden>
        <p class="rl-note-k">Reason <b id="rlNoteN">1</b></p>
        <p class="rl-note-t" id="rlNoteT" aria-live="polite"></p>
        <p class="rl-note-s">{{ $from }} &#9825;</p>
      </div>
      {!! $hiddenHeart('boats') !!}
    </div>
    <p class="rl-river-hint rl-rv"><span id="rlBoatHint" data-total="{{ count($reasons) }}">Tap a boat to unfold a reason</span>
      <small>psst… {{ ['one', 'two', 'three', 'four', 'five'][$heartCount - 1] }} tiny {{ $heartCount === 1 ? 'heart is' : 'hearts are' }} hiding on this page</small></p>
  </section>

  {{-- ─────────── our places: the shared memory map, with a river through it ─────────── --}}
  @if ($places)
    <section class="rl-sec rl-map" id="rlMap">
      {!! $hiddenHeart('map') !!}
      <p class="rl-kicker rl-rv">Our places</p>
      <h2 class="rl-h2 rl-rv">Every road led me to you</h2>
      <x-memory-map :places="$places" river :label="mb_strtolower($from.' & '.$name)" photo-class="rl-ph-btn" class="rl-map-box rl-rv" />
      <p class="rl-map-hint rl-rv">Tap a heart to open a memory</p>
    </section>
  @endif

  {{-- ─────────── now playing: the same song as the cover button (river-love.js keeps both in sync) ─────────── --}}
  @if ($hasMusic)
    <section class="rl-sec rl-np-sec" id="rlSong">
      <div class="rl-np rl-rv {{ $musicFile ? '' : 'is-stream' }}" id="rlNp">
        <div class="rl-np-disc" aria-hidden="true"><span><svg><use href="#rl-blossom"/></svg></span></div>
        <div class="rl-np-info">
          <p class="rl-np-k"><span class="rl-eq" aria-hidden="true"><i></i><i></i><i></i></span> Now playing</p>
          <h2 class="rl-np-title">{{ $songTitle }}</h2>
          <p class="rl-np-by">from {{ $from }}, for {{ $name }}</p>
          <div class="rl-np-bar" aria-hidden="true"><i id="rlNpFill"></i></div>
          <p class="rl-np-time" aria-hidden="true"><span id="rlNpCur">0:00</span><span id="rlNpDur">{{ $musicFile ? '–:––' : 'on repeat' }}</span></p>
        </div>
        <button class="rl-np-btn" id="rlNpPlay" type="button" aria-pressed="false" aria-label="Play {{ $songTitle }}">
          <svg class="play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>
          <svg class="pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
        </button>
      </div>
    </section>
  @endif

  {{-- ─────────── together for: a live count-up from together_since, ticking every second (river-love.js) ─────────── --}}
  @if ($since)
    <section class="rl-sec rl-time" id="rlTime">
      <p class="rl-kicker rl-rv">Together since {{ $since->format('j F Y') }}</p>
      <div class="rl-count rl-count-up rl-rv" id="rlTogether" role="timer" aria-live="off">
        <p><b data-u="d">{{ number_format($together['d']) }}</b><span>days</span></p><p><b data-u="h">{{ $together['h'] }}</b><span>hours</span></p>
        <p><b data-u="m">{{ $together['m'] }}</b><span>minutes</span></p><p><b data-u="s">{{ $together['s'] }}</b><span>seconds</span></p>
      </div>
      <p class="rl-time-note rl-rv">of loving you, and counting</p>
    </section>
  @endif

  {{-- ─────────── ending ─────────── --}}
  {{-- the guest sends a paper lantern up from the river (river-love.js); then closing_line + thanks_message show.
       Without the script the words are simply there. --}}
  <section class="rl-sec rl-end" id="rlEnd">
    <svg class="rl-end-flower rl-rv" aria-hidden="true"><use href="#rl-blossom"/></svg>
    <div class="rl-wish rl-rv">
      <p class="rl-kicker">One last thing</p>
      <p class="rl-wish-q">Make a wish with me</p>
      <button class="rl-btn" id="rlWishBtn" type="button"><span id="rlWishTxt">Send a lantern</span> <svg aria-hidden="true"><use href="#rl-heart"/></svg></button>
    </div>
    <div class="rl-lanterns" id="rlLanterns" aria-hidden="true"></div>
    <div class="rl-end-msg" id="rlEndMsg">
      <p class="rl-closing">{{ $closing }}</p>
      <p class="rl-thanks">{{ $thanks }}</p>
    </div>
    <template id="rlLanternTpl">
      <div class="rl-lantern"><svg viewBox="0 0 60 84" aria-hidden="true">
        <defs><linearGradient id="rlLanternG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff4dc"/><stop offset=".6" stop-color="#ffd596"/><stop offset="1" stop-color="#f3a35c"/></linearGradient></defs>
        <path d="M7 10 Q30 1 53 10 L46 64 Q30 70 14 64Z" fill="url(#rlLanternG)"/>
        <path d="M22 5.5 L19 66 M38 5.5 L41 66" fill="none" stroke="rgba(170,96,40,.22)" stroke-width="1"/>
        <ellipse cx="30" cy="65" rx="16" ry="3.6" fill="#b8642c" opacity=".55"/>
        <ellipse cx="30" cy="72" rx="3.2" ry="5" fill="#ffe7a6"/>
      </svg></div>
    </template>
  </section>

  <x-invitation-footer :names="$from.' ♡ '.$name" :date="$date?->format('d · m · Y')" love="Made with love"
    style="--inv-footer-bg: linear-gradient(180deg, transparent, rgba(30,16,24,.6) 45%, rgba(30,16,24,.72)); --inv-footer-text: #fbeef2; --inv-footer-muted: #d9b9c4; --inv-footer-accent: {{ $accent }}; --inv-footer-ring: rgba(251,238,242,.35); --inv-footer-names-font: 'Instrument Serif', 'Kantumruy Pro', serif;">
    <x-slot:ornament><svg aria-hidden="true"><use href="#rl-blossom"/></svg></x-slot:ornament>
  </x-invitation-footer>

  {{-- sound on/off: birdsong starts on the first tap; ducks under the song --}}
  <button class="rl-sound" id="rlSound" type="button" aria-pressed="false" aria-label="Mute sound">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path class="on" d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path class="off" d="M16.5 9.5l5 5m0-5l-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
  </button>

  {{-- hidden hearts hunt: counter (after the first find), a short toast, and the secret card when all are found --}}
  <p class="rl-hunt" id="rlHunt" hidden aria-live="polite"><svg aria-hidden="true"><use href="#rl-heart"/></svg> <b id="rlHuntN">0</b>/{{ $heartCount }}</p>
  <p class="rl-toast" id="rlToast" role="status" hidden></p>
  <div class="rl-secret" id="rlSecret" role="dialog" aria-modal="true" aria-labelledby="rlSecretT" hidden>
    <div class="rl-secret-card">
      <svg class="rl-secret-heart" aria-hidden="true"><use href="#rl-heart"/></svg>
      <p class="rl-secret-k">A little secret</p>
      <h2 class="rl-secret-t" id="rlSecretT">You found every heart, just like you found mine.</h2>
      <p class="rl-secret-by">from {{ $from }}, for {{ $name }}</p>
      <button type="button" class="rl-btn rl-secret-x" id="rlSecretX">Keep it <svg aria-hidden="true"><use href="#rl-heart"/></svg></button>
    </div>
  </div>

  {{-- photo preview --}}
  <div class="rl-lb" id="rlLb" role="dialog" aria-modal="true" aria-label="Photo preview" hidden>
    <figure><img id="rlLbImg" alt=""><figcaption id="rlLbCap"></figcaption></figure>
    <button type="button" class="rl-lb-x" id="rlLbX" aria-label="Close">&#10005;</button>
  </div>

  @if ($musicFile)<audio id="rlAudio" src="{{ $musicFile }}" preload="auto" loop></audio>@endif
</div>

<script id="rl-config" type="application/json">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="{{ $rlAsset('js/invitations/river-love.js') }}"></script>
