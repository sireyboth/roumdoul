@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Khmer Traditional Wedding. Everything personal comes from $fields (the standard
    // field catalog); all Khmer headings/labels below are template copy, not customer data.
    // On the unpurchased demo page $invitation is null, so every premium extra previews
    // unlocked (see docs/invitation-templates.md).
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomUpper = mb_strtoupper($groomName);
    $brideUpper = mb_strtoupper($brideName);
    $coupleTitle = collect([$groomUpper, $brideUpper])->filter()->implode(' & ');
    // Names may be typed in Khmer or English; Khmer script needs no tracking and taller lines.
    $kh = fn (?string $s) => preg_match('/\p{Khmer}/u', (string) $s) ? ' kw-kh' : '';

    $eventDate = ! empty($fields['event_date']) ? Carbon::parse($fields['event_date']) : null;
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate
        ? 'ថ្ងៃ'.$khDays[$eventDate->dayOfWeek].' ទី'.$khDigits($eventDate->day).' ខែ'.$khMonths[$eventDate->month - 1].' ឆ្នាំ'.$khDigits($eventDate->year)
        : null;
    $timeKhmer = $eventDate
        ? 'ម៉ោង '.$khDigits($eventDate->format('g:i')).' '.($eventDate->hour < 12 ? 'ព្រឹក' : ($eventDate->hour < 18 ? 'រសៀល' : 'ល្ងាច'))
        : null;
    $dateEnglish = $eventDate ? mb_strtoupper($eventDate->format('j F Y')).' | '.$eventDate->format('g:iA') : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    $venueName = $fields['venue_name'] ?? null;
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address'])) ? $fields['venue_address'] : null;
    $mapUrl = ($venueAddress || $venueName)
        ? 'https://maps.google.com/?q='.urlencode($venueAddress ?: $venueName)
        : null;
    $showVenue = $venueName || $venueAddress;

    $personalNote = ! empty($fields['message']) ? $fields['message'] : null;

    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : null;
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;
    $couplePhoto = $coverUrl ?: ($bridePhoto ?: $groomPhoto);

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))
        ->values();
    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))
        ->values();
    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()->map($resolveUrl)->values();
    // gallery layout: per-invitation 'gallery_layout' (frame|feed) or the 'gallery_feed' checkbox; ?layout= previews
    $galleryLayout = request('layout') ?: ($fields['gallery_layout'] ?? (array_key_exists('gallery_feed', $fields) ? ($fields['gallery_feed'] ? 'feed' : 'frame') : 'frame'));
    $galleryLayout = $galleryLayout === 'frame' ? 'frame' : 'feed';

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    $contacts = collect([
        ['name' => $groomUpper, 'phone' => trim($fields['groom_phone'] ?? ''), 'role' => 'ខាងកូនប្រុស'],
        ['name' => $brideUpper, 'phone' => trim($fields['bride_phone'] ?? ''), 'role' => 'ខាងកូនស្រី'],
    ])->filter(fn ($c) => $c['phone'] !== '')->values();

    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if ($musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '♡';
    $monogram = collect([$groomName, $brideName])->filter()->map($initial)->implode('&') ?: '♡';

    // wax seal for the closing: a slightly irregular disc, like pressed wax
    $sealEdge = 'M'.collect(range(0, 71))->map(function ($i) {
        $a = $i / 72 * 2 * M_PI;
        $r = 52 + 2.2 * sin($a * 9) + 1.3 * sin($a * 23 + 1.3);

        return round(cos($a) * $r, 2).' '.round(sin($a) * $r, 2);
    })->implode('L').'Z';

    // ------------------------------------------------------------------------------------
    // Scenery (pure decoration, no customer data). Built once here and echoed wherever a
    // scene needs it: Angkor Wat + sugar palms, roumdul branches, flying and perched birds.
    // ------------------------------------------------------------------------------------

    // lotus-bud prang (Angkor tower): base y $b, tip y $t, half-width $w, plus tier ribs
    $tower = function (float $cx, float $b, float $t, float $w): string {
        $h = $b - $t;
        $shape = sprintf('<path d="M%s %s C%s %s %s %s %s %sC%s %s %s %s %s %sZ"/>',
            $cx - $w, $b, $cx - $w, $b - $h * .55, $cx - $w * .3, $t + $h * .12, $cx, $t,
            $cx + $w * .3, $t + $h * .12, $cx + $w, $b - $h * .55, $cx + $w, $b);
        $ribs = '';
        foreach ([.22, .42, .6, .76] as $k) {
            $y = $b - $h * $k;
            $hw = $w * (1 - $k * $k * .75);
            $ribs .= sprintf('<path class="rib" d="M%s %sQ%s %s %s %s"/>', $cx - $hw, $y, $cx, $y - 1.4, $cx + $hw, $y);
        }

        return $shape.$ribs;
    };
    // thnot (sugar palm): slim trunk with a round crown of fan leaves; sways from its base
    $palm = function (float $x, float $h, float $lean, string $t, string $d) {
        $top = 110 - $h;
        $cx = $x + $lean;
        $fronds = '';
        foreach (range(0, 13) as $i) {
            $a = deg2rad(-200 + $i * 17.5);
            $len = 9 + ($i % 3) * 2.5;
            $fronds .= sprintf('<path d="M%1$s %2$sQ%3$s %4$s %5$s %6$s"/>', $cx, $top,
                $cx + cos($a) * $len * .6, $top + sin($a) * $len * .6 - 2, $cx + cos($a) * $len, $top + sin($a) * $len + 2);
        }

        return sprintf('<g class="kw-palm" style="--t:%s;--d:%s;transform-origin:%spx 110px">'
            .'<path class="trunk" d="M%s 110Q%s %s %s %s"/><circle cx="%s" cy="%s" r="4.5"/><g class="frond">%s</g></g>',
            $t, $d, $x, $x, $x, 110 - $h * .5, $cx, $top, $cx, $top, $fronds);
    };
    $templeSvg = '<svg class="kw-temple" viewBox="-70 0 540 112" preserveAspectRatio="xMidYMax meet" aria-hidden="true" focusable="false">'
        .$palm(-48, 70, -3, '9s', '-2s').$palm(-22, 84, 2, '11s', '-5s').$palm(424, 78, 3, '10s', '-1s').$palm(452, 62, -2, '8.5s', '-4s')
        .'<g class="wat">'
        .'<rect x="-70" y="106" width="540" height="6"/>'
        .'<path d="M22 106V96h356v10Z"/><path d="M30 96l6-4h328l6 4Z"/>'
        .$tower(40, 92, 74, 7).$tower(360, 92, 74, 7)
        .'<path d="M78 92V80h244v12Z"/><path d="M84 80l5-3h222l5 3Z"/>'
        .'<path d="M122 77V64h156v13Z"/><path d="M168 64V56h64v8Z"/>'
        .$tower(140, 64, 34, 12).$tower(260, 64, 34, 12)
        .$tower(170, 60, 24, 12).$tower(230, 60, 24, 12)
        .$tower(200, 56, 4, 17)
        .'</g></svg>';

    // flying birds: [top %, cross seconds, delay, size px, flap seconds, opacity, right-to-left]
    $flock = function (array $birds): string {
        $out = '';
        foreach ($birds as [$y, $t, $d, $s, $f, $o, $rev]) {
            $out .= sprintf('<div class="kw-fly%s" style="top:%s%%;--t:%ss;--d:%ss;opacity:%s"><div class="kw-fly-bob" style="--b:%ss">'
                .'<svg class="kw-gull" style="width:%spx;--f:%ss" viewBox="-28 -14 56 24" aria-hidden="true" focusable="false">'
                .'<g class="kw-wings"><path d="M0 0C-6-8-14-10-26-7-18-5-10-2-2 2Z"/><path d="M0 0C6-8 14-10 26-7 18-5 10-2 2 2Z"/></g>'
                .'<ellipse cx="0" cy="1" rx="3.2" ry="2.4"/></svg></div></div>',
                $rev ? ' rev' : '', $y, $t, $d, $o, round($t / 3.4, 1), $s, $f);
        }

        return '<div class="kw-flock kw-deco" aria-hidden="true">'.$out.'</div>';
    };

    // a small bird perched on the branch: looks around, pecks, flicks its tail
    $perchedBird = '<g transform="translate(146 79) rotate(10) scale(1.2)"><g class="kw-perch">'
        .'<g class="tail"><path d="M-11-6-29-1-27 3-10-2Z"/></g>'
        .'<path class="legs" d="M-3-1-4 1M2-1 2 1"/>'
        .'<path class="body" d="M-14-8C-10-19 4-21 10-14 14-10 12-2 4 0-4 2-12-1-14-8Z"/>'
        .'<path class="wing" d="M-9-12C-2-16 6-13 6-8 2-5-6-5-13-7Z"/>'
        .'<g class="head"><circle class="body" cx="10" cy="-18" r="6"/><path class="beak" d="M15.4-19.4 22-17.6 15.2-16.4Z"/><circle class="eye" cx="12" cy="-19.4" r="1.1"/></g>'
        .'</g></g>';

    // roumdul branch hanging from the top-left corner; three nested sways desync for a natural, wind-like motion
    $bloom = fn (float $x, float $y, float $s, string $t, string $d) => sprintf(
        '<g transform="translate(%s %s) scale(%s)"><g class="kw-bob" style="--t:%s;--d:%s"><use href="#kw-roumdul"/></g></g>', $x, $y, $s, $t, $d);
    $leaf = fn (float $x, float $y, float $r, float $s = 1) => sprintf(
        '<use class="lf" href="#kw-leaf" transform="translate(%s %s) rotate(%s) scale(%s)"/>', $x, $y, $r, $s);
    $branch = fn (bool $withBird) => '<svg class="kw-branch" viewBox="0 0 260 300" aria-hidden="true" focusable="false">'
        .'<g class="kw-sw" style="--t:8s;--d:-2s;--a:1.8deg;transform-origin:0px 0px">'
        .'<path class="stem" d="M-10 2C40 8 82 30 110 70 134 104 150 142 156 190"/>'
        .'<path class="stem thin" d="M52 16C70 10 86 4 104 6"/>'
        .$leaf(26, 6, 165, .9).$leaf(46, 13, 32, .8).$leaf(78, 34, 128, 1).$leaf(96, 50, 20, .85).$leaf(104, 6, -12, .7)
        .$leaf(126, 100, 145, .9).$leaf(142, 128, 40, .8).$leaf(150, 160, 120, .75)
        .$bloom(104, 8, .7, '3.8s', '-1s').$bloom(156, 194, 1, '4.2s', '-.4s').$bloom(126, 104, .75, '3.4s', '-2.2s')
        .'<g class="kw-sw" style="--t:5.8s;--d:-1s;--a:2.6deg;transform-origin:110px 70px">'
        .'<path class="stem" d="M110 70C138 72 172 86 200 118"/>'
        .$leaf(170, 84, -20, .85).$leaf(186, 100, 70, .9).$leaf(128, 72, 200, .7)
        .$bloom(202, 122, .95, '3.6s', '-1.4s').$bloom(186, 104, .62, '4.6s', '-3s')
        .($withBird ? $perchedBird : '')
        .'</g>'
        .'<g class="kw-sw" style="--t:4.6s;--d:-3s;--a:3.4deg;transform-origin:142px 130px">'
        .'<path class="stem thin" d="M142 130C160 140 170 160 172 184"/>'
        .$leaf(160, 146, 20, .7).$bloom(173, 188, .8, '3.2s', '-.8s')
        .'</g></g></svg>';
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Great+Vibes&family=Moul&family=Noto+Sans+Khmer:wght@400;500;600&family=Noto+Serif+Khmer:wght@400;600;700&display=swap">
<style>
  @property --kw-ang{ syntax:'<angle>'; inherits:false; initial-value:0deg; }
  :root{
    color-scheme: light;
    --red:#7A1515; --gold:#C9A24D; --gold-hi:#F3DDA0; --ivory:#F8F1E3; --maroon:#4A0D0D; --night:#140404; --beige:#E8D8BD;
    --gold-soft:rgba(201,162,77,.4); --ink:#3a1a14;
    --f-moul:'Moul','Khmer OS Muol Light','Noto Serif Khmer',serif;
    --f-kh-serif:'Noto Serif Khmer','Khmer OS Siemreap','Hanuman',serif;
    --f-kh-sans:'Noto Sans Khmer','Khmer OS Battambang','Battambang',sans-serif;
    --f-caps:'Cinzel','Cormorant Garamond','Times New Roman',serif;
    --f-en:'Cormorant Garamond','Playfair Display','Times New Roman',serif;
    --f-love:'Great Vibes','Cormorant Garamond',cursive;
    /* Cinzel has no Khmer glyphs, so the browser renders Latin letters in Cinzel and Khmer letters in Moul. */
    --f-names:'Cinzel','Moul','Khmer OS Muol Light','Noto Serif Khmer',serif;
    --ease:cubic-bezier(.22,.8,.3,1);
    --cine:cubic-bezier(.7,0,.3,1);
  }
  html{ background:var(--night); scroll-behavior:smooth; }
  body.kw-body{ margin:0; background:radial-gradient(120% 60% at 50% 0%, #2a0808, var(--night) 70%) fixed; color:var(--ink); font-family:var(--f-kh-sans); -webkit-font-smoothing:antialiased; overflow-x:hidden; }
  body.kw-locked{ overflow:hidden; height:100vh; }
  .kw *,.kw *::before,.kw *::after{ box-sizing:border-box; }
  .kw h1,.kw h2,.kw h3,.kw h4,.kw p{ margin:0; font-weight:inherit; }
  .kw svg{ display:block; }
  :where(.kw) button{ font:inherit; color:inherit; background:none; border:0; cursor:pointer; }
  .kw :focus-visible{ outline:2px solid var(--gold); outline-offset:3px; }

  /* the invitation is a phone-width column; on larger screens it floats on a night backdrop */
  .kw-page{ position:relative; z-index:1; max-width:520px; margin:0 auto; background:transparent; overflow:hidden;
    box-shadow:0 0 0 1px rgba(201,162,77,.25), 0 30px 90px rgba(0,0,0,.6); }

  /* rendered (Blender) animation, shown once the invitation opens: the card + flowers video sits behind
     the page, the butterflies fly over the text and let taps pass through. Both are H.264 (hardware-decoded
     on phones); the butterfly MP4 carries colour on its left half and the alpha mask on its right half,
     recombined into a transparent canvas with WebGL. */
  /* sized to the largest viewport (address bar hidden), so the video never rescales when the bar slides away on scroll */
  .kw-stage{ position:fixed; top:0; height:100vh; height:100lvh; left:50%; width:min(100%,520px); transform:translate3d(-50%,0,0); will-change:transform; pointer-events:none;
    opacity:0; visibility:hidden; transition:opacity .4s ease, visibility 0s linear .4s; }
  .kw-stage video,.kw-stage canvas{ width:100%; height:100%; object-fit:cover; display:block; }
  .kw-stage.bg{ z-index:0; background:var(--ivory); }
  .kw-stage.fg{ z-index:35; }
  /* the butterflies already fly over the cover; they step aside while the roumdul blooms (the stage restarts them in sync underneath) */
  .kw-covering .kw-stage.fg{ z-index:55; opacity:1; visibility:visible; transition-delay:0s,0s; }
  .kw-covering .kw-gate.blooming ~ .kw-stage.fg{ opacity:0; visibility:hidden; transition:opacity .4s ease, visibility 0s linear .4s; }
  .kw-staged .kw-stage{ opacity:1; visibility:visible; transition-delay:0s, 0s; }
  /* the butterfly source video must stay "on screen": iOS throttles decoding of display:none videos */
  .kw-stage-src{ position:fixed; left:0; top:0; width:2px; height:2px; opacity:.01; pointer-events:none; z-index:-1; }
  /* once the video runs, drop the effects that make phones repaint every frame */
  .kw-staged .kw-dustfield{ display:none; }
  .kw-staged .kw-branch{ filter:none; }
  .kw-js .kw-staged .kw-rev{ filter:none; }
  /* performance test switch (?kwtest=nofx): freezes every CSS/SVG ambient animation on the page */
  .kw-nofx .kw-page *,.kw-nofx .kw-petals,.kw-nofx .kw-petals *{ animation:none !important; }
  .kw-sec{ position:relative; padding:76px 26px; text-align:center; overflow:hidden; }
  .kw-sec > :not(.kw-deco):not(.kw-frame):not(.kw-corner){ position:relative; z-index:1; }
  .kw-deco{ position:absolute; pointer-events:none; }
  .kw-pattern{ background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Cpath d='M22 3 41 22 22 41 3 22Z' fill='none' stroke='%23C9A24D' stroke-opacity='.14'/%3E%3Ccircle cx='22' cy='22' r='1.6' fill='%23C9A24D' fill-opacity='.2'/%3E%3C/svg%3E"); }
  /* the "red" and "maroon" sections sit directly on the rendered floral background, so they are
     see-through and remap their light-on-dark palette to deep ink on the cream backdrop */
  .kw-red,.kw-maroon{ background:transparent; --ivory:#4A0D0D; --beige:#6b3a2e; --gold:#9a6f22; --gold-hi:#8a1c1c; --gold-soft:rgba(154,111,34,.4); color:var(--ivory); }
  .kw-red .kw-shimmer,.kw-maroon .kw-shimmer{ background-image:linear-gradient(100deg,#7a1515 0%,#b0822f 22%,#8a1c1c 44%,#c9973f 62%,#6e1212 82%,#a87a2e 100%); }
  .kw-red .kw-btn.gold{ background:linear-gradient(180deg,#8f1c1c,#7A1515); color:#F8F1E3; }
  .kw-maroon .kw-count > div{ background:rgba(255,250,240,.45); }
  .kw-beige{ background:rgba(232,216,189,.55); }

  /* scenery colour roles: gold leaf on the red/maroon sections */
  .kw-red,.kw-maroon{ --br-stem:#C9A24D; --br-leaf:rgba(201,162,77,.22); --br-leaf-edge:#C9A24D; --br-flower:#FBF4E4; --br-flower-edge:#C9A24D;
    --br-bird:#C9A24D; --br-bird-wing:#E9CD85; --br-bird-eye:#4A0D0D; --gull:rgba(110,40,30,.7); --tp-fill:rgba(74,13,13,.35); --tp-rim:rgba(201,162,77,.75); }

  /* type */
  .kw-eyebrow{ font-family:var(--f-moul); font-size:.95rem; letter-spacing:.02em; color:var(--gold); line-height:2; }
  .kw-title{ font-family:var(--f-moul); font-weight:400; font-size:1.3rem; line-height:2; color:var(--red); }
  .kw-red .kw-title,.kw-maroon .kw-title{ color:var(--gold); }
  .kw-names{ font-family:var(--f-names); font-weight:500; letter-spacing:.14em; line-height:1.2; overflow-wrap:anywhere; }
  .kw-names.kw-kh,.kw-footer-names.kw-kh,.kw-contact .n.kw-kh,.kw-venue-name.kw-kh{ font-weight:400; letter-spacing:0 !important; line-height:1.9; }
  .kw-body-text{ font-family:var(--f-kh-sans); font-size:1rem; line-height:2; text-wrap:pretty; overflow-wrap:break-word; }
  .kw-script{ font-family:var(--f-en); font-style:italic; color:var(--gold); }
  .kw-shimmer{ background:linear-gradient(100deg,#a87a2e 0%,#f7e2a8 22%,#c9a24d 44%,#fff3cf 62%,#b8893a 82%,#e9cd85 100%); background-size:260% 100%;
    -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; animation:kw-sheen 9s ease-in-out infinite; }
  /* handwritten English line under each Khmer heading */
  .kw-sub{ font-family:var(--f-love); font-size:1.75rem; line-height:1.25; color:var(--gold); margin-top:2px; }
  .kw-red .kw-sub,.kw-maroon .kw-sub{ color:var(--gold-hi); }

  /* GSAP-driven moments start hidden only once GSAP is running (no GSAP / reduced motion: shown as-is) */
  .kw-gsap .kw-gs{ opacity:0; }

  /* ornaments */
  .kw-divider{ display:flex; align-items:center; justify-content:center; gap:12px; margin:18px auto 30px; max-width:280px; color:var(--gold); }
  .kw-divider i{ flex:1; height:1px; background:linear-gradient(90deg,transparent,var(--gold)); position:relative; }
  .kw-divider i::after{ content:""; position:absolute; right:6px; top:-2px; width:5px; height:5px; background:var(--gold); transform:rotate(45deg); }
  .kw-divider i:last-child{ transform:scaleX(-1); }
  .kw-divider svg{ width:64px; height:32px; flex:none; animation:kw-breathe 6s ease-in-out infinite; }
  .kw-corner{ position:absolute; width:66px; height:66px; color:var(--gold); pointer-events:none; }
  .kw-corner.tl{ top:6px; left:6px; } .kw-corner.tr{ top:6px; right:6px; transform:scaleX(-1); }
  .kw-corner.bl{ bottom:6px; left:6px; transform:scaleY(-1); } .kw-corner.br{ bottom:6px; right:6px; transform:scale(-1,-1); }
  .kw-frame{ position:absolute; inset:16px; border:1px solid var(--gold-soft); pointer-events:none; }

  /* ---------- scenery pieces ---------- */
  /* roumdul branch */
  .kw-branch-wrap{ position:absolute; top:0; width:min(40%,210px); pointer-events:none; z-index:2; }
  .kw-branch-wrap.tl{ left:0; } .kw-branch-wrap.tr{ right:0; transform:scaleX(-1); }
  .kw-branch{ width:100%; height:auto; overflow:visible; filter:drop-shadow(0 6px 10px rgba(0,0,0,.25)); }
  .kw-branch .stem{ fill:none; stroke:var(--br-stem); stroke-width:2.4; stroke-linecap:round; }
  .kw-branch .stem.thin{ stroke-width:1.5; }
  .kw-branch .lf{ fill:var(--br-leaf); stroke:var(--br-leaf-edge); stroke-width:.7; }
  .kw-branch use[href="#kw-roumdul"]{ fill:var(--br-flower); stroke:var(--br-flower-edge); stroke-width:.7; }
  .kw-sw,.kw-bob,.kw-palm,.kw-perch .head,.kw-perch .tail{ transform-box:view-box; }
  .kw-sw{ animation:kw-sway var(--t) ease-in-out var(--d) infinite; }
  .kw-bob{ animation:kw-bob var(--t) ease-in-out var(--d) infinite; }
  /* perched bird */
  .kw-perch .body,.kw-perch .tail path{ fill:var(--br-bird); }
  .kw-perch .wing{ fill:var(--br-bird-wing); }
  .kw-perch .beak{ fill:var(--br-flower-edge); }
  .kw-perch .eye{ fill:var(--br-bird-eye); }
  .kw-perch .legs{ stroke:var(--br-bird); stroke-width:1.2; fill:none; }
  .kw-perch .head{ transform-origin:6px -13px; animation:kw-look 7s ease-in-out infinite; }
  .kw-perch .tail{ transform-origin:-11px -4px; animation:kw-flick 4.3s ease-in-out infinite; }
  /* flying birds */
  .kw-flock{ inset:0; z-index:1; overflow:hidden; }
  .kw-fly{ position:absolute; left:0; width:100%; animation:kw-cross var(--t) linear var(--d) infinite; }
  .kw-fly.rev{ animation-direction:reverse; }
  .kw-fly-bob{ animation:kw-glide var(--b) ease-in-out infinite alternate; }
  .kw-gull{ height:auto; fill:var(--gull); overflow:visible; }
  .kw-wings{ animation:kw-flap var(--f) ease-in-out infinite; }
  /* temple + palms */
  .kw-temple{ width:100%; height:auto; overflow:visible; }
  .kw-temple .wat,.kw-temple circle{ fill:var(--tp-fill); }
  .kw-temple .wat > path:not(.rib),.kw-temple .wat > rect{ stroke:var(--tp-rim); stroke-width:.5; }
  .kw-temple .rib{ fill:none; stroke:var(--tp-rim); stroke-width:.55; }
  .kw-temple .trunk,.kw-temple .frond path{ fill:none; stroke:var(--tp-fill); stroke-linecap:round; }
  .kw-temple .trunk{ stroke-width:2.4; } .kw-temple .frond path{ stroke-width:1.8; }
  .kw-palm{ animation:kw-sway var(--t) ease-in-out var(--d) infinite; --a:1.2deg; }
  /* floating gold dust */
  .kw-dustfield{ inset:0; z-index:1; overflow:hidden; }
  .kw-dust{ position:absolute; width:var(--s); height:var(--s); border-radius:50%; opacity:0;
    background:radial-gradient(circle,#fff6d2 0,#e7c06a 45%,transparent 72%); box-shadow:0 0 8px rgba(255,214,130,.7);
    animation:kw-dust var(--t) ease-in-out var(--d) infinite; }
  /* falling petals (whole page, after the invitation opens) */
  .kw-petals{ position:fixed; inset:0; max-width:520px; margin:0 auto; pointer-events:none; z-index:30; overflow:hidden; }
  .kw-petal{ position:absolute; top:0; left:var(--x); animation:kw-fall var(--t) linear var(--d) infinite; }
  .kw-petal b{ display:block; width:var(--s); height:calc(var(--s) * 1.35); border-radius:100% 0 100% 0;
    background:radial-gradient(circle at 30% 30%,#fffdf5,#f3dfa6 62%,#d6ad57); box-shadow:0 2px 4px rgba(74,13,13,.18);
    animation:kw-flutter var(--f) ease-in-out infinite; }

  /* ============ cover ============ */
  /* cover.mp4 (9:16) is the whole cover: its first frame is the card, and the open tap plays it through a
     roumdul bloom that lands on the background video's first frame. The live text sits on the same 9:16 box,
     positioned in % and sized in cqw so it stays on the card's rules at any screen size. */
  .kw-gate{ position:fixed; inset:0; z-index:50; overflow:hidden; background:#0b0202; transition:opacity .9s ease, visibility 0s linear .9s; }
  .kw-gate.open{ opacity:0; visibility:hidden; pointer-events:none; }
  /* tall screens fill the width (cropping the sides a little); wider ones show the whole card, centred */
  .kw-cover{ position:absolute; left:50%; top:50%; aspect-ratio:9/16; transform:translate(-50%,-50%); container-type:inline-size; background:#f6efe6;
    width:max(100vw,calc(100vh * 9 / 16)); width:max(100vw,calc(100svh * 9 / 16)); }
  @media (min-aspect-ratio:19/32){ .kw-cover{ width:calc(100vh * 9 / 16); width:calc(100svh * 9 / 16); } }
  .kw-cover video{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; }
  .kw-cover-ui{ position:absolute; inset:0; color:#6e1111; text-align:center; transition:opacity .7s ease; }
  .kw-gate.blooming .kw-cover-ui{ opacity:0; pointer-events:none; }
  .kw-cover-title{ position:absolute; left:14.2%; top:14.8%; width:71.6%; height:auto; display:block; }
  /* the text lines stay inside what the screen shows when the card's sides are cropped */
  .kw-cover-guest{ position:absolute; left:max(6%,calc((100cqw - 100vw) / 2 + 12px)); right:max(6%,calc((100cqw - 100vw) / 2 + 12px)); top:65.4%; height:8.2%;
    display:flex; align-items:center; justify-content:center; font-family:var(--f-kh-serif); font-weight:700; font-size:min(5.6cqw,6vw); line-height:1.4; overflow-wrap:anywhere; }
  .kw-cover-when{ position:absolute; left:max(5%,calc((100cqw - 100vw) / 2 + 12px)); right:max(5%,calc((100cqw - 100vw) / 2 + 12px)); top:80%;
    font-family:var(--f-kh-serif); font-weight:600; font-size:min(3.6cqw,3.9vw); line-height:1.5; }
  .kw-cover-when .en{ font-family:var(--f-kh-sans); font-weight:400; font-size:min(3.5cqw,3.8vw); line-height:1.55; letter-spacing:.02em; }
  .kw-cover-open{ position:absolute; left:28.7%; top:90.1%; width:43.6%; height:6%; display:flex; align-items:center; justify-content:center; border-radius:2.4cqw;
    transition:scale .3s var(--ease); }
  .kw-cover-open:active{ scale:.96; }
  .kw-cover-open img{ width:50%; height:auto; display:block; pointer-events:none; }
  .kw-cover-ui > *{ animation:kw-cover-in 1.4s var(--ease) both; }
  .kw-cover-ui > :nth-child(2){ animation-delay:.25s } .kw-cover-ui > :nth-child(3){ animation-delay:.45s }
  .kw-cover-ui > .kw-cover-open{ animation:kw-cover-in 1.4s var(--ease) .65s both, kw-cover-call 2.6s ease-out 2.4s infinite; }
  .kw-gate.blooming .kw-cover-ui > .kw-cover-open{ animation:none; }

  .kw-btn{ position:relative; display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:50px; padding:12px 32px; border-radius:999px; font-family:var(--f-kh-serif); font-weight:600; font-size:1rem;
    background:linear-gradient(180deg,#8f1c1c,var(--red)); color:var(--ivory); border:1px solid var(--gold); box-shadow:0 10px 28px rgba(122,21,21,.35), inset 0 1px 0 rgba(255,230,170,.25);
    transition:transform .4s var(--ease), box-shadow .4s; text-decoration:none; overflow:hidden; }
  .kw-btn::after{ content:""; position:absolute; top:0; bottom:0; width:40%; left:-60%; background:linear-gradient(100deg,transparent,rgba(255,240,200,.35),transparent); animation:kw-sweep 4.5s ease-in-out infinite; }
  .kw-btn:hover{ transform:translateY(-2px); box-shadow:0 14px 34px rgba(122,21,21,.45), inset 0 1px 0 rgba(255,230,170,.3); }
  .kw-btn.gold{ background:linear-gradient(180deg,#e2c272,var(--gold)); color:var(--maroon); box-shadow:0 10px 26px rgba(0,0,0,.3); }

  /* ============ hero ============ */
  .kw-hero{ padding-top:96px; padding-bottom:88px; }
  .kw-hero .kw-flock{ bottom:auto; height:46%; }
  .kw-hero-glow{ left:50%; top:60px; width:420px; height:420px; transform:translateX(-50%); border-radius:50%;
    background:radial-gradient(circle,rgba(255,214,140,.28),transparent 65%); animation:kw-glow 8s ease-in-out infinite alternate; }
  .kw-hero .kw-names{ font-size:clamp(1.6rem,8vw,2.7rem); letter-spacing:.1em; }
  .kw-arch{ width:min(70%,280px); aspect-ratio:3/4; margin:0 auto 36px; border-radius:999px 999px 6px 6px; padding:7px; border:1px solid var(--gold); position:relative;
    box-shadow:0 20px 50px rgba(0,0,0,.35); }
  .kw-arch::before{ content:""; position:absolute; inset:-1px; border-radius:inherit; padding:1.5px;
    background:conic-gradient(from var(--kw-ang),transparent 0 70%,#fff1c4 82%,transparent 92%);
    -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0); -webkit-mask-composite:xor; mask-composite:exclude; animation:kw-orbit 7s linear infinite; }
  .kw-arch-in{ width:100%; height:100%; overflow:hidden; border-radius:999px 999px 2px 2px; }
  .kw-arch img{ width:100%; height:100%; object-fit:cover; display:block; animation:kw-kenburns 26s ease-in-out infinite alternate; }
  .kw-guest{ font-family:var(--f-moul); font-size:1.25rem; color:var(--gold-hi); line-height:2.1; margin:6px 0; overflow-wrap:anywhere; }
  .kw-date-block{ margin-top:32px; padding:24px 14px; border-top:1px solid var(--gold-soft); border-bottom:1px solid var(--gold-soft); }
  .kw-date-num{ font-family:var(--f-caps); font-size:2rem; letter-spacing:.2em; color:var(--gold); }
  .kw-hero-names{ display:flex; flex-direction:column; align-items:center; }
  .kw-amp{ font-family:var(--f-love); font-size:clamp(2.6rem,12vw,3.6rem); line-height:1.1; color:var(--gold); }
  .kw-tagline{ margin-top:16px; }
  .kw-tagline .en{ font-family:var(--f-love); font-size:1.75rem; line-height:1.3; color:var(--gold-hi); }
  .kw-tagline .kh{ font-family:var(--f-kh-serif); font-size:.95rem; line-height:1.9; color:var(--beige); letter-spacing:.02em; }
  /* the couple's own words, set like a handwritten note */
  .kw-note{ position:relative; max-width:360px; margin:28px auto 0; padding:22px 18px 14px; border-top:1px solid var(--gold-soft); border-bottom:1px solid var(--gold-soft);
    font-family:'Cormorant Garamond','Noto Serif Khmer',serif; font-style:italic; font-size:1.15rem; line-height:1.9; color:var(--beige); overflow-wrap:break-word; }
  .kw-note::before{ content:"\201C"; position:absolute; left:50%; top:-.62em; transform:translateX(-50%); padding:0 10px; font-family:var(--f-en); font-style:normal; font-size:3rem; line-height:1; color:var(--gold); }

  /* love verse: a heartbeat line that draws itself into a heart */
  .kw-heartline{ width:min(100%,330px); height:auto; margin:4px auto 22px; overflow:visible; }
  .kw-heartline .ln{ fill:none; stroke:var(--red); stroke-width:1.6; stroke-linecap:round; stroke-linejoin:round; }
  .kw-heartline .gl{ fill:none; stroke:var(--gold); stroke-width:5; stroke-linecap:round; stroke-linejoin:round; opacity:.22; }
  .kw-heartline use{ fill:#fffaf0; stroke:var(--gold); stroke-width:.8; }
  .kw-verse-kh p{ font-family:var(--f-kh-serif); font-size:1.08rem; line-height:2.15; color:var(--maroon); }
  .kw-verse-en{ max-width:340px; margin:22px auto 0; font-family:var(--f-en); font-style:italic; font-size:1.22rem; line-height:1.7; color:#7a4a32; }

  /* couple */
  .kw-role{ font-family:var(--f-kh-serif); color:var(--gold); font-size:.95rem; letter-spacing:.06em; margin-top:18px; }
  /* the couple: one full-bleed photo, names over a soft dusk gradient */
  .kw-duo{ position:relative; margin:4px -26px 0; overflow:hidden; aspect-ratio:4/5; background:var(--maroon);
    border-top:1px solid var(--gold); border-bottom:1px solid var(--gold); box-shadow:0 24px 50px rgba(74,13,13,.22); }
  .kw-duo-img{ position:absolute; inset:0; }
  .kw-duo-img img,.kw-duo-img .ph{ width:100%; height:100%; object-fit:cover; display:block; }
  .kw-duo-img .ph{ display:flex; align-items:center; justify-content:center; background:linear-gradient(160deg,#7A1515,#3a0909); color:var(--gold-hi); font-family:var(--f-names); font-size:4.5rem; letter-spacing:.06em; }
  .kw-duo::after{ content:""; position:absolute; inset:0; pointer-events:none;
    background:linear-gradient(180deg,rgba(20,4,4,0) 38%,rgba(20,4,4,.45) 64%,rgba(20,4,4,.88)); }
  .kw-duo::before{ content:""; position:absolute; inset:12px; z-index:1; border:1px solid rgba(243,221,160,.45); pointer-events:none; }
  .kw-duo-cap{ position:absolute; left:0; right:0; bottom:0; z-index:2; display:flex; align-items:flex-end; justify-content:center; gap:10px; padding:0 26px 34px; }
  .kw-duo-person{ flex:1; min-width:0; }
  .kw-duo .kw-role{ margin:0 0 4px; font-size:.78rem; color:var(--gold-hi); }
  .kw-duo .kw-names{ font-size:clamp(1.05rem,5.4vw,1.5rem); color:#fffaf0; text-shadow:0 2px 12px rgba(0,0,0,.45); }
  .kw-duo-heart{ flex:none; width:38px; height:38px; margin-bottom:2px; }
  .kw-duo-heart svg{ width:100%; height:100%; fill:#fffaf0; stroke:var(--gold); stroke-width:.8; animation:kw-spin-slow 24s linear infinite; }
  .kw-destiny{ margin-top:34px; }
  .kw-destiny .kh{ font-family:var(--f-kh-serif); font-size:1rem; line-height:2; color:var(--maroon); }

  /* story timeline */
  .kw-tl{ position:relative; text-align:left; margin-top:8px; padding-left:34px; }
  .kw-tl::before{ content:""; position:absolute; left:9px; top:6px; bottom:6px; width:1px; background:linear-gradient(var(--gold),var(--gold-soft)); }
  .kw-tl-item{ position:relative; padding-bottom:30px; }
  .kw-tl-item:last-child{ padding-bottom:0; }
  .kw-tl-item::before{ content:""; position:absolute; left:-30px; top:9px; width:11px; height:11px; background:var(--ivory); border:1px solid var(--gold); transform:rotate(45deg); }
  .kw-tl-item h3{ font-family:var(--f-kh-serif); font-weight:700; color:var(--red); font-size:1.15rem; line-height:1.8; }
  .kw-tl-item p{ font-size:.95rem; line-height:2; color:#5a3a30; }
  .kw-tl-num{ font-family:var(--f-caps); color:var(--gold); font-size:.8rem; letter-spacing:.3em; }
  /* a red thread runs down the timeline as you read; each chapter lights up when it reaches it */
  .kw-tl-fill{ display:none; position:absolute; left:8px; top:6px; bottom:6px; width:3px; border-radius:3px; background:linear-gradient(var(--red),#b0822f); transform-origin:top; }
  .kw-gsap .kw-tl-fill{ display:block; }
  .kw-tl-item::before{ transition:background .5s, border-color .5s, box-shadow .5s; }
  .kw-tl-item.lit::before{ background:var(--red); border-color:var(--red); box-shadow:0 0 0 5px rgba(122,21,21,.12); }

  /* countdown */
  .kw-count{ display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:8px; }
  .kw-count > div{ border:1px solid var(--gold-soft); padding:16px 2px 12px; background:linear-gradient(180deg,rgba(248,241,227,.06),rgba(248,241,227,.01)); }
  .kw-count b{ display:block; font-family:var(--f-caps); font-weight:500; font-size:clamp(1.6rem,8vw,2.4rem); line-height:1.15; color:var(--gold); font-variant-numeric:tabular-nums; }
  .kw-count span{ display:block; margin-top:6px; font-family:var(--f-caps); font-size:.58rem; letter-spacing:.2em; color:var(--beige); }

  /* schedule */
  .kw-sched{ display:flex; flex-direction:column; gap:14px; text-align:left; }
  .kw-sched-item{ position:relative; display:grid; grid-template-columns:96px 1fr; gap:14px; align-items:center; padding:16px 16px 16px 14px; background:#fffaf0; border:1px solid var(--gold-soft);
    transition:transform .5s var(--ease), box-shadow .5s; }
  .kw-sched-item:hover{ transform:translateX(4px); box-shadow:-4px 0 0 var(--gold); }
  .kw-sched-item::after{ content:""; position:absolute; left:96px; top:14px; bottom:14px; width:1px; background:var(--gold-soft); margin-left:7px; }
  .kw-sched-time{ font-family:var(--f-caps); font-weight:600; font-size:.98rem; letter-spacing:.04em; color:var(--red); line-height:1.3; }
  .kw-sched-label{ font-family:var(--f-kh-serif); font-weight:600; font-size:1.05rem; line-height:1.8; color:var(--maroon); padding-left:12px; }

  /* gallery */
  .kw-gal{ columns:2; column-gap:12px; }
  .kw-gal figure{ margin:0 0 12px; break-inside:avoid; padding:7px; background:var(--ivory); border:1px solid var(--gold); position:relative; box-shadow:0 8px 20px rgba(74,13,13,.12); overflow:hidden; }
  .kw-gal img{ width:100%; display:block; transition:transform 1.4s var(--ease); }
  .kw-gal figure:hover img{ transform:scale(1.05); }
  /* laid out like keepsake prints, each at its own slight angle */
  .kw-gal figure:nth-child(odd){ rotate:-1.2deg; } .kw-gal figure:nth-child(even){ rotate:1.4deg; }

  /* venue + footer horizons */
  .kw-sec-horizon{ left:50%; bottom:0; width:150%; transform:translateX(-50%); z-index:0; opacity:.95; }
  .kw-venue{ padding-bottom:150px; }
  .kw-venue-name{ font-family:var(--f-names); font-weight:500; font-size:clamp(1.5rem,7.5vw,2.1rem); letter-spacing:.1em; line-height:1.3; overflow-wrap:anywhere; }

  /* rsvp */
  .kw-rsvp-opts{ display:flex; flex-direction:column; gap:12px; margin-top:22px; }
  .kw-opt{ min-height:52px; padding:12px 20px; border:1px solid var(--gold); background:transparent; font-family:var(--f-kh-serif); font-weight:600; font-size:1.05rem; color:var(--red); transition:background .3s, color .3s, transform .3s var(--ease); }
  .kw-opt:hover{ transform:translateY(-2px); }
  .kw-opt.chosen{ background:var(--red); color:var(--ivory); }
  .kw-rsvp-form{ display:none; margin-top:22px; gap:12px; flex-direction:column; }
  .kw-rsvp-form.show{ display:flex; }
  .kw-rsvp-form input{ width:100%; padding:13px 14px; border:1px solid var(--gold-soft); background:#fffaf0; font-family:var(--f-kh-sans); font-size:1rem; color:var(--ink); border-radius:0; }
  .kw-thanks{ display:none; margin-top:22px; font-family:var(--f-kh-serif); color:var(--red); font-size:1.1rem; line-height:1.9; }
  .kw-thanks.show{ display:block; }

  /* contact */
  .kw-contact{ display:grid; gap:14px; }
  .kw-contact a{ display:block; padding:18px 14px; border:1px solid var(--gold-soft); background:#fffaf0; text-decoration:none; color:inherit; transition:transform .3s var(--ease), border-color .3s; }
  .kw-contact a:hover{ transform:translateY(-2px); border-color:var(--gold); }
  .kw-contact .n{ font-family:var(--f-names); font-size:1.3rem; letter-spacing:.14em; color:var(--red); }
  .kw-contact .r{ font-family:var(--f-kh-serif); font-size:.85rem; color:var(--gold); }
  .kw-contact .p{ font-family:var(--f-en); font-size:1.15rem; letter-spacing:.06em; color:var(--maroon); margin-top:4px; }

  /* closing: a wax seal presses onto the letter */
  .kw-finale{ padding-top:96px; padding-bottom:180px; }
  .kw-finale .kw-names{ font-size:clamp(1.3rem,6.4vw,2rem); letter-spacing:.1em; margin-top:4px; }
  .kw-seal-wrap{ position:relative; width:124px; height:124px; margin:0 auto 26px; }
  .kw-seal{ width:100%; height:100%; overflow:visible; filter:drop-shadow(0 10px 16px rgba(74,13,13,.35)); }
  .kw-seal text{ font-family:var(--f-names); font-weight:600; fill:#F3DDA0; letter-spacing:.06em; }
  .kw-seal-ring{ position:absolute; inset:0; border-radius:50%; border:1.5px solid #b0822f; opacity:0; pointer-events:none; }
  .kw-finale-text{ max-width:360px; margin:18px auto 0; }
  .kw-sign{ margin-top:30px; font-family:var(--f-love); font-size:2rem; line-height:1.2; color:var(--gold-hi); }
  .kw-finale-date{ margin-top:10px; font-family:var(--f-caps); font-size:.85rem; letter-spacing:.32em; color:var(--gold); }

  /* footer band */
  .kw-footer{ position:relative; z-index:2; padding:56px 24px calc(30px + env(safe-area-inset-bottom)); text-align:center; color:var(--beige);
    background:linear-gradient(180deg,#5a1010,#2a0606); border-top:1px solid rgba(201,162,77,.55); }
  .kw-footer .kw-divider{ margin:0 auto 18px; max-width:220px; }
  .kw-footer .kw-divider svg{ width:52px; height:26px; }
  .kw-top{ position:absolute; left:50%; top:-25px; width:50px; height:50px; margin-left:-25px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:#5a1010; border:1px solid var(--gold); color:var(--gold-hi); box-shadow:0 8px 20px rgba(42,6,6,.4); transition:transform .4s var(--ease); }
  .kw-top:hover{ transform:translateY(-3px); }
  .kw-top svg{ width:20px; height:20px; }
  .kw-footer-names{ font-family:var(--f-names); font-size:1rem; letter-spacing:.24em; color:var(--gold-hi); overflow-wrap:anywhere; }
  .kw-footer-date{ margin-top:6px; font-family:var(--f-caps); font-size:.72rem; letter-spacing:.3em; color:rgba(232,216,189,.7); }
  .kw-footer-love{ margin-top:22px; font-family:var(--f-kh-serif); font-size:.85rem; line-height:1.9; color:rgba(232,216,189,.85); }
  .kw-footer-love .hb{ display:inline-block; color:#e0575b; animation:kw-beat 1.6s ease-in-out infinite; }
  .kw-brand{ margin-top:14px; font-family:var(--f-caps); font-size:.66rem; letter-spacing:.34em; color:rgba(232,216,189,.5); }
  .kw-brand b{ font-weight:600; color:var(--gold); }

  /* hearts that burst from the RSVP button */
  .kw-burst{ position:fixed; z-index:60; pointer-events:none; margin:-.5em 0 0 -.4em; font-family:'Times New Roman',serif; font-size:18px; line-height:1; }

  /* music */
  .kw-music{ position:fixed; right:16px; bottom:16px; z-index:40; width:48px; height:48px; border-radius:50%; background:var(--gold); color:var(--maroon);
    display:none; align-items:center; justify-content:center; box-shadow:0 8px 22px rgba(0,0,0,.35); border:1px solid rgba(74,13,13,.25); text-decoration:none; }
  .kw-music.show{ display:flex; }
  .kw-music svg{ width:22px; height:22px; }
  .kw-music.playing::after{ content:""; position:absolute; inset:-6px; border-radius:50%; border:1px solid var(--gold); animation:kw-pulse 2.4s ease-out infinite; }
  .kw-music .off{ display:none; } .kw-music.paused .on{ display:none; } .kw-music.paused .off{ display:block; }

  /* scroll reveal: fade, rise and come into focus */
  .kw-js .kw-rev{ opacity:0; transform:translateY(26px); filter:blur(6px); transition:opacity 1.3s var(--ease), transform 1.3s var(--ease), filter 1.3s var(--ease); }
  .kw-js .kw-rev.in{ opacity:1; transform:none; filter:none; }
  .kw-js .kw-rev.d1{ transition-delay:.15s; } .kw-js .kw-rev.d2{ transition-delay:.3s; } .kw-js .kw-rev.d3{ transition-delay:.45s; }
  /* sections out of view pause their ambient loops */
  .kw-idle,.kw-idle *{ animation-play-state:paused !important; }

  @keyframes kw-sway{ 0%,100%{ transform:rotate(calc(var(--a) * -1)) } 50%{ transform:rotate(var(--a)) } }
  @keyframes kw-bob{ 0%,100%{ transform:rotate(-7deg) scale(1) } 50%{ transform:rotate(7deg) scale(1.05) } }
  @keyframes kw-look{ 0%,22%{ transform:rotate(0) } 28%,44%{ transform:rotate(-16deg) } 50%{ transform:rotate(4deg) } 54%{ transform:rotate(26deg) } 58%{ transform:rotate(4deg) } 62%{ transform:rotate(24deg) } 68%,86%{ transform:rotate(0) } 90%{ transform:rotate(-8deg) } 100%{ transform:rotate(0) } }
  @keyframes kw-flick{ 0%,78%,100%{ transform:rotate(0) } 83%{ transform:rotate(-14deg) } 88%{ transform:rotate(6deg) } 93%{ transform:rotate(-4deg) } }
  @keyframes kw-cross{ 0%{ transform:translateX(-14%) } 72%,100%{ transform:translateX(114%) } }
  @keyframes kw-glide{ 0%{ transform:translateY(0) } 35%{ transform:translateY(-16px) } 70%{ transform:translateY(6px) } 100%{ transform:translateY(-6px) } }
  @keyframes kw-flap{ 0%,100%{ transform:scaleY(1) } 50%{ transform:scaleY(-.65) } }
  @keyframes kw-dust{ 0%{ transform:translate(0,0); opacity:0 } 15%,80%{ opacity:var(--o) } 100%{ transform:translate(var(--dx),var(--rise)); opacity:0 } }
  @keyframes kw-fall{ from{ transform:translate3d(0,-6vh,0) } to{ transform:translate3d(var(--dx),108vh,0) } }
  @keyframes kw-flutter{ 0%{ transform:translateX(-16px) rotate(0) rotateX(0) } 50%{ transform:translateX(16px) rotate(170deg) rotateX(65deg) } 100%{ transform:translateX(-16px) rotate(340deg) rotateX(0) } }
  @keyframes kw-glow{ from{ opacity:.8; transform:translate(-50%,-50%) scale(.96) } to{ opacity:1; transform:translate(-50%,-50%) scale(1.05) } }
  .kw-hero-glow{ animation-name:kw-glow-h; }
  @keyframes kw-glow-h{ from{ opacity:.6; transform:translateX(-50%) scale(.95) } to{ opacity:1; transform:translateX(-50%) scale(1.06) } }
  @keyframes kw-spin-slow{ to{ transform:rotate(360deg) } }
  @keyframes kw-sheen{ 0%,100%{ background-position:0% 50% } 50%{ background-position:100% 50% } }
  @keyframes kw-breathe{ 0%,100%{ transform:scale(1); opacity:.9 } 50%{ transform:scale(1.07); opacity:1 } }
  @keyframes kw-orbit{ to{ --kw-ang:360deg } }
  @keyframes kw-sweep{ 0%,55%{ left:-60% } 100%{ left:130% } }
  @keyframes kw-kenburns{ from{ transform:scale(1.02) translate(0,0) } to{ transform:scale(1.14) translate(-2%,-3%) } }
  @keyframes kw-pulse{ from{ opacity:.7; transform:scale(1) } to{ opacity:0; transform:scale(1.5) } }
  @keyframes kw-cover-in{ from{ opacity:0; transform:translateY(2cqw); filter:blur(6px) } to{ opacity:1; transform:none; filter:none } }
  @keyframes kw-beat{ 0%,40%,100%{ transform:scale(1) } 15%{ transform:scale(1.28) } 28%{ transform:scale(1.1) } }
  @keyframes kw-cover-call{ 0%{ box-shadow:0 0 0 0 rgba(110,17,17,.45) } 70%,100%{ box-shadow:0 0 0 3cqw rgba(110,17,17,0) } }

  @media (prefers-reduced-motion: reduce){
    html{ scroll-behavior:auto; }
    .kw *,.kw *::before,.kw *::after{ animation:none !important; transition:none !important; }
    .kw-js .kw-rev{ opacity:1; transform:none; filter:none; }
    .kw-fly,.kw-dust,.kw-petals,.kw-stage.fg{ display:none; }
  }
  @media (min-width:768px){
    .kw-sec{ padding:92px 40px; }
    .kw-title{ font-size:1.5rem; }
    .kw-duo{ margin-inline:-40px; }
    .kw-venue{ padding-bottom:170px; }
    .kw-finale{ padding-bottom:200px; }
  }
</style>

<div class="kw">
  {{-- shared ornaments --}}
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- lotus with kbach flame curls --}}
      <symbol id="kw-lotus" viewBox="0 0 96 40">
        <g fill="currentColor" fill-opacity=".14" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" stroke-linecap="round">
          <path d="M48 3C42 11 42 23 48 33 54 23 54 11 48 3Z"/>
          <path d="M48 33C36 30 28 20 30 10 40 14 46 22 48 33Z"/>
          <path d="M48 33C60 30 68 20 66 10 56 14 50 22 48 33Z"/>
          <path d="M48 34C34 35 22 29 17 20 30 18 42 24 48 34Z"/>
          <path d="M48 34C62 35 74 29 79 20 66 18 54 24 48 34Z"/>
          <path d="M15 26C8 26 3 22 3 17 3 13 7 11 10 13 7 14 7 18 10 19" fill="none"/>
          <path d="M81 26C88 26 93 22 93 17 93 13 89 11 86 13 89 14 89 18 86 19" fill="none"/>
          <circle cx="48" cy="37.5" r="1.4" fill="currentColor" fill-opacity="1"/>
        </g>
      </symbol>
      {{-- kbach corner: double rule + flame leaf (phñi) curling into the corner --}}
      <symbol id="kw-corner" viewBox="0 0 80 80">
        <g fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 78V30C3 15 15 3 30 3h48"/>
          <path d="M10 78V34C10 21 21 10 34 10h44" stroke-opacity=".5"/>
          <path d="M17 17C30 15 41 23 43 37 37 31 31 31 27 35 29 29 25 23 17 17Z" fill="currentColor" fill-opacity=".22"/>
          <path d="M23 43C17 37 18 29 26 26"/><path d="M43 23C37 17 29 18 26 26"/>
          <path d="M20 52c-4-3-4-8 0-10M52 20c-3-4-8-4-10 0" stroke-opacity=".7"/>
          <circle cx="30" cy="3" r="1.8" fill="currentColor"/><circle cx="3" cy="30" r="1.8" fill="currentColor"/>
          <circle cx="48" cy="48" r="1.4" fill="currentColor" stroke="none" fill-opacity=".7"/>
        </g>
      </symbol>
      {{-- roumdul (Cambodia's national flower): three thick outer petals, three inner, gold heart --}}
      <g id="kw-roumdul">
        <g><path d="M0 0C-6-4-6-12 0-15 6-12 6-4 0 0Z"/><path d="M0 0C-6-4-6-12 0-15 6-12 6-4 0 0Z" transform="rotate(120)"/><path d="M0 0C-6-4-6-12 0-15 6-12 6-4 0 0Z" transform="rotate(240)"/></g>
        <g fill-opacity=".72"><path d="M0 0C-3.5-3-3.5-8 0-10 3.5-8 3.5-3 0 0Z" transform="rotate(60)"/><path d="M0 0C-3.5-3-3.5-8 0-10 3.5-8 3.5-3 0 0Z" transform="rotate(180)"/><path d="M0 0C-3.5-3-3.5-8 0-10 3.5-8 3.5-3 0 0Z" transform="rotate(300)"/></g>
        <circle r="2.4" fill="#C9A24D" stroke="none"/>
      </g>
      <path id="kw-leaf" d="M0 0C8-8 24-9 38 0 24 8 8 7 0 0ZM3 0H34" />
      <radialGradient id="kw-wax" cx="40%" cy="35%" r="70%">
        <stop offset="0" stop-color="#b02a2a"/><stop offset=".55" stop-color="#7A1515"/><stop offset="1" stop-color="#4a0b0b"/>
      </radialGradient>
    </defs>
  </svg>

  {{-- ============ 1 · COVER (video: card -> roumdul bloom -> the page's background; plays on the open tap) ============ --}}
  <div class="kw-gate" id="kw-gate" role="dialog" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
    <div class="kw-cover">
      <video id="kw-cover-video" src="{{ asset('images/invitations/khmer-traditional/cover/cover.mp4') }}" poster="{{ asset('images/invitations/khmer-traditional/cover/poster.jpg') }}" muted playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>
      <div class="kw-cover-ui">
        <img class="kw-cover-title" src="{{ asset('images/invitations/khmer-traditional/cover/title.png') }}" alt="សិរីមង្គលអាពាហ៍ពិពាហ៍ · The Wedding Invitation">
        <p class="kw-cover-guest">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</p>
        <div class="kw-cover-when">
          @if ($dateKhmer)<p>{{ $dateKhmer }} វេលា{{ $timeKhmer }}</p>@endif
          @if ($venueName)<p>ស្ថិតនៅ {{ $venueName }}</p>@endif
          @if ($dateEnglish)<p class="en">{{ $dateEnglish }}</p>@endif
        </div>
        <button type="button" class="kw-cover-open" id="kw-open"><img src="{{ asset('images/invitations/khmer-traditional/cover/btn-text.png') }}" alt="ចុចបើកសំបុត្រ"></button>
      </div>
    </div>
  </div>

  <div class="kw-stage bg" aria-hidden="true">
    <video id="kw-bg-video" src="{{ asset('images/invitations/khmer-traditional/background.mp4') }}" poster="{{ asset('images/invitations/khmer-traditional/poster.jpg') }}" muted loop playsinline preload="auto" disablepictureinpicture disableremoteplayback></video>
  </div>

  <main class="kw-page">

    {{-- ============ 2 · HERO ============ --}}
    <section class="kw-sec kw-red kw-hero kw-live" id="kw-top">
      <div class="kw-deco kw-hero-glow"></div>
      {!! $flock([[8, 22, -6, 20, .8, .75, false], [11, 22, -5.5, 15, .7, .6, false], [26, 30, -18, 14, .75, .5, true]]) !!}
      <div class="kw-dustfield kw-deco" data-dust="12" data-rise="-300px"></div>
      <div class="kw-frame"></div>
      <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner bl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner br" aria-hidden="true"><use href="#kw-corner"/></svg>
      <div class="kw-deco kw-branch-wrap tl">{!! $branch(true) !!}</div>
      <div class="kw-deco kw-branch-wrap tr">{!! $branch(false) !!}</div>

      @if ($coverUrl)
        <div class="kw-arch kw-rev"><div class="kw-arch-in"><img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" loading="eager"></div></div>
      @endif

      <p class="kw-eyebrow kw-rev">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
      <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>

      @if ($coupleTitle)
        <h2 class="kw-hero-names kw-gs" aria-label="{{ $coupleTitle }}">
          @if ($groomUpper)<span class="kw-names kw-shimmer{{ $kh($groomName) }}">{{ $groomUpper }}</span>@endif
          @if ($groomUpper && $brideUpper)<span class="kw-amp" aria-hidden="true">&amp;</span>@endif
          @if ($brideUpper)<span class="kw-names kw-shimmer{{ $kh($brideName) }}">{{ $brideUpper }}</span>@endif
        </h2>
        <div class="kw-tagline">
          <p class="en kw-gs">Two hearts, one forever</p>
          <p class="kh kw-gs">ពីរបេះដូង រួមដំណើរតែមួយ ជារៀងរហូត</p>
        </div>
      @endif

      <div class="kw-body-text kw-rev d2" style="margin-top:26px">
        <p>សូមគោរពអញ្ជើញ</p>
        @if (! empty($recipientName))
          <p class="kw-guest">{{ $recipientName }}</p>
        @else
          <p class="kw-guest">លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p>ចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស</p>
        <p>ក្នុងពិធីមង្គលការរបស់យើងខ្ញុំ</p>
      </div>

      @if ($personalNote)
        <p class="kw-note kw-rev">{{ $personalNote }}</p>
      @endif

      @if ($eventDate)
        <div class="kw-date-block kw-rev d3">
          <p class="kw-date-num">{{ $dateNumeric }}</p>
          <p class="kw-body-text" style="color:var(--ivory)">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="kw-body-text" style="color:var(--beige); font-size:.9rem">{{ $khmerLunarDate }}</p>@endif
          <p class="kw-body-text" style="color:var(--gold)">{{ $timeKhmer }}</p>
        </div>
      @endif
    </section>

    {{-- ============ 2b · LOVE VERSE (a heartbeat line draws itself into a heart) ============ --}}
    <section class="kw-sec" id="kw-verse">
      <p class="kw-eyebrow kw-rev">ពាក្យពីបេះដូង</p>
      <svg class="kw-heartline" viewBox="0 0 300 80" aria-hidden="true" focusable="false">
        <path class="gl" d="M0 50H96l6-12 6 22 6-10h14c6 0 12 6 22 16-12-14-26-24-26-36 0-10 8-16 16-16 6 0 10 4 10 10 0-6 4-10 10-10 8 0 16 6 16 16 0 12-14 22-26 36 10-10 16-16 22-16h128"/>
        <path class="ln" d="M0 50H96l6-12 6 22 6-10h14c6 0 12 6 22 16-12-14-26-24-26-36 0-10 8-16 16-16 6 0 10 4 10 10 0-6 4-10 10-10 8 0 16 6 16 16 0 12-14 22-26 36 10-10 16-16 22-16h128"/>
        <g transform="translate(150 37) scale(.62)"><g class="bl"><use href="#kw-roumdul"/></g></g>
      </svg>
      <div class="kw-verse-kh">
        <p class="kw-gs">ក្នុងចំណោមមនុស្សរាប់លាននាក់លើលោកនេះ</p>
        <p class="kw-gs">បេះដូងខ្ញុំបានជ្រើសរើសតែអ្នកម្នាក់</p>
        <p class="kw-gs">ហើយចាប់ពីថ្ងៃនេះតទៅ</p>
        <p class="kw-gs">រាល់ព្រឹក និងរាល់ល្ងាច គឺជារបស់យើងទាំងពីរ។</p>
      </div>
      <p class="kw-verse-en kw-gs">“Among millions of souls, my heart chose only you — and from this day on, every sunrise and every sunset belongs to us.”</p>
    </section>

    {{-- ============ 3 · COUPLE ============ --}}
    @if ($groomName || $brideName)
      <section class="kw-sec kw-pattern kw-live" id="kw-couple">
        <p class="kw-eyebrow kw-rev">គូស្វាមីភរិយា</p>
        <p class="kw-sub kw-rev">The two of us</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        {{-- one full photo of the two of them, names set over it --}}
        <figure class="kw-duo kw-gs">
          <div class="kw-duo-img">
            @if ($couplePhoto)<img src="{{ $couplePhoto }}" alt="{{ $coupleTitle }}" loading="lazy">@else<div class="ph">{{ $monogram }}</div>@endif
          </div>
          <figcaption class="kw-duo-cap">
            @if ($brideName)
              <div class="kw-duo-person"><p class="kw-role">កូនក្រមុំ · THE BRIDE</p><h3 class="kw-names{{ $kh($brideName) }}">{{ $brideUpper }}</h3></div>
            @endif
            @if ($brideName && $groomName)
              <div class="kw-duo-heart" aria-hidden="true"><svg viewBox="-17 -17 34 34"><use href="#kw-roumdul"/></svg></div>
            @endif
            @if ($groomName)
              <div class="kw-duo-person"><p class="kw-role">កូនកំលោះ · THE GROOM</p><h3 class="kw-names{{ $kh($groomName) }}">{{ $groomUpper }}</h3></div>
            @endif
          </figcaption>
        </figure>
        @if ($brideName && $groomName)
          <div class="kw-destiny kw-rev">
            <p class="kw-sub">Destined to be</p>
            <p class="kh">ព្រហ្មលិខិតបាននាំយើងមកជួបគ្នា<br>ហើយក្តីស្រឡាញ់បានចងយើងជាប់ជាមួយគ្នា</p>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 4 · STORY ============ --}}
    @if ($story->isNotEmpty())
      <section class="kw-sec kw-beige" id="kw-story">
        <h2 class="kw-title kw-rev">ដំណើរជីវិតរបស់យើង</h2>
        <p class="kw-sub kw-rev">Our love story</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-tl">
          <span class="kw-tl-fill" aria-hidden="true"></span>
          @foreach ($story as $i => $chapter)
            <div class="kw-tl-item kw-rev">
              <span class="kw-tl-num">CHAPTER {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              @if (! empty($chapter['title']))<h3>{{ $chapter['title'] }}</h3>@endif
              @if (! empty($chapter['text']))<p>{{ $chapter['text'] }}</p>@endif
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 5 · COUNTDOWN ============ --}}
    @if ($showCountdown)
      <section class="kw-sec kw-maroon kw-pattern kw-live" id="kw-countdown">
        {!! $flock([[12, 28, -8, 16, .8, .55, true], [16, 28, -7.4, 12, .7, .45, true]]) !!}
        <div class="kw-dustfield kw-deco" data-dust="10" data-rise="-220px"></div>
        <h2 class="kw-title kw-rev">ថ្ងៃដ៏វិសេសរបស់យើង</h2>
        <p class="kw-sub kw-rev">Counting down to forever</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-count kw-rev" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
          <div><b data-u="d">00</b><span>DAYS</span></div>
          <div><b data-u="h">00</b><span>HOURS</span></div>
          <div><b data-u="m">00</b><span>MINUTES</span></div>
          <div><b data-u="s">00</b><span>SECONDS</span></div>
        </div>
        <p class="kw-body-text kw-rev" style="margin-top:22px">រាប់ថ្ងៃរង់ចាំ ថ្ងៃដែលយើងទាំងពីរ<br>នឹងក្លាយជាគ្រួសារតែមួយ</p>
      </section>
    @endif

    {{-- ============ 6 · PROGRAM ============ --}}
    @if ($schedule->isNotEmpty())
      <section class="kw-sec" id="kw-program">
        <h2 class="kw-title kw-rev">កម្មវិធីពិធីមង្គលការ</h2>
        <p class="kw-sub kw-rev">The celebration</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-sched">
          @foreach ($schedule as $row)
            <div class="kw-sched-item kw-rev">
              <div class="kw-sched-time">{{ $row['time'] ?? '' }}</div>
              <div class="kw-sched-label">{{ $row['label'] ?? '' }}</div>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 7 · GALLERY ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="kw-sec kw-beige kw-pattern" id="kw-gallery">
        <h2 class="kw-title kw-rev">កម្រងរូបភាព</h2>
        <p class="kw-sub kw-rev">Moments of us</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        @if ($galleryLayout === 'feed')
          @include('invitations.partials.photo-feed', ['photos' => $gallery, 'alt' => $coupleTitle, 'snap' => 'none'])
        @else
                <div class="kw-gal">
            @foreach ($gallery as $i => $url)
              <figure class="kw-gs"><img src="{{ $url }}" alt="{{ $coupleTitle }} {{ $i + 1 }}" loading="lazy"></figure>
            @endforeach
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 8 · VENUE ============ --}}
    @if ($showVenue)
      <section class="kw-sec kw-red kw-venue kw-live" id="kw-venue">
        {!! $flock([[10, 24, -3, 18, .8, .7, false], [13, 24, -2.5, 13, .7, .6, false]]) !!}
        <div class="kw-deco kw-sec-horizon">{!! $templeSvg !!}</div>
        <div class="kw-frame"></div>
        <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
        <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
        <h2 class="kw-title kw-rev">ទីតាំងប្រារព្ធពិធី</h2>
        <p class="kw-sub kw-rev">Where forever begins</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        @if ($venueName)<p class="kw-venue-name kw-shimmer kw-rev{{ $kh($venueName) }}">{{ mb_strtoupper($venueName) }}</p>@endif
        @if ($venueAddress)<p class="kw-body-text kw-rev" style="color:var(--beige); margin-top:10px">{{ $venueAddress }}</p>@endif
        @if ($mapUrl && $venueAddress)
          <div class="kw-rev" style="margin-top:28px"><a class="kw-btn gold" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 9 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="kw-sec kw-pattern" id="kw-rsvp">
        <h2 class="kw-title kw-rev">សូមអញ្ជើញបញ្ជាក់ការចូលរួម</h2>
        <p class="kw-sub kw-rev">Will you celebrate with us?</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <p class="kw-body-text kw-rev">វត្តមានរបស់លោកអ្នក នឹងធ្វើឲ្យថ្ងៃរបស់យើងកាន់តែពេញលេញ។<br>តើលោកអ្នកនឹងអញ្ជើញចូលរួមដែរឬទេ?</p>
        <div class="kw-rsvp-opts kw-rev" id="kw-rsvp-opts">
          <button type="button" class="kw-opt" data-status="yes">ចូលរួម</button>
          <button type="button" class="kw-opt" data-status="no">មិនអាចចូលរួម</button>
        </div>
        <form class="kw-rsvp-form" id="kw-rsvp-form">
          <input type="text" id="kw-rsvp-note" maxlength="500" placeholder="ផ្ញើសារជូនពរ (មិនចាំបាច់)" autocomplete="off">
          <button type="submit" class="kw-btn">បញ្ជូន</button>
        </form>
        <p class="kw-thanks" id="kw-thanks" role="status"></p>
      </section>
    @endif

    {{-- ============ 10 · CONTACT ============ --}}
    @if ($contacts->isNotEmpty())
      <section class="kw-sec kw-beige" id="kw-contact">
        <h2 class="kw-title kw-rev">ទំនាក់ទំនង</h2>
        <p class="kw-sub kw-rev">Get in touch</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-contact">
          @foreach ($contacts as $c)
            <a class="kw-rev" href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}">
              <div class="r">{{ $c['role'] }}</div>
              <div class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</div>
              <div class="p">{{ $c['phone'] }}</div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 11 · CLOSING (a wax seal presses the letter shut) ============ --}}
    <section class="kw-sec kw-maroon kw-finale kw-live" id="kw-finale">
      {!! $flock([[30, 26, -5, 18, .8, .7, false], [34, 26, -4.4, 14, .7, .6, false], [27, 26, -4, 11, .75, .5, false]]) !!}
      <div class="kw-dustfield kw-deco" data-dust="14" data-rise="-260px"></div>
      <div class="kw-deco kw-branch-wrap tr">{!! $branch(true) !!}</div>
      <div class="kw-deco kw-sec-horizon">{!! $templeSvg !!}</div>

      <div class="kw-seal-wrap">
        <svg class="kw-seal kw-gs" viewBox="-60 -60 120 120" aria-hidden="true" focusable="false">
          <path d="{{ $sealEdge }}" fill="url(#kw-wax)"/>
          <circle r="41" fill="none" stroke="#E9CD85" stroke-width="1.1" stroke-opacity=".8"/>
          <circle r="36" fill="none" stroke="#E9CD85" stroke-width=".6" stroke-opacity=".55" stroke-dasharray="1.5 3"/>
          <ellipse cx="-14" cy="-26" rx="17" ry="7" transform="rotate(-28)" fill="#fff" fill-opacity=".1"/>
          <text y="9" text-anchor="middle" font-size="{{ mb_strlen($monogram) > 2 ? 24 : 30 }}">{{ $monogram }}</text>
        </svg>
        <span class="kw-seal-ring" aria-hidden="true"></span>
      </div>

      <h2 class="kw-title kw-gs">សូមអរគុណ</h2>
      <p class="kw-sub kw-gs">Thank you, with all our hearts</p>
      <div class="kw-finale-text">
        <p class="kw-body-text kw-gs">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត<br>សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
        <p class="kw-body-text kw-gs" style="margin-top:10px">សូមអរគុណសម្រាប់ក្តីស្រឡាញ់ និងពរជ័យ<br>ដែលលោកអ្នកបានផ្តល់ជូនយើងទាំងពីរ។</p>
      </div>
      <p class="kw-sign kw-gs">With love,</p>
      @if ($coupleTitle)<p class="kw-names kw-shimmer kw-gs{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>@endif
      @if ($dateNumeric)<p class="kw-finale-date kw-gs">{{ $dateNumeric }}</p>@endif
    </section>

    {{-- ============ 12 · FOOTER ============ --}}
    <footer class="kw-footer kw-pattern">
      <button type="button" class="kw-top" id="kw-totop" aria-label="ត្រឡប់ទៅដើម · Back to top">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
      </button>
      <div class="kw-divider"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
      @if ($coupleTitle)<p class="kw-footer-names{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>@endif
      @if ($dateNumeric)<p class="kw-footer-date">{{ $dateNumeric }}</p>@endif
      <p class="kw-footer-love">រៀបចំឡើងដោយក្តីស្រឡាញ់ <span class="hb" aria-hidden="true">♥</span></p>
      <p class="kw-brand">CRAFTED BY <b>ROUMDOUL</b> · {{ now()->year }}</p>
    </footer>
  </main>

  <video class="kw-stage-src" id="kw-fly-video" src="{{ asset('images/invitations/khmer-traditional/butterflies.mp4') }}" muted loop playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>
  <div class="kw-stage fg" aria-hidden="true"><canvas id="kw-fly-canvas"></canvas></div>

  {{-- ============ MUSIC ============ --}}
  @if ($youtubeId)
    <iframe id="kw-yt" title="Background music" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;border:0"
      src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
      allow="autoplay; encrypted-media"></iframe>
    <button type="button" class="kw-music paused" id="kw-music" aria-label="Toggle music">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @elseif ($musicUrl)
    <a class="kw-music show" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Play music">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @endif
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
(function () {
  var root = document.documentElement, body = document.body;
  root.classList.add('kw-js');
  body.classList.add('kw-body', 'kw-locked');

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var rand = function (a, b) { return a + Math.random() * (b - a); };

  /* --- gold dust: generated so each field gets its own random drift --- */
  if (!reduced) {
    document.querySelectorAll('.kw-dustfield').forEach(function (field) {
      var n = +field.getAttribute('data-dust') || 12, rise = field.getAttribute('data-rise') || '-260px';
      for (var i = 0; i < n; i++) {
        var d = document.createElement('i'), t = rand(8, 16);
        d.className = 'kw-dust';
        d.style.cssText = 'left:' + rand(2, 98) + '%;bottom:' + rand(-5, 45) + '%;--s:' + rand(2, 5).toFixed(1) + 'px;--t:' + t.toFixed(1) + 's;--d:-' + rand(0, t).toFixed(1)
          + 's;--dx:' + rand(-40, 40).toFixed(0) + 'px;--o:' + rand(.45, .95).toFixed(2) + ';--rise:' + rise;
        field.appendChild(d);
      }
    });
  }

  /* --- falling roumdul petals over the whole page, once the invitation is open --- */
  function spawnPetals() {
    if (reduced) return;
    var layer = document.createElement('div');
    layer.className = 'kw-petals';
    layer.setAttribute('aria-hidden', 'true');
    var n = window.innerWidth < 480 ? 9 : 13;
    for (var i = 0; i < n; i++) {
      var p = document.createElement('i'), t = rand(14, 24);
      p.className = 'kw-petal';
      p.style.cssText = '--x:' + rand(0, 96) + '%;--t:' + t.toFixed(1) + 's;--d:' + (i < 3 ? -rand(2, 8) : rand(0, 14)).toFixed(1)
        + 's;--dx:' + rand(-60, 60).toFixed(0) + 'px;--s:' + rand(7, 12).toFixed(1) + 'px;--f:' + rand(4, 7.5).toFixed(1) + 's';
      p.appendChild(document.createElement('b'));
      layer.appendChild(p);
    }
    document.querySelector('.kw').appendChild(layer);
  }

  /* --- pause ambient loops in sections that are off screen --- */
  if ('IntersectionObserver' in window) {
    var idleIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { e.target.classList.toggle('kw-idle', !e.isIntersecting); });
    }, { rootMargin: '120px 0px' });
    document.querySelectorAll('.kw-live').forEach(function (el) { idleIo.observe(el); });
  }

  /* --- section reveal on scroll (started after the opening, so the hero plays in view) --- */
  function startReveal() {
    var revs = document.querySelectorAll('.kw-rev');
    if ('IntersectionObserver' in window && !reduced) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
      }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
      revs.forEach(function (el) { io.observe(el); });
    } else {
      revs.forEach(function (el) { el.classList.add('in'); });
    }
  }

  /* --- music (YouTube iframe is primed muted; the open tap is the user gesture that lets it play) --- */
  var yt = document.getElementById('kw-yt'), musicBtn = document.getElementById('kw-music'), playing = false;
  function ytCmd(fn) {
    if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*');
  }
  function setPlaying(on) {
    playing = on;
    if (!musicBtn) return;
    musicBtn.classList.toggle('playing', on);
    musicBtn.classList.toggle('paused', !on);
  }
  if (musicBtn) {
    musicBtn.classList.add('show');
    musicBtn.addEventListener('click', function () {
      if (playing) { ytCmd('pauseVideo'); setPlaying(false); }
      else { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    });
  }

  /* --- rendered background + butterfly videos (started by the open tap, which also satisfies autoplay rules) --- */
  var bgVideo = document.getElementById('kw-bg-video'), flyVideo = document.getElementById('kw-fly-video'), flyCanvas = document.getElementById('kw-fly-canvas');
  var stageOn = false, flyOn = false, scheduleFly = function () {};

  // performance test switches, e.g. ?kwtest=nofx,nofly  (nofx: freeze the template's CSS/SVG animations, nofly: no butterflies)
  var kwTest = (new URLSearchParams(location.search).get('kwtest') || '').split(',');
  if (kwTest.indexOf('nofx') > -1) body.classList.add('kw-nofx');

  /* --- GSAP: a handful of moments, each plays once (transforms + opacity only, so it stays light
         next to the videos). Without GSAP or with reduced motion everything is simply shown. --- */
  var G = window.gsap, ST = window.ScrollTrigger;
  var useGsap = !!(G && ST) && !reduced && kwTest.indexOf('nofx') < 0;
  if (useGsap) { G.registerPlugin(ST); root.classList.add('kw-gsap'); }

  function startMoments() {
    if (!useGsap) return;
    var once = function (el, start) { return { trigger: el, start: start || 'top 80%', once: true }; };
    var q = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var qa = function (sel, ctx) { return G.utils.toArray((ctx || document).querySelectorAll(sel)); };

    // hero: each name is written in, the ampersand blooms between them
    var names = q('.kw-hero-names');
    if (names) {
      G.set(names, { opacity: 1 });
      var lines = qa('.kw-names', names), amp = q('.kw-amp', names);
      var hero = G.timeline({ delay: .45 });
      lines.forEach(function (ln, i) {
        hero.fromTo(ln, { clipPath: 'inset(-20% 100% -20% -10%)' },
          { clipPath: 'inset(-20% -10% -20% -10%)', duration: 1.3, ease: 'power2.inOut', clearProps: 'clipPath' }, i ? '-=.35' : 0);
        if (!i && amp) hero.fromTo(amp, { opacity: 0, scale: .3, rotation: -35 }, { opacity: 1, scale: 1, rotation: 0, duration: .9, ease: 'back.out(2.2)' }, '-=.35');
      });
      hero.fromTo(qa('.kw-tagline > *'), { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 1, stagger: .22, ease: 'power3.out' }, '-=.4');
    }

    // love verse: the heartbeat line draws itself into a heart, a roumdul opens inside it
    var verse = q('#kw-verse');
    if (verse) {
      var strokes = qa('.kw-heartline path', verse), bl = q('.kw-heartline .bl', verse), len = strokes[0].getTotalLength();
      G.set(strokes, { strokeDasharray: len, strokeDashoffset: len });
      G.set(bl, { scale: 0, transformOrigin: '50% 50%' });
      G.timeline({ scrollTrigger: once(verse, 'top 72%') })
        .to(strokes, { strokeDashoffset: 0, duration: 2.6, ease: 'power1.inOut' })
        .to(bl, { scale: 1, rotation: 120, duration: 1, ease: 'back.out(2)' }, '-=1.1')
        .fromTo(qa('.kw-gs', verse), { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 1.1, stagger: .32, ease: 'power2.out' }, '-=1.9');
    }

    // couple: the photo opens like a curtain lifting, settles, then their names rise in
    // with the roumdul blooming between them
    var duo = q('#kw-couple .kw-duo');
    if (duo) {
      var people = qa('.kw-duo-person', duo), heart = q('.kw-duo-heart', duo);
      G.set(duo, { opacity: 1 });
      var lift = G.timeline({ scrollTrigger: once(duo, 'top 82%') })
        .fromTo(duo, { clipPath: 'inset(50% 0% 50% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease: 'power3.inOut', clearProps: 'clipPath' })
        .fromTo(q('.kw-duo-img', duo), { scale: 1.25 }, { scale: 1, duration: 2.4, ease: 'power2.out' }, 0)
        .fromTo(people, { opacity: 0, y: 22 }, { opacity: 1, y: 0, duration: 1, stagger: .25, ease: 'power3.out' }, '-=1.3');
      if (heart) lift.fromTo(heart, { opacity: 0, scale: 0, rotation: -140 }, { opacity: 1, scale: 1, rotation: 0, duration: 1, ease: 'back.out(1.8)' }, '-=.9');
    }

    // story: a red thread follows the reader down the timeline, lighting each chapter
    var tl = q('.kw-tl');
    if (tl) {
      G.fromTo(q('.kw-tl-fill', tl), { scaleY: 0 }, { scaleY: 1, ease: 'none', scrollTrigger: { trigger: tl, start: 'top 65%', end: 'bottom 65%', scrub: .6 } });
      qa('.kw-tl-item', tl).forEach(function (it) {
        ST.create({ trigger: it, start: 'top 65%', onEnter: function () { it.classList.add('lit'); }, onLeaveBack: function () { it.classList.remove('lit'); } });
      });
    }

    // gallery: prints are dealt onto the table
    var figs = qa('.kw-gal figure');
    if (figs.length) {
      G.set(figs, { y: 50, rotation: function (i) { return i % 2 ? 8 : -8; } });
      ST.batch(figs, { start: 'top 90%', once: true, onEnter: function (batch) {
        G.to(batch, { opacity: 1, y: 0, rotation: 0, duration: 1.1, stagger: .14, ease: 'power3.out' });
      } });
    }

    // closing: the wax seal presses down, a ripple runs out, then the thank-you is written
    var fin = q('#kw-finale');
    if (fin) {
      var seal = q('.kw-seal', fin), ring = q('.kw-seal-ring', fin);
      G.timeline({ scrollTrigger: once(fin, 'top 70%') })
        .fromTo(seal, { opacity: 0, scale: 2.3, rotation: -28 }, { opacity: 1, scale: 1, rotation: 0, duration: .6, ease: 'power4.in' })
        .to(seal, { scaleX: 1.08, scaleY: .92, duration: .09, yoyo: true, repeat: 1, ease: 'power1.out' })
        .fromTo(ring, { opacity: .8, scale: .95 }, { opacity: 0, scale: 1.9, duration: 1.2, ease: 'power2.out' }, '<')
        .fromTo(qa('.kw-gs:not(.kw-seal)', fin), { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 1, stagger: .18, ease: 'power3.out' }, '-=.9');
    }

    // lazy images change the page height; keep the trigger points honest
    var refreshT;
    qa('.kw-page img').forEach(function (img) {
      if (!img.complete) img.addEventListener('load', function () { clearTimeout(refreshT); refreshT = setTimeout(function () { ST.refresh(); }, 200); });
    });
    ST.refresh();
  }

  // a little burst of hearts and roumdul when a guest says yes
  function heartBurst(from) {
    if (!useGsap || !from) return;
    var r = from.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    for (var i = 0; i < 16; i++) {
      var h = document.createElement('span');
      h.className = 'kw-burst';
      h.setAttribute('aria-hidden', 'true');
      h.textContent = i % 3 ? '♥' : '✿';
      h.style.cssText = 'left:' + cx + 'px;top:' + cy + 'px;color:' + (i % 3 ? (i % 2 ? '#b3262a' : '#d9535a') : '#C9A24D');
      document.body.appendChild(h);
      G.fromTo(h, { x: 0, y: 0, scale: .4, opacity: 1 }, {
        x: rand(-150, 150), y: rand(-260, -70), scale: rand(.8, 1.7), rotation: rand(-70, 70), opacity: 0,
        duration: rand(1.4, 2.3), ease: 'power2.out', onComplete: h.remove.bind(h)
      });
    }
  }

  var toTop = document.getElementById('kw-totop');
  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); });

  var dropFly = function () { flyCanvas.parentNode.remove(); flyVideo.remove(); flyVideo = null; };

  // butterflies: left half of each frame is colour, right half the alpha mask; a WebGL quad joins them
  (function initFly() {
    var gl = !reduced && kwTest.indexOf('nofly') < 0 && flyCanvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false, preserveDrawingBuffer: false });
    if (!gl) { dropFly(); return; }
    var sh = function (type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    var prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, 'attribute vec2 p; varying vec2 uv; void main(){ uv = vec2(p.x*.5+.5, .5-p.y*.5); gl_Position = vec4(p,0.,1.); }'));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, 'precision mediump float; varying vec2 uv; uniform sampler2D t;'
      + 'void main(){ vec3 c = texture2D(t, vec2(uv.x*.5, uv.y)).rgb; float a = texture2D(t, vec2(.5+uv.x*.5, uv.y)).r; gl_FragColor = vec4(c*a, a); }'));
    gl.linkProgram(prog); gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc); gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.bindTexture(gl.TEXTURE_2D, gl.createTexture());
    [gl.TEXTURE_WRAP_S, gl.TEXTURE_WRAP_T].forEach(function (k) { gl.texParameteri(gl.TEXTURE_2D, k, gl.CLAMP_TO_EDGE); });
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.clearColor(0, 0, 0, 0);

    var hasVfc = 'requestVideoFrameCallback' in HTMLVideoElement.prototype, texReady = false;
    var draw = function () {
      if (flyVideo.readyState >= 2) {
        var w = flyVideo.videoWidth / 2, h = flyVideo.videoHeight;
        if (flyCanvas.width !== w || flyCanvas.height !== h) { flyCanvas.width = w; flyCanvas.height = h; gl.viewport(0, 0, w, h); texReady = false; }
        // allocate the texture once, then only upload new pixels
        if (!texReady) { gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, flyVideo); texReady = true; }
        else gl.texSubImage2D(gl.TEXTURE_2D, 0, 0, 0, gl.RGBA, gl.UNSIGNED_BYTE, flyVideo);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
      }
      scheduleFly();
    };
    // redraw only when the video has a new frame (30/s), not on every screen refresh
    scheduleFly = function () {
      if (!flyOn || document.hidden) return;
      if (hasVfc) flyVideo.requestVideoFrameCallback(draw); else requestAnimationFrame(draw);
    };
  })();

  var playStage = function () {
    if (!reduced) bgVideo.play().catch(function () {});
    if (flyVideo) flyVideo.play().catch(function () {});
  };
  function startStage() {
    var wasFlying = flyOn;
    stageOn = flyOn = true;
    body.classList.add('kw-staged');
    bgVideo.currentTime = 0;
    if (flyVideo) flyVideo.currentTime = 0;
    playStage();
    if (!wasFlying) scheduleFly();
    // keep the butterflies in step with their shadows in the background video
    if (flyVideo) setInterval(function () {
      if (bgVideo.paused || flyVideo.paused || flyVideo.readyState < 2) return;
      var d = Math.abs(flyVideo.currentTime - bgVideo.currentTime);
      if (d > .12 && d < bgVideo.duration - .12) flyVideo.currentTime = bgVideo.currentTime;
    }, 1000);
  }
  // nothing runs while the tab is in the background
  document.addEventListener('visibilitychange', function () {
    if (!stageOn && !flyOn) return;
    if (document.hidden) { bgVideo.pause(); if (flyVideo) flyVideo.pause(); }
    else { if (stageOn) playStage(); else flyVideo.play().catch(function () {}); scheduleFly(); }
  });

  // butterflies over the cover from the start (muted, so no tap needed)
  if (flyVideo) {
    flyOn = true;
    body.classList.add('kw-covering');
    flyVideo.play().catch(function () {});
    scheduleFly();
  }

  /* --- cover: the open tap plays the cover video (card -> roumdul bloom -> the background's first frame),
         the text fades as the bloom grows, then the page takes over under it --- */
  var gate = document.getElementById('kw-gate'), coverVideo = document.getElementById('kw-cover-video'), opened = false, revealed = false;
  function reveal() {
    if (revealed) return;
    revealed = true;
    if (!stageOn) startStage();
    gate.classList.add('blooming', 'open');
    body.classList.remove('kw-locked');
    window.scrollTo(0, 0);
    setTimeout(startReveal, reduced ? 0 : 500);
    setTimeout(startMoments, reduced ? 0 : 400);
    setTimeout(spawnPetals, 900);
    setTimeout(function () { gate.style.display = 'none'; coverVideo.pause(); body.classList.remove('kw-covering'); }, reduced ? 0 : 1000);
  }
  document.getElementById('kw-open').addEventListener('click', function () {
    if (opened) return;
    opened = true;
    if (yt) { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    if (reduced) { reveal(); return; }
    coverVideo.addEventListener('timeupdate', function () {
      var t = coverVideo.currentTime;
      if (t > 2.3) gate.classList.add('blooming');   // the roumdul starts covering the card
      if (t > 4.9 && !stageOn) startStage();         // cover is on the background's first frame; start it underneath
      if (t > 5.5) reveal();
    });
    coverVideo.addEventListener('ended', reveal);
    coverVideo.addEventListener('error', reveal);
    coverVideo.currentTime = 0;
    var p = coverVideo.play();
    if (p) p.catch(reveal);
    setTimeout(reveal, 10000);   // a stalled download never keeps the guest on the cover
  });

  /* --- countdown (uses the invitation's real event date) --- */
  var cd = document.querySelector('.kw-count');
  if (cd) {
    var target = new Date(cd.getAttribute('data-target')).getTime();
    var els = {};
    cd.querySelectorAll('[data-u]').forEach(function (b) { els[b.getAttribute('data-u')] = b; });
    var pad = function (n) { return String(n).padStart(2, '0'); };
    // each number drops softly into place when it changes
    var set = function (u, v) {
      var el = els[u], txt = pad(v);
      if (el.textContent === txt) return;
      el.textContent = txt;
      if (useGsap && !document.hidden) G.fromTo(el, { yPercent: -35, opacity: .2 }, { yPercent: 0, opacity: 1, duration: .45, ease: 'power2.out', overwrite: true });
    };
    var tick = function () {
      var diff = Math.max(0, target - Date.now());
      set('d', Math.floor(diff / 86400000));
      set('h', Math.floor(diff / 3600000) % 24);
      set('m', Math.floor(diff / 60000) % 60);
      set('s', Math.floor(diff / 1000) % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  /* --- RSVP: posts to the existing invitation RSVP endpoint (status yes|no + optional note) --- */
  var opts = document.getElementById('kw-rsvp-opts');
  if (opts) {
    var rsvpUrl = @json($rsvpUrl);
    var form = document.getElementById('kw-rsvp-form'), note = document.getElementById('kw-rsvp-note'), thanks = document.getElementById('kw-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.kw-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.kw-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show'); thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត'
          : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នកដោយក្តីរីករាយ ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។ ក្តីនឹករលឹក និងពរជ័យរបស់លោកអ្នក គឺមានន័យណាស់សម្រាប់យើង។');
        if (ok && chosen === 'yes') heartBurst(form.querySelector('button'));
        thanks.classList.add('show'); if (ok) form.classList.remove('show' );
      };
      if (!rsvpUrl) { done(true); return; }   // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
})();
</script>
