{{--
  MEMORY HALL (prefix `mh`) — a real-time 3D wedding gallery the guest walks through in first person.
  Cover: night, a grand double door with the couple's monogram; «បើកទ្វារ» swings the doors open and the camera walks
  into a candle-lit hall (red aisle carpet, marble floor, chandeliers, barrel-vault ceiling). The hall has wings, each
  behind its own pair of doors that open as the guest walks up: Welcome → Our day → Memories → For our guests → the
  floral arch at the end. Photos hang on the walls in gilded frames (tap one = walk up to it + the shared FLIP preview),
  and each stop's details show in a glass panel. Walk: swipe up/down, wheel, W/S/↑/↓, the ‹ › buttons, the stop chips,
  tap the floor, or «ដើរទស្សនា» (auto tour). Look: drag sideways, A/D/←/→; the gaze also turns to the photo you pass.
  The 3D scene is resources/js/invitations/memory-hall.js (Vite, bundles three.js); styles public/css/invitations/memory-hall.css.
  No WebGL / reduced motion / ?flat=1 / the «page» button = every stop as a normal scrolling page (same markup).
  Built on WeddingData + <x-invite-kit> (docs/invitation-templates.md «Start a new template from the shared kit»).
--}}
@php
    use App\Support\Invitations\WeddingData as W;

    $w = W::from($fields, $invitation, $recipient ?? null, ['accent' => '#c9a24d', 'closing_line' => 'Forever starts here']);
    $kh = fn (?string $s) => W::isKhmer($s) ? ' ik-kh' : '';
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';
    $skipCover = request()->boolean('open');
    $css = 'css/invitations/memory-hall.css';

    $wings = [
        'welcome' => ['kh' => 'សាលស្វាគមន៍', 'en' => 'Welcome'],
        'day' => ['kh' => 'ថ្ងៃមង្គល', 'en' => 'Our day'],
        'memories' => ['kh' => 'សាលអនុស្សាវរីយ៍', 'en' => 'Memories'],
        'guests' => ['kh' => 'សម្រាប់ភ្ញៀវកិត្តិយស', 'en' => 'For our guests'],
        'finale' => ['kh' => 'រហូតទៅ', 'en' => 'Forever'],
    ];
    // every stop of the walk, in hall order; a stop whose fields are empty / locked is left out (and its 3D part too)
    $stops = collect([
        ['id' => 'invite', 'wing' => 'welcome', 'kh' => 'សូមស្វាគមន៍', 'en' => 'Welcome', 'on' => true],
        ['id' => 'couple', 'wing' => 'welcome', 'kh' => 'កូនកំលោះ & កូនក្រមុំ', 'en' => 'The couple', 'on' => $w->groomPhoto || $w->bridePhoto],
        ['id' => 'message', 'wing' => 'welcome', 'kh' => 'ពាក្យពីយើង', 'en' => 'A note', 'on' => $w->message !== ''],
        ['id' => 'date', 'wing' => 'day', 'kh' => 'ថ្ងៃមង្គល', 'en' => 'Save the date', 'on' => (bool) $w->calendar],
        ['id' => 'story', 'wing' => 'day', 'kh' => 'ដំណើររឿងស្នេហា', 'en' => 'Our story', 'on' => $w->story->isNotEmpty()],
        ['id' => 'schedule', 'wing' => 'day', 'kh' => 'កម្មវិធី', 'en' => 'Programme', 'on' => $w->scheduleImage || $w->schedule->isNotEmpty()],
        ['id' => 'gallery', 'wing' => 'memories', 'kh' => 'រូបអនុស្សាវរីយ៍', 'en' => 'Gallery', 'on' => $w->gallery->isNotEmpty()],
        ['id' => 'venue', 'wing' => 'guests', 'kh' => 'ទីតាំង', 'en' => 'Venue', 'on' => $w->showVenue],
        ['id' => 'notes', 'wing' => 'guests', 'kh' => 'ព័ត៌មានសម្រាប់ភ្ញៀវ', 'en' => 'Guest notes', 'on' => $w->dressCode !== '' || $w->whatToBring !== '' || (bool) $w->rsvpBy],
        ['id' => 'rsvp', 'wing' => 'guests', 'kh' => 'ការឆ្លើយតប', 'en' => 'RSVP', 'on' => $w->showRsvp],
        ['id' => 'gift', 'wing' => 'guests', 'kh' => 'ចំណងដៃ', 'en' => 'Gift', 'on' => (bool) $w->giftQr],
        ['id' => 'contacts', 'wing' => 'guests', 'kh' => 'ទំនាក់ទំនង', 'en' => 'Contact', 'on' => $w->contacts->isNotEmpty() || (bool) $w->ctaLabel],
        ['id' => 'letters', 'wing' => 'guests', 'kh' => 'សារពីយើង', 'en' => 'Letters', 'on' => ! empty($w->letters)],
        ['id' => 'finale', 'wing' => 'finale', 'kh' => 'រហូតទៅ', 'en' => 'Forever', 'on' => true],
    ])->filter(fn ($s) => $s['on'])->map(fn ($s) => collect($s)->except('on')->all())->values();
    $has = fn (string $id) => $stops->contains('id', $id);

    $d = $w->eventDate;
    $data = [
        'accent' => $w->accent,
        'guest' => $guest,
        'names' => $w->coupleTitle,
        'groom' => $w->groom,
        'bride' => $w->bride,
        'initials' => $w->initials,
        'headline' => $w->headline,
        'dateKh' => $w->dateKhmer,
        'dateNum' => $w->dateNumeric,
        'timeKh' => $w->timeKhmer,
        'khmerDate' => $w->khmerDate,
        'day' => $d ? W::khDigits($d->day) : null,
        'month' => $d ? 'ខែ'.W::KH_MONTHS[$d->month - 1] : null,
        'monthEn' => $d ? W::EN_MONTHS[$d->month - 1] : null,
        'year' => $d ? W::khDigits($d->year) : null,
        'weekday' => $d ? 'ថ្ងៃ'.W::KH_DAYS[$d->dayOfWeek] : null,
        'calendar' => $w->calendar ? ['title' => $w->calendar['title'], 'cells' => array_map(fn ($c) => $c ? W::khDigits($c) : '', $w->calendar['cells']), 'day' => W::khDigits($w->calendar['day']), 'heads' => W::KH_DAYS_SHORT] : null,
        'cover' => $w->cover,
        'background' => $w->background ?: $w->cover,
        'groomPhoto' => $w->groomPhoto,
        'bridePhoto' => $w->bridePhoto,
        'gallery' => $w->gallery->values()->map(fn ($u, $i) => ['src' => $u, 'cap' => $w->captions[$i] ?? ''])->all(),
        'message' => $w->message,
        'story' => $w->story->values()->all(),
        'schedule' => $w->schedule->values()->all(),
        'scheduleImage' => $w->scheduleImage,
        'dress' => $w->dressColours,
        'venueName' => $w->venueName,
        'closing' => $w->closingLine,
        'letters' => collect($w->letters)->map(fn ($l) => ['kh' => $l[0], 'text' => $l[2]])->values()->all(),
        'stops' => $stops->all(),
        'wings' => $wings,
        'rsvpUrl' => $w->rsvpUrl,
    ];
    $photoKeys = collect([
        'cover' => [$w->cover, 'cover', 0, $w->coupleTitle],
        'groom' => [$w->groomPhoto, 'couple', 0, $w->groom],
        'bride' => [$w->bridePhoto, 'couple', 1, $w->bride],
        'sched' => [$has('schedule') ? $w->scheduleImage : null, 'sched', 0, 'កម្មវិធី'],
        'finale' => [$data['background'], 'finale', 0, $w->closingLine],
    ])->filter(fn ($p) => $p[0]);
@endphp
<title>{{ $w->coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Great+Vibes&family=Moulpali&family=Noto+Serif+Khmer:wght@400;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset($css) }}?v={{ @filemtime(public_path($css)) }}">

<div class="mh{{ $skipCover ? ' is-open is-inside' : '' }}" id="mh" style="--ik-accent:{{ $w->accent }}; --mh-gold:{{ $w->accent }};">
  {{-- the 3D hall --}}
  <canvas class="mh-canvas" id="mh-canvas" aria-hidden="true"></canvas>
  <div class="mh-flash" id="mh-flash" aria-hidden="true"></div>
  <div class="mh-vignette" aria-hidden="true"></div>
  <div class="mh-loading" id="mh-loading" aria-hidden="true"><span></span></div>

  {{-- ===== cover: outside the closed doors at night ===== --}}
  @unless ($skipCover)
    <div class="mh-cover" id="mh-cover" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$w->coupleTitle) }}">
      <div class="mh-cover-top">
        <p class="mh-eyebrow{{ $kh($w->headline) }}">{{ $w->headline ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</p>
        @if ($w->titleImage)
          <img class="mh-title-img" src="{{ $w->titleImage }}" alt="{{ $w->coupleTitle }}">
        @else
          <h1 class="mh-names{{ $kh($w->coupleTitle) }}">
            @if ($w->groom && $w->bride)<span>{{ $w->groom }}</span><em>&amp;</em><span>{{ $w->bride }}</span>@else{{ $w->coupleTitle }}@endif
          </h1>
        @endif
        @if ($w->dateKhmer)<p class="mh-cover-date">{{ $w->dateKhmer }}</p>@endif
      </div>
      <div class="mh-cover-bottom">
        <p class="mh-cover-guest">សូមគោរពអញ្ជើញ<b class="{{ trim($kh($guest)) }}">{{ $guest }}</b></p>
        <button type="button" class="mh-open" id="mh-open">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h5v18M13 3h5a2 2 0 0 1 2 2v16M2 21h20M9.5 12h.01M14.5 12h.01" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          <span>បើកទ្វារ</span><small>Open the door</small>
        </button>
        <p class="mh-cover-tip">ចូលដើរទស្សនាសាលអនុស្សាវរីយ៍របស់យើង · 🎧</p>
      </div>
    </div>
  @endunless

  {{-- ===== HUD (3D mode only) ===== --}}
  <header class="mh-top">
    <p class="mh-badge"><b class="mh-mono">{{ $w->initials }}</b><span class="{{ trim($kh($w->coupleTitle)) }}">{{ $w->coupleTitle }}</span></p>
    <div class="mh-tools">
      <button type="button" class="mh-tool" id="mh-tour" aria-pressed="false" aria-label="ដើរទស្សនាដោយស្វ័យប្រវត្តិ">
        <svg class="play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
        <svg class="pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z" fill="currentColor"/></svg>
        <span>ដើរទស្សនា</span>
      </button>
      <button type="button" class="mh-tool icon" id="mh-flat" aria-pressed="false" aria-label="មើលជាទំព័រ">
        <svg class="page" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM9 11h7M9 15h7M9 7h4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <svg class="cube" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l9 5v10l-9 5-9-5V7zM12 12l9-5M12 12v10M12 12L3 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </header>
  <p class="mh-hint" id="mh-hint" aria-hidden="true">
    <span><i class="swipe"></i>អូសឡើងលើ ដើម្បីដើរ</span><span><i class="drag"></i>អូសឆ្វេង/ស្ដាំ ដើម្បីមើលជុំវិញ</span><span><i class="tap"></i>ចុចលើរូប ដើម្បីមើលធំ</span>
  </p>
  <p class="mh-wing" id="mh-wing" aria-live="polite"><small></small><b></b></p>

  <nav class="mh-nav" aria-label="ផ្នែកនៃសាល">
    <button type="button" class="mh-arrow" id="mh-prev" aria-label="ផ្នែកមុន"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
    <div class="mh-chips" id="mh-chips">
      @foreach ($stops as $i => $s)
        <button type="button" class="mh-chip" data-go="{{ $i }}"><i>{{ W::khDigits($i + 1) }}</i>{{ $s['kh'] }}</button>
      @endforeach
    </div>
    <button type="button" class="mh-arrow next" id="mh-next" aria-label="ផ្នែកបន្ទាប់"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
  </nav>

  {{-- invisible preview anchors for the photos on the walls: placed over the 3D frame before the shared lightbox opens,
       so the preview zooms out of the frame itself --}}
  <div class="mh-pbtns" id="mh-pbtns" aria-hidden="true">
    @foreach ($photoKeys as $key => [$src, $group, $gi, $cap])
      <button type="button" tabindex="-1" data-ik-photo data-key="{{ $key }}" data-group="{{ $group }}" data-g="{{ $gi }}" data-full="{{ $src }}" data-cap="{{ $cap }}"></button>
    @endforeach
    @foreach ($w->gallery as $gi => $url)
      <button type="button" tabindex="-1" data-ik-photo data-key="gallery-{{ $gi }}" data-group="gallery" data-g="{{ $gi }}" data-full="{{ $url }}" data-cap="{{ $w->captions[$gi] ?? '' }}"></button>
    @endforeach
  </div>

  {{-- ===== the stops: a glass panel per stop in 3D, a normal page in flat mode ===== --}}
  <main class="mh-sheet" id="mh-sheet">
    <button type="button" class="mh-grip" id="mh-grip" aria-expanded="true" aria-label="បង្រួម / ពង្រីក"><i></i></button>
    <div class="mh-flat-hero">
      @if ($w->cover)<button type="button" class="mh-flat-photo" data-ik-photo data-group="cover" data-g="0" data-full="{{ $w->cover }}" aria-label="{{ $w->coupleTitle }}"><img src="{{ $w->cover }}" alt=""></button>@endif
      <p class="mh-eyebrow{{ $kh($w->headline) }}">{{ $w->headline ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</p>
      <h2 class="mh-names{{ $kh($w->coupleTitle) }}">{{ $w->coupleTitle }}</h2>
      @if ($w->dateKhmer)<p class="mh-cover-date">{{ $w->dateKhmer }}</p>@endif
    </div>

    @foreach ($stops as $i => $s)
      <section class="mh-stop{{ in_array($s['id'], ['gallery', 'couple'], true) ? ' compact' : '' }}" data-stop="{{ $s['id'] }}" data-i="{{ $i }}" aria-labelledby="mh-h-{{ $s['id'] }}">
        <header class="mh-stop-head">
          <small>{{ $s['en'] }} · {{ W::khDigits($i + 1) }}/{{ W::khDigits($stops->count()) }}</small>
          <h3 id="mh-h-{{ $s['id'] }}">{{ $s['kh'] }}</h3>
        </header>

        @switch($s['id'])
          @case('invite')
            @if ($w->groomParents || $w->brideParents)
              <div class="mh-parents">
                <p>@foreach ($w->groomParents as $p)<span>{{ $p }}</span>@endforeach</p>
                <p>@foreach ($w->brideParents as $p)<span>{{ $p }}</span>@endforeach</p>
              </div>
            @endif
            <p class="mh-soft">មានកិត្តិយសសូមគោរពអញ្ជើញ</p>
            <p class="mh-guest{{ $kh($guest) }}">{{ $guest }}</p>
            <p class="mh-text">{{ $w->receptionText }}</p>
            <div class="mh-when">
              @if ($w->dateKhmer)<b>{{ $w->dateKhmer }}</b>@endif
              @if ($w->khmerDate)<span>{{ $w->khmerDate }}</span>@endif
              @if ($w->timeKhmer)<span class="gold">{{ $w->timeKhmer }}</span>@endif
              @if ($w->venueName)<span>{{ $w->venueName }}</span>@endif
            </div>
            @break

          @case('couple')
            <div class="mh-duo">
              @foreach ([['groom', $w->groomPhoto, $w->groom, 'កូនកំលោះ'], ['bride', $w->bridePhoto, $w->bride, 'កូនក្រមុំ']] as [$key, $photo, $name, $role])
                @if ($photo)
                  <figure>
                    <button type="button" class="mh-thumb" data-ik-photo data-key="{{ $key }}" data-group="couple" data-g="{{ $key === 'groom' ? 0 : 1 }}" data-full="{{ $photo }}" data-cap="{{ $name }}" aria-label="{{ $name }}"><img src="{{ $photo }}" alt="" loading="lazy"></button>
                    <figcaption><small>{{ $role }}</small><b class="{{ trim($kh($name)) }}">{{ $name }}</b></figcaption>
                  </figure>
                @endif
              @endforeach
            </div>
            @break

          @case('message')
            <blockquote class="mh-quote{{ $kh($w->message) }}">{{ $w->message }}</blockquote>
            @break

          @case('date')
            <div class="mh-cal">
              <p class="mh-cal-title"><b>{{ $w->calendar['title'] }}</b><small>{{ $w->calendar['en'] }}</small></p>
              <div class="mh-cal-grid">
                @foreach (W::KH_DAYS_SHORT as $dd)<span class="h">{{ $dd }}</span>@endforeach
                @foreach ($w->calendar['cells'] as $c)<span class="{{ $c === $w->calendar['day'] ? 'on' : '' }}">{{ $c ? W::khDigits($c) : '' }}</span>@endforeach
              </div>
            </div>
            <p class="mh-text"><b>{{ $w->dateKhmer }}</b>@if ($w->khmerDate)<br><span class="mh-soft">{{ $w->khmerDate }}</span>@endif<br><span class="gold">{{ $w->timeKhmer }}</span></p>
            @if ($w->showCountdown)
              <div class="mh-count" id="mh-count" data-target="{{ $w->isoDate }}">
                @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $l)<div><b data-u="{{ $u }}">០០</b><span>{{ $l }}</span></div>@endforeach
              </div>
            @endif
            @break

          @case('story')
            <ol class="mh-story">
              @foreach ($w->story as $c)
                <li><b class="{{ trim($kh($c['place'])) }}">{{ $c['place'] }}</b>@if ($c['when'])<small>{{ $c['when'] }}</small>@endif<p>{{ $c['text'] }}</p></li>
              @endforeach
            </ol>
            @break

          @case('schedule')
            @if ($w->scheduleImage)
              <button type="button" class="mh-thumb wide" data-ik-photo data-key="sched" data-group="sched" data-g="0" data-full="{{ $w->scheduleImage }}" data-cap="កម្មវិធី" aria-label="កម្មវិធី"><img src="{{ $w->scheduleImage }}" alt="" loading="lazy"></button>
            @else
              <ul class="mh-sched">@foreach ($w->schedule as $row)<li><b>{{ $row['time'] }}</b><span>{{ $row['label'] }}</span></li>@endforeach</ul>
            @endif
            @break

          @case('gallery')
            <p class="mh-soft mh-3d-only">ចុចលើរូបនៅលើជញ្ជាំង ឬរូបតូចៗខាងក្រោម</p>
            <div class="mh-strip" id="mh-strip">
              @foreach ($w->gallery as $gi => $url)
                <button type="button" class="mh-thumb" data-ik-photo data-key="gallery-{{ $gi }}" data-group="gallery" data-g="{{ $gi }}" data-full="{{ $url }}" data-cap="{{ $w->captions[$gi] ?? '' }}" aria-label="{{ ($w->captions[$gi] ?? '') ?: 'រូបភាពទី '.W::khDigits($gi + 1) }}"><img src="{{ $url }}" alt="" loading="lazy"></button>
              @endforeach
            </div>
            @break

          @case('venue')
            @if ($w->venueName)<p class="mh-big{{ $kh($w->venueName) }}">{{ $w->venueName }}</p>@endif
            @if ($w->venueAddress)<p class="mh-text">{{ $w->venueAddress }}</p>@endif
            @if ($w->mapEmbed)<div class="mh-map" id="mh-map" data-src="{{ $w->mapEmbed }}"><button type="button" class="mh-btn ghost" data-map>បង្ហាញផែនទី</button></div>@endif
            @if ($w->mapUrl)<a class="mh-btn" href="{{ $w->mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">បើក Google Maps</a>@endif
            @break

          @case('notes')
            @if ($w->dressCode !== '')
              <p class="mh-label">ពណ៌សម្លៀកបំពាក់</p>
              @if ($w->dressColours)
                <ul class="mh-swatches">@foreach ($w->dressColours as $c)<li style="--c:{{ $c['hex'] }}"><button type="button" data-swatch aria-label="{{ $c['name'] }}"><i></i></button><span class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</span></li>@endforeach</ul>
              @endif
              <p class="mh-text">{{ $w->dressCode }}</p>
            @endif
            @if ($w->whatToBring !== '')<p class="mh-note"><b>សូមយកមកជាមួយ</b>{{ $w->whatToBring }}</p>@endif
            @if ($w->rsvpBy)<p class="mh-note"><b>សូមឆ្លើយតបមុន</b>{{ $w->rsvpBy }}</p>@endif
            @break

          @case('rsvp')
            <div id="mh-rsvp">
              <p class="mh-text">{{ $w->rsvpQuestion }}</p>
              <div class="mh-opts" id="mh-rsvp-opts" role="radiogroup" aria-label="ការឆ្លើយតប">
                <button type="button" role="radio" aria-checked="false" data-status="yes">ខ្ញុំនឹងចូលរួម ♥</button>
                <button type="button" role="radio" aria-checked="false" data-status="no">សោកស្ដាយ មិនអាចចូលរួមបាន</button>
              </div>
              <form class="mh-form" id="mh-rsvp-form"><textarea id="mh-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea><button type="submit" class="mh-btn">ចុះហត្ថលេខាក្នុងសៀវភៅភ្ញៀវ</button></form>
              <p class="mh-thanks" id="mh-rsvp-thanks" role="status"></p>
            </div>
            @break

          @case('gift')
            <p class="mh-text">{{ $w->giftMessage }}</p>
            <img class="mh-qr" src="{{ $w->giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
            @if ($w->giftAccount)<p class="mh-text" style="white-space:pre-line">{{ $w->giftAccount }}</p>@endif
            <div class="mh-row">
              @if ($w->giftNumber)<button type="button" class="mh-btn" data-copy="{{ $w->giftNumber }}">ចម្លងលេខគណនី</button>@endif
              <a class="mh-btn ghost" href="{{ $w->giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
            </div>
            @break

          @case('contacts')
            <ul class="mh-contacts">
              @foreach ($w->contacts as $c)<li><small>{{ $c['role'] }}</small><b class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</b><a href="tel:{{ $c['tel'] }}" data-sfx="tap">{{ $c['phone'] }}</a></li>@endforeach
            </ul>
            @if ($w->ctaLabel)<a class="mh-btn" href="{{ $w->ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $w->ctaLabel }}</a>@endif
            @break

          @case('letters')
            @foreach ($w->letters as [$khHead, $en, $text])
              <article class="mh-letter"><h4>{{ $khHead }} <small>{{ $en }}</small></h4><p>{{ $text }}</p></article>
            @endforeach
            @break

          @case('finale')
            <p class="mh-closing{{ $kh($w->closingLine) }}">{{ $w->closingLine }}</p>
            <p class="mh-soft">សូមអរគុណដែលបានដើរទស្សនាសាលអនុស្សាវរីយ៍របស់យើង</p>
            <div class="mh-row">
              <button type="button" class="mh-btn" id="mh-love">ផ្ញើក្តីស្រឡាញ់ ♥</button>
              <button type="button" class="mh-btn ghost mh-3d-only" id="mh-again">ដើរម្ដងទៀត</button>
            </div>
            <x-invitation-footer prefix="mh" :names="$w->coupleTitle" :names-class="W::isKhmer($w->coupleTitle) ? 'ik-kh' : ''" :date="$w->dateNumeric"
              style="--inv-footer-bg:transparent; --inv-footer-text:#f6ead3; --inv-footer-muted:#c9b79a; --inv-footer-accent:{{ $w->accent }}; --inv-footer-names-font:'Great Vibes','Moulpali',cursive;" />
            @break
        @endswitch
      </section>
    @endforeach
  </main>

  <x-invite-kit :w="$w" :sfx="['step' => 'footstep.wav', 'whoosh' => 'whoosh.wav']" wait />
  <script type="application/json" id="mh-data">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
  @vite('resources/js/invitations/memory-hall.js')
</div>
