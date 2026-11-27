{{--
  Blossom Swing — soft watercolour wedding (prefix `bs`). The hanging head-flower garland
  (public/images/invitations/assets/head-flowers/garland-top.webp, cut from Cover-Frame.png) and the bouquet at the
  bottom (garland-bottom.webp) sway in an endless, never-repeating breeze: each picture is split into a few
  feather-masked pieces (one per flower cluster) that swing round their own stem point, the wind passes across them
  like a wave, and a tap, a finger brushing them or a fast scroll sends a gust through them (with a leaf rustle).
  CSS public/css/invitations/blossom-swing.css · JS public/js/invitations/blossom-swing.js · demo /demo/wedding/blossom-swing
  (?open=1 skips the cover). Built on WeddingData + <x-invite-kit>; supports every wedding field.
--}}
@php
    use App\Support\Invitations\WeddingData as W;

    $w = W::from($fields, $invitation, $recipient ?? null, ['accent' => '#d0668c', 'closing_line' => 'Happily ever after']);
    $kh = fn (?string $s) => W::isKhmer($s) ? ' ik-kh' : '';
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';
    $skipCover = request()->boolean('open');
    $bsAsset = fn (string $f) => asset($f).'?v='.@filemtime(public_path($f));

    // the two watercolour pieces and where each is cut into clusters that sway on their own (0–1 across the width)
    $garland = [
        'top' => ['src' => $bsAsset('images/invitations/assets/head-flowers/garland-top.webp'), 'ratio' => '1400 / 410', 'cuts' => [.17, .31, .71, .86]],
        'bottom' => ['src' => $bsAsset('images/invitations/assets/head-flowers/garland-bottom.webp'), 'ratio' => '1400 / 447', 'cuts' => [.40, .59, .84]],
    ];
    // one swinging garland, built like a branch: the middle cluster is the root, and each cluster further out is a
    // CHILD of its neighbour (nested), turning round the joint they share. So a piece always stays attached to the one
    // it hangs from however far they swing, the motion adds up towards the light ends (a whip-like wave), and blooming
    // grows the garland outward from the middle. A bone carries the motion; its .bs-petal holds the whole picture,
    // masked to its cluster. On each seam the outer (upper) piece fades in, and the inner one keeps full cover across
    // the seam underneath it, then fades out softly, so seams never thin out or show a hard edge, even mid-bloom.
    $swing = function (string $where, string $class = '') use ($garland) {
        $g = $garland[$where];
        $edges = array_merge([0], $g['cuts'], [1]);
        $n = count($edges) - 1;
        $f = 4;
        $root = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($edges[$i] <= .5 && $edges[$i + 1] > .5) {
                $root = $i;
            }
        }
        $y = $where === 'top' ? '0%' : '100%';
        $bone = function (int $i) use (&$bone, $edges, $n, $root, $f, $y, $g) {
            $a = $edges[$i] * 100;
            $b = $edges[$i + 1] * 100;
            if ($i === 0) {
                $left = '#000 0%';
            } elseif ($i > $root) {
                $left = 'transparent '.($a - $f).'%,#000 '.($a + $f).'%';      // I'm the outer piece: fade in
            } else {
                $left = 'transparent '.($a - $f - 7).'%,#000 '.($a - $f).'%';     // under the outer piece: soft, never a hard cut while it is still folded
            }
            if ($i === $n - 1) {
                $right = '#000 100%';
            } elseif ($i < $root) {
                $right = '#000 '.($b - $f).'%,transparent '.($b + $f).'%';
            } else {
                $right = '#000 '.($b + $f).'%,transparent '.($b + $f + 7).'%';
            }
            $mask = "linear-gradient(90deg,{$left},{$right})";
            $mid = ($a + $b) / 2;
            $pivot = $i === $root ? $mid : ($i < $root ? $b : $a);           // the joint with the piece it hangs from
            $kids = $i === $root ? array_filter([$i - 1, $i + 1], fn ($k) => $k >= 0 && $k < $n) : array_filter([$i < $root ? $i - 1 : $i + 1], fn ($k) => $k >= 0 && $k < $n);

            return '<div class="bs-bone" data-x="'.round($mid / 100, 3).'" data-depth="'.abs($i - $root).'" data-side="'.($i === $root ? 0 : ($i < $root ? -1 : 1)).'" style="transform-origin:'.$pivot.'% '.$y.'">'
                .'<div class="bs-petal" style="-webkit-mask-image:'.$mask.';mask-image:'.$mask.'"><img src="'.$g['src'].'" alt="" draggable="false" decoding="async"></div>'
                .implode('', array_map($bone, $kids))
                .'</div>';
        };

        return '<div class="bs-swing bs-swing--'.$where.($class ? ' '.$class : '').'" data-swing="'.$where.'" style="aspect-ratio:'.$g['ratio'].'" aria-hidden="true">'
            .$bone($root).'</div>';
    };

    // the floral gold frame (assets/img-frame/3.png, trimmed to 3.webp) with the names + date inside it — no photo.
    // The art is drawn into the frame's own box; .bs-nf-in sits over the inside of the gold octagon.
    $frameArt = $bsAsset('images/invitations/assets/img-frame/3.webp');
    // the watercolour bouquet (head-flowers/flowers-3.png, trimmed to flower-3.webp) dressing the photo ovals + note card
    $bouquet = $bsAsset('images/invitations/assets/head-flowers/flower-3.webp');

    // the Khmer title artwork «សិរីមង្គលអាពាហ៍ពិពាហ៍» (assets/text/kh-wedding.png, trimmed to .webp) between the head
    // garland and the frame; a light sweeps across its letters (the shine is masked to the lettering).
    // `headline` still shows as a small line under it — unless it just repeats the same words.
    $khTitleSrc = $bsAsset('images/invitations/assets/text/kh-wedding.webp');
    $khTitle = '<div class="bs-kh-title" style="--bs-title:url(\''.$khTitleSrc.'\')"><img src="'.$khTitleSrc.'" alt="សិរីមង្គលអាពាហ៍ពិពាហ៍" draggable="false"></div>';
    $eyebrow = trim((string) $w->headline);
    if (preg_replace('/\s+/u', '', $eyebrow) === 'សិរីមង្គលអាពាហ៍ពិពាហ៍') {
        $eyebrow = '';
    }
    $nameFrame = function (string $tag, string $id) use ($w, $kh, $frameArt) {
        $names = $w->titleImage
            ? '<img class="bs-title-img" src="'.e($w->titleImage).'" alt="'.e($w->coupleTitle).'">'
            : "<{$tag} class=\"bs-names\"><span class=\"".trim($kh($w->groom)).'">'.e($w->groom).'</span>'
              .'<i class="bs-amp">&amp;</i><span class="'.trim($kh($w->bride)).'">'.e($w->bride)."</span></{$tag}>";

        return '<div class="bs-nf" id="'.$id.'">'
            .'<span class="bs-nf-box"><img class="bs-nf-art" src="'.$frameArt.'" alt="" draggable="false"></span>'
            .'<div class="bs-nf-in">'.$names
            .($w->dateKhmer ? '<p class="bs-date">'.e($w->dateKhmer).'</p>' : '')
            .'</div></div>';
    };
@endphp
<title>{{ $w->coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Great+Vibes&family=Moulpali&family=Noto+Serif+Khmer:wght@400;600&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="preload" as="image" href="{{ $garland['top']['src'] }}">
<link rel="preload" as="image" href="{{ $garland['bottom']['src'] }}">
<link rel="preload" as="image" href="{{ $frameArt }}">
<link rel="preload" as="image" href="{{ $khTitleSrc }}">
<link rel="stylesheet" href="{{ $bsAsset('css/invitations/blossom-swing.css') }}">

<div class="bs{{ $skipCover ? ' is-open' : '' }}" style="--ik-accent:{{ $w->accent }}; --bs-accent:{{ $w->accent }}; --bs-smoke-wide:url('{{ $bsAsset('images/invitations/assets/background/image/bg-pink-smoke-wide.webp') }}'); --bs-smoke-tall:url('{{ $bsAsset('images/invitations/assets/background/image/bg-pink-smoke-tall.webp') }}');" data-rsvp-url="{{ $w->rsvpUrl }}" data-blooms="{{ collect(range(1, 3))->map(fn ($i) => $bsAsset("images/invitations/assets/head-flowers/flower-{$i}.webp"))->implode('|') }}">
  {{-- the flowers start folded (they bloom open from JS); if the script never runs they show after 3 s anyway --}}
  <script>(function (r) { r.classList.add('js', 'bs-bud'); setTimeout(function () { if (!r.classList.contains('bs-live')) r.classList.remove('bs-bud'); }, 3000); })(document.currentScript.parentNode);</script>
  {{-- pink smoke background, drifting slowly (two layers moving against each other) --}}
  <div class="bs-smoke" aria-hidden="true"><i></i><i></i></div>

  {{-- ===== cover: the full flower frame swaying, the couple photo, the guest's name, «open» ===== --}}
  @unless ($skipCover)
    <div class="bs-cover" id="bs-cover" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$w->coupleTitle) }}">
      <div class="bs-smoke bs-smoke--cover" aria-hidden="true"><i></i><i></i></div>
      <div class="bs-frame">
        <div class="bs-smoke bs-smoke--frame" aria-hidden="true"><i></i><i></i></div>
        {!! $swing('top', 'bs-cover-top') !!}
        <div class="bs-cover-body">
          {!! $khTitle !!}
          @if ($eyebrow)<p class="bs-eyebrow">{{ $eyebrow }}</p>@endif
          {!! $nameFrame('h1', 'bs-cover-frame') !!}
          <div class="bs-guest">
            <small>សូមគោរពអញ្ជើញ</small>
            <b class="{{ trim($kh($guest)) }}">{{ $guest }}</b>
          </div>
          <button type="button" class="bs-btn bs-open" id="bs-open">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.7 4.5c2.1 0 3.6 1.2 5.3 3.2 1.7-2 3.2-3.2 5.3-3.2 3.7 0 5.8 3.8 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
            បើកសំបុត្រ
          </button>
        </div>
        {{-- the wrapper is display:contents on phones; on desktop it fades the bouquet's flat-cut ends --}}
        <div class="bs-cover-fade">{!! $swing('bottom', 'bs-cover-bottom') !!}</div>
        {{-- desktop only: full-width flower bands along the top and bottom (random flower-1…3, built in JS) --}}
        <div class="bs-band bs-band--top" data-band="top" aria-hidden="true"></div>
        <div class="bs-band bs-band--bottom" data-band="bottom" aria-hidden="true"></div>
        {{-- desktop only: watercolour blooms swaying on the left and right edges of the wide cover --}}
        @foreach (['l' => [1, 3, 5], 'r' => [4, 2, 3]] as $side => $blooms)
          <span class="bs-dk-side {{ $side }}" aria-hidden="true">
            @foreach ($blooms as $n)<img class="f{{ $loop->iteration }}" src="{{ $bsAsset("images/invitations/assets/head-flowers/bloom-{$n}.webp") }}" alt="" draggable="false">@endforeach
          </span>
        @endforeach
      </div>
    </div>
  @endunless

  <x-flower-drop class="bs-flowers" :color="$w->accent" shape="blossom" leaf-color="#9cb59a" density=".4" />

  {{-- after opening, the head garland stays pinned to the top of the screen and the bouquet to the bottom while the
       page scrolls between them (they bloom open as the flowers part, and keep swinging) --}}
  {{-- desktop only: the same full-width flower bands, pinned to the top and bottom of the screen under the garlands --}}
  <div class="bs-band bs-band--top" data-band="top" aria-hidden="true"></div>
  <div class="bs-band bs-band--bottom" data-band="bottom" aria-hidden="true"></div>
  <div class="bs-sticky bs-sticky-top" aria-hidden="true">{!! $swing('top') !!}</div>
  <div class="bs-sticky bs-sticky-bottom" aria-hidden="true">{!! $swing('bottom') !!}</div>

  <main class="bs-page">
    {{-- ===== first section (what the flowers part to show): the invitation itself inside the garland panel —
         not a second cover. Its lines rise in one after another once the transition has ended (.bs-landed). ===== --}}
    <section class="bs-hero bs-first">
      <div class="bs-smoke bs-smoke--panel" aria-hidden="true"><i></i><i></i></div>
      <div class="bs-hero-body bs-invite">
        {!! $khTitle !!}
        @php($ringSrc = $bsAsset('images/invitations/assets/img-frame/ring.webp'))
        <div class="bs-rings" style="--bs-rings:url('{{ $ringSrc }}')" aria-hidden="true"><img src="{{ $ringSrc }}" alt="" loading="lazy" draggable="false"></div>
        @if ($w->groomParents || $w->brideParents)
          <div class="bs-parents">
            <div><small>ខាងកូនប្រុស</small>@foreach ($w->groomParents as $p)<p>{{ $p }}</p>@endforeach</div>
            <div><small>ខាងកូនស្រី</small>@foreach ($w->brideParents as $p)<p>{{ $p }}</p>@endforeach</div>
          </div>
        @endif
        <h2 class="bs-h"><span class="en">You are invited</span>សូមគោរពអញ្ជើញ</h2>
        <p class="bs-guest-name{{ $kh($guest) }}" id="bs-guest-name">{{ $guest }}</p>
        <p class="bs-reception">{{ $w->receptionText }}</p>
        <p class="bs-invite-couple">
          <span class="{{ trim($kh($w->groom)) }}">{{ $w->groom }}</span>
          <i class="bs-amp">&amp;</i>
          <span class="{{ trim($kh($w->bride)) }}">{{ $w->bride }}</span>
        </p>
        <div class="bs-when">
          <p>{{ $w->dateKhmer }}</p>
          @if ($w->khmerDate)<p class="bs-soft">{{ $w->khmerDate }}</p>@endif
          @if ($w->timeKhmer)<p class="bs-accent">{{ $w->timeKhmer }}</p>@endif
        </div>
      </div>
    </section>

    @if ($w->groomPhoto || $w->bridePhoto)
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">The bride &amp; groom</span>កូនកំលោះ និង កូនក្រមុំ</h3>
        <div class="bs-couple">
          @foreach ([[$w->groomPhoto, $w->groom, 'កូនកំលោះ'], [$w->bridePhoto, $w->bride, 'កូនក្រមុំ']] as [$photo, $name, $role])
            @if ($photo)
              <figure class="ik-r" data-ik="{{ $loop->first ? 'left' : 'right' }}">
                {{-- oval photo: gradient pink → gold ring, white gap, flowers on the outer bottom corner --}}
                <div class="bs-oval{{ $loop->first ? '' : ' is-right' }}">
                  <div class="bs-oval-ring">
                    <button type="button" class="bs-oval-photo" data-ik-photo data-group="couple" data-full="{{ $photo }}" data-cap="{{ $name }}"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></button>
                  </div>
                  {{-- the flowers-3 bouquet tucked on the oval's outer bottom corner --}}
                  <span class="bs-oval-fl" aria-hidden="true"><img class="b1" src="{{ $bouquet }}" alt="" loading="lazy" draggable="false"></span>
                </div>
                <figcaption><small>{{ $role }}</small><b class="{{ trim($kh($name)) }}">{{ $name }}</b></figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->message !== '')
      {{-- the couple's note on a glass letter card: pink → gold hairline, a dashed gold inner edge, flowers on two corners --}}
      <section class="bs-sec bs-sec--tight">
        <blockquote class="bs-card bs-glass bs-note ik-r" data-ik="up">
          {{-- the flowers-3 bouquet hanging upside down from the top-left corner, standing on the bottom-right one --}}
          <span class="bs-note-fl tl" aria-hidden="true"><img src="{{ $bouquet }}" alt="" loading="lazy" draggable="false"></span>
          <span class="bs-note-fl br" aria-hidden="true"><img src="{{ $bouquet }}" alt="" loading="lazy" draggable="false"></span>
          <span class="bs-note-mark" aria-hidden="true">“</span>
          <p class="{{ trim($kh($w->message)) }}" data-ik-words>{{ $w->message }}</p>
        </blockquote>
      </section>
    @endif

    {{-- ===== save the date ===== --}}
    @if ($w->calendar)
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Save the date</span>ថ្ងៃមង្គល</h3>
        <div class="bs-card bs-glass ik-r" data-ik="zoom">
          <p class="bs-cal-title">{{ $w->calendar['title'] }}</p>
          <div class="bs-cal">
            @foreach (W::KH_DAYS_SHORT as $d)<span class="bs-soft">{{ $d }}</span>@endforeach
            @foreach ($w->calendar['cells'] as $c)<span class="{{ $c === $w->calendar['day'] ? 'on' : '' }}">{{ $c ? W::khDigits($c) : '' }}</span>@endforeach
          </div>
          @if ($w->showCountdown)
            <div class="bs-count" id="bs-count" data-target="{{ $w->isoDate }}">
              @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $l)<div><b data-u="{{ $u }}">០០</b><span>{{ $l }}</span></div>@endforeach
            </div>
          @endif
        </div>
      </section>
    @endif

    @if ($w->story->isNotEmpty())
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Our story</span>ដំណើររឿងរបស់យើង</h3>
        <ol class="bs-story">
          @foreach ($w->story as $c)
            <li class="ik-r" data-ik="{{ $loop->odd ? 'left' : 'right' }}">
              <span class="bs-dot" aria-hidden="true" style="--n:{{ $loop->index }}"><svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.7 4.5c2.1 0 3.6 1.2 5.3 3.2 1.7-2 3.2-3.2 5.3-3.2 3.7 0 5.8 3.8 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg></span>
              <b class="{{ trim($kh($c['place'])) }}">{{ $c['place'] }}</b>@if ($c['when'])<small>{{ $c['when'] }}</small>@endif
              @if ($c['text'])<p>{{ $c['text'] }}</p>@endif
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    @if ($w->scheduleImage || $w->schedule->isNotEmpty())
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Programme</span>កម្មវិធី</h3>
        @if ($w->scheduleImage)
          <button type="button" class="bs-sched-img ik-r" data-ik="zoom" data-ik-photo data-group="sched" data-full="{{ $w->scheduleImage }}"><img src="{{ $w->scheduleImage }}" alt="កម្មវិធី" loading="lazy"></button>
        @else
          <ul class="bs-sched">
            {{-- each row: a blossom badge on the left (centred on the row), joined to the next by a thin line --}}
            @foreach ($w->schedule as $row)
              <li class="ik-r" data-ik="up" style="--n:{{ $loop->index }}">
                <i class="bs-sched-ic" aria-hidden="true"><svg viewBox="0 0 24 24"><g>@foreach ([0, 72, 144, 216, 288] as $deg)<ellipse cx="12" cy="6.6" rx="3.3" ry="4.7" transform="rotate({{ $deg }} 12 12)"/>@endforeach</g><circle cx="12" cy="12" r="2.6"/></svg></i>
                <b>{{ $row['time'] }}</b><span>{{ $row['label'] }}</span>
              </li>
            @endforeach
          </ul>
        @endif
      </section>
    @endif

    @if ($w->gallery->isNotEmpty())
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Moments</span>អនុស្សាវរីយ៍</h3>
        <div class="bs-gal">
          @foreach ($w->gallery as $i => $url)
            <button type="button" class="ik-r{{ $i % 5 === 0 ? ' wide' : '' }}" data-ik="mask" data-ik-photo data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $w->captions[$i] ?? '' }}" aria-label="{{ ($w->captions[$i] ?? '') ?: 'រូបភាពទី '.W::khDigits($i + 1) }}"><img src="{{ $url }}" alt="" loading="lazy"></button>
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->showVenue)
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Venue</span>ទីតាំង</h3>
        <div class="bs-card ik-r" data-ik="up">
          @if ($w->venueName)<p class="bs-venue{{ $kh($w->venueName) }}">{{ $w->venueName }}</p>@endif
          @if ($w->venueAddress)<p class="bs-soft">{{ $w->venueAddress }}</p>@endif
          @if ($w->mapEmbed)<iframe class="bs-map" src="{{ $w->mapEmbed }}" loading="lazy" title="ផែនទី"></iframe>@endif
          @if ($w->mapUrl)<a class="bs-btn" href="{{ $w->mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">មើលផែនទី</a>@endif
        </div>
      </section>
    @endif

    @if ($w->dressCode !== '' || $w->whatToBring !== '' || $w->rsvpBy)
      <section class="bs-sec bs-sec--guests">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">For our guests</span>ព័ត៌មានសម្រាប់ភ្ញៀវ</h3>
        @if ($w->dressCode !== '')
          <div class="bs-card ik-r" data-ik="up">
            <b>ពណ៌សម្លៀកបំពាក់</b>
            @if ($w->dressColours)
              <ul class="bs-swatches">@foreach ($w->dressColours as $c)<li style="--c:{{ $c['hex'] }}"><i></i><span class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</span></li>@endforeach</ul>
            @endif
            <p class="bs-soft">{{ $w->dressCode }}</p>
          </div>
        @endif
        <div class="bs-notes">
          @if ($w->whatToBring !== '')<p class="ik-r" data-ik="up"><small>សូមយកមកជាមួយ</small>{{ $w->whatToBring }}</p>@endif
          @if ($w->rsvpBy)<p class="ik-r" data-ik="up"><small>សូមឆ្លើយតបមុន</small>{{ $w->rsvpBy }}</p>@endif
        </div>
      </section>
    @endif

    @if ($w->showRsvp)
      <section class="bs-sec bs-sec--rsvp">
        <div class="bs-card bs-crowned ik-r" data-ik="fold" id="bs-rsvp-card">
          {!! $swing('top', 'bs-crown') !!}
          <h3 class="bs-h"><span class="en">RSVP</span>ការឆ្លើយតប</h3>
          <p>{{ $w->rsvpQuestion }}</p>
          <div class="bs-opts" id="bs-rsvp-opts" role="radiogroup" aria-label="ការឆ្លើយតប">
            <button type="button" role="radio" aria-checked="false" data-status="yes">ខ្ញុំនឹងចូលរួម ♥</button>
            <button type="button" role="radio" aria-checked="false" data-status="no">សោកស្ដាយ មិនអាចចូលរួមបាន</button>
          </div>
          <form class="bs-form" id="bs-rsvp-form">
            <textarea id="bs-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
            <button type="submit" class="bs-btn">ផ្ញើ</button>
          </form>
          <p id="bs-rsvp-thanks" class="bs-accent" role="status"></p>
        </div>
      </section>
    @endif

    @if ($w->giftQr)
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Wedding gift</span>ចំណងដៃ</h3>
        <div class="bs-card ik-r" data-ik="zoom">
          <p>{{ $w->giftMessage }}</p>
          <img class="bs-qr" src="{{ $w->giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
          @if ($w->giftAccount)<p class="bs-pre">{{ $w->giftAccount }}</p>@endif
          <div class="bs-row">
            @if ($w->giftNumber)<button type="button" class="bs-btn" data-copy="{{ $w->giftNumber }}">ចម្លងលេខគណនី</button>@endif
            <a class="bs-btn ghost" href="{{ $w->giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
          </div>
        </div>
      </section>
    @endif

    @if ($w->contacts->isNotEmpty() || $w->ctaLabel)
      <section class="bs-sec">
        <h3 class="bs-h ik-r" data-ik="up"><span class="en">Contact</span>ទំនាក់ទំនង</h3>
        <div class="bs-contacts">
          @foreach ($w->contacts as $c)
            <a class="ik-r" data-ik="up" href="tel:{{ $c['tel'] }}" data-sfx="tap"><small>{{ $c['role'] }}</small><b>{{ $c['name'] }}</b><span>{{ $c['phone'] }}</span></a>
          @endforeach
        </div>
        @if ($w->ctaLabel)<p style="text-align:center"><a class="bs-btn" href="{{ $w->ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $w->ctaLabel }}</a></p>@endif
      </section>
    @endif

    @foreach ($w->letters as [$khHead, $en, $text])
      <section class="bs-sec">
        <div class="bs-card bs-letter ik-r" data-ik="up"><h3 class="bs-h"><span class="en">{{ $en }}</span>{{ $khHead }}</h3><p>{{ $text }}</p></div>
      </section>
    @endforeach

    {{-- ===== finale: the frame one last time ===== --}}
    <section class="bs-hero bs-finale">
      <div class="bs-smoke bs-smoke--panel" aria-hidden="true"><i></i><i></i></div>
      <div class="bs-hero-body">
        @if ($w->background)
          {{-- the closing arch photo, with the flowers-3 bouquet standing on its bottom-left and (mirrored) bottom-right --}}
          <div class="bs-finale-frame ik-r" data-ik="zoom">
            <div class="bs-arch bs-finale-photo"><img src="{{ $w->background }}" alt="" loading="lazy"></div>
            <span class="bs-arch-fl l" aria-hidden="true"><img src="{{ $bouquet }}" alt="" loading="lazy" draggable="false"></span>
            <span class="bs-arch-fl r" aria-hidden="true"><img src="{{ $bouquet }}" alt="" loading="lazy" draggable="false"></span>
          </div>
        @endif
        <p class="bs-closing ik-r{{ $kh($w->closingLine) }}" data-ik="blur">{{ $w->closingLine }}</p>
        <button type="button" class="bs-btn ghost ik-r" data-ik="up" id="bs-breeze">ផ្ញើក្តីស្រឡាញ់ ♥</button>
      </div>
    </section>

    <x-invitation-footer :names="$w->coupleTitle" :names-class="W::isKhmer($w->coupleTitle) ? 'ik-kh' : ''" :date="$w->dateNumeric"
      style="--inv-footer-bg:#4a3438; --inv-footer-text:#fff7f6; --inv-footer-muted:#e3c9cc; --inv-footer-accent:{{ $w->accent }}; --inv-footer-names-font:'Great Vibes','Moulpali',cursive; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;" />
  </main>

  <x-invite-kit :w="$w" :sfx="['rustle' => 'rustle.wav']" wait />
</div>

<script src="{{ $bsAsset('js/invitations/blossom-swing.js') }}"></script>
