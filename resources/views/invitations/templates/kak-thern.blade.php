@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Kathina ceremony (បុណ្យកឋិនទាន). Opens straight onto the content — no "tap to open"
    // cover. Everything personal comes from $fields; the Khmer headings, invitation wording
    // and blessing below are template copy. On the unpurchased demo page $invitation is null,
    // so every premium extra previews unlocked (see docs/invitation-templates.md).
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $ktAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $img = fn (string $file) => asset('images/invitations/kak-thern/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    $customHeadline = trim($fields['headline'] ?? '');
    $headline = $customHeadline ?: 'បុណ្យកឋិនទានសាមគ្គី';
    // the big title: an uploaded title image, else the typed headline as text, else the template's own artwork
    $titleImage = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;
    $message = trim($fields['message'] ?? '');
    // the rest of the wording and pictures: the customer's, else the template's own (left empty = default)
    $openingLine = trim($fields['opening_line'] ?? '') ?: 'នមោ តស្ស ភគវតោ អរហតោ សម្មាសម្ពុទ្ធស្ស';
    $centerImage = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $img('trai.webp');
    $backgroundImage = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : null;
    $giftMessage = trim($fields['gift_message'] ?? '');
    $rsvpMessage = trim($fields['rsvp_message'] ?? '') ?: 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមកុសលជាមួយយើងខ្ញុំដែរឬទេ?';
    $blessing = trim($fields['thanks_message'] ?? '');
    $hosts = collect(preg_split('/\R/u', trim($fields['host_names'] ?? '')))->map(fn ($l) => trim($l))->filter()->values();

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

    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))
        ->values();
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    // "06:00 AM" / "17:30" -> "ម៉ោង ០៦:០០ ព្រឹក"; anything else (already Khmer, a day heading) shows as typed
    $schedTime = function (?string $t) use ($khDigits): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }
        $period = $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));

        return 'ម៉ោង '.$khDigits(sprintf('%02d:%s', $h % 12 ?: 12, $m[2])).' '.$period;
    };

    $donationQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $donationAccount = ($donationQr && ! empty($fields['gift_account'])) ? $fields['gift_account'] : null;
    // "ABA · SOK HENG\n000 123 456": a last line with digits is the account number (big, copyable), the rest is the bank/name
    $accountLines = collect(preg_split('/\r?\n/', trim($donationAccount ?? '')))->map(fn ($l) => trim($l))->filter()->values();
    $accountNumber = preg_match('/\d/', (string) $accountLines->last()) ? $accountLines->pop() : null;

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()->map($resolveUrl)->values();

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    $contactPhone = trim($fields['contact_phone'] ?? '');
    $ctaLabel = $fields['cta_label'] ?? null;
    $ctaUrl = $fields['cta_url'] ?? null;
    $showCta = $ctaLabel && $ctaUrl && $isUnlocked('cta_label');

    // an uploaded song wins over a link; it isn't fetched until the guest's first tap (preload="none")
    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }
@endphp
<title>{{ $headline }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Moul&family=Noto+Sans+Khmer:wght@400;500;600&family=Noto+Serif+Khmer:wght@400;600;700&display=swap">
<link rel="stylesheet" href="{{ $ktAsset('css/invitations/kak-thern.css') }}">

<div class="kt" style="--bg-portrait:url('{{ $backgroundImage ?? $img('portrait.webp') }}');--bg-land:url('{{ $backgroundImage ?? $img('bg-landscape.webp') }}')">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- lotus with kbach flame curls --}}
      <symbol id="kt-lotus" viewBox="0 0 96 40">
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
    </defs>
  </svg>

  {{-- the temple at sunrise, fixed behind the page; "window" sections let it show through --}}
  <div class="kt-bg" aria-hidden="true"></div>

  {{-- intro: the temple doors open onto the sunrise, plays once and fades into the page (kak-thern.js
       picks the portrait or landscape cut); tapping skips it --}}
  <div class="kt-intro" id="kt-intro" aria-hidden="true">
    <video muted playsinline preload="auto" disablepictureinpicture disableremoteplayback
      data-portrait="{{ $img('intro-portrait.mp4') }}" data-land="{{ $img('intro-landscape.mp4') }}"></video>
  </div>
  <noscript><style>.kt-intro{ display:none; }</style></noscript>

  {{-- butterflies: a packed colour|alpha clip, joined and tiled small in WebGL (kak-thern.js);
       the falling yellow flowers are added by the script too --}}
  <div class="kt-fx kt-butterflies" aria-hidden="true">
    <video data-key src="{{ $img('butterflies.mp4') }}" muted loop playsinline autoplay preload="auto" disablepictureinpicture disableremoteplayback></video>
  </div>

  <main class="kt-page">

    {{-- ============ 1 · HERO ============ --}}
    <section class="kt-hero" id="kt-top">
      {{-- the title: the customer's image, else their headline as text, else the artwork "អនុមោទនាពិធីបុណ្យ កឋិនទាន" --}}
      @if ($titleImage)
        <h1 class="kt-title-art kt-rev"><img src="{{ $titleImage }}" alt="{{ $headline }}" fetchpriority="high"></h1>
      @elseif ($customHeadline !== '')
        <h1 class="kt-headline kt-fit kt-rev">{{ $customHeadline }}</h1>
      @else
        <h1 class="kt-title-art kt-rev"><img src="{{ $img('title.webp') }}" alt="អនុមោទនាពិធីបុណ្យកឋិនទាន" width="900" height="506" fetchpriority="high"></h1>
      @endif
      <p class="kt-namo kt-rev d1">{{ $openingLine }}</p>
      <div class="kt-trai kt-rev d1">
        <span class="kt-halo" aria-hidden="true"></span>
        <img src="{{ $centerImage }}" alt="ត្រៃចីវរ" fetchpriority="high">
      </div>
      <div class="kt-hero-text">
        {{-- the guest first, then the date, big --}}
        <p class="kt-invite-title kt-rev d2">សូមគោរពអញ្ជើញ</p>
        @if (! empty($recipientName))
          <p class="kt-guest kt-rev d2">{{ $recipientName }}</p>
        @else
          <p class="kt-guest generic kt-rev d2">ឯកឧត្តម លោកឧកញ៉ា លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        @if ($eventDate)
          <div class="kt-divider kt-rev d3"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
          <p class="kt-hero-date kt-fit kt-rev d3"><span>ថ្ងៃ{{ $khDays[$eventDate->dayOfWeek] }}</span> ទី{{ $khDigits($eventDate->day) }} ខែ{{ $khMonths[$eventDate->month - 1] }} ឆ្នាំ{{ $khDigits($eventDate->year) }}</p>
        @endif
      </div>
      <a href="#kt-invite" class="kt-scroll" aria-label="អានបន្ត · Scroll down">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
      </a>
    </section>

    {{-- ============ 2 · INVITATION ============ --}}
    <section class="kt-sec kt-paper" id="kt-invite">
      <div class="kt-card">
        {{-- the greeting and the guest's name are on the first screen (hero); the letter carries on from them --}}
        <h2 class="kt-title kt-rev">លិខិតអញ្ជើញ</h2>
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        @if ($message !== '')
          <p class="kt-body kt-own kt-rev d1">{{ $message }}</p>
        @else
          <p class="kt-body kt-rev d1">
            យើងខ្ញុំសូមគោរពអញ្ជើញ ឯកឧត្តម លោកឧកញ៉ា លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា
            និងពុទ្ធបរិស័ទទាំងអស់ មេត្តាអញ្ជើញចូលរួមកសាងកុសល ក្នុងពិធី{{ $headline }}
            ដើម្បីវេរប្រគេនចំពោះព្រះភិក្ខុសង្ឃ ដែលគង់ចាំព្រះវស្សាគ្រប់ត្រៃមាស@if ($venueName) នៅ<b class="kt-venue-inline">{{ $venueName }}</b>@endif
            ដោយសទ្ធាជ្រះថ្លា ។
          </p>
        @endif

        @if ($eventDate)
          <div class="kt-when kt-rev d2">
            <p class="kt-when-num">{{ $dateNumeric }}</p>
            <p>{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="kt-when-lunar">{{ $khmerLunarDate }}</p>@endif
            <p class="kt-when-time">វេលា{{ $timeKhmer }}</p>
          </div>
        @endif

        @if ($hosts->isNotEmpty())
          <div class="kt-hosts kt-rev d3">
            <p class="kt-label">ម្ចាស់ពិធី</p>
            @foreach ($hosts as $host)<p>{{ $host }}</p>@endforeach
          </div>
        @endif
      </div>
    </section>

    {{-- ============ 3 · PROGRAM ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="kt-sec kt-paper" id="kt-program">
        <h2 class="kt-title kt-rev">កម្មវិធីបុណ្យ</h2>
        @if ($dateKhmer)<p class="kt-sub kt-rev">{{ $dateKhmer }}</p>@endif
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        @if ($scheduleImg)
          <a class="kt-sched-img kt-rev" href="{{ $scheduleImg }}" target="_blank" rel="noopener">
            <img src="{{ $scheduleImg }}" alt="កម្មវិធីបុណ្យ" loading="lazy">
          </a>
        @else
          <ol class="kt-sched">
            @foreach ($schedule as $row)
              @if (trim($row['label'] ?? '') === '')
                {{-- a row with no activity is a day heading, e.g. "ថ្ងៃសៅរ៍ ២កើត" over a two-day program --}}
                <li class="kt-sched-day kt-rev">{{ $row['time'] }}</li>
                @continue
              @endif
              <li class="kt-rev">
                <span class="kt-sched-time">{{ ! empty($row['time']) ? $schedTime($row['time']) : '' }}</span>
                <span class="kt-sched-dot" aria-hidden="true"></span>
                <span class="kt-sched-label">{{ $row['label'] ?? '' }}</span>
              </li>
            @endforeach
          </ol>
        @endif
      </section>
    @endif

    {{-- ============ 4 · VENUE ============ --}}
    @if ($showVenue)
      <section class="kt-sec kt-paper alt" id="kt-venue">
        <h2 class="kt-title kt-rev">ទីតាំងប្រារព្ធពិធី</h2>
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        @if ($venueName)<p class="kt-venue-name kt-rev">{{ $venueName }}</p>@endif
        @if ($venueAddress)<p class="kt-body kt-rev">{{ $venueAddress }}</p>@endif
        @if ($mapEmbed)
          <div class="kt-map kt-rev">
            <iframe src="{{ $mapEmbed }}" title="Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        @endif
        @if ($mapUrl && ($venueAddress || $mapLink))
          <div class="kt-rev kt-actions"><a class="kt-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 5 · DONATION (bank QR) ============ --}}
    @if ($donationQr)
      <section class="kt-sec kt-paper" id="kt-donate">
        <h2 class="kt-title kt-rev">ចូលរួមបច្ច័យ</h2>
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        @if ($giftMessage !== '')
          <p class="kt-body kt-own kt-rev">{{ $giftMessage }}</p>
        @else
          <p class="kt-body kt-rev">សម្រាប់សប្បុរសជនដែលមិនអាចអញ្ជើញមកដល់ ហើយមានបំណងចូលរួមកសាងកុសល<br>សូមស្កេន QR ខាងក្រោម ។ សាធុ!</p>
        @endif
        <figure class="kt-qr kt-rev">
          <div class="kt-qr-head">
            <svg aria-hidden="true"><use href="#kt-lotus"/></svg>
            <span>ស្កេនដើម្បីចូលរួមកុសល</span>
          </div>
          <div class="kt-qr-code"><img src="{{ $donationQr }}" alt="QR code សម្រាប់ចូលរួមបច្ច័យ" loading="lazy"></div>
          @if ($accountLines->isNotEmpty() || $accountNumber)
            <figcaption>
              @foreach ($accountLines as $line)<span class="kt-qr-name">{{ $line }}</span>@endforeach
              @if ($accountNumber)
                <span class="kt-qr-num">{{ $accountNumber }}</span>
                <button type="button" class="kt-qr-copy" data-copy="{{ $accountNumber }}" data-done="បានចម្លងរួចរាល់ ✓">ចម្លងលេខគណនី</button>
              @endif
            </figcaption>
          @endif
        </figure>
        <div class="kt-rev kt-actions"><a class="kt-btn ghost" href="{{ $donationQr }}" download target="_blank" rel="noopener noreferrer">រក្សាទុក QR</a></div>
      </section>
    @endif

    {{-- ============ 6 · GALLERY ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="kt-sec kt-paper alt" id="kt-gallery">
        <h2 class="kt-title kt-rev">កម្រងរូបភាព</h2>
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        <div class="kt-gal">
          @foreach ($gallery as $i => $url)
            {{-- --d staggers each row's photos, --tilt / --kx alternate their entrance tilt and slow drift --}}
            <a class="kt-ph kt-rev" href="{{ $url }}" target="_blank" rel="noopener" data-i="{{ $i }}"
              style="--d:{{ ($i % 3) * 0.14 }}s;--tilt:{{ $i % 2 ? 2.5 : -2.5 }}deg;--kx:{{ $i % 2 ? -3 : 3 }}%"><img src="{{ $url }}" alt="{{ $headline }} {{ $i + 1 }}" loading="lazy"></a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 7 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="kt-sec kt-paper" id="kt-rsvp">
        <h2 class="kt-title kt-rev">សូមបញ្ជាក់ការចូលរួម</h2>
        <div class="kt-divider kt-rev"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
        <p class="kt-body kt-own kt-rev">{{ $rsvpMessage }}</p>
        <div class="kt-rsvp-opts kt-rev" id="kt-rsvp-opts" data-url="{{ $rsvpUrl }}">
          <button type="button" class="kt-opt" data-status="yes">ចូលរួម</button>
          <button type="button" class="kt-opt" data-status="no">មិនអាចចូលរួម</button>
        </div>
        <form class="kt-rsvp-form" id="kt-rsvp-form">
          <input type="text" id="kt-rsvp-note" maxlength="500" placeholder="ផ្ញើពាក្យអនុមោទនា (មិនចាំបាច់)" autocomplete="off">
          <button type="submit" class="kt-btn">បញ្ជូន</button>
        </form>
        <p class="kt-thanks" id="kt-thanks" role="status"></p>
      </section>
    @endif

    {{-- ============ 8 · CONTACT / CUSTOM BUTTON ============ --}}
    @if ($contactPhone !== '' || $showCta)
      <section class="kt-sec kt-paper alt" id="kt-contact">
        @if ($contactPhone !== '')
          <p class="kt-label kt-rev">ព័ត៌មានបន្ថែម សូមទាក់ទង</p>
          <a class="kt-phone kt-rev" href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}">{{ $contactPhone }}</a>
        @endif
        @if ($showCta)
          <div class="kt-rev kt-actions"><a class="kt-btn" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer">{{ $ctaLabel }}</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 9 · BLESSING (a window onto the temple) ============ --}}
    <section class="kt-sec kt-window" id="kt-blessing">
      <div class="kt-glass kt-rev">
        <img class="kt-trai-sm" src="{{ $centerImage }}" alt="" aria-hidden="true" loading="lazy">
        <h2 class="kt-title light">សូមអនុមោទនា</h2>
        @if ($blessing !== '')
          <p class="kt-body kt-own light">{{ $blessing }}</p>
        @else
          <p class="kt-body light">សូមកុសលផលបុណ្យនេះ ជួយតាមថែរក្សា លោកអ្នកទាំងអស់<br>ឲ្យប្រកបដោយពុទ្ធពរទាំងបួនប្រការ គឺ<br><b>អាយុ វណ្ណៈ សុខៈ ពលៈ</b><br>កុំបីឃ្លៀងឃ្លាតឡើយ ។</p>
        @endif
        <p class="kt-sadhu">សាធុ ! សាធុ ! សាធុ !</p>
      </div>
    </section>

    {{-- ============ 10 · COUNTDOWN (last, a window onto the temple) ============ --}}
    @if ($showCountdown)
      <section class="kt-sec kt-window" id="kt-countdown">
        <div class="kt-glass kt-rev">
          <h2 class="kt-title light">រាប់ថយក្រោយដល់ថ្ងៃបុណ្យ</h2>
          <div class="kt-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
            <div><b data-u="d">00</b><span>ថ្ងៃ</span></div>
            <div><b data-u="h">00</b><span>ម៉ោង</span></div>
            <div><b data-u="m">00</b><span>នាទី</span></div>
            <div><b data-u="s">00</b><span>វិនាទី</span></div>
          </div>
        </div>
      </section>
    @endif

    <footer class="kt-footer">
      <div class="kt-divider"><i></i><svg aria-hidden="true"><use href="#kt-lotus"/></svg><i></i></div>
      @if ($dateNumeric)<p class="kt-footer-date">{{ $dateNumeric }}</p>@endif
      <p class="kt-brand">CRAFTED BY <b>ROUMDOUL</b> · {{ now()->year }}</p>
    </footer>
  </main>

  {{-- ============ MUSIC (an uploaded song, or a YouTube link primed muted; the guest's first tap anywhere starts it) ============ --}}
  @if ($musicFile || $youtubeId)
    @if ($musicFile)
      <audio id="kt-audio" src="{{ $musicFile }}" preload="none" loop></audio>
    @else
      <iframe id="kt-yt" title="Background music" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;border:0"
        src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
        allow="autoplay; encrypted-media"></iframe>
    @endif
    <button type="button" class="kt-music paused" id="kt-music" aria-label="Toggle music">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @elseif ($musicUrl)
    <a class="kt-music" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Play music">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @endif
</div>

<script src="{{ $ktAsset('js/invitations/kak-thern.js') }}"></script>
