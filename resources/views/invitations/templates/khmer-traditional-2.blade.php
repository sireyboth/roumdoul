@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Khmer Traditional Wedding II: gold on deep maroon. Same sections and dashboard fields as
    // the Khmer Traditional Wedding template, plus `couple_initials` inside the cover's gold
    // frame. Everything personal comes from $fields; the Khmer headings/labels below are
    // template copy. On the demo page $invitation is null, so every premium extra previews.
    // Artwork: public/images/invitations/khmer-traditional-2/{portrait,landscape}/
    //   cover.mp4       the card (title, gold frame, guest band) drawing itself in, then fading out
    //   background.mp4  lamp posts lighting up over the pattern, behind the scrolling page
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $k2Asset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $art = fn (string $file) => asset('images/invitations/khmer-traditional-2/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomUpper = mb_strtoupper($groomName);
    $brideUpper = mb_strtoupper($brideName);
    $coupleTitle = collect([$groomUpper, $brideUpper])->filter()->implode(' & ');
    // Names may be typed in Khmer or English; Khmer script needs no tracking and taller lines.
    $kh = fn (?string $s) => preg_match('/\p{Khmer}/u', (string) $s) ? ' k2-kh' : '';

    // the couple's initials inside the cover's gold frame, e.g. «ប&ម»; empty = first letter of each name
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');
    // "ប & ម" / "ប&ម" -> the letters with a smaller, lighter ampersand between them
    $initialParts = preg_split('/\s*&\s*/u', $initials, 2);

    $eventDate = ! empty($fields['event_date']) ? Carbon::parse($fields['event_date']) : null;
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate
        ? 'ថ្ងៃ'.$khDays[$eventDate->dayOfWeek].' ទី'.$khDigits($eventDate->day).' ខែ'.$khMonths[$eventDate->month - 1].' ឆ្នាំ'.$khDigits($eventDate->year)
        : null;
    $timeKhmer = $eventDate
        ? 'ម៉ោង '.$khDigits($eventDate->format('g:i')).' '.($eventDate->hour < 12 ? 'ព្រឹក' : ($eventDate->hour < 18 ? 'រសៀល' : 'ល្ងាច'))
        : null;
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
    // embed only when we know the spot: coordinates from the link, or a typed address
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery
        ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed'
        : null;
    $showVenue = $venueName || $venueAddress || $mapLink;
    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? $fields['gift_account'] : null;

    $personalNote = ! empty($fields['message']) ? $fields['message'] : null;
    // closing statements: [heading, text] pairs, each shown only when the customer filled it in
    $letters = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[1] !== '');

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
    // a ready-made schedule image replaces the typed timeline
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
    // reception card: the last timed program item (the reception closes the day), else the main event time
    $receptionRow = $schedule->filter(fn ($row) => ! empty($row['time']))->last();
    $receptionLabel = trim($receptionRow['label'] ?? '') ?: 'ពិធីអាពាហ៍ពិពាហ៍';
    $receptionTime = $receptionRow ? $schedTime($receptionRow['time']) : ($eventDate ? $schedTime($eventDate->format('H:i')) : null);
    if ($receptionTime && str_starts_with($receptionTime, 'ម៉ោង')) {
        $receptionTime = 'វេលា'.$receptionTime;
    }
    $receptionCustom = trim($fields['reception_message'] ?? '');
    $showReception = $receptionTime || $venueName || $receptionCustom !== '';
    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()->map($resolveUrl)->values();
    // gallery layout: 'gallery_layout' (frame|feed) or the 'gallery_feed' checkbox; ?layout= previews. Unset = feed.
    $galleryLayout = request('layout') ?: ($fields['gallery_layout'] ?? (array_key_exists('gallery_feed', $fields) ? ($fields['gallery_feed'] ? 'feed' : 'frame') : 'feed'));
    $galleryLayout = $galleryLayout === 'frame' ? 'frame' : 'feed';

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    // parents' names are typed without a title; "លោក" / "លោកស្រី" is added unless already typed
    $parent = function (string $key, string $title) use ($fields): ?string {
        $n = trim($fields[$key] ?? '');

        return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
    };
    $groomParents = array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]);
    $brideParents = array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]);

    $contacts = collect([
        ['name' => $groomUpper, 'phone' => trim($fields['groom_phone'] ?? ''), 'role' => 'ខាងកូនប្រុស'],
        ['name' => $brideUpper, 'phone' => trim($fields['bride_phone'] ?? ''), 'role' => 'ខាងកូនស្រី'],
    ])->filter(fn ($c) => $c['phone'] !== '')->values();

    // an uploaded song wins over a link; it isn't fetched until it plays (preload="none")
    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    // wax seal for the closing: a slightly irregular disc, like pressed wax
    $sealEdge = 'M'.collect(range(0, 71))->map(function ($i) {
        $a = $i / 72 * 2 * M_PI;
        $r = 52 + 2.2 * sin($a * 9) + 1.3 * sin($a * 23 + 1.3);

        return round(cos($a) * $r, 2).' '.round(sin($a) * $r, 2);
    })->implode('L').'Z';

    $divider = '<div class="k2-divider k2-rev"><i></i><svg aria-hidden="true"><use href="#k2-lotus"/></svg><i></i></div>';
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Great+Vibes&family=Moul&family=Moulpali&family=Noto+Sans+Khmer:wght@400;500;600&family=Noto+Serif+Khmer:wght@400;600;700&display=swap">
<link rel="stylesheet" href="{{ $k2Asset('css/invitations/khmer-traditional-2.css') }}">

<div class="k2">
  {{-- shared ornaments --}}
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      <linearGradient id="k2-goldfill" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#FBE7A6"/><stop offset=".5" stop-color="#E2B755"/><stop offset="1" stop-color="#B07F28"/>
      </linearGradient>
      <radialGradient id="k2-wax" cx=".4" cy=".35" r=".75">
        <stop offset="0" stop-color="#F6DC8E"/><stop offset=".55" stop-color="#D9AE4E"/><stop offset="1" stop-color="#9C6E1E"/>
      </radialGradient>
      {{-- lotus with kbach flame curls --}}
      <symbol id="k2-lotus" viewBox="0 0 96 40">
        <g fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" stroke-linecap="round">
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
      {{-- a small four-petal flower --}}
      <symbol id="k2-flower" viewBox="-17 -17 34 34">
        <g fill="url(#k2-goldfill)" stroke="#8a5f14" stroke-width=".6">
          <path d="M0-16C5-10 5-4 0 0-5-4-5-10 0-16Z"/><path d="M16 0C10 5 4 5 0 0 4-5 10-5 16 0Z"/>
          <path d="M0 16C-5 10-5 4 0 0 5 4 5 10 0 16Z"/><path d="M-16 0C-10-5-4-5 0 0-4 5-10 5-16 0Z"/>
          <circle r="3.2"/>
        </g>
      </symbol>
    </defs>
  </svg>

  {{-- ============ 1 · COVER ============
       cover.mp4 draws the card in (title, gold frame, guest band) and plays muted on load; the couple's
       initials, the guest's name and the open button are live text placed over it in % of the card.
       Phones use the 9:16 render, desktop the 16:9 one (the JS layout picker swaps the sources). --}}
  <div class="k2-gate" id="k2-gate" role="dialog" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
    <div class="k2-cover">
      <video id="k2-cover-video" data-src="{{ $art('portrait/cover.mp4') }}" data-land="{{ $art('landscape/cover.mp4') }}" data-poster="{{ $art('portrait/cover.jpg') }}" data-land-poster="{{ $art('landscape/cover.jpg') }}" muted playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true"></video>
      <div class="k2-cover-ui">
        <p class="k2-initials{{ $kh($initials) }}" data-fit aria-label="{{ $initials }}">
          @if (count($initialParts) === 2)
            <span>{{ $initialParts[0] }}</span><span class="amp" aria-hidden="true">&amp;</span><span>{{ $initialParts[1] }}</span>
          @else
            <span>{{ $initials }}</span>
          @endif
        </p>
        <p class="k2-cover-guest{{ $kh($recipientName ?? '') }}" data-fit>{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</p>
        <button type="button" class="k2-cover-open" id="k2-open">បើកសំបុត្រ</button>
      </div>
    </div>
  </div>

  <div class="k2-stage" aria-hidden="true">
    <video id="k2-bg-video" data-src="{{ $art('portrait/background.mp4') }}" data-land="{{ $art('landscape/background.mp4') }}" data-poster="{{ $art('portrait/background.jpg') }}" data-land-poster="{{ $art('landscape/background.jpg') }}" muted playsinline preload="auto" disablepictureinpicture disableremoteplayback></video>
  </div>

  <main class="k2-page">

    {{-- ============ 2 · INVITATION ============ --}}
    <section class="k2-sec k2-hero" id="k2-top">
      <p class="k2-hero-title k2-rev">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
      {!! $divider !!}

      @if ($groomParents || $brideParents)
        <div class="k2-parents k2-rev">
          <div class="k2-parents-side">@foreach ($groomParents as $line)<p data-fit>{{ $line }}</p>@endforeach</div>
          <div class="k2-parents-side">@foreach ($brideParents as $line)<p data-fit>{{ $line }}</p>@endforeach</div>
        </div>
      @endif

      <h1 class="k2-invite-title k2-rev">សូមគោរពអញ្ជើញ</h1>

      <div class="k2-invite-text k2-rev d1">
        @if (! empty($recipientName))
          <p class="k2-guest">{{ $recipientName }}</p>
        @else
          <p>ឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p>អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ</p>
      </div>

      @if ($groomName || $brideName)
        <h2 class="k2-couple-names k2-rev d2" aria-label="{{ $coupleTitle }}">
          @if ($groomName)
            <span class="k2-person"><span class="k2-role">កូនប្រុសនាម</span><span class="k2-names{{ $kh($groomName) }}" data-fit>{{ $groomUpper }}</span></span>
          @endif
          @if ($groomName && $brideName)
            <svg class="k2-heart" aria-hidden="true"><use href="#k2-flower"/></svg>
          @endif
          @if ($brideName)
            <span class="k2-person"><span class="k2-role">កូនស្រីនាម</span><span class="k2-names{{ $kh($brideName) }}" data-fit>{{ $brideUpper }}</span></span>
          @endif
        </h2>
      @endif

      @if ($personalNote)
        <p class="k2-note k2-rev">{{ $personalNote }}</p>
      @endif

      @if ($eventDate)
        <div class="k2-date-block k2-rev d3">
          <p class="k2-date-num">{{ $dateNumeric }}</p>
          <p class="k2-body-text">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="k2-body-text k2-soft">{{ $khmerLunarDate }}</p>@endif
          <p class="k2-body-text k2-gold">{{ $timeKhmer }}</p>
        </div>
      @endif
    </section>

    {{-- ============ 2b · LOVE VERSE ============ --}}
    <section class="k2-sec" id="k2-verse">
      <p class="k2-eyebrow k2-rev">ពាក្យពីបេះដូង</p>
      <svg class="k2-heartline k2-rev" viewBox="0 0 300 80" aria-hidden="true" focusable="false">
        <path class="gl" d="M0 50H96l6-12 6 22 6-10h14c6 0 12 6 22 16-12-14-26-24-26-36 0-10 8-16 16-16 6 0 10 4 10 10 0-6 4-10 10-10 8 0 16 6 16 16 0 12-14 22-26 36 10-10 16-16 22-16h128"/>
        <path class="ln" pathLength="1" d="M0 50H96l6-12 6 22 6-10h14c6 0 12 6 22 16-12-14-26-24-26-36 0-10 8-16 16-16 6 0 10 4 10 10 0-6 4-10 10-10 8 0 16 6 16 16 0 12-14 22-26 36 10-10 16-16 22-16h128"/>
        <use href="#k2-flower" x="139" y="26" width="22" height="22"/>
      </svg>
      <div class="k2-verse-kh k2-rev d1">
        <p>ក្នុងចំណោមមនុស្សរាប់លាននាក់លើលោកនេះ</p>
        <p>បេះដូងខ្ញុំបានជ្រើសរើសតែអ្នកម្នាក់</p>
        <p>ហើយចាប់ពីថ្ងៃនេះតទៅ</p>
        <p>រាល់ព្រឹក និងរាល់ល្ងាច គឺជារបស់យើងទាំងពីរ។</p>
      </div>
      <p class="k2-verse-en k2-rev d2">“Among millions of souls, my heart chose only you — and from this day on, every sunrise and every sunset belongs to us.”</p>
    </section>

    {{-- ============ 3 · COUPLE ============ --}}
    @if ($groomName || $brideName)
      <section class="k2-sec" id="k2-couple">
        <p class="k2-eyebrow k2-rev">គូស្វាមីភរិយា</p>
        <p class="k2-sub k2-rev">The two of us</p>
        {!! $divider !!}
        <figure class="k2-duo k2-rev">
          <div class="k2-duo-img">
            @if ($couplePhoto)<img src="{{ $couplePhoto }}" alt="{{ $coupleTitle }}" loading="lazy">@else<div class="ph{{ $kh($initials) }}">{{ $initials }}</div>@endif
          </div>
          <figcaption class="k2-duo-cap">
            @if ($groomName)
              <div class="k2-duo-person k2-duo-left"><p class="k2-role">កូនកំលោះ · THE GROOM</p><h3 class="k2-names{{ $kh($groomName) }}">{{ $groomUpper }}</h3></div>
            @endif
            @if ($brideName && $groomName)
              <svg class="k2-duo-heart" aria-hidden="true"><use href="#k2-flower"/></svg>
            @endif
            @if ($brideName)
              <div class="k2-duo-person k2-duo-right"><p class="k2-role">កូនក្រមុំ · THE BRIDE</p><h3 class="k2-names{{ $kh($brideName) }}">{{ $brideUpper }}</h3></div>
            @endif
          </figcaption>
        </figure>
        @if ($brideName && $groomName)
          <div class="k2-destiny k2-rev">
            <p class="k2-sub">Destined to be</p>
            <p class="kh">ព្រហ្មលិខិតបាននាំយើងមកជួបគ្នា<br>ហើយក្តីស្រឡាញ់បានចងយើងជាប់ជាមួយគ្នា</p>
          </div>
        @endif
        @if ($bridePhoto || $groomPhoto)
          <div class="k2-portraits k2-rev">
            @if ($bridePhoto)
              <figure class="k2-portrait-card">
                <div class="k2-portrait"><div class="k2-portrait-in"><img src="{{ $bridePhoto }}" alt="{{ $brideName }}" loading="lazy"></div></div>
                <figcaption><p class="k2-role">កូនក្រមុំ</p>@if ($brideUpper)<p class="k2-names{{ $kh($brideName) }}">{{ $brideUpper }}</p>@endif</figcaption>
              </figure>
            @endif
            @if ($groomPhoto)
              <figure class="k2-portrait-card">
                <div class="k2-portrait"><div class="k2-portrait-in"><img src="{{ $groomPhoto }}" alt="{{ $groomName }}" loading="lazy"></div></div>
                <figcaption><p class="k2-role">កូនកំលោះ</p>@if ($groomUpper)<p class="k2-names{{ $kh($groomName) }}">{{ $groomUpper }}</p>@endif</figcaption>
              </figure>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 4 · STORY ============ --}}
    @if ($story->isNotEmpty())
      <section class="k2-sec k2-panel" id="k2-story">
        <h2 class="k2-title k2-rev">ដំណើរជីវិតរបស់យើង</h2>
        <p class="k2-sub k2-rev">Our love story</p>
        {!! $divider !!}
        <div class="k2-tl">
          @foreach ($story as $i => $chapter)
            <div class="k2-tl-item k2-rev">
              <span class="k2-tl-num">CHAPTER {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              @if (! empty($chapter['title']))<h3>{{ $chapter['title'] }}</h3>@endif
              @if (! empty($chapter['text']))<p>{{ $chapter['text'] }}</p>@endif
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 5 · COUNTDOWN ============ --}}
    @if ($showCountdown)
      <section class="k2-sec" id="k2-countdown">
        <h2 class="k2-title k2-rev">ថ្ងៃដ៏វិសេសរបស់យើង</h2>
        <p class="k2-sub k2-rev">Counting down to forever</p>
        {!! $divider !!}
        <div class="k2-count k2-rev" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
          <div><b data-u="d">00</b><span>DAYS</span></div>
          <div><b data-u="h">00</b><span>HOURS</span></div>
          <div><b data-u="m">00</b><span>MINUTES</span></div>
          <div><b data-u="s">00</b><span>SECONDS</span></div>
        </div>
        <p class="k2-body-text k2-rev" style="margin-top:22px">រាប់ថ្ងៃរង់ចាំ ថ្ងៃដែលយើងទាំងពីរ<br>នឹងក្លាយជាគ្រួសារតែមួយ</p>
      </section>
    @endif

    {{-- ============ 6 · PROGRAM ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="k2-sec k2-panel" id="k2-program">
        <h2 class="k2-title k2-rev">កម្មវិធីអាពាហ៍ពិពាហ៍</h2>
        @if ($dateKhmer)
          <p class="k2-prog-date k2-rev">{{ $dateKhmer }}</p>
        @endif
        {!! $divider !!}
        @if ($scheduleImg)
          <a class="k2-sched-img k2-rev" href="{{ $scheduleImg }}" target="_blank" rel="noopener">
            <img src="{{ $scheduleImg }}" alt="កម្មវិធីអាពាហ៍ពិពាហ៍" loading="lazy">
          </a>
        @else
          <div class="k2-sched">
            @foreach ($schedule as $row)
              <div class="k2-sched-item k2-rev">
                <div class="k2-sched-time">{{ ! empty($row['time']) ? $schedTime($row['time']) : '' }}</div>
                <span class="k2-sched-dot" aria-hidden="true"></span>
                <div class="k2-sched-label">{{ $row['label'] ?? '' }}</div>
              </div>
            @endforeach
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 6b · RECEPTION CARD ============ --}}
    @if ($showReception)
      <section class="k2-sec" id="k2-reception">
        <div class="k2-rcard k2-rev">
          <span class="k2-rcard-corner tl" aria-hidden="true"></span><span class="k2-rcard-corner tr" aria-hidden="true"></span>
          <span class="k2-rcard-corner bl" aria-hidden="true"></span><span class="k2-rcard-corner br" aria-hidden="true"></span>
          <h2 class="k2-rcard-title">សូមគោរពអញ្ជើញ</h2>
          <svg class="k2-rcard-orn" aria-hidden="true"><use href="#k2-lotus"/></svg>
          <div class="k2-rcard-text">
            @if (! empty($recipientName))
              <p class="k2-rcard-guest">{{ $recipientName }}</p>
            @else
              <p>ឯកឧត្តម លោកឧកញ៉ា លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
            @endif
            @if ($receptionCustom !== '')
              <p class="k2-rcard-custom">{{ $receptionCustom }}</p>
            @else
              <p>និងញាតិមិត្ត អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយសក្នុង {{ $receptionLabel }}</p>
              <p>
                @if ($receptionTime){{ $receptionTime }}@endif
                @if ($venueName) នៅ <span class="k2-rcard-venue{{ $kh($venueName) }}">{{ $venueName }}</span>@endif
                ដោយមេត្រីភាព ។
              </p>
            @endif
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 7 · GALLERY ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="k2-sec" id="k2-gallery">
        <h2 class="k2-title k2-rev">កម្រងរូបភាព</h2>
        <p class="k2-sub k2-rev">Moments of us</p>
        {!! $divider !!}
        @if ($galleryLayout === 'feed')
          @include('invitations.partials.photo-feed', ['photos' => $gallery, 'alt' => $coupleTitle, 'snap' => 'none'])
        @else
          <div class="k2-gal">
            @foreach ($gallery as $i => $url)
              <figure class="k2-rev"><img src="{{ $url }}" alt="{{ $coupleTitle }} {{ $i + 1 }}" loading="lazy"></figure>
            @endforeach
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 8 · VENUE ============ --}}
    @if ($showVenue)
      <section class="k2-sec k2-panel" id="k2-venue">
        <h2 class="k2-title k2-rev">ទីតាំងប្រារព្ធពិធី</h2>
        <p class="k2-sub k2-rev">Where forever begins</p>
        {!! $divider !!}
        @if ($venueName)<p class="k2-venue-name k2-shimmer k2-rev{{ $kh($venueName) }}">{{ mb_strtoupper($venueName) }}</p>@endif
        @if ($venueAddress)<p class="k2-body-text k2-soft k2-rev" style="margin-top:10px">{{ $venueAddress }}</p>@endif
        @if ($mapEmbed)
          <div class="k2-map k2-rev">
            <iframe src="{{ $mapEmbed }}" title="Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        @endif
        @if ($mapUrl && ($venueAddress || $mapLink))
          <div class="k2-rev" style="margin-top:28px"><a class="k2-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 8b · WEDDING GIFT (bank QR) ============ --}}
    @if ($giftQr)
      <section class="k2-sec" id="k2-gift">
        <h2 class="k2-title k2-rev">ចំណងដៃ</h2>
        <p class="k2-sub k2-rev">Wedding gift</p>
        {!! $divider !!}
        <p class="k2-body-text k2-rev">សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ<br>សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!</p>
        <figure class="k2-qr k2-rev">
          <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
          @if ($giftAccount)<figcaption>{{ $giftAccount }}</figcaption>@endif
        </figure>
        <div class="k2-rev" style="margin-top:22px"><a class="k2-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer">រក្សាទុក QR</a></div>
      </section>
    @endif

    {{-- ============ 9 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="k2-sec k2-panel" id="k2-rsvp">
        <h2 class="k2-title k2-rev">សូមអញ្ជើញបញ្ជាក់ការចូលរួម</h2>
        <p class="k2-sub k2-rev">Will you celebrate with us?</p>
        {!! $divider !!}
        <p class="k2-body-text k2-rev">វត្តមានរបស់លោកអ្នក នឹងធ្វើឲ្យថ្ងៃរបស់យើងកាន់តែពេញលេញ។<br>តើលោកអ្នកនឹងអញ្ជើញចូលរួមដែរឬទេ?</p>
        <div class="k2-rsvp-opts k2-rev" id="k2-rsvp-opts" data-url="{{ $rsvpUrl }}">
          <button type="button" class="k2-opt" data-status="yes">ចូលរួម</button>
          <button type="button" class="k2-opt" data-status="no">មិនអាចចូលរួម</button>
        </div>
        <form class="k2-rsvp-form" id="k2-rsvp-form">
          <input type="text" id="k2-rsvp-note" maxlength="500" placeholder="ផ្ញើសារជូនពរ (មិនចាំបាច់)" autocomplete="off">
          <button type="submit" class="k2-btn">បញ្ជូន</button>
        </form>
        <p class="k2-thanks" id="k2-thanks" role="status"></p>
      </section>
    @endif

    {{-- ============ 10 · CONTACT ============ --}}
    @if ($contacts->isNotEmpty())
      <section class="k2-sec" id="k2-contact">
        <h2 class="k2-title k2-rev">ទំនាក់ទំនង</h2>
        <p class="k2-sub k2-rev">Get in touch</p>
        {!! $divider !!}
        <div class="k2-contact">
          @foreach ($contacts as $c)
            <a class="k2-rev" href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}">
              <div class="r">{{ $c['role'] }}</div>
              <div class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</div>
              <div class="p">{{ $c['phone'] }}</div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 10b · THANKS & APOLOGY ============ --}}
    @if ($letters->isNotEmpty())
      <section class="k2-sec k2-panel k2-letters" id="k2-letters">
        @foreach ($letters as [$heading, $text])
          <article class="k2-letter">
            <h2 class="k2-title k2-rev">{{ $heading }}</h2>
            {!! $divider !!}
            <p class="k2-letter-text k2-rev">{{ $text }}</p>
          </article>
        @endforeach
      </section>
    @endif

    {{-- ============ 11 · CLOSING (a gold seal with the couple's initials presses the letter shut) ============ --}}
    <section class="k2-sec k2-finale" id="k2-finale">
      <div class="k2-seal-wrap k2-rev">
        <svg class="k2-seal" viewBox="-60 -60 120 120" aria-hidden="true" focusable="false">
          <path d="{{ $sealEdge }}" fill="url(#k2-wax)"/>
          <circle r="41" fill="none" stroke="#7a1a1a" stroke-width="1.1" stroke-opacity=".55"/>
          <circle r="36" fill="none" stroke="#7a1a1a" stroke-width=".6" stroke-opacity=".45" stroke-dasharray="1.5 3"/>
          <ellipse cx="-14" cy="-26" rx="17" ry="7" transform="rotate(-28)" fill="#fff" fill-opacity=".18"/>
          <text y="{{ $kh($initials) ? 6 : 9 }}" text-anchor="middle" class="{{ trim($kh($initials)) }}" font-size="{{ mb_strlen($initials) > 3 ? 18 : 24 }}">{{ $initials }}</text>
        </svg>
        <span class="k2-seal-ring" aria-hidden="true"></span>
      </div>

      <h2 class="k2-title k2-rev">សូមអរគុណ</h2>
      <p class="k2-sub k2-rev">Thank you, with all our hearts</p>
      <div class="k2-finale-text k2-rev d1">
        <p class="k2-body-text">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត<br>សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
        <p class="k2-body-text" style="margin-top:10px">សូមអរគុណសម្រាប់ក្តីស្រឡាញ់ និងពរជ័យ<br>ដែលលោកអ្នកបានផ្តល់ជូនយើងទាំងពីរ។</p>
      </div>
      <p class="k2-sign k2-rev d2">With love,</p>
      @if ($brideName && $groomName)
        <p class="k2-names k2-shimmer k2-rev d2{{ $kh($groomName) }}">{{ $groomUpper }}</p>
        <p class="k2-amp k2-rev d2" aria-hidden="true">&amp;</p>
        <p class="k2-names k2-shimmer k2-rev d2{{ $kh($brideName) }}">{{ $brideUpper }}</p>
      @elseif ($coupleTitle)
        <p class="k2-names k2-shimmer k2-rev d2{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>
      @endif
      @if ($dateNumeric)<p class="k2-finale-date k2-rev d3">{{ $dateNumeric }}</p>@endif
    </section>

    {{-- ============ 12 · FOOTER ============ --}}
    <footer class="k2-footer">
      <button type="button" class="k2-top" id="k2-totop" aria-label="ត្រឡប់ទៅដើម · Back to top">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
      </button>
      <div class="k2-divider"><i></i><svg aria-hidden="true"><use href="#k2-lotus"/></svg><i></i></div>
      @if ($coupleTitle)<p class="k2-footer-names{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>@endif
      @if ($dateNumeric)<p class="k2-footer-date">{{ $dateNumeric }}</p>@endif
      <p class="k2-footer-love">រៀបចំឡើងដោយក្តីស្រឡាញ់ <span class="hb" aria-hidden="true">♥</span></p>
      <p class="k2-brand">CRAFTED BY <b>ROUMDOUL</b> · {{ now()->year }}</p>
    </footer>
  </main>

  {{-- ============ MUSIC ============ --}}
  @if ($musicFile || $youtubeId)
    @if ($musicFile)
      <audio id="k2-audio" src="{{ $musicFile }}" preload="none" loop></audio>
    @else
      <iframe id="k2-yt" title="Background music" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;border:0"
        src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
        allow="autoplay; encrypted-media"></iframe>
    @endif
    <button type="button" class="k2-music paused" id="k2-music" aria-label="Toggle music">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @elseif ($musicUrl)
    <a class="k2-music show" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Play music">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @endif
</div>

<script src="{{ $k2Asset('js/invitations/khmer-traditional-2.js') }}"></script>
