{{--
  A hand-drawn "memory map": a paper map with heart pins joined by a dotted route. Tapping a pin opens a little
  polaroid with that place's photo, name, date and note. Taken from Rose Bloom's memory map and made shared.
  Behaviour: public/js/invitations/shared/memory-map.js, styles: public/css/invitations/shared/memory-map.css
  (both loaded once by this component).

    <x-memory-map :places="$places" />
    <x-memory-map :places="$places" river label="our river" photo-class="rl-ph-btn" class="my-map" />

  places      list of ['name' => …, 'date' => …, 'note' => …, 'photo' => url|null] (max 6 are shown).
              Optional 'x' / 'y' (0–100, % of the map) place a pin yourself; otherwise pins follow a fixed winding route.
              Usually built from story_chapters: title «Place | when» → name + date, text → note.
  river       draw a soft winding river across the paper (default false)
  label       small script line written on the paper, bottom-left ('' = none), default 'here be us'
  seed        changes the hills/contours drawn on the paper, default 7
  photoClass  extra class on the polaroid's photo button, so a template's own photo preview can open it
              (the button carries data-full + data-cap like the templates' lightbox buttons expect)
  closeLabel  aria-label of the close button, default 'Close'
  sound       pin pop: true = shared sfx (images/invitations/assets/sfx/pop.mp3), a URL = your own, false = silent
  Anything else (class, style, data-*) goes on the map.

  Theme with CSS variables on the map or any parent:
    --inv-map-paper   paper colour            --inv-map-ink     contour lines, compass, label
    --inv-map-land    soft green patches      --inv-map-water   the river
    --inv-map-pin     pin + route colour      --inv-map-ratio   map aspect ratio (default 4/3; 1/1.12 on phones)
    --inv-map-card    polaroid background     --inv-map-card-ink  polaroid text
    --inv-map-font    place name font         --inv-map-note-font  note font
    --inv-map-radius  corner radius
  JS: a pin fires a bubbling `inv-map:open` event (detail: { index, place }); window.InvMap has init(root) and
  setMuted(bool) (hook it to a template's own mute button).
  No JavaScript → the paper stays plain and every memory is listed under the map instead.
--}}
@props([
    'places' => [],
    'river' => false,
    'label' => 'here be us',
    'seed' => 7,
    'photoClass' => '',
    'closeLabel' => 'Close',
    'sound' => true,
])
@php
    $shared = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $soundUrl = $sound === true ? $shared('images/invitations/assets/sfx/pop.mp3') : (is_string($sound) && $sound !== '' ? $sound : null);
    // a fixed winding route; a custom x/y wins
    $spots = [[22, 60], [43, 30], [68, 64], [82, 26], [58, 84], [30, 22]];
    $pins = collect($places)->filter(fn ($p) => is_array($p) && trim(($p['name'] ?? '').($p['note'] ?? '')) !== '')
        ->take(count($spots))->values()
        ->map(fn ($p, $i) => [
            'name' => trim((string) ($p['name'] ?? '')) ?: 'A memory',
            'date' => trim((string) ($p['date'] ?? '')),
            'note' => trim((string) ($p['note'] ?? '')),
            'photo' => filled($p['photo'] ?? null) ? $p['photo'] : null,
            'x' => is_numeric($p['x'] ?? null) ? max(6, min(94, (float) $p['x'])) : $spots[$i][0],
            'y' => is_numeric($p['y'] ?? null) ? max(8, min(92, (float) $p['y'])) : $spots[$i][1],
        ])->all();
    $route = '';
    foreach ($pins as $i => $p) {
        $route .= $i === 0 ? "M{$p['x']} {$p['y']}"
            : ' Q'.(($pins[$i - 1]['x'] + $p['x']) / 2).' '.(min($pins[$i - 1]['y'], $p['y']) - 14)." {$p['x']} {$p['y']}";
    }
@endphp

@once
  <link rel="stylesheet" href="{{ $shared('css/invitations/shared/memory-map.css') }}">
  <script src="{{ $shared('js/invitations/shared/memory-map.js') }}" defer></script>
@endonce

@if ($pins)
  <div {{ $attributes->class(['inv-map']) }} data-inv-map data-river="{{ $river ? '1' : '0' }}" data-seed="{{ (int) $seed }}" data-label="{{ $label }}"
    @if ($soundUrl) data-sound="{{ $soundUrl }}" @endif>
    <div class="inv-map__paper">
      <canvas class="inv-map__canvas" aria-hidden="true"></canvas>
      <svg class="inv-map__route" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $route }}"/></svg>

      @foreach ($pins as $i => $p)
        <button type="button" class="inv-map__pin" style="left: {{ $p['x'] }}%; top: {{ $p['y'] }}%; --i: {{ $i }};"
          data-i="{{ $i }}" data-name="{{ $p['name'] }}" data-date="{{ $p['date'] }}" data-note="{{ $p['note'] }}"
          @if ($p['photo']) data-photo="{{ $p['photo'] }}" @endif aria-label="{{ $p['name'] }}" aria-haspopup="dialog">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/></svg>
        </button>
        {{-- names near an edge grow inwards, so they never run off the paper --}}
        <span @class(['inv-map__label', 'inv-map__label--l' => $p['x'] < 28, 'inv-map__label--r' => $p['x'] > 72]) style="left: {{ $p['x'] }}%; top: {{ $p['y'] }}%; --i: {{ $i }};" aria-hidden="true">{{ $p['name'] }}</span>
      @endforeach

      <div class="inv-map__card" role="dialog" aria-modal="false" aria-live="polite" tabindex="-1">
        <button type="button" class="inv-map__photo {{ $photoClass }}" data-full="" data-cap="">
          <img alt="" decoding="async" draggable="false">
        </button>
        <p class="inv-map__name"></p>
        <p class="inv-map__date"></p>
        <p class="inv-map__note"></p>
        <button type="button" class="inv-map__x" aria-label="{{ $closeLabel }}">&#10005;</button>
      </div>
    </div>

    {{-- without JavaScript (and for screen readers' "list" view) every memory is still readable --}}
    <noscript>
      <ol class="inv-map__list">
        @foreach ($pins as $p)
          <li><b>{{ $p['name'] }}</b>@if ($p['date'] !== '') · {{ $p['date'] }}@endif @if ($p['note'] !== '')<br>{{ $p['note'] }}@endif</li>
        @endforeach
      </ol>
    </noscript>
  </div>
@endif
