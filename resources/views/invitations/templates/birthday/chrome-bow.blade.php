{{--
  Chrome Bow — modern birthday invitation (prefix `cb`). Pinterest 2026 party look: pearl + bubblegum, puffy chrome
  balloon numerals, coquette satin bows, a clear bubble balloon, holographic ticket, a Lambeth-piped cake.
  CSS public/css/invitations/chrome-bow.css · JS public/js/invitations/chrome-bow.js · demo /demo/birthday/chrome-bow
  (?open=1 skips the cover). Field prep comes from WeddingData (dates, venue, gallery, RSVP, gift, notes…); the birthday
  layer (celebrant_name, turning_age, sender_name as host) is added here. Every section hides when its field is empty.
--}}
@php
    use App\Support\Invitations\WeddingData as W;

    $w = W::from($fields, $invitation, $recipient ?? null, [
        'accent' => '#ff4fa0',
        'headline' => 'Happy Birthday',
        'closing_line' => "Let's party!",
        'reception_message' => 'សូមគោរពអញ្ជើញមកចូលរួមអបអរខួបកំណើតដ៏រីករាយ ជប់លៀង សើចសប្បាយ និងបង្កើតអនុស្សាវរីយ៍ថ្មីៗជាមួយគ្នា។ វត្តមានរបស់អ្នក គឺជាកាដូដ៏ល្អបំផុត!',
        'rsvp_message' => 'តើអ្នកនឹងមកចូលរួមជប់លៀងជាមួយគ្នាដែរឬទេ?',
        'gift_message' => 'វត្តមានរបស់អ្នកគឺជាកាដូដ៏ល្អបំផុតហើយ។ បើចង់ផ្ញើកាដូពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។',
    ]);
    $kh = fn (?string $s) => W::isKhmer($s) ? ' ik-kh' : '';
    $guest = ! empty($recipientName) ? $recipientName : 'ភ្ញៀវកិត្តិយស';
    $skipCover = request()->boolean('open');
    $asset = fn (string $f) => asset($f).'?v='.@filemtime(public_path($f));

    // birthday layer
    $host = W::pretty($w->field('sender_name'));
    $name = W::pretty($w->field('celebrant_name')) ?: ($host ?: 'Birthday Star');
    $nameKh = W::isKhmer($name);
    $ageRaw = $w->field('turning_age');
    $ageAscii = strtr($ageRaw, array_flip(['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩']));
    $ageDigits = preg_replace('/\D/', '', $ageAscii);
    $ageShow = $ageDigits !== '' ? mb_substr($ageDigits, 0, 3) : '';
    $candles = $ageShow !== '' ? str_split($ageShow) : ['|', '|', '|'];
    $contactPhone = $w->field('contact_phone');

    // colours: the accent and its lighter / darker satin shades
    $mix = function (string $hex, float $t): string {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $to = $t >= 0 ? 255 : 0;
        $t = abs($t);

        return sprintf('#%02x%02x%02x', $r + ($to - $r) * $t, $g + ($to - $g) * $t, $b + ($to - $b) * $t);
    };
    $acc = $w->accent;
    $accL = $mix($acc, .55);
    $accXL = $mix($acc, .85);
    $accD = $mix($acc, -.3);

    // barcode on the ticket, drawn from the date digits
    $bars = collect(str_split(preg_replace('/\D/', '', ($w->dateNumeric ?? '').$ageShow.'2026') ?: '1234567890'))
        ->flatMap(fn ($d) => [1 + (int) $d % 3, 1 + ((int) $d + 1) % 2])->take(36)->all();

    // section decorations: chrome stars, sparkles and bows peeking in from the edges, moving with scroll
    $spots = [
        [['star', 'left:-14px;top:24px;width:58px', .12], ['spark', 'right:18px;top:40px;width:22px', -.08], ['bow', 'right:-10px;bottom:30px;width:64px;--rot:14deg', .1]],
        [['spark', 'left:20px;top:18px;width:18px', .06], ['star', 'right:-18px;top:46%;width:70px', -.12], ['heart', 'left:-8px;bottom:18%;width:42px', .14]],
        [['bow', 'left:-12px;top:30px;width:60px;--rot:-16deg', -.1], ['spark', 'right:26px;bottom:40px;width:26px', .1], ['star', 'left:12%;bottom:-10px;width:40px', .06]],
        [['heart', 'right:-6px;top:22px;width:46px', .1], ['star', 'left:-20px;top:52%;width:62px', -.08], ['spark', 'right:30%;bottom:16px;width:16px', .12]],
    ];
    $decor = function (int $i) use ($spots): string {
        $html = '';
        foreach ($spots[$i % count($spots)] as [$kind, $style, $depth]) {
            $html .= '<svg class="cb-deco cb-deco-'.$kind.'" style="'.$style.'" data-depth="'.$depth.'" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-'.$kind.'"/></svg>';
        }

        return $html;
    };
    $sec = 0;

    // Lambeth piping on the cake: beads walked along an ellipse
    $beads = function (float $cx, float $cy, float $rx, float $ry, int $n, float $from, float $to, float $r) {
        $out = '';
        for ($i = 0; $i <= $n; $i++) {
            $t = $from + ($to - $from) * $i / $n;
            $out .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f"/>', $cx + $rx * cos($t), $cy + $ry * sin($t), $r);
        }

        return $out;
    };
    $swags = function (float $x1, float $x2, float $y, int $n, float $dip) {
        $out = '';
        $step = ($x2 - $x1) / $n;
        for ($i = 0; $i < $n; $i++) {
            $a = $x1 + $step * $i;
            $out .= sprintf('<path d="M%.1f %.1f Q%.1f %.1f %.1f %.1f"/>', $a, $y, $a + $step / 2, $y + $dip, $a + $step, $y);
        }

        return $out;
    };
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Serif:ital@0;1&family=Koulen&family=Noto+Serif+Khmer:wght@400;600&family=Kantumruy+Pro:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ $asset('css/invitations/chrome-bow.css') }}">

<div class="cb{{ $skipCover ? ' is-open' : '' }}" style="--cb-accent:{{ $acc }}; --cb-accent-l:{{ $accL }}; --cb-accent-xl:{{ $accXL }}; --cb-accent-d:{{ $accD }}; --ik-accent:{{ $acc }};">

  {{-- shared drawings (<g> in defs, not <symbol>: a sizeless symbol <use> scales to the whole drawing) --}}
  <svg class="cb-defs" aria-hidden="true" focusable="false">
    <defs>
      <linearGradient id="cb-chrome" x1="0" y1="0" x2=".35" y2="1">
        <stop offset="0" stop-color="#ffffff"/><stop offset=".2" stop-color="#cfd2db"/><stop offset=".38" stop-color="#868c9b"/>
        <stop offset=".52" stop-color="#f6f7fa"/><stop offset=".68" stop-color="#a9aebb"/><stop offset=".84" stop-color="#ffffff"/><stop offset="1" stop-color="#9aa0ad"/>
      </linearGradient>
      <linearGradient id="cb-chrome-pink" x1="0" y1="0" x2=".3" y2="1">
        <stop offset="0" stop-color="#ffffff"/><stop offset=".18" stop-color="{{ $accXL }}"/><stop offset=".4" stop-color="{{ $acc }}"/>
        <stop offset=".55" stop-color="{{ $accXL }}"/><stop offset=".7" stop-color="{{ $accL }}"/><stop offset=".86" stop-color="{{ $accD }}"/><stop offset="1" stop-color="{{ $accL }}"/>
      </linearGradient>
      <linearGradient id="cb-satin" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="{{ $accL }}"/><stop offset=".45" stop-color="{{ $acc }}"/><stop offset=".6" stop-color="{{ $accL }}"/><stop offset="1" stop-color="{{ $accD }}"/>
      </linearGradient>
      {{-- puffy foil: the shape's own alpha, blurred, lit from top-left → an inflated balloon highlight --}}
      <filter id="cb-puff" x="-15%" y="-15%" width="130%" height="130%" color-interpolation-filters="sRGB">
        <feGaussianBlur in="SourceAlpha" stdDeviation="5" result="b"/>
        <feSpecularLighting in="b" surfaceScale="7" specularConstant="1.15" specularExponent="24" lighting-color="#ffffff" result="s">
          <fePointLight x="-250" y="-350" z="420"/>
        </feSpecularLighting>
        <feComposite in="s" in2="SourceAlpha" operator="in" result="sp"/>
        <feComposite in="SourceGraphic" in2="sp" operator="arithmetic" k1="0" k2="1" k3=".9" k4="0" result="lit"/>
        <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#5a2a45" flood-opacity=".28"/>
      </filter>
      <g id="cb-star"><path d="M50 2C54 34 66 46 98 50C66 54 54 66 50 98C46 66 34 54 2 50C34 46 46 34 50 2Z" fill="url(#cb-chrome)" filter="url(#cb-puff)"/></g>
      <g id="cb-spark"><path d="M50 0C52 40 60 48 100 50C60 52 52 60 50 100C48 60 40 52 0 50C40 48 48 40 50 0Z" fill="{{ $acc }}"/></g>
      <g id="cb-heart"><path d="M50 88C20 66 6 50 8 32C10 16 24 8 36 10C44 11 48 17 50 22C52 17 56 11 64 10C76 8 90 16 92 32C94 50 80 66 50 88Z" fill="url(#cb-chrome-pink)" filter="url(#cb-puff)"/></g>
      <g id="cb-bow">
        <path d="M48 46C42 62 34 76 26 92L37 87L42 97C47 80 51 63 53 48Z" fill="url(#cb-satin)"/>
        <path d="M52 46C58 62 66 76 74 92L63 87L58 97C53 80 49 63 47 48Z" fill="url(#cb-satin)"/>
        <path d="M50 42C38 20 10 14 7 32C4 50 30 54 50 46Z" fill="url(#cb-satin)"/>
        <path d="M50 42C62 20 90 14 93 32C96 50 70 54 50 46Z" fill="url(#cb-satin)"/>
        <path d="M44 40C34 28 18 26 16 33" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2.4" stroke-linecap="round"/>
        <path d="M56 40C66 28 82 26 84 33" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.4" stroke-linecap="round"/>
        <ellipse cx="50" cy="44" rx="8" ry="9" fill="{{ $acc }}"/><ellipse cx="48" cy="41" rx="3" ry="3.5" fill="#fff" fill-opacity=".5"/>
      </g>
    </defs>
  </svg>

  {{-- page background: pearl, drifting iridescent blobs, a faint checkerboard, twinkles --}}
  <div class="cb-bg" aria-hidden="true">
    <i class="cb-blob b1"></i><i class="cb-blob b2"></i><i class="cb-blob b3"></i>
    @foreach ([[8, 14], [88, 9], [16, 46], [92, 38], [6, 78], [84, 72], [50, 92]] as $k => [$x, $y])
      <svg class="cb-twinkle" style="left:{{ $x }}%;top:{{ $y }}%;--d:{{ $k * .7 }}s" viewBox="0 0 100 100"><use href="#cb-spark"/></svg>
    @endforeach
  </div>

  {{-- wide screens: balloon clusters + chrome stars in the side space --}}
  <div class="cb-side l" aria-hidden="true">
    <span class="cb-gloss" style="--c:{{ $acc }};left:40px;top:12vh"></span><span class="cb-gloss" style="--c:#c9b6ff;left:110px;top:6vh;--s:.8"></span><span class="cb-gloss" style="--c:#d9dce3;left:70px;top:22vh;--s:.7"></span>
    <svg viewBox="0 0 100 100" style="left:60px;top:58vh;width:90px"><use href="#cb-star"/></svg>
    <svg viewBox="0 0 100 100" style="left:150px;top:74vh;width:70px;--rot:-12deg"><use href="#cb-bow"/></svg>
  </div>
  <div class="cb-side r" aria-hidden="true">
    <span class="cb-gloss" style="--c:#d9dce3;right:50px;top:50vh"></span><span class="cb-gloss" style="--c:{{ $accL }};right:120px;top:44vh;--s:.85"></span><span class="cb-gloss" style="--c:{{ $acc }};right:84px;top:60vh;--s:.7"></span>
    <svg viewBox="0 0 100 100" style="right:70px;top:10vh;width:110px"><use href="#cb-heart"/></svg>
    <svg viewBox="0 0 100 100" style="right:160px;top:24vh;width:60px"><use href="#cb-star"/></svg>
  </div>

  {{-- ===== cover: a party scene — pop the balloon bouquet to open ===== --}}
  @unless ($skipCover)
    @php
        // bunting rope: a quadratic curve across the top (viewBox 400×80); flags hang along it, the disco ball from its middle
        $rope = fn (float $t) => [(1 - $t) ** 2 * -10 + 2 * (1 - $t) * $t * 200 + $t ** 2 * 410, (1 - $t) ** 2 * 6 + 2 * (1 - $t) * $t * 60 + $t ** 2 * 6];
        $ropeAngle = fn (float $t) => rad2deg(atan2(2 * (1 - $t) * 54 - 2 * $t * 54, 2 * (1 - $t) * 210 + 2 * $t * 210));
        $flagColours = [$acc, '#c9b6ff', '#1c1622', '#ffd36e', $accL, '#9fd8f0'];
        $flags = [['P', .06], ['A', .13], ['R', .2], ['T', .27], ['Y', .34], ['T', .66], ['I', .73], ['M', .8], ['E', .87], ['!', .94]];
        // the bouquet: x / y = balloon centre, s = width, all in % of the bouquet box
        $bouquet = [
            ['c' => $acc, 'x' => 13, 'y' => 22, 's' => 27], ['c' => '#c9b6ff', 'x' => 87, 'y' => 18, 's' => 25],
            ['c' => '#d9dce3', 'x' => 6, 'y' => 62, 's' => 23], ['c' => '#ffd36e', 'x' => 94, 'y' => 58, 's' => 25],
            ['c' => $accL, 'x' => 33, 'y' => 1, 's' => 21], ['c' => '#9fd8f0', 'x' => 68, 'y' => -1, 's' => 22],
        ];
        $popWords = ['មក', 'ជប់លៀង', 'ជាមួយ', 'គ្នា', 'ណា!', '🎉'];
    @endphp
    <div class="cb-cover" id="cb-cover" role="dialog" aria-modal="true" aria-label="{{ 'Birthday invitation · '.$name }}" data-words='@json($popWords)'>
      <div class="cb-cover-bg" aria-hidden="true"></div>
      <div class="cb-lights" aria-hidden="true"></div>
      {{-- wide screens: balloon bunches + chrome stars in the side space of the cover --}}
      <div class="cb-cover-side l" aria-hidden="true">
        <span class="cb-gloss" style="--c:{{ $acc }};left:9vw;top:30vh"></span><span class="cb-gloss" style="--c:#c9b6ff;left:15vw;top:22vh;--s:.8"></span><span class="cb-gloss" style="--c:#ffd36e;left:5vw;top:42vh;--s:.75"></span>
        <svg viewBox="0 0 100 100" style="left:18vw;top:62vh;width:70px"><use href="#cb-star"/></svg>
        <svg viewBox="0 0 100 100" style="left:6vw;top:14vh;width:40px"><use href="#cb-spark"/></svg>
      </div>
      <div class="cb-cover-side r" aria-hidden="true">
        <span class="cb-gloss" style="--c:#9fd8f0;right:10vw;top:26vh"></span><span class="cb-gloss" style="--c:{{ $accL }};right:16vw;top:36vh;--s:.85"></span><span class="cb-gloss" style="--c:#d9dce3;right:5vw;top:40vh;--s:.7"></span>
        <svg viewBox="0 0 100 100" style="right:17vw;top:12vh;width:90px"><use href="#cb-heart"/></svg>
        <svg viewBox="0 0 100 100" style="right:8vw;top:70vh;width:56px"><use href="#cb-star"/></svg>
      </div>

      <div class="cb-bunting" aria-hidden="true">
        <svg viewBox="0 0 400 80">
          <path d="M-10 6Q200 60 410 6" fill="none" stroke="#b8bcc8" stroke-width="1.4"/>
          @foreach ($flags as $k => [$letter, $t])
            @php([$fx, $fy] = $rope($t))
            <g transform="translate({{ round($fx, 1) }} {{ round($fy, 1) }}) rotate({{ round($ropeAngle($t), 1) }})">
              <g class="cb-flag" style="--i:{{ $k }}">
                <path d="M-14 0H14L0 32Z" fill="{{ $flagColours[$k % count($flagColours)] }}"/>
                <path d="M-14 0H14L12.4 3.5H-12.4Z" fill="#fff" fill-opacity=".4"/>
                <text x="0" y="14" text-anchor="middle" font-size="13" fill="{{ $k % count($flagColours) === 3 || $k % count($flagColours) === 5 ? '#1c1622' : '#fff' }}">{{ $letter }}</text>
              </g>
            </g>
          @endforeach
        </svg>
      </div>
      <div class="cb-disco" aria-hidden="true"><div class="cb-disco-in">
        <i class="cb-disco-line"></i><span class="cb-disco-ball"></span>
        <svg class="cb-glint g1" viewBox="0 0 100 100"><use href="#cb-spark"/></svg><svg class="cb-glint g2" viewBox="0 0 100 100"><use href="#cb-spark"/></svg>
      </div></div>

      <div class="cb-cover-in">
        <p class="cb-psst cb-cv">Psst… <b class="{{ trim($kh($guest)) }}">{{ $guest }}</b>! <span aria-hidden="true">👀</span><small>អ្នកមានការអញ្ជើញពិសេសមួយ!</small></p>

        <div class="cb-bouquet cb-cv" id="cb-bouquet">
          <svg class="cb-strings" viewBox="0 0 100 100" aria-hidden="true">
            @foreach ($bouquet as $i => $b)
              <path data-str="{{ $i }}" d="M{{ $b['x'] }} {{ round($b['y'] + $b['s'] * .6, 1) }}Q{{ round($b['x'] * .5 + 25, 1) }} {{ round($b['y'] + 52, 1) }} 50 112"/>
            @endforeach
            <path d="M50 75Q47 95 50 112"/>
          </svg>
          @foreach ($bouquet as $i => $b)
            <button type="button" class="cb-pb" data-i="{{ $i }}" style="--c:{{ $b['c'] }};--x:{{ $b['x'] }};--y:{{ $b['y'] }};--s:{{ $b['s'] }};--i:{{ $i }}" aria-label="ប៉ះបំបែកប៉េងប៉ោង"><i></i></button>
          @endforeach
          <button type="button" class="cb-bubble" id="cb-bubble" aria-label="បើកសំបុត្រ · Open">
            <span class="cb-bubble-photo" id="cb-cover-photo">@if ($w->cover)<img src="{{ $w->cover }}" alt="{{ $name }}">@endif</span>
            <span class="cb-bubble-glass" aria-hidden="true"></span>
          </button>
          @if ($ageShow !== '')
            <svg class="cb-age cb-age-cover" viewBox="0 0 400 260" aria-hidden="true"><text x="200" y="150" text-anchor="middle" dominant-baseline="middle" font-size="{{ strlen($ageShow) > 2 ? 180 : 250 }}" fill="url(#cb-chrome-pink)" filter="url(#cb-puff)">{{ $ageShow }}</text></svg>
          @endif
          <svg class="cb-knot" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-bow"/></svg>
        </div>

        <p class="cb-words cb-cv" id="cb-words" aria-live="polite">@foreach ($popWords as $k => $word)<span class="cb-word" style="--r:{{ [-4, 3, -2, 4, -3, 5][$k] }}deg"></span>@endforeach</p>
        <p class="cb-eyebrow cb-cv{{ $kh($w->headline) }}">{{ $w->headline }}</p>
        @if ($w->titleImage)
          <img class="cb-title-img cb-cv" src="{{ $w->titleImage }}" alt="{{ $name }}">
        @else
          <h1 class="cb-cover-name cb-cv{{ $nameKh ? ' cb-kh' : '' }}">{{ $name }}</h1>
        @endif
        <button type="button" class="cb-btn cb-cv" id="cb-open"><span aria-hidden="true">🎈</span> បំបែកប៉េងប៉ោងទាំងអស់!</button>
        <p class="cb-hint cb-cv">ឬប៉ះម្ដងមួយៗ · <span class="en">tap the balloons!</span></p>
      </div>
    </div>
  @endunless

  <main class="cb-page">
    {{-- ===== hero ===== --}}
    <section class="cb-hero">
      <p class="cb-pill ik-r{{ $kh($w->headline) }}" data-ik="pop">{{ $w->headline }}</p>
      <div class="cb-hero-stage" id="cb-hero-stage">
        @if ($ageShow !== '')
          <svg class="cb-age cb-age-hero" id="cb-age" viewBox="0 0 400 260" role="img" aria-label="{{ $ageShow }}"><text x="200" y="150" text-anchor="middle" dominant-baseline="middle" font-size="{{ strlen($ageShow) > 2 ? 180 : 250 }}" fill="url(#cb-chrome-pink)" filter="url(#cb-puff)">{{ $ageShow }}</text></svg>
        @else
          <svg class="cb-age cb-age-hero" id="cb-age" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-star"/></svg>
        @endif
        <div class="cb-hero-photo" id="cb-hero-photo">@if ($w->cover)<img src="{{ $w->cover }}" alt="{{ $name }}">@endif</div>
        <svg class="cb-hero-bow" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-bow"/></svg>
        <svg class="cb-deco" style="left:2%;top:58%;width:44px" data-depth="-.1" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-star"/></svg>
        <svg class="cb-deco" style="right:4%;top:8%;width:26px" data-depth=".08" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-spark"/></svg>
      </div>
      @if ($w->titleImage)
        <img class="cb-title-img ik-r" data-ik="up" src="{{ $w->titleImage }}" alt="{{ $name }}">
      @else
        <h1 class="cb-name ik-r{{ $nameKh ? ' cb-kh' : '' }}" data-ik="up">{{ $name }}</h1>
      @endif
      @if ($ageShow !== '')
        <p class="cb-turn ik-r" data-ik="up"><span class="en">turning {{ $ageShow }}</span> · អាយុគម្រប់ {{ W::khDigits($ageShow) }} ឆ្នាំ</p>
      @endif
      @if ($w->dateKhmer)<p class="cb-date-pill ik-r" data-ik="up">{{ $w->dateKhmer }}</p>@endif
      <span class="cb-scroll" aria-hidden="true"><i></i></span>
    </section>

    <div class="cb-marquee" aria-hidden="true"><div class="cb-marquee-track" data-marquee="1">
      @for ($i = 0; $i < 6; $i++)<span>HAPPY BIRTHDAY</span><b>✦</b><span class="{{ $nameKh ? 'cb-kh' : '' }}">{{ $name }}</span><b>✦</b>@if ($ageShow !== '')<span>{{ $ageShow }}</span><b>✦</b>@endif @endfor
    </div></div>

    {{-- ===== the invitation: a holographic party ticket ===== --}}
    <section class="cb-sec">{!! $decor($sec++) !!}
      <h2 class="cb-h ik-r" data-ik="up"><span class="en">You're invited</span><span class="kh">លិខិតអញ្ជើញ</span></h2>
      <div class="cb-ticket-wrap ik-r" data-ik="fold">
        <div class="cb-ticket" id="cb-ticket" tabindex="0" role="group" aria-label="សំបុត្រអញ្ជើញ">
          <i class="cb-holo" aria-hidden="true"></i>
          <div class="cb-ticket-top">
            <p class="cb-ticket-kicker"><span>ADMIT ONE</span><span>No. {{ $ageShow !== '' ? str_pad($ageShow, 3, '0', STR_PAD_LEFT) : '001' }}</span></p>
            <p class="cb-soft">ជូនចំពោះ</p>
            <p class="cb-guest{{ $kh($guest) }}">{{ $guest }}</p>
            <p class="cb-recep ik-r" data-ik="words">{{ $w->receptionText }}</p>
            <p class="cb-for">ក្នុងពិធីខួបកំណើតរបស់ <b class="{{ $nameKh ? 'cb-kh' : 'en' }}">{{ $name }}</b></p>
          </div>
          <div class="cb-ticket-bot">
            @if ($w->dateKhmer)
              <div class="cb-tk-row"><span class="cb-tk-l">ថ្ងៃ</span><span>{{ $w->dateKhmer }}</span></div>
              <div class="cb-tk-row"><span class="cb-tk-l">ម៉ោង</span><span>{{ $w->timeKhmer }}</span></div>
            @endif
            @if ($w->khmerDate)<div class="cb-tk-row"><span class="cb-tk-l">ចន្ទគតិ</span><span>{{ $w->khmerDate }}</span></div>@endif
            @if ($w->venueName)<div class="cb-tk-row"><span class="cb-tk-l">ទីកន្លែង</span><span class="{{ trim($kh($w->venueName)) }}">{{ $w->venueName }}</span></div>@endif
            @if ($host !== '' && $host !== $name)<div class="cb-tk-row"><span class="cb-tk-l">រៀបចំដោយ</span><span class="{{ trim($kh($host)) }}">{{ $host }}</span></div>@endif
            <div class="cb-barcode" aria-hidden="true">@foreach ($bars as $k => $b)<i style="width:{{ $b }}px;{{ $k % 2 ? 'background:transparent' : '' }}"></i>@endforeach</div>
          </div>
        </div>
      </div>
    </section>

    {{-- ===== the note ===== --}}
    @if ($w->message !== '')
      <section class="cb-sec cb-note-sec">{!! $decor($sec++) !!}
        <span class="cb-quote-mark" aria-hidden="true">“</span>
        <p class="cb-quote ik-r{{ $kh($w->message) }}" data-ik="words">{{ $w->message }}</p>
        @if ($host !== '')<p class="cb-sign ik-r" data-ik="up">— <span class="{{ trim($kh($host)) }}">{{ $host }}</span></p>@endif
      </section>
    @endif

    {{-- ===== make a wish: tap each candle to blow it out ===== --}}
    <section class="cb-sec cb-wish-sec">{!! $decor($sec++) !!}
      <h2 class="cb-h ik-r" data-ik="up"><span class="en">Make a wish</span><span class="kh">ប៉ះទៀនដើម្បីផ្លុំ ហើយធ្វើការប្រាថ្នា</span></h2>
      <div class="cb-cake ik-r" data-ik="zoom" id="cb-cake">
        <div class="cb-candles" id="cb-candles">
          @foreach ($candles as $c)
            <button type="button" class="cb-candle{{ $c === '|' ? ' stick' : '' }}" aria-label="ផ្លុំទៀន">
              <span class="cb-flame" aria-hidden="true"><i></i></span><span class="cb-smoke" aria-hidden="true"><i></i><i></i><i></i></span>
              @if ($c === '|')<span class="cb-stick" aria-hidden="true"></span>@else<b aria-hidden="true">{{ $c }}</b>@endif
            </button>
          @endforeach
        </div>
        <svg class="cb-cake-svg" viewBox="0 0 320 260" aria-hidden="true">
          <ellipse cx="160" cy="238" rx="152" ry="17" fill="#fff" stroke="#e6dfe6"/>
          <ellipse cx="160" cy="236" rx="138" ry="12" fill="#f4eef3"/>
          {{-- bottom tier --}}
          <path d="M40 142V222A120 14 0 0 0 280 222V142Z" fill="{{ $accXL }}"/>
          <path d="M40 142V222A120 14 0 0 0 100 233V150Z" fill="#fff" fill-opacity=".35"/>
          <ellipse cx="160" cy="142" rx="120" ry="14" fill="#fff8fb"/>
          <g fill="none" stroke="{{ $accL }}" stroke-width="5" stroke-linecap="round">{!! $swags(46, 274, 160, 6, 26) !!}</g>
          <g fill="#fff" stroke="{{ $accL }}" stroke-width="1">{!! $beads(160, 222, 120, 14, 22, 0, M_PI, 6) !!}</g>
          <g fill="#fff" stroke="#f0dbe6" stroke-width="1">{!! $beads(160, 142, 120, 14, 22, 0, M_PI, 4.5) !!}</g>
          <g fill="{{ $acc }}">@for ($i = 0; $i <= 6; $i++)<circle cx="{{ 46 + $i * 38 }}" cy="160" r="5"/>@endfor</g>
          {{-- top tier --}}
          <path d="M84 76V132A76 10 0 0 0 236 132V76Z" fill="#fff8fb"/>
          <path d="M84 76V132A76 10 0 0 0 120 140V82Z" fill="{{ $accXL }}" fill-opacity=".5"/>
          <ellipse cx="160" cy="76" rx="76" ry="10" fill="#fff"/>
          <g fill="none" stroke="{{ $acc }}" stroke-width="3.5" stroke-linecap="round">{!! $swags(90, 230, 92, 4, 18) !!}</g>
          <g fill="{{ $accL }}" stroke="#fff" stroke-width="1">{!! $beads(160, 132, 76, 10, 16, 0, M_PI, 5) !!}</g>
          <g fill="#fff" stroke="#ecdce5" stroke-width="1">{!! $beads(160, 76, 76, 10, 16, 0, M_PI, 4) !!}</g>
          {{-- cherries + bows on the sides --}}
          <g><circle cx="112" cy="70" r="7" fill="#d61f4f"/><circle cx="110" cy="68" r="2" fill="#fff" fill-opacity=".7"/><path d="M112 63Q116 52 124 50" stroke="#3d6b3a" stroke-width="1.6" fill="none"/></g>
          <g><circle cx="210" cy="71" r="7" fill="#d61f4f"/><circle cx="208" cy="69" r="2" fill="#fff" fill-opacity=".7"/><path d="M210 64Q206 53 198 51" stroke="#3d6b3a" stroke-width="1.6" fill="none"/></g>
          <use href="#cb-bow" transform="translate(28 170) scale(.34)"/><use href="#cb-bow" transform="translate(258 170) scale(.34)"/>
          <use href="#cb-bow" transform="translate(140 100) scale(.4)"/>
        </svg>
      </div>
      <div class="cb-wish-done" id="cb-wish-done" aria-live="polite">
        <p class="cb-wish-title"><span class="en">Wish granted</span> ✦ សូមឲ្យសម្រេចគ្រប់ប្រាថ្នា!</p>
        <button type="button" class="cb-btn ghost small" id="cb-relight">អុជទៀនម្ដងទៀត</button>
      </div>
    </section>

    {{-- ===== save the date + countdown ===== --}}
    @if ($w->calendar)
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Save the date</span><span class="kh">{{ $w->calendar['title'] }}</span></h2>
        <div class="cb-cal-card ik-r" data-ik="fold">
          <p class="cb-cal-month"><span class="en">{{ $w->calendar['en'] }}</span></p>
          <div class="cb-cal">
            @foreach (W::KH_DAYS_SHORT as $d)<span class="cb-cal-h">{{ $d }}</span>@endforeach
            @foreach ($w->calendar['cells'] as $c)
              @if ($c === $w->calendar['day'])
                <span class="cb-cal-day"><svg class="cb-cal-heart" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-heart"/></svg><b>{{ W::khDigits($c) }}</b></span>
              @else
                <span>{{ $c ? W::khDigits($c) : '' }}</span>
              @endif
            @endforeach
          </div>
          <p class="cb-cal-time">{{ $w->timeKhmer }}</p>
          @if ($w->khmerDate)<p class="cb-soft">{{ $w->khmerDate }}</p>@endif
        </div>
        @if ($w->showCountdown)
          <div class="cb-count ik-r" data-ik="up" id="cb-count" data-target="{{ $w->isoDate }}">
            @foreach (['d' => ['ថ្ងៃ', 'days'], 'h' => ['ម៉ោង', 'hrs'], 'm' => ['នាទី', 'min'], 's' => ['វិនាទី', 'sec']] as $u => [$l, $en])
              <div class="cb-count-cell"><b data-u="{{ $u }}">០០</b><span>{{ $l }}</span><small>{{ $en }}</small></div>
            @endforeach
          </div>
          <p class="cb-party-now" id="cb-party-now" hidden>🎉 <span class="en">It's party time!</span> ដល់ពេលជប់លៀងហើយ!</p>
        @endif
      </section>
    @endif

    {{-- ===== through the years (story chapters «Place | when») ===== --}}
    @if ($w->story->isNotEmpty())
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Through the years</span><span class="kh">ដំណើរជីវិតតាមឆ្នាំ</span></h2>
        <ol class="cb-years" id="cb-years">
          <i class="cb-years-line" aria-hidden="true"><i id="cb-years-fill"></i></i>
          @foreach ($w->story as $c)
            <li class="cb-year ik-r" data-ik="{{ $loop->odd ? 'left' : 'right' }}">
              <svg class="cb-year-node" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-star"/></svg>
              @if ($c['when'] !== '')<span class="cb-year-when{{ $kh($c['when']) }}">{{ $c['when'] }}</span>@endif
              @if ($c['place'] !== '')<h3 class="cb-year-t{{ $kh($c['place']) }}">{{ $c['place'] }}</h3>@endif
              @if ($c['text'] !== '')<p>{{ $c['text'] }}</p>@endif
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    {{-- ===== the plan: a party playlist ===== --}}
    @if ($w->scheduleImage || $w->schedule->isNotEmpty())
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">The plan</span><span class="kh">កម្មវិធីជប់លៀង</span></h2>
        @if ($w->scheduleImage)
          <button type="button" class="cb-sched-img ik-r" data-ik="zoom" data-ik-photo data-group="sched" data-full="{{ $w->scheduleImage }}" aria-label="មើលកម្មវិធី"><img src="{{ $w->scheduleImage }}" alt="កម្មវិធី" loading="lazy"></button>
        @else
          <div class="cb-playlist ik-r" data-ik="fold">
            <div class="cb-pl-head">
              <span class="cb-pl-cover" aria-hidden="true"><svg viewBox="0 0 100 100"><use href="#cb-heart"/></svg></span>
              <div><p class="cb-pl-kicker">PARTY PLAYLIST</p><p class="cb-pl-title{{ $nameKh ? ' cb-kh' : '' }}">{{ $name }}'s night</p></div>
              <span class="cb-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            </div>
            <ol class="cb-pl">
              @foreach ($w->schedule as $row)
                <li class="ik-r" data-ik="left" data-sfx="tap" tabindex="0">
                  <span class="cb-pl-n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                  <span class="cb-pl-l{{ $kh($row['label']) }}">{{ $row['label'] }}</span>
                  <span class="cb-pl-t">{{ $row['time'] }}</span>
                </li>
              @endforeach
            </ol>
          </div>
        @endif
      </section>
    @endif

    {{-- ===== gallery: a Pinterest board ===== --}}
    @if ($w->gallery->isNotEmpty())
      <section class="cb-sec cb-board-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Mood board</span><span class="kh">រូបភាពអនុស្សាវរីយ៍</span></h2>
        <div class="cb-board">
          @foreach ($w->gallery as $i => $url)
            @php($cap = $w->captions[$i] ?? '')
            <figure class="cb-pin ik-r" data-ik="pin" style="--rot:{{ [-2, 1.5, -1, 2, 0, -1.5][$i % 6] }}deg">
              <button type="button" data-ik-photo data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $cap }}" aria-label="{{ $cap ?: 'រូបភាពទី '.W::khDigits($i + 1) }}">
                <img src="{{ $url }}" alt="" loading="lazy">
                <span class="cb-pin-save" aria-hidden="true">Save</span>
              </button>
              @if ($cap !== '')<figcaption class="{{ trim($kh($cap)) }}">{{ $cap }}</figcaption>@endif
            </figure>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ===== venue ===== --}}
    @if ($w->showVenue)
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Where</span><span class="kh">ទីតាំងជប់លៀង</span></h2>
        <div class="cb-card cb-venue ik-r" data-ik="up">
          <svg class="cb-venue-pin" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-heart"/></svg>
          @if ($w->venueName)<p class="cb-venue-name{{ $kh($w->venueName) }}">{{ $w->venueName }}</p>@endif
          @if ($w->venueAddress)<p class="cb-soft">{{ $w->venueAddress }}</p>@endif
          @if ($w->mapEmbed)<div class="cb-map"><iframe src="{{ $w->mapEmbed }}" loading="lazy" title="ផែនទី" referrerpolicy="no-referrer-when-downgrade"></iframe></div>@endif
          @if ($w->mapUrl)<a class="cb-btn" href="{{ $w->mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">បើកផែនទី · Directions</a>@endif
        </div>
      </section>
    @endif

    {{-- ===== guest notes: dress code as glossy balloons ===== --}}
    @if ($w->dressCode !== '' || $w->whatToBring !== '' || $w->rsvpBy)
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Good to know</span><span class="kh">ព័ត៌មានសម្រាប់ភ្ញៀវ</span></h2>
        @if ($w->dressCode !== '')
          <div class="cb-card cb-dress ik-r" data-ik="up">
            <p class="cb-label">Dress code · ពណ៌សម្លៀកបំពាក់</p>
            @if ($w->dressColours)
              <ul class="cb-balloons" id="cb-balloons">
                @foreach ($w->dressColours as $c)
                  <li style="--c:{{ $c['hex'] }};--i:{{ $loop->index }}">
                    <button type="button" class="cb-balloon" aria-label="{{ $c['name'] }}"><i aria-hidden="true"></i></button>
                    <svg class="cb-b-string" viewBox="0 0 20 60" aria-hidden="true"><path d="M10 0C4 14 16 26 10 40C6 50 12 56 10 60" fill="none" stroke="#b8bcc8" stroke-width="1.2"/></svg>
                    <span class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="cb-dress-text">{{ $w->dressCode }}</p>
          </div>
        @endif
        <div class="cb-stickers">
          @if ($w->whatToBring !== '')<div class="cb-sticker ik-r" data-ik="pop" style="--rot:-3deg"><b>🎁 សូមយកមកជាមួយ</b><span>{{ $w->whatToBring }}</span></div>@endif
          @if ($w->rsvpBy)<div class="cb-sticker alt ik-r" data-ik="pop" style="--rot:2.5deg"><b>⏳ សូមឆ្លើយតបមុន</b><span>{{ $w->rsvpBy }}</span></div>@endif
        </div>
      </section>
    @endif

    {{-- ===== RSVP ===== --}}
    @if ($w->showRsvp)
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Are you coming?</span><span class="kh">ការឆ្លើយតប</span></h2>
        <div class="cb-card cb-rsvp ik-r" data-ik="fold">
          <p class="cb-rsvp-q">{{ $w->rsvpQuestion }}</p>
          <div class="cb-opts" id="cb-rsvp-opts" role="radiogroup" aria-label="ការឆ្លើយតប">
            <button type="button" role="radio" aria-checked="false" data-status="yes"><span class="e" aria-hidden="true">🥳</span>ខ្ញុំនឹងមក!<small>count me in</small></button>
            <button type="button" role="radio" aria-checked="false" data-status="no"><span class="e" aria-hidden="true">🥺</span>មិនអាចមកបាន<small>sending love</small></button>
          </div>
          <form class="cb-form" id="cb-rsvp-form">
            <textarea id="cb-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរខួបកំណើត (មិនចាំបាច់)"></textarea>
            <button type="submit" class="cb-btn">ផ្ញើ · Send</button>
          </form>
          <p class="cb-rsvp-thanks" id="cb-rsvp-thanks" role="status"></p>
        </div>
      </section>
    @endif

    {{-- ===== gift: a box that opens to the QR ===== --}}
    @if ($w->giftQr)
      <section class="cb-sec">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Gifts</span><span class="kh">កាដូ</span></h2>
        <p class="cb-gift-msg ik-r" data-ik="up">{{ $w->giftMessage }}</p>
        <div class="cb-gift ik-r" data-ik="zoom">
          <button type="button" class="cb-gift-box" id="cb-gift" aria-expanded="false" aria-label="បើកប្រអប់កាដូ">
            <span class="cb-gift-qr"><img src="{{ $w->giftQr }}" alt="QR code សម្រាប់ផ្ញើកាដូ" loading="lazy"></span>
            <span class="cb-gift-body" aria-hidden="true"><i class="rib"></i></span>
            <span class="cb-gift-lid" aria-hidden="true"><i class="rib"></i><svg viewBox="0 0 100 100"><use href="#cb-bow"/></svg></span>
          </button>
          <p class="cb-hint" id="cb-gift-hint">ប៉ះដើម្បីបើកកាដូ · tap to open</p>
        </div>
        @if ($w->giftAccount)<p class="cb-gift-acc">{{ $w->giftAccount }}</p>@endif
        <div class="cb-row">
          @if ($w->giftNumber)<button type="button" class="cb-btn" data-copy="{{ $w->giftNumber }}">ចម្លងលេខគណនី</button>@endif
          <a class="cb-btn ghost" href="{{ $w->giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
        </div>
      </section>
    @endif

    {{-- ===== contact + custom button ===== --}}
    @if ($contactPhone !== '' || $w->ctaLabel)
      <section class="cb-sec cb-contact">{!! $decor($sec++) !!}
        <h2 class="cb-h ik-r" data-ik="up"><span class="en">Say hi</span><span class="kh">ទំនាក់ទំនង</span></h2>
        <div class="cb-row ik-r" data-ik="up">
          @if ($contactPhone !== '')
            <a class="cb-call" href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" data-sfx="tap">
              <span class="ic" aria-hidden="true">☎</span><span><small>{{ $host !== '' ? $host : $name }}</small>{{ $contactPhone }}</span>
            </a>
          @endif
          @if ($w->ctaLabel)<a class="cb-btn" href="{{ $w->ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $w->ctaLabel }} ↗</a>@endif
        </div>
      </section>
    @endif

    {{-- ===== thank-you / apology notes ===== --}}
    @foreach ($w->letters as [$khHead, $en, $text])
      <section class="cb-sec">{!! $decor($sec++) !!}
        <div class="cb-letter ik-r" data-ik="{{ $loop->odd ? 'left' : 'right' }}" style="--rot:{{ $loop->odd ? -1.5 : 1.5 }}deg">
          <i class="cb-tape" aria-hidden="true"></i>
          <p class="cb-letter-en">{{ $en }}</p>
          <h3 class="cb-letter-h">{{ $khHead }}</h3>
          <p>{{ $text }}</p>
        </div>
      </section>
    @endforeach

    <div class="cb-marquee pink" aria-hidden="true"><div class="cb-marquee-track" data-marquee="-1">
      @for ($i = 0; $i < 6; $i++)<span>LET'S CELEBRATE</span><b>✦</b><span>ជប់លៀងទាំងអស់គ្នា</span><b>✦</b>@endfor
    </div></div>

    {{-- ===== finale ===== --}}
    <section class="cb-sec cb-finale">{!! $decor($sec++) !!}
      @if ($w->background)
        <div class="cb-finale-photo ik-r" data-ik="zoom">
          <img src="{{ $w->background }}" alt="" loading="lazy">
          <svg class="cb-finale-bow" viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-bow"/></svg>
        </div>
      @endif
      <p class="cb-closing ik-r{{ $kh($w->closingLine) }}" data-ik="blur">{{ $w->closingLine }}</p>
      <p class="cb-soft ik-r" data-ik="up">ជួបគ្នានៅថ្ងៃជប់លៀង ♥ see you there</p>
      <button type="button" class="cb-btn big ik-r" data-ik="pop" id="cb-throw">🎉 បាចក្រដាសពណ៌ · Confetti!</button>
    </section>

    <x-invitation-footer prefix="cb" :names="$name" :names-class="$nameKh ? 'cb-kh' : ''" :date="$w->dateNumeric"
      love="រៀបចំឡើងដោយក្តីស្រឡាញ់ · Made with love"
      style="--inv-footer-bg:#1c1622; --inv-footer-text:#fbf4f8; --inv-footer-muted:#c9bccb; --inv-footer-accent:{{ $acc }}; --inv-footer-names-font:'Instrument Serif','Koulen',serif;">
      <x-slot:ornament><svg viewBox="0 0 100 100" aria-hidden="true"><use href="#cb-bow"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  <x-invite-kit :w="$w" :sfx="['party' => 'sonican-playful-comical-stinger-426071.mp3', 'sparkle' => 'universfield-clean-logo-reveal-139678.mp3']" wait />
</div>

<script src="{{ $asset('js/invitations/chrome-bow.js') }}"></script>
<script>
  ChromeBow({ rsvpUrl: @json($w->rsvpUrl) });
</script>
