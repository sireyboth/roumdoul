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
    $kwAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $contentImg = fn (string $file) => asset('images/invitations/khmer-traditional/content/'.rawurlencode($file));

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
    // a pasted Google Maps link pins the exact spot; otherwise fall back to searching the address
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $fields['map_url'] ?? '')) ? $fields['map_url'] : null;
    // a Maps link pasted into the address box is a map link, not an address to print or search
    if ($venueAddress && preg_match('#^https?://\S+$#i', trim($venueAddress))) {
        $mapLink = $mapLink ?: trim($venueAddress);
        $venueAddress = null;
    }
    $mapQuery = $venueAddress ?: $venueName;
    $mapPinned = false;
    if ($mapLink && preg_match('#[@=](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#', urldecode($mapLink), $pin)) {
        $mapQuery = $pin[1].','.$pin[2];
        $mapPinned = true;
    }
    $mapUrl = $mapLink ?: ($mapQuery ? 'https://maps.google.com/?q='.urlencode($mapQuery) : null);
    // embed only when we know the spot: coordinates from the link, or a typed address.
    // A short link (maps.app.goo.gl) carries no coordinates, so it gets just the button.
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery
        ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed'
        : null;
    $showVenue = $venueName || $venueAddress || $mapLink;
    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? $fields['gift_account'] : null;

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
    // a ready-made schedule image (e.g. a kbach-designed card) replaces the typed timeline
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    // "06:00 AM" / "17:30" -> "ម៉ោង ០៦:០០ នាទីព្រឹក"; anything else (already Khmer, free text) shows as typed
    $schedTime = function (?string $t) use ($khDigits): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }
        $period = $h < 12 ? 'នាទីព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));

        return 'ម៉ោង '.$khDigits(sprintf('%02d:%s', $h % 12 ?: 12, $m[2])).' '.$period;
    };
    // reception card under the schedule: the last timed program item (the reception closes the
    // day), else the main event time; venue from venue_name
    $receptionRow = $schedule->filter(fn ($row) => ! empty($row['time']))->last();
    $receptionLabel = trim($receptionRow['label'] ?? '') ?: 'ពិធីអាពាហ៍ពិពាហ៍';
    $receptionTime = $receptionRow ? $schedTime($receptionRow['time']) : ($eventDate ? $schedTime($eventDate->format('H:i')) : null);
    if ($receptionTime && str_starts_with($receptionTime, 'ម៉ោង')) {
        $receptionTime = 'វេលា'.$receptionTime;
    }
    $showReception = $receptionTime || $venueName;
    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()->map($resolveUrl)->values();
    // gallery layout: per-invitation 'gallery_layout' (frame|feed) or the 'gallery_feed' checkbox; ?layout= previews.
    // Unset = the vertical feed, this template's design; unticking 'gallery_feed' switches to the framed grid.
    $galleryLayout = request('layout') ?: ($fields['gallery_layout'] ?? (array_key_exists('gallery_feed', $fields) ? ($fields['gallery_feed'] ? 'feed' : 'frame') : 'feed'));
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
    // scene needs it: roumdul branches, flying and perched birds.
    // ------------------------------------------------------------------------------------

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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Great+Vibes&family=Moul&family=Moulpali&family=Noto+Sans+Khmer:wght@400;500;600&family=Noto+Serif+Khmer:wght@400;600;700&display=swap">
<link rel="stylesheet" href="{{ $kwAsset('css/invitations/khmer-traditional-wedding.css') }}">

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
    </defs>
  </svg>

  {{-- ============ 1 · COVER (video: card -> roumdul bloom -> the page's background; plays on the open tap) ============
       Phones play the 9:16 cover; desktop plays cover/landscape/cover.mp4, which starts on the landscape background's
       first frame and blooms the same roumdul. The layout picker (JS) sets the source and poster. --}}
  <div class="kw-gate" id="kw-gate" role="dialog" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
    <div class="kw-cover">
      <video id="kw-cover-video" data-src="{{ asset('images/invitations/khmer-traditional/cover/cover.mp4') }}" data-land="{{ asset('images/invitations/khmer-traditional/cover/landscape/cover.mp4') }}" data-poster="{{ asset('images/invitations/khmer-traditional/cover/poster.jpg') }}" data-land-poster="{{ asset('images/invitations/khmer-traditional/cover/landscape/poster.jpg') }}" muted playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>
      <div class="kw-cover-ui">
        <img class="kw-cover-title" src="{{ asset('images/invitations/khmer-traditional/cover/title.png') }}" alt="សិរីមង្គលអាពាហ៍ពិពាហ៍ · The Wedding Invitation">
        <p class="kw-cover-guest">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</p>
        <div class="kw-cover-when">
          @if ($dateKhmer)<p>{{ $dateKhmer }} វេលា{{ $timeKhmer }}</p>@endif
          @if ($venueName)<p>ស្ថិតនៅ {{ $venueName }}</p>@endif
          @if ($dateEnglish)<p class="en">{{ $dateEnglish }}</p>@endif
        </div>
        <button type="button" class="kw-cover-open" id="kw-open"><img src="{{ asset('images/invitations/khmer-traditional/cover/btn-text.png') }}" alt="ចុចបើកសំបុត្រ"></button>
        {{-- desktop card only (on the phone this artwork is part of the cover video) --}}
        <img class="kw-cover-logo kw-desk" src="{{ asset('images/invitations/khmer-traditional/cover/landscape/logo.png') }}" alt="" aria-hidden="true" loading="lazy">
        <img class="kw-cover-swirl top kw-desk" src="{{ asset('images/invitations/khmer-traditional/cover/landscape/swirl-top.png') }}" alt="" aria-hidden="true" loading="lazy">
        <img class="kw-cover-swirl bot kw-desk" src="{{ asset('images/invitations/khmer-traditional/cover/landscape/swirl-bottom.png') }}" alt="" aria-hidden="true" loading="lazy">
      </div>
    </div>
  </div>

  <div class="kw-stage bg" aria-hidden="true">
    <video id="kw-bg-video" data-src="{{ asset('images/invitations/khmer-traditional/background.mp4') }}" data-land="{{ asset('images/invitations/khmer-traditional/landscape/background.mp4') }}" data-poster="{{ asset('images/invitations/khmer-traditional/poster.jpg') }}" data-land-poster="{{ asset('images/invitations/khmer-traditional/landscape/poster.jpg') }}" muted loop playsinline preload="auto" disablepictureinpicture disableremoteplayback></video>
  </div>

  {{-- hanging flowers swinging at the top of the screen while the page scrolls --}}
  <div class="kw-swing" id="kw-swing" aria-hidden="true"><canvas id="kw-swing-canvas"></canvas></div>
  <video class="kw-stage-src" id="kw-swing-video" data-src="{{ $contentImg('overlay.mp4') }}" data-land="{{ asset('images/invitations/khmer-traditional/landscape/swing.mp4') }}" muted loop playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>

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
{{--
      @if ($coverUrl)
        <div class="kw-arch kw-rev"><div class="kw-arch-in"><img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" loading="eager"></div></div>
      @endif --}}

      <img class="kw-kbach-top kw-rev" src="{{ $contentImg('kbach-dombol.png') }}" alt="" aria-hidden="true">

      <h1 class="kw-invite-title kw-rev">សូមគោរពអញ្ជើញ</h1>
      <img class="kw-tacteing kw-rev" src="{{ $contentImg('tacteing 3 .png') }}" alt="" aria-hidden="true">

      <div class="kw-invite-text kw-rev d2">
        @if (! empty($recipientName))
          <p class="kw-guest">{{ $recipientName }}</p>
        @else
          <p>ឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p>អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ</p>
      </div>

      {{-- name kbach: the artwork carries the "កូនប្រុសនាម / កូនស្រីនាម" labels, the names sit under them --}}
      <h2 class="kw-namekbach kw-hero-names kw-gs" aria-label="{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}">
        <img src="{{ $contentImg('name kbach 1.png') }}" alt="" aria-hidden="true">
        {{-- first name on the top line, the rest (family name) on the line below --}}
        @php
          $splitName = fn ($n) => array_pad(preg_split('/[\s\p{Z}\p{Cf}]+/u', preg_replace('/^[\s\p{Z}\p{Cf}]+|[\s\p{Z}\p{Cf}]+$/u', '', (string) $n), 2) ?: [''], 2, '');
          [$groomFirst, $groomLast] = $splitName($groomUpper);
          [$brideFirst, $brideLast] = $splitName($brideUpper);
        @endphp
        @if ($groomUpper)<span class="kw-names groom{{ $kh($groomName) }}">{{ $groomFirst }}@if ($groomLast)<br>{{ $groomLast }}@endif</span>@endif
        @if ($brideUpper)<span class="kw-names bride{{ $kh($brideName) }}">{{ $brideFirst }}@if ($brideLast)<br>{{ $brideLast }}@endif</span>@endif
      </h2>

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
            @if ($groomName)
              <div class="kw-duo-person kw-duo-left"><p class="kw-role">កូនកំលោះ · THE GROOM</p><h3 class="kw-names{{ $kh($groomName) }}">{{ $groomUpper }}</h3></div>
            @endif
            @if ($brideName && $groomName)
              <div class="kw-duo-heart" aria-hidden="true"><svg viewBox="-17 -17 34 34"><use href="#kw-roumdul"/></svg></div>
            @endif
            @if ($brideName)
              <div class="kw-duo-person kw-duo-right"><p class="kw-role">កូនក្រមុំ · THE BRIDE</p><h3 class="kw-names{{ $kh($brideName) }}">{{ $brideUpper }}</h3></div>
            @endif
          </figcaption>
        </figure>
        @if ($brideName && $groomName)
          <div class="kw-destiny kw-rev">
            <p class="kw-sub">Destined to be</p>
            <p class="kh">ព្រហ្មលិខិតបាននាំយើងមកជួបគ្នា<br>ហើយក្តីស្រឡាញ់បានចងយើងជាប់ជាមួយគ្នា</p>
          </div>
        @endif
        {{-- their own portraits, side by side, when uploaded --}}
        @if ($bridePhoto || $groomPhoto)
          <div class="kw-portraits kw-rev">
            @if ($bridePhoto)
              <figure class="kw-portrait-card">
                <div class="kw-portrait"><div class="kw-portrait-in"><img src="{{ $bridePhoto }}" alt="{{ $brideName }}" loading="lazy"></div></div>
                <figcaption><p class="kw-role">កូនក្រមុំ</p>@if ($brideUpper)<p class="kw-names{{ $kh($brideName) }}">{{ $brideUpper }}</p>@endif</figcaption>
              </figure>
            @endif
            @if ($groomPhoto)
              <figure class="kw-portrait-card">
                <div class="kw-portrait"><div class="kw-portrait-in"><img src="{{ $groomPhoto }}" alt="{{ $groomName }}" loading="lazy"></div></div>
                <figcaption><p class="kw-role">កូនកំលោះ</p>@if ($groomUpper)<p class="kw-names{{ $kh($groomName) }}">{{ $groomUpper }}</p>@endif</figcaption>
              </figure>
            @endif
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
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="kw-sec" id="kw-program">
        <h2 class="kw-title kw-rev">កម្មវិធីអាពាហ៍ពិពាហ៍</h2>
        @if ($dateKhmer)
          <p class="kw-prog-date kw-rev">{{ $dateKhmer }}</p>
        @endif
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        @if ($scheduleImg)
          <a class="kw-sched-img kw-rev" href="{{ $scheduleImg }}" target="_blank" rel="noopener">
            <img src="{{ $scheduleImg }}" alt="កម្មវិធីអាពាហ៍ពិពាហ៍" loading="lazy">
          </a>
        @else
        <div class="kw-sched">
          @foreach ($schedule as $row)
            <div class="kw-sched-item kw-rev">
              <div class="kw-sched-time">{{ ! empty($row['time']) ? $schedTime($row['time']) : '' }}</div>
              <span class="kw-sched-dot" aria-hidden="true"></span>
              <div class="kw-sched-text">
                <div class="kw-sched-label">{{ $row['label'] ?? '' }}</div>
              </div>
            </div>
          @endforeach
        </div>
        @endif
      </section>
    @endif

    {{-- ============ 6b · RECEPTION CARD (formal invitation to the reception) ============ --}}
    @if ($showReception)
      <section class="kw-sec" id="kw-reception">
        <div class="kw-rcard kw-rev">
          <h2 class="kw-rcard-title">សូមគោរពអញ្ជើញ</h2>
          <img class="kw-rcard-orn" src="{{ $contentImg('tacteing 3 .png') }}" alt="" aria-hidden="true">
          <div class="kw-rcard-text">
            @if (! empty($recipientName))
              <p class="kw-rcard-guest">{{ $recipientName }}</p>
            @else
              <p>ឯកឧត្តម លោកឧកញ៉ា លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
            @endif
            <p>និងញាតិមិត្ត អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយសក្នុង {{ $receptionLabel }}</p>
            <p>
              @if ($receptionTime){{ $receptionTime }}@endif
              @if ($venueName) នៅ <span class="kw-rcard-venue{{ $kh($venueName) }}">{{ $venueName }}</span>@endif
              ដោយមេត្រីភាព ។
            </p>
          </div>
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
        <div class="kw-frame"></div>
        <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
        <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
        <h2 class="kw-title kw-rev">ទីតាំងប្រារព្ធពិធី</h2>
        <p class="kw-sub kw-rev">Where forever begins</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        @if ($venueName)<p class="kw-venue-name kw-shimmer kw-rev{{ $kh($venueName) }}">{{ mb_strtoupper($venueName) }}</p>@endif
        @if ($venueAddress)<p class="kw-body-text kw-rev" style="color:var(--beige); margin-top:10px">{{ $venueAddress }}</p>@endif
        @if ($mapEmbed)
          <div class="kw-map kw-rev">
            <iframe src="{{ $mapEmbed }}" title="Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        @endif
        @if ($mapUrl && ($venueAddress || $mapLink))
          <div class="kw-rev kw-map-cta" style="margin-top:28px"><a class="kw-btn gold" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 8b · WEDDING GIFT (bank QR) ============ --}}
    @if ($giftQr)
      <section class="kw-sec kw-beige" id="kw-gift">
        <h2 class="kw-title kw-rev">ចំណងដៃ</h2>
        <p class="kw-sub kw-rev">Wedding gift</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <p class="kw-body-text kw-rev">សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ<br>សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!</p>
        <figure class="kw-qr kw-rev">
          <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
          @if ($giftAccount)<figcaption>{{ $giftAccount }}</figcaption>@endif
        </figure>
        <div class="kw-rev" style="margin-top:22px"><a class="kw-btn" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer">រក្សាទុក QR</a></div>
      </section>
    @endif

    {{-- ============ 9 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="kw-sec kw-pattern" id="kw-rsvp">
        <h2 class="kw-title kw-rev">សូមអញ្ជើញបញ្ជាក់ការចូលរួម</h2>
        <p class="kw-sub kw-rev">Will you celebrate with us?</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <p class="kw-body-text kw-rev">វត្តមានរបស់លោកអ្នក នឹងធ្វើឲ្យថ្ងៃរបស់យើងកាន់តែពេញលេញ។<br>តើលោកអ្នកនឹងអញ្ជើញចូលរួមដែរឬទេ?</p>
        <div class="kw-rsvp-opts kw-rev" id="kw-rsvp-opts" data-url="{{ $rsvpUrl }}">
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
      @if ($brideName && $groomName)
        <p class="kw-names kw-shimmer kw-gs{{ $kh($groomName) }}">{{ $groomUpper }}</p>
        <p class="kw-amp kw-gs" aria-hidden="true">&amp;</p>
        <p class="kw-names kw-shimmer kw-gs{{ $kh($brideName) }}">{{ $brideUpper }}</p>
      @elseif ($coupleTitle)
        <p class="kw-names kw-shimmer kw-gs{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>
      @endif
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

  <video class="kw-stage-src" id="kw-fly-video" data-src="{{ asset('images/invitations/khmer-traditional/butterflies.mp4') }}" data-land="{{ asset('images/invitations/khmer-traditional/landscape/butterflies.mp4') }}" muted loop playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>
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

{{-- the template script goes first so it can start the right videos downloading; the rest of it waits for GSAP (DOMContentLoaded) --}}
<script src="{{ $kwAsset('js/invitations/khmer-traditional-wedding.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
