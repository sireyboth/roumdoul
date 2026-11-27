{{--
  Green Wedding — a wisteria night garden (prefix `gw`). Cloned from Blossom Swing, rethemed in deep green + gold on
  the starry background assets/background/image/bg-green.webp (from Bg-green.png). The head frame is the wisteria
  curtain assets/head-flowers/top-frame.webp (from top-frame.png): it is cut into overlapping strands, each one hanging
  from its own point at the top and swinging on its own, while a random wind rolls through them mostly from left to
  right (gusts of random strength and timing travel across the curtain), the tips trail and flutter, and a tap or a
  finger brushing through pushes the strands aside (with a leaf rustle). The curtain unrolls left → right when it
  first shows. CSS public/css/invitations/green-wedding.css · JS public/js/invitations/green-wedding.js ·
  demo /demo/wedding/green-wedding (?open=1 skips the cover). Built on WeddingData + <x-invite-kit>; every wedding field.
--}}
@php
    use App\Support\Invitations\WeddingData as W;

    $w = W::from($fields, $invitation, $recipient ?? null, ['accent' => '#e2c27d', 'closing_line' => 'Forever & always']);
    $kh = fn (?string $s) => W::isKhmer($s) ? ' ik-kh' : '';
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';
    $skipCover = request()->boolean('open');
    $gwAsset = fn (string $f) => asset($f).'?v='.@filemtime(public_path($f));

    $curtainSrc = $gwAsset('images/invitations/assets/head-flowers/top-frame.webp');

    // the wisteria curtain, cut into strands (0–1 across the picture). Each strand shows the whole picture masked to its
    // own band and swings round the top of that band. Seams: the strand on the right fades in on top, the one under it
    // stays solid across the seam and fades out just past it, so a seam never thins out or shows a hard edge.
    // $wide repeats it four times (every other copy mirrored): on desktop the copies sit side by side across the whole
    // screen, so each one is smaller and the curtain spans the full width; phones show only the first copy.
    $cuts = [.09, .18, .27, .355, .44, .53, .62, .705, .79, .88];
    $curtain = function (string $class = '', bool $wide = false) use ($cuts, $curtainSrc) {
        $edges = array_merge([0], $cuts, [1]);
        $n = count($edges) - 1;
        $f = 2.6;
        $copy = function (bool $mirror) use ($edges, $n, $f, $curtainSrc) {
            $out = '';
            for ($i = 0; $i < $n; $i++) {
                $a = $edges[$i] * 100;
                $b = $edges[$i + 1] * 100;
                $left = $i === 0 ? '#000 0%' : 'transparent '.($a - $f).'%,#000 '.($a + $f).'%';
                $right = $i === $n - 1 ? '#000 100%' : '#000 '.($b + $f).'%,transparent '.($b + $f + 5).'%';
                $mask = "linear-gradient(90deg,{$left},{$right})";
                $mid = round(($a + $b) / 2, 2);
                $out .= '<div class="gw-strand" data-x="'.round($mid / 100, 3).'" style="transform-origin:'.$mid.'% 0;-webkit-mask-image:'.$mask.';mask-image:'.$mask.'">'
                    .'<img src="'.$curtainSrc.'" alt="" draggable="false" decoding="async"></div>';
            }

            return '<div class="gw-cpy'.($mirror ? ' is-mirror' : '').'">'.$out.'</div>';
        };

        return '<div class="gw-curtain'.($wide ? ' gw-curtain--wide' : '').($class ? ' '.$class : '').'" data-curtain aria-hidden="true">'
            .$copy(false).($wide ? $copy(true).$copy(false).$copy(true) : '').'</div>';
    };

    // the title «សិរីមង្គលអាពាហ៍ពិពាហ៍» in gold lettering, with a light sweeping across it
    $khTitle = '<p class="gw-kh-title"><span data-text="សិរីមង្គលអាពាហ៍ពិពាហ៍">សិរីមង្គលអាពាហ៍ពិពាហ៍</span></p>';
    $eyebrow = trim((string) $w->headline);
    if (preg_replace('/\s+/u', '', $eyebrow) === 'សិរីមង្គលអាពាហ៍ពិពាហ៍') {
        $eyebrow = '';
    }

    // the gold double-line heart (drawn in SVG, it draws itself in) on dark glass cut to the heart, with the names (or
    // title_image) + the date inside its wide upper part. Light, «polished gold»: every 8 s a soft band of white light
    // sweeps slowly across the gold lines from the top-left to the bottom-right (an animated gradient stroke over the
    // lines, like light catching metal), and a lens-flare star blooms where it passes (the left lobe, then the lower
    // right curve; timed to the sweep in the CSS); the lines' glow breathes in between.
    $nameFrame = function (string $tag, string $id) use ($w, $kh) {
        $names = $w->titleImage
            ? '<img class="gw-title-img" src="'.e($w->titleImage).'" alt="'.e($w->coupleTitle).'">'
            : "<{$tag} class=\"gw-names\"><span class=\"".trim($kh($w->groom)).'">'.e($w->groom).'</span>'
              .'<i class="gw-amp">&amp;</i><span class="'.trim($kh($w->bride)).'">'.e($w->bride)."</span></{$tag}>";
        // the outer heart and the inner one 10 units inside it (300 × 300 box, tip at the bottom)
        $heart = [
            'M150 284C62 222 8 162 8 96C8 46 45 12 90 12C118 12 140 28 150 48C160 28 182 12 210 12C255 12 292 46 292 96C292 162 238 222 150 284Z',
            'M150 270C68 212 18 158 18 97C18 53 51 22 91 22C117 22 138 37 150 59C162 37 183 22 209 22C249 22 282 53 282 97C282 158 232 212 150 270Z',
        ];
        $g = 'gw-gold-'.$id;
        // the glass is clipped to the outer heart (same path in 0–1 units, so it fits the box at any size)
        $unit = preg_replace_callback('/\d+(\.\d+)?/', fn ($m) => round($m[0] / 300, 4), $heart[0]);
        // a lens flare: soft halo, long horizontal + vertical rays, short diagonal rays, a white core
        $flare = fn (float $x, float $y, string $cls) => '<g transform="translate('.$x.' '.$y.')"><g class="gw-flare '.$cls.'">'
            .'<circle r="16" fill="url(#'.$g.'-halo)"/>'
            .'<ellipse rx="30" ry="1.1" fill="#fffbe8"/><ellipse rx="1.1" ry="22" fill="#fffbe8"/>'
            .'<ellipse rx="9" ry=".8" fill="#fff3cf" transform="rotate(45)"/><ellipse rx="9" ry=".8" fill="#fff3cf" transform="rotate(-45)"/>'
            .'<circle r="2.8" fill="#fff"/></g></g>';

        return '<div class="gw-nf" id="'.$id.'">'
            .'<span class="gw-nf-box">'
            .'<span class="gw-nf-glass" style="clip-path:url(#'.$g.'-clip);-webkit-clip-path:url(#'.$g.'-clip)"></span>'
            .'<svg class="gw-nf-art" viewBox="0 0 300 300" aria-hidden="true"><defs><linearGradient id="'.$g.'" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="#f6e2a6"/><stop offset=".35" stop-color="#c99a45"/><stop offset=".55" stop-color="#fff1c4"/><stop offset=".8" stop-color="#b8913f"/><stop offset="1" stop-color="#f0d590"/></linearGradient>'
            .'<linearGradient id="'.$g.'-sheen" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="300" y2="300">'
            .'<stop offset=".36" stop-color="#fff" stop-opacity="0"/><stop offset=".47" stop-color="#fff6d6" stop-opacity=".8"/><stop offset=".5" stop-color="#fff" stop-opacity="1"/>'
            .'<stop offset=".53" stop-color="#fff6d6" stop-opacity=".8"/><stop offset=".64" stop-color="#fff" stop-opacity="0"/>'
            .'<animateTransform attributeName="gradientTransform" type="translate" values="-420 -420;420 420;420 420" keyTimes="0;.45;1" dur="8s" repeatCount="indefinite" calcMode="spline" keySplines=".42 0 .58 1;0 0 1 1"/>'
            .'</linearGradient>'
            .'<radialGradient id="'.$g.'-halo"><stop offset="0" stop-color="#fff" stop-opacity=".95"/><stop offset=".35" stop-color="#ffe9a8" stop-opacity=".45"/><stop offset="1" stop-color="#e2c27d" stop-opacity="0"/></radialGradient>'
            .'<filter id="'.$g.'-glow" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="2.2" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>'
            .'<clipPath id="'.$g.'-clip" clipPathUnits="objectBoundingBox"><path d="'.$unit.'"/></clipPath></defs>'
            .'<path class="gw-nf-line" d="'.$heart[0].'" pathLength="1" stroke="url(#'.$g.')" stroke-width="1.6"/>'
            .'<path class="gw-nf-line" d="'.$heart[1].'" pathLength="1" stroke="url(#'.$g.')" stroke-width=".8"/>'
            .'<g class="gw-nf-light">'
            .'<g filter="url(#'.$g.'-glow)"><path d="'.$heart[0].'" fill="none" stroke="url(#'.$g.'-sheen)" stroke-width="2.6"/>'
            .'<path d="'.$heart[1].'" fill="none" stroke="url(#'.$g.'-sheen)" stroke-width="1.6"/></g>'
            .$flare(32, 35, 'f1').$flare(255, 190, 'f2')
            .'</g>'
            .'<path class="gw-nf-gem" d="M150 278l7 10-7 10-7-10z" fill="url(#'.$g.')"/></svg>'
            .'</span>'
            .'<div class="gw-nf-in">'.$names
            .($w->dateKhmer ? '<p class="gw-date">'.e($w->dateKhmer).'</p>' : '')
            .'</div></div>';
    };

    // fireflies + twinkling stars over the sky (a new scatter on every load)
    $flies = collect(range(1, 16))->map(fn () => [mt_rand(2, 98), mt_rand(8, 96), mt_rand(30, 70) / 10, mt_rand(90, 180) / 10, -mt_rand(0, 180) / 10]);
    $stars = collect(range(1, 34))->map(fn () => [mt_rand(0, 100), mt_rand(0, 100), mt_rand(10, 26) / 10, mt_rand(25, 60) / 10, -mt_rand(0, 60) / 10]);
@endphp
<title>{{ $w->coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Great+Vibes&family=Moul&family=Moulpali&family=Noto+Serif+Khmer:wght@400;600&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="preload" as="image" href="{{ $curtainSrc }}">
<link rel="stylesheet" href="{{ $gwAsset('css/invitations/green-wedding.css') }}">

<div class="gw{{ $skipCover ? ' is-open' : '' }}" style="--ik-accent:{{ $w->accent }}; --gw-accent:{{ $w->accent }}; --gw-sky:url('{{ $gwAsset('images/invitations/assets/background/image/bg-green.webp') }}');" data-rsvp-url="{{ $w->rsvpUrl }}">
  {{-- the strands start rolled up (they unroll from JS); if the script never runs they show after 3 s anyway --}}
  <script>(function (r) { r.classList.add('js', 'gw-bud'); setTimeout(function () { if (!r.classList.contains('gw-live')) r.classList.remove('gw-bud'); }, 3000); })(document.currentScript.parentNode);</script>

  {{-- the starry green sky, drifting slowly, with twinkling stars and fireflies --}}
  <div class="gw-sky" aria-hidden="true">
    <i class="gw-sky-img"></i>
    @foreach ($stars as [$x, $y, $s, $d, $dl])<b class="gw-star" style="left:{{ $x }}%;top:{{ $y }}%;--s:{{ $s }}px;--d:{{ $d }}s;--dl:{{ $dl }}s"></b>@endforeach
    @foreach ($flies as [$x, $y, $s, $d, $dl])<b class="gw-fly" style="left:{{ $x }}%;top:{{ $y }}%;--s:{{ $s }}px;--d:{{ $d }}s;--dl:{{ $dl }}s"></b>@endforeach
  </div>

  {{-- ===== cover: the wisteria curtain swaying over the gold frame, the guest's name, «open» ===== --}}
  @unless ($skipCover)
    <div class="gw-cover" id="gw-cover" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$w->coupleTitle) }}">
      <div class="gw-frame">
        {!! $curtain('gw-cover-top', true) !!}
        <div class="gw-cover-body">
          {!! $khTitle !!}
          @if ($eyebrow)<p class="gw-eyebrow">{{ $eyebrow }}</p>@endif
          {!! $nameFrame('h1', 'gw-cover-frame') !!}
          <div class="gw-guest">
            <small>សូមគោរពអញ្ជើញ</small>
            <b class="{{ trim($kh($guest)) }}">{{ $guest }}</b>
          </div>
          <button type="button" class="gw-btn gw-open" id="gw-open">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.7 4.5c2.1 0 3.6 1.2 5.3 3.2 1.7-2 3.2-3.2 5.3-3.2 3.7 0 5.8 3.8 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
            បើកសំបុត្រ
          </button>
        </div>
      </div>
    </div>
  @endunless


  {{-- after opening, the curtain stays pinned to the top of the screen (it lifts as the page scrolls, so it never
       covers the text) and keeps swaying --}}
  <div class="gw-sticky" aria-hidden="true">{!! $curtain('', true) !!}</div>

  <main class="gw-page">
    {{-- ===== first section: the invitation itself; its lines rise in one after another once the cover is gone ===== --}}
    <section class="gw-hero gw-first">
      <div class="gw-hero-body gw-invite">
        {!! $khTitle !!}
        @php($ringSrc = $gwAsset('images/invitations/assets/img-frame/ring.webp'))
        <div class="gw-rings" style="--gw-rings:url('{{ $ringSrc }}')" aria-hidden="true"><img src="{{ $ringSrc }}" alt="" loading="lazy" draggable="false"></div>
        @if ($w->groomParents || $w->brideParents)
          <div class="gw-parents">
            <div><small>ខាងកូនប្រុស</small>@foreach ($w->groomParents as $p)<p>{{ $p }}</p>@endforeach</div>
            <div><small>ខាងកូនស្រី</small>@foreach ($w->brideParents as $p)<p>{{ $p }}</p>@endforeach</div>
          </div>
        @endif
        <h2 class="gw-h"><span class="en">You are invited</span>សូមគោរពអញ្ជើញ</h2>
        <p class="gw-guest-name{{ $kh($guest) }}" id="gw-guest-name">{{ $guest }}</p>
        <p class="gw-reception">{{ $w->receptionText }}</p>
        <p class="gw-invite-couple">
          <span class="{{ trim($kh($w->groom)) }}">{{ $w->groom }}</span>
          <i class="gw-amp">&amp;</i>
          <span class="{{ trim($kh($w->bride)) }}">{{ $w->bride }}</span>
        </p>
        <div class="gw-when">
          <p>{{ $w->dateKhmer }}</p>
          @if ($w->khmerDate)<p class="gw-soft">{{ $w->khmerDate }}</p>@endif
          @if ($w->timeKhmer)<p class="gw-accent">{{ $w->timeKhmer }}</p>@endif
        </div>
      </div>
    </section>

    @if ($w->groomPhoto || $w->bridePhoto)
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">The bride &amp; groom</span>កូនកំលោះ និង កូនក្រមុំ</h3>
        <div class="gw-couple">
          @foreach ([[$w->groomPhoto, $w->groom, 'កូនកំលោះ'], [$w->bridePhoto, $w->bride, 'កូនក្រមុំ']] as [$photo, $name, $role])
            @if ($photo)
              <figure class="ik-r" data-ik="{{ $loop->first ? 'left' : 'right' }}">
                {{-- arched photo in a gold ring --}}
                <div class="gw-oval{{ $loop->first ? '' : ' is-right' }}">
                  <div class="gw-oval-ring">
                    <button type="button" class="gw-oval-photo" data-ik-photo data-group="couple" data-full="{{ $photo }}" data-cap="{{ $name }}"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></button>
                  </div>
                </div>
                <figcaption><small>{{ $role }}</small><b class="{{ trim($kh($name)) }}">{{ $name }}</b></figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->message !== '')
      <section class="gw-sec gw-sec--tight">
        <blockquote class="gw-card gw-glass gw-note ik-r" data-ik="up">
          <span class="gw-note-mark" aria-hidden="true">“</span>
          <p class="{{ trim($kh($w->message)) }}" data-ik-words>{{ $w->message }}</p>
        </blockquote>
      </section>
    @endif

    {{-- ===== save the date ===== --}}
    @if ($w->calendar)
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Save the date</span>ថ្ងៃមង្គល</h3>
        <div class="gw-card gw-glass ik-r" data-ik="zoom">
          <p class="gw-cal-title">{{ $w->calendar['title'] }}</p>
          <div class="gw-cal">
            @foreach (W::KH_DAYS_SHORT as $d)<span class="gw-soft">{{ $d }}</span>@endforeach
            @foreach ($w->calendar['cells'] as $c)<span class="{{ $c === $w->calendar['day'] ? 'on' : '' }}">{{ $c ? W::khDigits($c) : '' }}</span>@endforeach
          </div>
          @if ($w->showCountdown)
            <div class="gw-count" id="gw-count" data-target="{{ $w->isoDate }}">
              @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $l)<div><b data-u="{{ $u }}">០០</b><span>{{ $l }}</span></div>@endforeach
            </div>
          @endif
        </div>
      </section>
    @endif

    @if ($w->story->isNotEmpty())
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Our story</span>ដំណើររឿងរបស់យើង</h3>
        <ol class="gw-story">
          @foreach ($w->story as $c)
            <li class="ik-r" data-ik="{{ $loop->odd ? 'left' : 'right' }}">
              <span class="gw-dot" aria-hidden="true" style="--n:{{ $loop->index }}"><svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.7 4.5c2.1 0 3.6 1.2 5.3 3.2 1.7-2 3.2-3.2 5.3-3.2 3.7 0 5.8 3.8 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg></span>
              <b class="{{ trim($kh($c['place'])) }}">{{ $c['place'] }}</b>@if ($c['when'])<small>{{ $c['when'] }}</small>@endif
              @if ($c['text'])<p>{{ $c['text'] }}</p>@endif
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    @if ($w->scheduleImage || $w->schedule->isNotEmpty())
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Programme</span>កម្មវិធី</h3>
        @if ($w->scheduleImage)
          <button type="button" class="gw-sched-img ik-r" data-ik="zoom" data-ik-photo data-group="sched" data-full="{{ $w->scheduleImage }}"><img src="{{ $w->scheduleImage }}" alt="កម្មវិធី" loading="lazy"></button>
        @else
          <ul class="gw-sched">
            @foreach ($w->schedule as $row)
              <li class="ik-r" data-ik="up" style="--n:{{ $loop->index }}">
                <i class="gw-sched-ic" aria-hidden="true"><svg viewBox="0 0 24 24"><g>@foreach ([0, 72, 144, 216, 288] as $deg)<ellipse cx="12" cy="6.6" rx="3.3" ry="4.7" transform="rotate({{ $deg }} 12 12)"/>@endforeach</g><circle cx="12" cy="12" r="2.6"/></svg></i>
                <b>{{ $row['time'] }}</b><span>{{ $row['label'] }}</span>
              </li>
            @endforeach
          </ul>
        @endif
      </section>
    @endif

    @if ($w->gallery->isNotEmpty())
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Moments</span>អនុស្សាវរីយ៍</h3>
        <div class="gw-gal">
          @foreach ($w->gallery as $i => $url)
            <button type="button" class="ik-r{{ $i % 5 === 0 ? ' wide' : '' }}" data-ik="mask" data-ik-photo data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $w->captions[$i] ?? '' }}" aria-label="{{ ($w->captions[$i] ?? '') ?: 'រូបភាពទី '.W::khDigits($i + 1) }}"><img src="{{ $url }}" alt="" loading="lazy"></button>
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->showVenue)
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Venue</span>ទីតាំង</h3>
        <div class="gw-card gw-glass ik-r" data-ik="up">
          @if ($w->venueName)<p class="gw-venue{{ $kh($w->venueName) }}">{{ $w->venueName }}</p>@endif
          @if ($w->venueAddress)<p class="gw-soft">{{ $w->venueAddress }}</p>@endif
          @if ($w->mapEmbed)<iframe class="gw-map" src="{{ $w->mapEmbed }}" loading="lazy" title="ផែនទី"></iframe>@endif
          @if ($w->mapUrl)<a class="gw-btn" href="{{ $w->mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">មើលផែនទី</a>@endif
        </div>
      </section>
    @endif

    @if ($w->dressCode !== '' || $w->whatToBring !== '' || $w->rsvpBy)
      <section class="gw-sec gw-sec--guests">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">For our guests</span>ព័ត៌មានសម្រាប់ភ្ញៀវ</h3>
        @if ($w->dressCode !== '')
          <div class="gw-card gw-glass ik-r" data-ik="up">
            <b>ពណ៌សម្លៀកបំពាក់</b>
            @if ($w->dressColours)
              <ul class="gw-swatches">@foreach ($w->dressColours as $c)<li style="--c:{{ $c['hex'] }}"><i></i><span class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</span></li>@endforeach</ul>
            @endif
            <p class="gw-soft">{{ $w->dressCode }}</p>
          </div>
        @endif
        <div class="gw-notes">
          @if ($w->whatToBring !== '')<p class="ik-r" data-ik="up"><small>សូមយកមកជាមួយ</small>{{ $w->whatToBring }}</p>@endif
          @if ($w->rsvpBy)<p class="ik-r" data-ik="up"><small>សូមឆ្លើយតបមុន</small>{{ $w->rsvpBy }}</p>@endif
        </div>
      </section>
    @endif

    @if ($w->showRsvp)
      <section class="gw-sec gw-sec--rsvp">
        <div class="gw-card gw-glass gw-crowned ik-r" data-ik="fold" id="gw-rsvp-card">
          {!! $curtain('gw-crown') !!}
          <h3 class="gw-h"><span class="en">RSVP</span>ការឆ្លើយតប</h3>
          <p>{{ $w->rsvpQuestion }}</p>
          <div class="gw-opts" id="gw-rsvp-opts" role="radiogroup" aria-label="ការឆ្លើយតប">
            <button type="button" role="radio" aria-checked="false" data-status="yes">ខ្ញុំនឹងចូលរួម ♥</button>
            <button type="button" role="radio" aria-checked="false" data-status="no">សោកស្ដាយ មិនអាចចូលរួមបាន</button>
          </div>
          <form class="gw-form" id="gw-rsvp-form">
            <textarea id="gw-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
            <button type="submit" class="gw-btn">ផ្ញើ</button>
          </form>
          <p id="gw-rsvp-thanks" class="gw-accent" role="status"></p>
        </div>
      </section>
    @endif

    @if ($w->giftQr)
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Wedding gift</span>ចំណងដៃ</h3>
        <div class="gw-card gw-glass ik-r" data-ik="zoom">
          <p>{{ $w->giftMessage }}</p>
          <img class="gw-qr" src="{{ $w->giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
          @if ($w->giftAccount)<p class="gw-pre">{{ $w->giftAccount }}</p>@endif
          <div class="gw-row">
            @if ($w->giftNumber)<button type="button" class="gw-btn" data-copy="{{ $w->giftNumber }}">ចម្លងលេខគណនី</button>@endif
            <a class="gw-btn ghost" href="{{ $w->giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
          </div>
        </div>
      </section>
    @endif

    @if ($w->contacts->isNotEmpty() || $w->ctaLabel)
      <section class="gw-sec">
        <h3 class="gw-h ik-r" data-ik="up"><span class="en">Contact</span>ទំនាក់ទំនង</h3>
        <div class="gw-contacts">
          @foreach ($w->contacts as $c)
            <a class="ik-r" data-ik="up" href="tel:{{ $c['tel'] }}" data-sfx="tap"><small>{{ $c['role'] }}</small><b>{{ $c['name'] }}</b><span>{{ $c['phone'] }}</span></a>
          @endforeach
        </div>
        @if ($w->ctaLabel)<p style="text-align:center"><a class="gw-btn" href="{{ $w->ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $w->ctaLabel }}</a></p>@endif
      </section>
    @endif

    @foreach ($w->letters as [$khHead, $en, $text])
      <section class="gw-sec">
        <div class="gw-card gw-glass gw-letter ik-r" data-ik="up"><h3 class="gw-h"><span class="en">{{ $en }}</span>{{ $khHead }}</h3><p>{{ $text }}</p></div>
      </section>
    @endforeach

    {{-- ===== finale ===== --}}
    <section class="gw-hero gw-finale">
      <div class="gw-hero-body">
        @if ($w->background)
          <div class="gw-finale-frame ik-r" data-ik="zoom">
            <div class="gw-arch gw-finale-photo"><img src="{{ $w->background }}" alt="" loading="lazy"></div>
          </div>
        @endif
        <p class="gw-closing ik-r{{ $kh($w->closingLine) }}" data-ik="blur">{{ $w->closingLine }}</p>
        <button type="button" class="gw-btn ghost ik-r" data-ik="up" id="gw-breeze">ផ្ញើក្តីស្រឡាញ់ ♥</button>
      </div>
    </section>

    <x-invitation-footer :names="$w->coupleTitle" :names-class="W::isKhmer($w->coupleTitle) ? 'ik-kh' : ''" :date="$w->dateNumeric"
      style="--inv-footer-bg:#06190d; --inv-footer-text:#f4f1e4; --inv-footer-muted:#b9c9b0; --inv-footer-accent:{{ $w->accent }}; --inv-footer-names-font:'Great Vibes','Moulpali',cursive; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;" />
  </main>

  <x-invite-kit :w="$w" :sfx="['rustle' => 'rustle.wav']" wait />
</div>

<script src="{{ $gwAsset('js/invitations/green-wedding.js') }}"></script>
