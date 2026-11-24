@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Golden Hour: a cinematic wedding invitation built around the couple's photo.
    // The cover is the couple's photo, full screen, slowly zooming in warm light with gold dust
    // drifting over it; the names rise letter by letter and a glowing «Open» button waits. A tap
    // splits the photo into two doors that swing open on a flash of light, and the page behind
    // plays scroll animations everywhere: mask wipes, word-by-word text, parallax photos, a
    // drawing story line, flip-clock countdown, tilting gallery, heart bursts on RSVP.
    // Everything personal comes from $fields; Khmer headings/labels below are template copy.
    // On demo pages $invitation is null, so every premium extra previews.
    // CSS public/css/invitations/golden-hour.css · JS public/js/invitations/golden-hour.js
    // Debug: ?open=1 skips the cover (screenshots / reviewing).
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $ghAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $khDayShort = ['អា', 'ច', 'អ', 'ពុ', 'ព្រ', 'សុ', 'ស'];
    $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // names may be typed in Khmer or English: Khmer gets the Khmer display face, Latin the script face
    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' gh-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);
    // a name split into letters for the rising-letters animation (Latin only: Khmer letters stack, so
    // a Khmer name rises as one piece)
    $letters = function (string $name, float $delay = 0) use ($isKh): string {
        if ($name === '') {
            return '';
        }
        if ($isKh($name)) {
            return '<span class="gh-ch" style="--i:'.$delay.'">'.e($name).'</span>';
        }
        preg_match_all('/\X/u', $name, $m);

        return collect($m[0])->map(fn ($c, $i) => $c === ' '
            ? '<span class="gh-ch sp" style="--i:'.($delay + $i).'"> </span>'
            : '<span class="gh-ch" style="--i:'.($delay + $i).'">'.e($c).'</span>')->implode('');
    };

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');

    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#d4a76a';
    $headline = trim($fields['headline'] ?? '') ?: 'We are getting married';
    $titleImg = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate
        ? 'ថ្ងៃ'.$khDays[$eventDate->dayOfWeek].' ទី'.$khDigits($eventDate->day).' ខែ'.$khMonths[$eventDate->month - 1].' ឆ្នាំ'.$khDigits($eventDate->year)
        : null;
    $period = fn (int $h) => $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));
    $khTime = fn (int $h, string $min) => $khDigits(sprintf('%d:%s', $h % 12 ?: 12, $min)).' '.$period($h);
    // end time (optional, «22:00»): shown after the start time, «ម៉ោង ៥:០០ ល្ងាច – ១០:០០ យប់»
    $endTime = null;
    if (preg_match('/^(\d{1,2}):(\d{2})/', trim($fields['end_time'] ?? ''), $et)) {
        $endTime = $khTime((int) $et[1], $et[2]);
    }
    $timeKhmer = $eventDate
        ? 'វេលាម៉ោង '.$khTime($eventDate->hour, $eventDate->format('i')).($endTime ? ' – '.$endTime : '')
        : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    // save-the-date calendar of the wedding month (weeks start on Sunday, like Khmer wall calendars)
    $calendar = null;
    if ($eventDate) {
        $first = $eventDate->copy()->startOfMonth();
        $cells = array_merge(array_fill(0, $first->dayOfWeek, null), range(1, $first->daysInMonth));
        $calendar = ['title' => 'ខែ'.$khMonths[$eventDate->month - 1].' '.$khDigits($eventDate->year), 'en' => $enMonths[$eventDate->month - 1].' '.$eventDate->year, 'cells' => $cells, 'day' => $eventDate->day];
    }

    $venueName = trim($fields['venue_name'] ?? '') ?: null;
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address'])) ? $fields['venue_address'] : null;
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $fields['map_url'] ?? '')) ? $fields['map_url'] : null;
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
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery
        ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed'
        : null;
    $showVenue = $venueName || $venueAddress || $mapLink;

    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? trim($fields['gift_account']) : null;
    // the account number to copy: the longest run of digits (and spaces) in the account text
    $giftNumber = null;
    if ($giftAccount && preg_match_all('/\d[\d\s-]{4,}\d/', $giftAccount, $nums)) {
        $giftNumber = collect($nums[0])->sortByDesc(fn ($n) => strlen($n))->first();
    }
    $giftMessage = trim($fields['gift_message'] ?? '') ?: 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!';

    $personalNote = trim($fields['message'] ?? '');
    $receptionText = trim($fields['reception_message'] ?? '')
        ?: 'អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ';
    $letters2 = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', 'A gentle apology', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'Forever starts today';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    $finaleBg = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : ($gallery->last() ?: $coverUrl);
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;
    // the photo reel under the hero: every gallery photo, twice (it scrolls forever)
    $reel = $gallery->count() >= 4 ? $gallery : collect();

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))->values();
    // a chapter title may be «Place | when»: the card shows both
    $splitTitle = fn ($t) => array_pad(array_map('trim', explode('|', (string) $t, 2)), 2, '');

    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))->values();
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    $schedTime = function (?string $t) use ($khTime): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }

        return $khTime($h, $m[2]);
    };

    // guest notes: dress code, what to bring, reply-by (each hides itself when empty)
    $rsvpBy = null;
    if (! empty($fields['rsvp_deadline'])) {
        $d = \App\Support\Invitations\EventDate::parse($fields['rsvp_deadline']);
        $rsvpBy = $d ? 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year) : null;
    }
    // dress code: always shown as colour swatches (Khmer / English colour words or #hex → DressCode), text under them
    $dressCode = trim($fields['dress_code'] ?? '');
    $dressColours = \App\Support\Invitations\DressCode::colours($dressCode);
    $notes = collect([
        ['bring', 'សូមយកមកជាមួយ', 'What to bring', trim($fields['what_to_bring'] ?? '')],
        ['reply', 'សូមឆ្លើយតបមុន', 'Kindly reply by', (string) $rsvpBy],
    ])->filter(fn ($n) => $n[3] !== '')->values();

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', $recipient) : null;
    $rsvpQuestion = trim($fields['rsvp_message'] ?? '') ?: 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមជាមួយយើងខ្ញុំដែរឬទេ?';

    $ctaLabel = $isUnlocked('cta_label') ? trim($fields['cta_label'] ?? '') : '';
    $ctaUrl = $isUnlocked('cta_url') && preg_match('#^https?://#i', trim($fields['cta_url'] ?? '')) ? trim($fields['cta_url']) : null;
    $showCta = $ctaLabel !== '' && $ctaUrl;

    $parent = function (string $key, string $title) use ($fields): ?string {
        $n = trim($fields[$key] ?? '');

        return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
    };
    $groomParents = array_values(array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]));
    $brideParents = array_values(array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]));

    $tel = fn (string $p) => preg_replace('/[^\d+]/', '', $p);
    $contacts = collect([
        ['role' => 'ខាងកូនប្រុស', 'name' => $groomPretty, 'phone' => trim($fields['groom_phone'] ?? '')],
        ['role' => 'ខាងកូនស្រី', 'name' => $bridePretty, 'phone' => trim($fields['bride_phone'] ?? '')],
        ['role' => 'ព័ត៌មានបន្ថែម', 'name' => '', 'phone' => trim($fields['contact_phone'] ?? '')],
    ])->filter(fn ($c) => $c['phone'] !== '')->unique('phone')->values();

    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }
    $songTitle = trim($fields['song_title'] ?? '');

    $skipGate = request()->boolean('open');
    // section heading: Khmer eyebrow + English script line with a gold shine sweeping across
    $head = fn (string $khText, string $en) => '<div class="gh-head gh-a" data-a="rise"><p class="gh-eyebrow">'.e($khText).'</p><p class="gh-script gh-shine">'.e($en).'</p><span class="gh-head-line" aria-hidden="true"></span></div>';
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Latin faces (Cormorant Garamond, Great Vibes) are always followed by a Khmer face in the CSS stacks;
     Kantumruy Pro carries both scripts for the body text --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Great+Vibes&family=Moulpali&family=Noto+Serif+Khmer:wght@400;500;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $ghAsset('css/invitations/golden-hour.css') }}">
<noscript><style>.gh-gate{display:none}.gh-page{visibility:visible!important}.gh-hero-in{opacity:1!important}.gh-a{opacity:1!important;transform:none!important;clip-path:none!important;filter:none!important}</style></noscript>

<div class="gh{{ $skipGate ? ' is-open' : '' }}" style="--gh-accent: {{ $accent }};">

  {{-- ============ 0 · COVER: the couple's photo + «Open» ============ --}}
  @unless ($skipGate)
    <div class="gh-gate" id="gh-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      {{-- the photo, cut into two doors that swing open --}}
      @foreach (['l', 'r'] as $side)
        <div class="gh-door {{ $side }}" aria-hidden="true">
          <div class="gh-door-img">
            @if ($coverUrl)
              <img src="{{ $coverUrl }}" alt="" fetchpriority="high" draggable="false">
            @else
              <div class="gh-door-empty"></div>
            @endif
          </div>
        </div>
      @endforeach
      <div class="gh-gate-shade" aria-hidden="true"></div>
      <div class="gh-gate-leak" aria-hidden="true"></div>

      <div class="gh-gate-in">
        <p class="gh-gate-kicker"><span>សិរីមង្គលអាពាហ៍ពិពាហ៍</span></p>
        <p class="gh-gate-headline gh-script">{{ $headline }}</p>
        @if ($titleImg)
          <img class="gh-gate-title" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
        @elseif ($groomPretty || $bridePretty)
          <h1 class="gh-gate-names" aria-label="{{ $coupleTitle }}">
            @if ($groomPretty)<span class="gh-name{{ $kh($groomPretty) }}" aria-hidden="true">{!! $letters($groomPretty, 0) !!}</span>@endif
            @if ($groomPretty && $bridePretty)<span class="gh-amp" aria-hidden="true">&amp;</span>@endif
            @if ($bridePretty)<span class="gh-name{{ $kh($bridePretty) }}" aria-hidden="true">{!! $letters($bridePretty, mb_strlen($groomPretty) + 2) !!}</span>@endif
          </h1>
        @endif
        @if ($eventDate)
          <p class="gh-gate-date"><span>{{ $eventDate->format('d') }}</span><i></i><span>{{ $eventDate->format('m') }}</span><i></i><span>{{ $eventDate->format('Y') }}</span></p>
        @endif

        <div class="gh-gate-guest">
          <p class="gh-gate-to">សូមគោរពអញ្ជើញ</p>
          <p class="gh-gate-name{{ $kh($recipientName ?? '') }}">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</p>
        </div>

        <button type="button" class="gh-open" id="gh-open">
          <span class="gh-open-ring" aria-hidden="true"></span>
          <span class="gh-open-ring two" aria-hidden="true"></span>
          <span class="gh-open-face">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7l9 6 9-6"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
            <span>បើកសំបុត្រ</span>
            <small>Open invitation</small>
          </span>
        </button>
      </div>
      <div class="gh-flash" aria-hidden="true"></div>
    </div>
  @endunless

  {{-- gold dust drifting in the light (a tap scatters a few sparks) --}}
  <canvas class="gh-dust" id="gh-dust" aria-hidden="true"></canvas>

  <main class="gh-page" id="gh-top">

    {{-- ============ 1 · HERO ============ --}}
    <section class="gh-hero">
      <div class="gh-hero-photo">
        @if ($coverUrl)
          <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" data-speed=".12">
        @else
          <div class="gh-hero-empty{{ $kh($initials) }}">{{ $initials }}</div>
        @endif
        <div class="gh-hero-grad" aria-hidden="true"></div>
      </div>
      <div class="gh-hero-text">
        <p class="gh-eyebrow en gh-hero-in" style="--d:0">The wedding of</p>
        <h2 class="gh-hero-names gh-hero-in" style="--d:1" aria-label="{{ $coupleTitle }}">
          @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
          @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
          @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
        </h2>
        @if ($dateKhmer)<p class="gh-hero-date gh-hero-in" style="--d:2">{{ $dateKhmer }}</p>@endif
        @if ($venueName)<p class="gh-hero-venue gh-hero-in{{ $kh($venueName) }}" style="--d:3">{{ $venueName }}</p>@endif
        <a class="gh-scroll gh-hero-in" style="--d:4" href="#gh-invite" aria-label="អានបន្ត" data-sfx="tap"><span></span></a>
      </div>
    </section>

    {{-- names marquee, two rows sliding opposite ways --}}
    @if ($coupleTitle)
      <div class="gh-marquee" aria-hidden="true">
        @foreach (['a', 'b'] as $row)
          <div class="gh-marquee-row {{ $row }}">
            <div class="gh-marquee-track">
              @for ($i = 0; $i < 8; $i++)
                <span class="{{ trim($kh($coupleTitle)) }}">{{ $coupleTitle }}</span><b>✦</b>
                @if ($dateNumeric)<span class="num">{{ $dateNumeric }}</span><b>✦</b>@endif
              @endfor
            </div>
          </div>
        @endforeach
      </div>
    @endif

    {{-- ============ 2 · THE INVITATION ============ --}}
    <section class="gh-sec" id="gh-invite">
      <article class="gh-card gh-invite gh-a" data-a="zoom">
        <span class="gh-corner tl" aria-hidden="true"></span><span class="gh-corner tr" aria-hidden="true"></span>
        <span class="gh-corner bl" aria-hidden="true"></span><span class="gh-corner br" aria-hidden="true"></span>
        @if ($groomParents || $brideParents)
          <div class="gh-parents">
            <div class="gh-a" data-a="left">
              <p class="gh-label">មាតាបិតាខាងកូនប្រុស</p>
              @foreach ($groomParents as $line)<p class="gh-parent">{{ $line }}</p>@endforeach
            </div>
            <span class="gh-parents-amp" aria-hidden="true">&amp;</span>
            <div class="gh-a" data-a="right">
              <p class="gh-label">មាតាបិតាខាងកូនស្រី</p>
              @foreach ($brideParents as $line)<p class="gh-parent">{{ $line }}</p>@endforeach
            </div>
          </div>
        @endif

        <h3 class="gh-invite-title gh-a" data-a="blur">សូមគោរពអញ្ជើញ</h3>
        @if (! empty($recipientName))
          <p class="gh-guest gh-a{{ $kh($recipientName) }}" data-a="mask">{{ $recipientName }}</p>
        @else
          <p class="gh-body">ឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p class="gh-body gh-a" data-a="words">{{ $receptionText }}</p>

        @if ($groomPretty || $bridePretty)
          <div class="gh-couple-names">
            @if ($groomPretty)
              <div class="gh-a" data-a="left"><p class="gh-label">កូនប្រុសនាម</p><p class="gh-couple-name{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>
            @endif
            @if ($groomPretty && $bridePretty)<span class="gh-heart gh-a" data-a="pop" aria-hidden="true">♥</span>@endif
            @if ($bridePretty)
              <div class="gh-a" data-a="right"><p class="gh-label">កូនស្រីនាម</p><p class="gh-couple-name{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>
            @endif
          </div>
        @endif

        @if ($eventDate)
          <div class="gh-when gh-a" data-a="rise">
            <p>{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="gh-soft">{{ $khmerLunarDate }}</p>@endif
            <p class="gh-accent-text">{{ $timeKhmer }}</p>
            @if ($venueName)<p class="gh-soft{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          </div>
        @endif
      </article>
    </section>

    {{-- ============ 3 · MEET THE COUPLE (groom + bride portraits) ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="gh-sec" id="gh-couple">
        {!! $head('កូនកំលោះ និងកូនក្រមុំ', 'The bride & groom') !!}
        <div class="gh-duo">
          @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ', $groomParents, 'left'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ', $brideParents, 'right']] as [$photo, $name, $role, $parents, $dir])
            @if ($photo)
              <figure class="gh-duo-item gh-a" data-a="{{ $dir }}">
                <div class="gh-duo-photo gh-tilt"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" data-speed=".06"></div>
                <figcaption>
                  <span class="gh-label">{{ $role }}</span>
                  @if ($name)<span class="gh-duo-name{{ $kh($name) }}">{{ $name }}</span>@endif
                </figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 4 · A NOTE FROM US (word-by-word) ============ --}}
    @if ($personalNote !== '')
      <section class="gh-sec gh-quote" id="gh-quote">
        <span class="gh-quote-mark gh-a" data-a="pop" aria-hidden="true">“</span>
        <p class="gh-quote-text gh-a" data-a="words">{{ $personalNote }}</p>
        <p class="gh-script gh-a" data-a="rise">with love</p>
      </section>
    @endif

    {{-- ============ 5 · SAVE THE DATE: calendar + countdown ============ --}}
    @if ($calendar)
      <section class="gh-sec" id="gh-date">
        {!! $head('ចំណាំថ្ងៃដ៏មានអត្ថន័យ', 'Save the date') !!}
        <div class="gh-card gh-cal gh-a" data-a="zoom">
          <p class="gh-cal-title">{{ $calendar['title'] }}</p>
          <p class="gh-cal-en">{{ $calendar['en'] }}</p>
          <div class="gh-cal-grid" role="grid" aria-label="{{ $calendar['title'] }}">
            @foreach ($khDayShort as $dn)<span class="dn">{{ $dn }}</span>@endforeach
            @foreach ($calendar['cells'] as $i => $c)
              @if ($c === null)
                <span></span>
              @elseif ($c === $calendar['day'])
                <span class="day is-the-day" style="--i:{{ $i }}">
                  <svg viewBox="0 0 40 40" aria-hidden="true"><path pathLength="1" d="M20 33C9 25 5 19 6.5 13.5 8 8.5 14 7 17.5 10.5L20 13l2.5-2.5C26 7 32 8.5 33.5 13.5 35 19 31 25 20 33Z"/></svg>
                  <b>{{ $khDigits($c) }}</b>
                </span>
              @else
                <span class="day" style="--i:{{ $i }}">{{ $khDigits($c) }}</span>
              @endif
            @endforeach
          </div>
          <p class="gh-body">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="gh-body gh-soft">{{ $khmerLunarDate }}</p>@endif
          <p class="gh-accent-text">{{ $timeKhmer }}</p>
        </div>

        @if ($showCountdown)
          <div class="gh-count gh-a" data-a="rise" id="gh-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
            @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
              <div class="gh-count-cell"><b data-u="{{ $u }}">០០</b><span>{{ $label }}</span></div>
            @endforeach
          </div>
          <p class="gh-count-done" id="gh-count-done" hidden>ថ្ងៃនេះហើយ! ♥</p>
        @endif
      </section>
    @endif

    {{-- ============ 6 · OUR STORY (the line draws as you scroll) ============ --}}
    @if ($story->isNotEmpty())
      <section class="gh-sec" id="gh-story">
        {!! $head('ដំណើររឿងស្នេហារបស់យើង', 'Our love story') !!}
        <ol class="gh-story" id="gh-story-list">
          <span class="gh-story-line" aria-hidden="true"><span id="gh-story-fill"></span></span>
          @foreach ($story as $i => $c)
            @php([$place, $when] = $splitTitle($c['title'] ?? ''))
            <li class="gh-story-item {{ $i % 2 ? 'r' : 'l' }}">
              <span class="gh-story-dot" aria-hidden="true">♥</span>
              <div class="gh-story-card gh-a" data-a="{{ $i % 2 ? 'right' : 'left' }}">
                @if ($gallery->isNotEmpty())
                  <div class="gh-story-photo"><img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy" data-speed=".05"></div>
                @endif
                @if ($when !== '')<p class="gh-story-when">{{ $when }}</p>@endif
                @if ($place !== '')<h4 class="gh-story-title{{ $kh($place) }}">{{ $place }}</h4>@endif
                @if (! empty($c['text']))<p class="gh-body">{{ $c['text'] }}</p>@endif
              </div>
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    {{-- ============ 7 · PROGRAM ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="gh-sec" id="gh-program">
        {!! $head('កម្មវិធីសិរីមង្គល', 'Order of the day') !!}
        @if ($scheduleImg)
          <figure class="gh-card gh-sched-img gh-a" data-a="mask">
            <button type="button" class="gh-ph" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីសិរីមង្គល"><img src="{{ $scheduleImg }}" alt="កម្មវិធីសិរីមង្គល" loading="lazy"></button>
          </figure>
        @else
          <ol class="gh-sched">
            @foreach ($schedule as $i => $row)
              <li class="gh-a" data-a="left" style="--stagger:{{ $i }}">
                <span class="t">{{ $schedTime($row['time'] ?? '') }}</span>
                <span class="dot" aria-hidden="true"></span>
                <span class="l{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
              </li>
            @endforeach
          </ol>
        @endif
      </section>
    @endif

    {{-- ============ 8 · GALLERY ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="gh-sec gh-wide" id="gh-gallery">
        {!! $head('កម្រងរូបភាពអនុស្សាវរីយ៍', 'Moments of us') !!}
        <div class="gh-gal">
          @foreach ($gallery as $i => $url)
            <figure class="gh-gal-item {{ ['tall', 'sq', 'sq', 'wide', 'sq', 'tall'][$i % 6] }} gh-a" data-a="mask">
              <button type="button" class="gh-ph gh-tilt" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="{{ ($captions[$i] ?? '') ?: 'មើលរូបភាពទី '.$khDigits($i + 1) }}">
                <img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="lazy">
              </button>
              @if (($captions[$i] ?? '') !== '')<figcaption>{{ $captions[$i] }}</figcaption>@endif
            </figure>
          @endforeach
        </div>
        @if ($reel->isNotEmpty())
          <div class="gh-reel" aria-hidden="true">
            <div class="gh-reel-track">
              @foreach ([...$reel, ...$reel] as $url)<img src="{{ $url }}" alt="" loading="lazy">@endforeach
            </div>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 9 · VENUE ============ --}}
    @if ($showVenue)
      <section class="gh-sec" id="gh-venue">
        {!! $head('ទីតាំងកម្មវិធី', 'Where to find us') !!}
        <div class="gh-card gh-venue gh-a" data-a="zoom">
          <svg class="gh-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
          @if ($venueName)<p class="gh-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="gh-body">{{ $venueAddress }}</p>@endif
          @if ($timeKhmer)<p class="gh-accent-text">{{ $timeKhmer }}</p>@endif
          @if ($mapEmbed)
            <div class="gh-map"><iframe src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe></div>
          @endif
          @if ($mapUrl)
            <a class="gh-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l18-8-8 18-2-8-8-2Z"/></svg>
              មើលផែនទី
            </a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES ============ --}}
    @if ($dressCode !== '' || $notes->isNotEmpty())
      <section class="gh-sec" id="gh-notes">
        {!! $head('ព័ត៌មានសម្រាប់ភ្ញៀវ', 'Good to know') !!}
        @if ($dressCode !== '')
          <div class="gh-card gh-dress gh-a" data-a="zoom">
            <span class="gh-note-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6a2 2 0 1 1 2-2M12 6v1.5L3 14h18l-9-6.5"/><path d="M5 14v6h14v-6"/></svg></span>
            <p class="gh-label">ពណ៌សម្លៀកបំពាក់</p>
            <p class="gh-script-sm">Dress code</p>
            @if ($dressColours)
              <ul class="gh-swatches" aria-label="ពណ៌សម្លៀកបំពាក់">
                @foreach ($dressColours as $i => $c)
                  <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                    <button type="button" class="gh-swatch" aria-label="{{ $c['name'] }}"></button>
                    <span class="gh-swatch-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="gh-body">{{ $dressCode }}</p>
          </div>
        @endif
        <div class="gh-notes">
          @foreach ($notes as $i => [$icon, $khLabel, $en, $text])
            <div class="gh-card gh-note gh-a" data-a="rise" style="--stagger:{{ $i }}">
              <span class="gh-note-ico" aria-hidden="true">
                @if ($icon === 'bring')
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="1.5"/><path d="M3 12h18M12 8v13M12 8C9 8 7 6.5 7.5 4.8 8 3 11 3.5 12 8Zm0 0c3 0 5-1.5 4.5-3.2C16 3 13 3.5 12 8Z"/></svg>
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg>
                @endif
              </span>
              <p class="gh-label">{{ $khLabel }}</p>
              <p class="gh-script-sm">{{ $en }}</p>
              <p class="gh-body">{{ $text }}</p>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 11 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="gh-sec" id="gh-rsvp">
        {!! $head('សូមបញ្ជាក់ការចូលរួម', 'Will you join us?') !!}
        <div class="gh-card gh-rsvp gh-a" data-a="zoom">
          <p class="gh-body">{{ $rsvpQuestion }}</p>
          @if ($rsvpBy)<p class="gh-soft">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
          <div class="gh-rsvp-opts" id="gh-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
            <button type="button" class="gh-opt yes" data-status="yes" role="radio" aria-checked="false"><span aria-hidden="true">♥</span>ខ្ញុំនឹងចូលរួម</button>
            <button type="button" class="gh-opt no" data-status="no" role="radio" aria-checked="false"><span aria-hidden="true">✉</span>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
          </div>
          <form class="gh-rsvp-form" id="gh-rsvp-form">
            <label class="gh-sr" for="gh-rsvp-note">សារជូនពរ</label>
            <textarea id="gh-rsvp-note" rows="3" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
            <button type="submit" class="gh-btn">ផ្ញើការឆ្លើយតប</button>
          </form>
          <p class="gh-thanks" id="gh-thanks" role="status"></p>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT ============ --}}
    @if ($giftQr)
      <section class="gh-sec" id="gh-gift">
        {!! $head('ចំណងដៃ', 'With love and thanks') !!}
        <div class="gh-card gh-gift gh-a" data-a="flip">
          <p class="gh-body">{{ $giftMessage }}</p>
          <figure class="gh-qr">
            <span class="gh-qr-scan" aria-hidden="true"></span>
            <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
          </figure>
          @if ($giftAccount)<p class="gh-gift-acc">{{ $giftAccount }}</p>@endif
          <div class="gh-gift-btns">
            @if ($giftNumber)
              <button type="button" class="gh-btn" id="gh-copy" data-copy="{{ preg_replace('/\D/', '', $giftNumber) }}">ចម្លងលេខគណនី</button>
            @endif
            <a class="gh-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACT + CUSTOM BUTTON ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="gh-sec" id="gh-contact">
        {!! $head('ទំនាក់ទំនង', 'Questions? Call us') !!}
        <div class="gh-contacts">
          @foreach ($contacts as $i => $c)
            <a class="gh-card gh-contact gh-a" data-a="rise" style="--stagger:{{ $i }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p">{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
        @if ($showCta)
          <a class="gh-btn gh-cta gh-a" data-a="pop" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }}</a>
        @endif
      </section>
    @endif

    {{-- ============ 14 · THANKS / APOLOGY ============ --}}
    @if ($letters2->isNotEmpty())
      <section class="gh-sec" id="gh-letters">
        @foreach ($letters2 as [$khHead, $en, $text])
          <article class="gh-card gh-letter gh-a" data-a="rise">
            <p class="gh-eyebrow">{{ $khHead }}</p>
            <p class="gh-script gh-shine">{{ $en }}</p>
            <p class="gh-body gh-a" data-a="words">{{ $text }}</p>
          </article>
        @endforeach
      </section>
    @endif

    {{-- ============ 15 · FINALE ============ --}}
    <section class="gh-finale" id="gh-finale">
      @if ($finaleBg)<img class="gh-finale-bg" src="{{ $finaleBg }}" alt="" loading="lazy" data-speed=".18">@endif
      <div class="gh-finale-shade" aria-hidden="true"></div>
      <div class="gh-finale-in">
        <p class="gh-finale-line gh-script gh-a" data-a="blur">{{ $closingLine }}</p>
        <p class="gh-body gh-a" data-a="rise">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
        @if ($coupleTitle)
          <p class="gh-finale-names gh-a{{ $kh($coupleTitle) }}" data-a="mask">{{ $coupleTitle }}</p>
        @endif
        <button type="button" class="gh-love" id="gh-love" aria-label="ផ្ញើក្តីស្រឡាញ់">
          <span aria-hidden="true">♥</span>
        </button>
        <p class="gh-soft gh-love-hint">ចុចដើម្បីផ្ញើក្តីស្រឡាញ់</p>
      </div>
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'gh-kh' : ''" :date="$dateNumeric"
      class="gh-footer"
      style="--inv-footer-bg:#120d09; --inv-footer-text:#f4e9d8; --inv-footer-muted:#bfae95; --inv-footer-accent:{{ $accent }}; --inv-footer-ring:rgba(212,167,106,.25); --inv-footer-names-font:'Great Vibes','Moulpali',cursive; --inv-footer-caps-font:'Cormorant Garamond','Noto Serif Khmer',serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><span class="gh-footer-orn" aria-hidden="true">✦ ♥ ✦</span></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview --}}
  <div class="gh-lb" id="gh-lb" hidden role="dialog" aria-modal="true" aria-label="កម្រងរូបភាព">
    <button type="button" class="gh-lb-x" id="gh-lb-x" aria-label="បិទ">&times;</button>
    <button type="button" class="gh-lb-nav prev" id="gh-lb-prev" aria-label="មុន">&#8249;</button>
    <figure class="gh-lb-fig">
      <img id="gh-lb-img" alt="">
      <figcaption id="gh-lb-cap"></figcaption>
    </figure>
    <button type="button" class="gh-lb-nav next" id="gh-lb-next" aria-label="បន្ទាប់">&#8250;</button>
  </div>

  <div class="gh-toast" id="gh-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything (music and the little sound effects) --}}
  @if ($musicFile)
    <audio id="gh-audio" src="{{ $musicFile }}" preload="none" loop></audio>
  @endif
  <div class="gh-sound-wrap">
    @if ($songTitle !== '' && ($musicFile || $youtubeId))
      <p class="gh-now" id="gh-now"><span class="eq" aria-hidden="true"><i></i><i></i><i></i></span><span class="t{{ $kh($songTitle) }}">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $musicFile && ! $youtubeId)
      <a class="gh-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      </a>
    @else
      <button type="button" class="gh-sound" id="gh-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="gh-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'reveal' => $sfx('reveal.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $ghAsset('js/invitations/golden-hour.js') }}"></script>
