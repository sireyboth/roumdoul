@php
    use App\Support\Invitations\ChristmasTemplate as Xmas;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Christmas party — English-only (copy: lang/en/christmas.php, dates en-GB).
    // Everything personal comes from $fields (catalog keys, see docs/invitation-templates.md);
    // every optional line hides itself when its field is empty. On the demo page $invitation
    // is null, so every premium line previews unlocked.
    //
    // <Invitation>: .xm — two sections, each one screen tall:
    //   <InvitationCover>   #xm-cover   background video, cover PNG, guest name, title, Open button
    //   <InvitationDetails> #xm-details hidden until "Open Invitation"; every event detail
    //   <SnowFall>          #xm-snow    over both sections (public/js/invitations/christmas.js)
    // Layers, bottom to top: background (z0) → cover PNG (z1) → snow (z2) → text and buttons (z3).
    //
    // The dashboard editor shows this page in an iframe (?editor=1) and live-updates it with
    // postMessage, so each dynamic bit carries data-xm / data-xm-line hooks christmas.js fills in
    // the same way as the PHP below.
    // ------------------------------------------------------------------------------------
    $t = fn (string $key, array $replace = []) => Xmas::t($key, $replace);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $xmAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $text = fn (string $key) => trim((string) ($fields[$key] ?? ''));
    $editorPreview = $editorPreview ?? false;

    $title = $text('headline') ?: $t('default_title');
    $guest = trim((string) ($recipientName ?? ''));
    $host = $text('sender_name');

    [$startDay, $startTime] = Xmas::splitStart($fields['event_date'] ?? null);
    $endTime = Xmas::validTime($text('end_time')) ? $text('end_time') : null;
    $dateText = $startDay ? Xmas::formatDate($startDay) : '';
    $timeText = $startTime ? Xmas::formatTime($startTime).($endTime ? ' – '.Xmas::formatTime($endTime) : '') : '';

    $venueName = $text('venue_name');
    $venueAddress = $isUnlocked('venue_address') ? $text('venue_address') : '';
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $text('map_url'))) ? $text('map_url') : null;
    // a Maps link pasted into the address box is a map link, not an address to print
    if ($venueAddress !== '' && preg_match('#^https?://\S+$#i', $venueAddress)) {
        $mapLink = $mapLink ?: $venueAddress;
        $venueAddress = '';
    }
    $mapUrl = $mapLink ?: ($venueAddress !== '' ? 'https://maps.google.com/?q='.urlencode($venueAddress) : '');

    $message = $text('message');
    $dressCode = $text('dress_code');
    $bring = $text('what_to_bring');

    $rsvpBy = Xmas::validDate($text('rsvp_deadline')) ? Xmas::formatDate($text('rsvp_deadline')) : '';
    $contact = $text('contact_phone');
    // looks like a phone number -> tappable tel: link; anything else (a Telegram handle, ...) shows as typed
    $contactTel = preg_match('/^\+?[\d\s().-]{6,}$/', $contact) ? preg_replace('/[^\d+]/', '', $contact) : '';
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpQuestion = $text('rsvp_message') ?: $t('page.rsvp_question');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    // an uploaded song wins over a link; it isn't fetched until the guest taps play (preload="none")
    $musicFile = (! empty($fields['music_file']) && $isUnlocked('music_file')) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $isUnlocked('music_url') ? $text('music_url') : '';
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $dir = 'images/invitations/christmas/';
    $video = fn (int $n) => [
        'portrait' => $xmAsset($dir."portrait/Portrait-session{$n}.mp4"),
        'landscape' => $xmAsset($dir."landscape/Landscape-session{$n}.mp4"),
    ];
    $coverArt = asset($dir.rawurlencode('cistmas Text.png'));

    // what christmas.js needs to re-render the same lines in the editor's live preview
    $config = [
        'defaultTitle' => $t('default_title'),
        'dear' => $t('page.dear', ['name' => ':name']),
        'rsvpQuestion' => $t('page.rsvp_question'),
        'locked' => collect(['venue_address', 'map_url', 'rsvp_enabled', 'music_url', 'music_file'])->reject($isUnlocked)->values(),
        'editor' => $editorPreview,
    ];
@endphp
<link rel="preload" as="image" href="{{ $coverArt }}" fetchpriority="high">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,600;0,700;1,500&display=swap">
<link rel="stylesheet" href="{{ $xmAsset('css/invitations/christmas.css') }}">

<div class="xm" id="xm" data-config="{{ json_encode($config) }}">

  {{-- ===== Section 1: cover ===== --}}
  <section class="xm-section xm-cover" id="xm-cover" aria-label="{{ $title }}">
    <video class="xm-bg" data-bg data-portrait="{{ $video(1)['portrait'] }}" data-landscape="{{ $video(1)['landscape'] }}"
      muted loop playsinline autoplay preload="auto" aria-hidden="true" tabindex="-1"></video>

    <div class="xm-cover-inner">
      <p class="xm-dear xm-layer-text" data-xm-line="dear" @if ($guest === '') hidden @endif>
        <span data-xm="dear">{{ $t('page.dear', ['name' => $guest]) }}</span>
      </p>

      <img class="xm-cover-art" src="{{ $coverArt }}" alt="Merry Christmas" width="1200" height="1200"
        fetchpriority="high" decoding="async" draggable="false">

      {{-- the title sits on the plain snow at the bottom of both videos, clear of the village --}}
      <div class="xm-cover-spacer"></div>

      <h1 class="xm-cover-title xm-layer-text" data-xm="title">{{ $title }}</h1>

      <button type="button" class="xm-open xm-layer-text" id="xm-open" aria-controls="xm-details" aria-expanded="false" data-capture-box>
        {{ $t('page.open') }}
      </button>
    </div>
  </section>

  {{-- ===== Section 2: event details (hidden until "Open Invitation") ===== --}}
  <section class="xm-section xm-details" id="xm-details" hidden aria-label="{{ $t('page.invited') }} {{ $title }}">
    {{-- lazy: no src until the invitation is opened --}}
    <video class="xm-bg" data-bg data-lazy data-portrait="{{ $video(2)['portrait'] }}" data-landscape="{{ $video(2)['landscape'] }}"
      muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"></video>

    <div class="xm-panel xm-layer-text" data-capture-box>
      <p class="xm-kicker">{{ $t('page.invited') }}</p>
      <h2 class="xm-title" data-xm="title">{{ $title }}</h2>
      <p class="xm-host" data-xm-line="host" @if ($host === '') hidden @endif>
        {{ $t('page.hosted_by') }} <strong data-xm="host">{{ $host }}</strong>
      </p>

      <hr class="xm-rule" data-capture-box>

      <div class="xm-group" data-xm-group @if ($dateText === '' && $timeText === '') hidden @endif>
        <h3 class="xm-label">{{ $t('page.when') }}</h3>
        <p class="xm-strong" data-xm-line="date" @if ($dateText === '') hidden @endif><span data-xm="date">{{ $dateText }}</span></p>
        <p data-xm-line="time" @if ($timeText === '') hidden @endif><span data-xm="time">{{ $timeText }}</span></p>
      </div>

      <div class="xm-group" data-xm-group @if ($venueName === '' && $venueAddress === '' && $mapUrl === '') hidden @endif>
        <h3 class="xm-label">{{ $t('page.where') }}</h3>
        <p class="xm-strong" data-xm-line="venue" @if ($venueName === '') hidden @endif><span data-xm="venue">{{ $venueName }}</span></p>
        <p class="xm-pre" data-xm-line="address" @if ($venueAddress === '') hidden @endif><span data-xm="address">{{ $venueAddress }}</span></p>
        <p data-xm-line="map" @if ($mapUrl === '') hidden @endif>
          <a class="xm-btn-ghost" data-xm="map" data-capture-box href="{{ $mapUrl ?: '#' }}" target="_blank" rel="noopener noreferrer">{{ $t('page.view_map') }}</a>
        </p>
      </div>

      <p class="xm-message xm-pre" data-xm-line="message" @if ($message === '') hidden @endif><span data-xm="message">{{ $message }}</span></p>

      <div class="xm-group" data-xm-group @if ($dressCode === '') hidden @endif>
        <h3 class="xm-label">{{ $t('page.dress_code') }}</h3>
        <p data-xm-line="dress" @if ($dressCode === '') hidden @endif><span data-xm="dress">{{ $dressCode }}</span></p>
      </div>

      <div class="xm-group" data-xm-group @if ($bring === '') hidden @endif>
        <h3 class="xm-label">{{ $t('page.what_to_bring') }}</h3>
        <p data-xm-line="bring" @if ($bring === '') hidden @endif><span data-xm="bring">{{ $bring }}</span></p>
      </div>

      <div class="xm-group" data-xm-group @if ($rsvpBy === '' && $contact === '' && ! $showRsvp) hidden @endif>
        <h3 class="xm-label">{{ $t('page.rsvp') }}</h3>
        <p data-xm-line="rsvpby" @if ($rsvpBy === '') hidden @endif>{{ $t('page.rsvp_by') }} <strong data-xm="rsvpby">{{ $rsvpBy }}</strong></p>
        <p data-xm-line="contact" @if ($contact === '') hidden @endif>{{ $t('page.rsvp_contact') }}:
          <a data-xm="contact" @if ($contactTel !== '') href="tel:{{ $contactTel }}" @endif>{{ $contact }}</a>
        </p>

        <div class="xm-rsvp" id="xm-rsvp" data-xm-line="rsvp" data-url="{{ $rsvpUrl }}" @unless ($showRsvp) hidden @endunless>
          <p class="xm-rsvp-q" data-xm="rsvpq">{{ $rsvpQuestion }}</p>
          <div class="xm-rsvp-opts">
            <button type="button" class="xm-btn" data-status="yes" data-capture-box>{{ $t('page.rsvp_yes') }}</button>
            <button type="button" class="xm-btn-ghost" data-status="no" data-capture-box>{{ $t('page.rsvp_no') }}</button>
          </div>
          <form class="xm-rsvp-form" hidden>
            <input type="text" maxlength="500" placeholder="{{ $t('page.rsvp_note') }}" autocomplete="off" aria-label="{{ $t('page.rsvp_note') }}">
            <button type="submit" class="xm-btn">{{ $t('page.rsvp_send') }}</button>
          </form>
          <p class="xm-rsvp-thanks" hidden>{{ $t('page.rsvp_thanks') }}</p>
          <p class="xm-rsvp-error" hidden>{{ $t('page.rsvp_error') }}</p>
        </div>
      </div>

      <button type="button" class="xm-back" id="xm-back" data-capture-skip>↑ {{ $t('page.back') }}</button>
    </div>
  </section>

  {{-- ===== snow over both sections (flakes added by christmas.js; client-only) ===== --}}
  <div class="xm-snow" id="xm-snow" aria-hidden="true" data-src="{{ asset($dir.'snowflake.svg') }}"></div>

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
