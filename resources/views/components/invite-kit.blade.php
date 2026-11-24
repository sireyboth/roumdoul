{{--
  Invite Kit: the shared toolbox for invitation templates. Put it once inside the template's root element,
  BEFORE the template's own <script>:

    <x-invite-kit :w="$w" />          ($w = WeddingData::from(...))
    <x-invite-kit :w="$w" :sfx="['open' => 'reveal.mp3']" wait />

  It prints:
    · invite-kit.css + invite-kit.js (once)   → window.InviteKit (see the header of the JS file for the API)
    · the music <audio id="ik-audio"> (music_file), or the YouTube id for the hidden player (music_url)
    · one sound button (#ik-sound: mutes music + sfx) and a «now playing» pill (#ik-now) for song_title.
      A music link that can't play inline becomes a button that opens it in a new tab.
    · #ik-config JSON: the sfx URLs (pop, chime, reveal, card, envelope, paper… from assets/sfx/) + youtube id

  Props:
    w      a WeddingData (or any object with musicFile, musicUrl, youtubeId, songTitle)
    sfx    extra / replacement sounds: ['name' => 'file.mp3' in assets/sfx/ or a full URL]
    wait   hide the sound button until the template's root gets .is-open (covers)
  Theme with --ik-accent, --ik-ink, --ik-paper, --ik-lb-bg, --ik-cap-font, --ik-font on the template root.
--}}
@props(['w' => null, 'sfx' => [], 'wait' => false])
@php
    $shared = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfxUrl = fn (string $f) => preg_match('#^https?://#', $f) ? $f : asset('images/invitations/assets/sfx/'.$f);
    $sounds = collect(array_merge([
        'tap' => 'pop.mp3', 'pop' => 'dragon-studio-pop-402324.mp3', 'chime' => 'chime.mp3', 'reveal' => 'reveal.mp3',
        'card' => 'card.mp3', 'envelope' => 'envelope.mp3', 'paper' => 'papercrackle1.mp3', 'scratch' => 'scratch.mp3',
    ], $sfx))->map($sfxUrl)->all();
    $musicFile = $w->musicFile ?? null;
    $musicUrl = $w->musicUrl ?? null;
    $youtubeId = $w->youtubeId ?? null;
    $songTitle = trim((string) ($w->songTitle ?? ''));
    $hasMusic = $musicFile || $youtubeId;
@endphp
@once
  <link rel="stylesheet" href="{{ $shared('css/invitations/shared/invite-kit.css') }}">
  <script src="{{ $shared('js/invitations/shared/invite-kit.js') }}"></script>
@endonce
@if ($musicFile)<audio id="ik-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
<div class="ik-sound-wrap{{ $wait ? ' ik-wait' : '' }}">
  @if ($songTitle !== '' && $hasMusic)
    <p class="ik-now" id="ik-now"><span class="disc" aria-hidden="true"></span><span class="t">{{ $songTitle }}</span></p>
  @endif
  @if ($musicUrl)
    <a class="ik-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @else
    <button type="button" class="ik-sound" id="ik-sound" aria-pressed="false" aria-label="បិទសំឡេង">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @endif
</div>
<script type="application/json" id="ik-config">{!! json_encode(['youtube' => $youtubeId, 'sfx' => $sounds], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
