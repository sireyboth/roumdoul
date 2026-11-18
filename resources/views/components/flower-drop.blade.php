{{--
  Falling flowers: petals (and a few leaves) drifting down over the whole page in 3D, forever. Each petal spins
  on all three axes and catches the light; near ones are bigger, faster and soft-focus. Scrolling gives them a
  little wind and a finger brushes them aside. Taken from River Love's petal fall and made shared.
  Behaviour: public/js/invitations/shared/flower-drop.js, styles: public/css/invitations/shared/flower-drop.css
  (both loaded once by this component). Off (nothing drawn) with prefers-reduced-motion.

    <x-flower-drop :color="$accent" />
    <x-flower-drop :color="$accent" shape="rose" :leaves="false" density=".6" />

  color     petal colour (hex #rrggbb); three tones are made from it, paler towards the rim. Default blush pink.
  shape     'blossom' (cherry petal, notch at the tip) or 'rose' (round, cupped rose petal). Default blossom.
  leaves    mix in a few green leaves (default true). leafColor = their base green.
  density   1 = normal, .5 = half as many, 1.5 = more (default 1)
  tap       a tap/click anywhere drops a handful of petals where the finger was (default true)
  bursts    a revealed <x-scratch-card> bursts into petals and a pin opened on <x-memory-map> lets a few fall
            from it (default true)
  Anything else (class, style, data-*) goes on the canvas.

  Theme / layout with CSS on the canvas or any parent:
    --inv-flowers-z   stacking order (default 30: above the page, under the template's dialogs)
    Hide it until something happens (an envelope opens…) with your own CSS, e.g.
    `.my-tpl:not(.is-open) .inv-flowers{ opacity:0 }` — it fades in.
  JS: window.InvFlowers.drop(x, y, scale = 1) drops a handful at a screen point (clientX/Y);
      InvFlowers.pause() / resume() stop and restart the fall. No sound of its own: the taps, scratches and
      pins that set it off already play theirs.
--}}
@props([
    'color' => '#e8789a',
    'shape' => 'blossom',
    'leaves' => true,
    'leafColor' => '#8cc068',
    'density' => 1,
    'tap' => true,
    'bursts' => true,
])
@php
    $shared = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $hex = fn ($c, $d) => is_string($c) && preg_match('/^#[0-9a-f]{6}$/i', $c) ? $c : $d;
@endphp

@once
  <link rel="stylesheet" href="{{ $shared('css/invitations/shared/flower-drop.css') }}">
  <script src="{{ $shared('js/invitations/shared/flower-drop.js') }}" defer></script>
@endonce

<canvas {{ $attributes->class(['inv-flowers']) }} data-inv-flowers aria-hidden="true"
  data-color="{{ $hex($color, '#e8789a') }}" data-shape="{{ $shape === 'rose' ? 'rose' : 'blossom' }}"
  data-leaves="{{ $leaves ? $hex($leafColor, '#8cc068') : '' }}" data-density="{{ max(.2, min(2, (float) $density)) }}"
  data-tap="{{ $tap ? '1' : '0' }}" data-bursts="{{ $bursts ? '1' : '0' }}"></canvas>
