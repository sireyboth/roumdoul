@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // River Love — romantic anniversary / love page.
    // Cover: a full-screen looping video of a blossom lake (cropped, never shrunk, on phones),
    // the words in the middle rise softly out of a blur, and petals and leaves fall forever over the
    // whole page. Below: the letter → our photos → together since (or a countdown) → closing line.
    //
    // Everything personal comes from $fields (catalog keys, docs/invitation-templates.md):
    //   recipient name   → «For …» on the cover and «Dear …» on the letter (the guest link's name)
    //   sender_name      → signature + top bar   headline → the big cover line (« | » = line break)
    //   message          → the letter (blank line = new paragraph); its first sentence is the cover subline
    //   event_date       → past = «together since» + days count; future = countdown (premium countdown_enabled)
    //   photo_gallery + photo_captions → the photo reels (caption «Title | small line» = the card's film labels)
    //   cover_image      → first photo of the photos section (default: gallery photo 1)
    //   background_image → replaces the cover video with the customer's own photo
    //   music_file / music_url + song_title → «Play our song» on the cover
    //   closing_line + thanks_message → the ending      accent_color → petals, buttons, hearts
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
    if (! empty($fields['cover_image']) && $isUnlocked('cover_image')) {
        $photos = $photos->prepend($resolveUrl($fields['cover_image']))->unique()->values();
    }
    $captions = $lines('photo_captions');
    $gallery = $photos->take(18)->map(function ($src, $i) use ($captions) {
        [$title, $sub] = array_pad(array_map('trim', explode('|', $captions[$i] ?? '', 2)), 2, '');

        return ['src' => $src, 'title' => $title, 'sub' => $sub];
    })->all();

    // together since (past date) or a countdown to the day (future date)
    $date = null;
    if ($isUnlocked('event_date') && $text('event_date') !== '') {
        try {
            $date = Carbon::parse($text('event_date'));
        } catch (\Throwable) {
            $date = null;
        }
    }
    $isPast = $date?->isPast() ?? false;
    $days = $date && $isPast ? (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    $showCountdown = $date && ! $isPast && $isUnlocked('countdown_enabled') && ! empty($fields['countdown_enabled'] ?? true);

    // an uploaded song wins over a link; nothing downloads until «Play our song» is tapped
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

    $bgPhoto = ! empty($fields['background_image']) && $isUnlocked('background_image') ? $resolveUrl($fields['background_image']) : null;
    $video = 'images/invitations/assets/videos/river-love-loop.mp4';
    $poster = 'images/invitations/assets/videos/river-love-poster.jpg';
    $videoPortrait = 'images/invitations/assets/videos/river-love-portrait-loop.mp4';
    $posterPortrait = 'images/invitations/assets/videos/river-love-portrait-poster.jpg';

    $closing = $text('closing_line') ?: 'I love you, always.';
    $thanks = $text('thanks_message') ?: 'Today, tomorrow, and on every ordinary day in between.';

    $config = [
        'music' => ['file' => $musicFile, 'youtube' => $youtubeId],
        'countdown' => $showCountdown ? $date->toIso8601String() : null,
        // template ambience: a seamless 40 s birdsong loop (made with ffmpeg from soundreality-birds-forest-nature-445379.mp3)
        'birds' => $rlAsset('images/invitations/assets/sfx/birds-loop.mp3'),
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
    @if ($bgPhoto)
      <img class="rl-photo" src="{{ $bgPhoto }}" alt="">
    @else
      {{-- portrait screens (phones) get the portrait cut; river-love.js swaps it when the phone is rotated --}}
      <video class="rl-video" id="rlVideo" autoplay muted loop playsinline preload="auto" disablepictureinpicture
        poster="{{ $rlAsset($poster) }}"
        data-landscape="{{ $rlAsset($video) }}" data-landscape-poster="{{ $rlAsset($poster) }}"
        data-portrait="{{ $rlAsset($videoPortrait) }}" data-portrait-poster="{{ $rlAsset($posterPortrait) }}">
        <source src="{{ $rlAsset($videoPortrait) }}" type="video/mp4" media="(orientation: portrait)">
        <source src="{{ $rlAsset($video) }}" type="video/mp4">
      </video>
    @endif
    <div class="rl-shade"></div>
  </div>

  {{-- ─────────── cover ─────────── --}}
  <section class="rl-cover" id="rlCover">

    <header class="rl-bar">
      <span class="rl-logo"><svg aria-hidden="true"><use href="#rl-blossom"/></svg></span>
      <nav class="rl-nav" aria-label="Sections">
        <a href="#rlLetter">Letter</a>
        @if ($gallery)<a href="#rlPhotos">Moments</a>@endif
        @if ($date)<a href="#rlTime">{{ $isPast ? 'Us' : 'Our day' }}</a>@endif
      </nav>
      <a class="rl-chip" href="#rlLetter">For {{ $name }} <svg aria-hidden="true"><use href="#rl-heart"/></svg></a>
    </header>

    <div class="rl-center">
      <p class="rl-pill rl-in" style="--d:.2s">
        <b>{{ $isPast ? 'Since '.$date->format('Y') : ($date ? $date->format('j M Y') : 'With love') }}</b>
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
      $prints = $photos->take(3)->values()->all();
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

            return [
                'src' => $g['src'], 'cap' => $g['title'], 'n' => $i + 1,
                'ratio' => $ratios[($k + $r * 2) % count($ratios)],
                'top' => ($k + $r) % 2 ? ['code', '▶ '.$reelCode] : ['name', $title],
                'bottom' => ($k + $r) % 2 ? ['tag', $g['sub'] !== '' ? mb_strtoupper($g['sub']) : $reelTags[($k + $r) % count($reelTags)]] : ['num', $reelNum.' ▶'],
            ];
        })->all())->all();
    @endphp
    <section class="rl-reel" id="rlPhotos">
      <p class="rl-reel-ghost" aria-hidden="true">{{ $name }}</p>
      <header class="rl-reel-head">
        <p class="rl-kicker rl-rv">Little pieces of us</p>
        <h2 class="rl-reel-title rl-rv">Moments<span>I keep</span></h2>
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

  {{-- ─────────── together since / countdown ─────────── --}}
  @if ($date)
    <section class="rl-sec rl-time" id="rlTime">
      @if ($isPast)
        <p class="rl-kicker rl-rv">Together since {{ $date->format('j F Y') }}</p>
        <p class="rl-days rl-rv"><b data-count="{{ $days }}">{{ number_format($days) }}</b><span>days of loving you</span></p>
      @elseif ($showCountdown)
        <p class="rl-kicker rl-rv">Until {{ $date->format('l, j F Y') }}</p>
        <div class="rl-count rl-rv" id="rlCount">
          <p><b data-u="d">0</b><span>days</span></p><p><b data-u="h">0</b><span>hours</span></p>
          <p><b data-u="m">0</b><span>minutes</span></p><p><b data-u="s">0</b><span>seconds</span></p>
        </div>
      @else
        <p class="rl-kicker rl-rv">Save the date</p>
        <p class="rl-days rl-rv"><b>{{ $date->format('j F') }}</b><span>{{ $date->format('l · Y') }}</span></p>
      @endif
    </section>
  @endif

  {{-- ─────────── ending ─────────── --}}
  <section class="rl-sec rl-end">
    <svg class="rl-end-flower rl-rv" aria-hidden="true"><use href="#rl-blossom"/></svg>
    <p class="rl-closing rl-rv">{{ $closing }}</p>
    <p class="rl-thanks rl-rv">{{ $thanks }}</p>
  </section>

  <x-invitation-footer :names="$from.' ♡ '.$name" :date="$date?->format('d · m · Y')" love="Made with love"
    style="--inv-footer-bg: linear-gradient(180deg, transparent, rgba(30,16,24,.6) 45%, rgba(30,16,24,.72)); --inv-footer-text: #fbeef2; --inv-footer-muted: #d9b9c4; --inv-footer-accent: {{ $accent }}; --inv-footer-ring: rgba(251,238,242,.35); --inv-footer-names-font: 'Instrument Serif', 'Kantumruy Pro', serif;">
    <x-slot:ornament><svg aria-hidden="true"><use href="#rl-blossom"/></svg></x-slot:ornament>
  </x-invitation-footer>

  {{-- sound on/off: birdsong starts on the first tap; ducks under the song --}}
  <button class="rl-sound" id="rlSound" type="button" aria-pressed="false" aria-label="Mute sound">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path class="on" d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path class="off" d="M16.5 9.5l5 5m0-5l-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
  </button>

  {{-- photo preview --}}
  <div class="rl-lb" id="rlLb" role="dialog" aria-modal="true" aria-label="Photo preview" hidden>
    <figure><img id="rlLbImg" alt=""><figcaption id="rlLbCap"></figcaption></figure>
    <button type="button" class="rl-lb-x" id="rlLbX" aria-label="Close">&#10005;</button>
  </div>

  @if ($musicFile)<audio id="rlAudio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
</div>

<script id="rl-config" type="application/json">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="{{ $rlAsset('js/invitations/river-love.js') }}"></script>
