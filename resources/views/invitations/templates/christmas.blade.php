@php
    use App\Support\Invitations\ChristmasTemplate as Xmas;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Christmas party — English-only (copy: lang/en/christmas.php, dates en-GB).
    // Everything personal comes from $fields (catalog keys, see docs/invitation-templates.md);
    // every optional line hides itself when its field is empty. On the demo page $invitation
    // is null, so every premium line previews unlocked.
    //
    // Two separate pages, each with its own route (Xmas::pageData() gives $page + $pageUrls):
    //   cover   — background video, cover PNG, «Dear guest,», title, the gift-box button. Opening
    //             the box plays its animation, then navigates (same tab) to the details URL.
    //   details — the event card on a cream paper card; «Back to cover» links back.
    //   <SnowFall> #xm-snow over the page (public/js/invitations/christmas.js)
    // Layers, bottom to top: background (z0) → cover PNG (z1) → snow (z2) → text and buttons (z3).
    //
    // The dashboard editor shows a page in an iframe (?editor=1) and live-updates it with
    // postMessage, so each dynamic bit carries data-xm / data-xm-line hooks christmas.js fills in
    // the same way as the PHP below.
    // ------------------------------------------------------------------------------------
    $t = fn (string $key, array $replace = []) => Xmas::t($key, $replace);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $xmAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $text = fn (string $key) => trim((string) ($fields[$key] ?? ''));
    $typo = fn (string $key) => Xmas::typo($text($key));
    $editorPreview = $editorPreview ?? false;

    $page = in_array($page ?? 'cover', Xmas::PAGES, true) ? ($page ?? 'cover') : 'cover';
    // rendered without routes (tests, a one-off include): page 2 lives at «this URL»/details
    $pageUrls = $pageUrls ?? ['cover' => url()->current(), 'details' => rtrim(url()->current(), '/').'/details'];

    $title = Xmas::typo($text('headline')) ?: $t('default_title');
    $guest = trim((string) ($recipientName ?? ''));
    $host = $typo('sender_name');

    [$startDay, $startTime] = Xmas::splitStart($fields['event_date'] ?? null);
    $endTime = Xmas::validTime($text('end_time')) ? $text('end_time') : null;
    $dateText = $startDay ? Xmas::formatDate($startDay) : '';
    $timeText = $startTime ? Xmas::formatTime($startTime).($endTime ? ' – '.Xmas::formatTime($endTime) : '') : '';

    $venueName = $typo('venue_name');
    $venueAddress = $isUnlocked('venue_address') ? $text('venue_address') : '';
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $text('map_url'))) ? $text('map_url') : null;
    // a Maps link pasted into the address box is a map link, not an address to print
    if ($venueAddress !== '' && preg_match('#^https?://\S+$#i', $venueAddress)) {
        $mapLink = $mapLink ?: $venueAddress;
        $venueAddress = '';
    }
    $mapUrl = $mapLink ?: ($venueAddress !== '' ? 'https://maps.google.com/?q='.urlencode($venueAddress) : '');
    $venueAddress = Xmas::typo($venueAddress);

    $message = $typo('message');
    $dressCode = $typo('dress_code');
    $bring = $typo('what_to_bring');

    $rsvpBy = Xmas::validDate($text('rsvp_deadline')) ? Xmas::formatDate($text('rsvp_deadline')) : '';
    $contact = $text('contact_phone');
    // looks like a phone number -> a «Call to RSVP» tel: button; anything else (a Telegram handle, ...) shows as typed
    $contactTel = preg_match('/^\+?[\d\s().-]{6,}$/', $contact) ? preg_replace('/[^\d+]/', '', $contact) : '';
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpQuestion = Xmas::typo($text('rsvp_message')) ?: $t('page.rsvp_question');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    // an uploaded song wins over a link; it isn't fetched until the guest taps play (preload="none")
    $musicFile = (! empty($fields['music_file']) && $isUnlocked('music_file')) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $isUnlocked('music_url') ? $text('music_url') : '';
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $dir = 'images/invitations/christmas/';
    $session = $page === 'cover' ? 1 : 2;
    $video = [
        'portrait' => $xmAsset($dir."portrait/Portrait-session{$session}.mp4"),
        'landscape' => $xmAsset($dir."landscape/Landscape-session{$session}.mp4"),
    ];
    $coverArt = asset($dir.rawurlencode('cistmas Text.png'));

    // what christmas.js needs: the page, both page URLs, and the copy to re-render the same lines
    // in the editor's live preview
    $config = [
        'page' => $page,
        'urls' => $pageUrls,
        'defaultTitle' => $t('default_title'),
        'dear' => $t('page.dear', ['name' => ':name']),
        'rsvpQuestion' => $t('page.rsvp_question'),
        'locked' => collect(['venue_address', 'map_url', 'rsvp_enabled', 'music_url', 'music_file'])->reject($isUnlocked)->values(),
        'editor' => $editorPreview,
    ];
@endphp
@if ($page === 'cover')
  <link rel="preload" as="image" href="{{ $coverArt }}" fetchpriority="high">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Great+Vibes&family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,500&display=swap">
<link rel="stylesheet" href="{{ $xmAsset('css/invitations/christmas.css') }}">

<div class="xm xm-page-{{ $page }}" id="xm" data-config="{{ json_encode($config) }}">

  @if ($page === 'cover')
    {{-- ===== Page 1: cover ===== --}}
    <section class="xm-section xm-cover" id="xm-cover" aria-label="{{ $title }}">
      <video class="xm-bg" data-bg data-portrait="{{ $video['portrait'] }}" data-landscape="{{ $video['landscape'] }}"
        muted loop playsinline autoplay preload="auto" aria-hidden="true" tabindex="-1"></video>

      <div class="xm-cover-inner">
        <p class="xm-dear xm-layer-text xm-intro" data-xm-line="dear" @if ($guest === '') hidden @endif>
          <span data-xm="dear">{{ $t('page.dear', ['name' => $guest]) }}</span>
        </p>

        <img class="xm-cover-art" src="{{ $coverArt }}" alt="Merry Christmas" width="1200" height="1200"
          fetchpriority="high" decoding="async" draggable="false">

        {{-- the title sits on the plain snow at the bottom of both videos, clear of the village --}}
        <div class="xm-cover-spacer"></div>

        <h1 class="xm-cover-title xm-layer-text" data-xm="title">{{ $title }}</h1>

        {{-- the gift box: drawn in SVG (no image file); christmas.js plays the opening, then
             navigates to page 2. A real link target too, in case the script never runs. --}}
        <button type="button" class="xm-gift xm-layer-text xm-intro" id="xm-open" aria-label="{{ $t('page.open') }}"
          data-href="{{ $pageUrls['details'] }}">
          <span class="xm-gift-wiggle">
            <svg class="xm-gift-art" viewBox="0 0 140 150" width="140" height="150" aria-hidden="true" focusable="false">
              <g class="xm-gift-body">
                <rect x="18" y="62" width="104" height="80" rx="5" fill="#A8172F"/>
                <rect x="88" y="62" width="34" height="80" rx="5" fill="#8E1227" opacity=".55"/>
                <rect x="18" y="62" width="104" height="7" fill="#000" opacity=".16"/>
                <rect x="61" y="62" width="18" height="80" fill="#1F4D36"/>
                <rect x="64" y="62" width="3" height="80" fill="#2E6B4C"/>
                <path d="M79 104q14 4 26 14" fill="none" stroke="#C9A24B" stroke-width="1.4" stroke-linecap="round"/>
              </g>
              <g class="xm-gift-lid">
                <rect x="11" y="44" width="118" height="22" rx="5" fill="#BE2440"/>
                <rect x="11" y="60" width="118" height="6" rx="2" fill="#000" opacity=".12"/>
                <rect x="61" y="44" width="18" height="22" fill="#1F4D36"/>
                <rect x="64" y="44" width="3" height="22" fill="#2E6B4C"/>
                <g class="xm-gift-bow">
                  <path class="xm-bow-l" d="M70 42C55 13 24 13 30 33c4 13 26 13 40 9z" fill="#1F4D36"/>
                  <path class="xm-bow-r" d="M70 42c15-29 46-29 40-9-4 13-26 13-40 9z" fill="#1F4D36"/>
                  <path class="xm-bow-l" d="M65 40C55 24 38 22 39 32" fill="none" stroke="#2E6B4C" stroke-width="2.4" stroke-linecap="round"/>
                  <path class="xm-bow-r" d="M75 40c10-16 27-18 26-8" fill="none" stroke="#2E6B4C" stroke-width="2.4" stroke-linecap="round"/>
                  <ellipse cx="70" cy="42" rx="8" ry="6.5" fill="#173B29"/>
                </g>
              </g>
            </svg>
            <span class="xm-gift-tag" aria-hidden="true">{{ $t('page.open_me') }}</span>
          </span>
          <span class="xm-burst" aria-hidden="true"></span>
        </button>
      </div>
    </section>
  @else
    {{-- ===== Page 2: event details ===== --}}
    <section class="xm-section xm-details" id="xm-details" aria-label="{{ $t('page.invited') }} {{ $title }}">
      <video class="xm-bg" data-bg data-portrait="{{ $video['portrait'] }}" data-landscape="{{ $video['landscape'] }}"
        muted loop playsinline autoplay preload="auto" aria-hidden="true" tabindex="-1"></video>

      <article class="xm-card xm-layer-text" data-capture-box>
        <span class="xm-card-inner" data-capture-box aria-hidden="true"></span>
        {{ Xmas::svg('corner-tl', 'xm-corner-tl') }}
        {{ Xmas::svg('corner-tr', 'xm-corner-tr') }}
        {{ Xmas::svg('corner-bl', 'xm-corner-bl') }}
        {{ Xmas::svg('corner-br', 'xm-corner-br') }}

        <header class="xm-head">
          <div class="xm-rise">{{ Xmas::svg('holly') }}</div>
          <p class="xm-kicker xm-rise">{{ $t('page.invited') }}</p>
          <h1 class="xm-title xm-rise" data-xm="title">{{ $title }}</h1>
          <p class="xm-host xm-rise" data-xm-line="host" @if ($host === '') hidden @endif>
            {{ $t('page.hosted_by') }} <span data-xm="host">{{ $host }}</span>
          </p>
        </header>

        <div class="xm-block xm-rise" data-xm-group @if ($message === '') hidden @endif>
          {{ Xmas::svg('divider') }}
          <p class="xm-message xm-pre" data-xm-line="message" @if ($message === '') hidden @endif><span data-xm="message">{{ $message }}</span></p>
        </div>

        <div class="xm-block" data-xm-group @if ($dateText === '' && $timeText === '' && $venueName === '' && $venueAddress === '' && $mapUrl === '' && $dressCode === '' && $bring === '') hidden @endif>
          <div class="xm-rise">{{ Xmas::svg('divider') }}</div>

          <div class="xm-group xm-rise" data-xm-group @if ($dateText === '' && $timeText === '') hidden @endif>
            <h2 class="xm-label">{{ Xmas::svg('calendar') }} {{ $t('page.when') }}</h2>
            <p class="xm-value" data-xm-line="date" @if ($dateText === '') hidden @endif><span class="xm-nowrap" data-xm="date">{{ $dateText }}</span></p>
            <p class="xm-value" data-xm-line="time" @if ($timeText === '') hidden @endif><span data-xm="time">{{ $timeText }}</span></p>
          </div>

          <div class="xm-group xm-rise" data-xm-group @if ($venueName === '' && $venueAddress === '' && $mapUrl === '') hidden @endif>
            <h2 class="xm-label">{{ Xmas::svg('pin') }} {{ $t('page.where') }}</h2>
            <p class="xm-value" data-xm-line="venue" @if ($venueName === '') hidden @endif><span data-xm="venue">{{ $venueName }}</span></p>
            <p class="xm-value xm-pre" data-xm-line="address" @if ($venueAddress === '') hidden @endif><span data-xm="address">{{ $venueAddress }}</span></p>
            <p data-xm-line="map" @if ($mapUrl === '') hidden @endif>
              <a class="xm-btn-gold" data-xm="map" data-capture-box href="{{ $mapUrl ?: '#' }}" target="_blank" rel="noopener noreferrer">{{ Xmas::svg('pin') }} {{ $t('page.view_map') }}</a>
            </p>
          </div>

          <div class="xm-group xm-rise" data-xm-group @if ($dressCode === '') hidden @endif>
            <h2 class="xm-label">{{ Xmas::svg('star') }} {{ $t('page.dress_code') }}</h2>
            <p class="xm-value" data-xm-line="dress" @if ($dressCode === '') hidden @endif><span data-xm="dress">{{ $dressCode }}</span></p>
          </div>

          <div class="xm-group xm-rise" data-xm-group @if ($bring === '') hidden @endif>
            <h2 class="xm-label">{{ Xmas::svg('gift') }} {{ $t('page.what_to_bring') }}</h2>
            <p class="xm-value" data-xm-line="bring" @if ($bring === '') hidden @endif><span data-xm="bring">{{ $bring }}</span></p>
          </div>
        </div>

        <div class="xm-block" data-xm-group @if ($rsvpBy === '' && $contact === '' && ! $showRsvp) hidden @endif>
          <div class="xm-rise">{{ Xmas::svg('divider') }}</div>

          <div class="xm-group xm-rise" data-xm-group @if ($rsvpBy === '' && $contact === '' && ! $showRsvp) hidden @endif>
            <h2 class="xm-label">{{ Xmas::svg('phone') }} {{ $t('page.rsvp') }}</h2>
            <p class="xm-value" data-xm-line="rsvpby" @if ($rsvpBy === '') hidden @endif>{{ $t('page.rsvp_by') }} <span class="xm-nowrap" data-xm="rsvpby">{{ $rsvpBy }}</span></p>
            <p class="xm-value" data-xm-line="contact" @if ($contact === '') hidden @endif><span data-xm="contact">{{ $contact }}</span></p>
            <p data-xm-line="call" @if ($contactTel === '') hidden @endif>
              <a class="xm-btn-gold" data-xm="call" data-capture-box href="tel:{{ $contactTel }}">{{ Xmas::svg('phone') }} {{ $t('page.call_rsvp') }}</a>
            </p>

            <div class="xm-rsvp" id="xm-rsvp" data-xm-line="rsvp" data-url="{{ $rsvpUrl }}" @unless ($showRsvp) hidden @endunless>
              <p class="xm-rsvp-q" data-xm="rsvpq">{{ $rsvpQuestion }}</p>
              <div class="xm-rsvp-opts">
                <button type="button" class="xm-btn" data-status="yes" data-capture-box>{{ $t('page.rsvp_yes') }}</button>
                <button type="button" class="xm-btn-gold" data-status="no" data-capture-box>{{ $t('page.rsvp_no') }}</button>
              </div>
              <form class="xm-rsvp-form" hidden>
                <input type="text" maxlength="500" placeholder="{{ $t('page.rsvp_note') }}" autocomplete="off" aria-label="{{ $t('page.rsvp_note') }}">
                <button type="submit" class="xm-btn">{{ $t('page.rsvp_send') }}</button>
              </form>
              <p class="xm-rsvp-thanks" hidden>{{ $t('page.rsvp_thanks') }}</p>
              <p class="xm-rsvp-error" hidden>{{ $t('page.rsvp_error') }}</p>
            </div>
          </div>
        </div>

        <p class="xm-rise xm-back-wrap" data-capture-skip>
          <a class="xm-back" id="xm-back" href="{{ $pageUrls['cover'] }}">{{ Xmas::svg('gift') }} {{ $t('page.back') }}</a>
        </p>
      </article>
    </section>
  @endif

  {{-- ===== snow over the page (flakes added by christmas.js; client-only) ===== --}}
  <div class="xm-snow" id="xm-snow" aria-hidden="true" data-src="{{ asset($dir.'snowflake.svg') }}"></div>

  {{-- the cream fade between the two pages: fades in on the cover before leaving, out on page 2 --}}
  <div class="xm-veil" id="xm-veil" aria-hidden="true"></div>

  @if ($musicFile || $youtubeId)
    @if ($musicFile)
      <audio id="xm-audio" src="{{ $musicFile }}" preload="none" loop></audio>
    @else
      <iframe id="xm-yt" title="Background music" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;border:0"
        src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
        allow="autoplay; encrypted-media"></iframe>
    @endif
    <button type="button" class="xm-music" id="xm-music" aria-label="{{ $t('page.music') }}" data-capture-skip>♪</button>
  @endif
</div>

<script src="{{ $xmAsset('js/invitations/christmas.js') }}"></script>
