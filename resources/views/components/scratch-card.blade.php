{{--
  A scratch-off card: a foil cover the guest rubs away with a finger (or mouse) to reveal a photo or any content.
  Taken from Rose Bloom's scratch stamps and made shared. Behaviour: public/js/invitations/shared/scratch-card.js,
  styles: public/css/invitations/shared/scratch-card.css (both loaded once by this component).

    <x-scratch-card :src="$photo" caption="that night we laughed" class="my-card" />
    <x-scratch-card stamp :src="$photo" caption="…" />             (perforated postage-stamp frame)
    <x-scratch-card cover="open me" hint="scratch here"><p>Will you marry me?</p></x-scratch-card>

  src        photo under the foil (fills the card, object-fit:cover). Without it, the slot is shown instead.
  caption    shown under the card once it's revealed; hint is shown there until then (empty = no caption row)
  cover      the script text written on the foil ('' = none), with a small heart under it (heart=false to drop it)
  ratio      card face aspect ratio, default 4/5
  threshold  share of the foil that must be scratched before the rest melts away, default .45
  sound      true = shared scratch loop (images/invitations/assets/sfx/scratch-loop.mp3), a URL = your own, false = silent
  revealSound true = a light chime when it opens, a URL = your own, false = none
  stamp      perforated postage-stamp frame (the Rose Bloom look)
  Anything else (class, style, data-*) goes on the card. Inside the slot, put interactive bits (a lightbox button)
  — they only become clickable after the reveal.

  Theme with CSS variables on the card or any parent:
    --inv-scratch-foil   comma list of colours for the foil gradient (default pearl-blush)
    --inv-scratch-ink    cover text colour        --inv-scratch-accent  the heart's colour
    --inv-scratch-font   cover text font family   --inv-scratch-radius  face corner radius
    --inv-scratch-frame  card background (stamp)  --inv-scratch-cap-font / --inv-scratch-cap-color / --inv-scratch-hint-color
  JS: the card fires a bubbling `inv-scratch:reveal` event; window.InvScratch has init(root), reveal(card), setMuted(bool).
  No JavaScript → no foil is painted and the content and caption simply show.
--}}
@props([
    'src' => null,
    'alt' => '',
    'caption' => null,
    'hint' => 'scratch to see',
    'cover' => 'scratch me',
    'heart' => true,
    'ratio' => '4/5',
    'threshold' => .45,
    'sound' => true,
    'revealSound' => true,
    'stamp' => false,
    'label' => 'Scratch to reveal',
])
@php
    $sfx = fn (string $file) => asset("images/invitations/assets/sfx/{$file}").'?v='.@filemtime(public_path("images/invitations/assets/sfx/{$file}"));
    $soundUrl = $sound === true ? $sfx('scratch-loop.mp3') : (is_string($sound) && $sound !== '' ? $sound : null);
    $revealUrl = $revealSound === true ? $sfx('chime.mp3') : (is_string($revealSound) && $revealSound !== '' ? $revealSound : null);
    $shared = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $hasCaption = filled($caption) || filled($hint);
@endphp

@once
  <link rel="stylesheet" href="{{ $shared('css/invitations/shared/scratch-card.css') }}">
  <script src="{{ $shared('js/invitations/shared/scratch-card.js') }}" defer></script>
@endonce

<div {{ $attributes->class(['inv-scratch', 'inv-scratch--stamp' => $stamp])->merge(['style' => "--inv-scratch-ratio: {$ratio};"]) }}
  data-inv-scratch tabindex="0" role="button" aria-label="{{ $label }}"
  data-cover="{{ $cover }}" data-heart="{{ $heart ? '1' : '0' }}" data-threshold="{{ $threshold }}"
  @if ($soundUrl) data-sound="{{ $soundUrl }}" @endif
  @if ($revealUrl) data-reveal-sound="{{ $revealUrl }}" @endif>
  <div class="inv-scratch__face">
    @if ($src)
      <img class="inv-scratch__img" src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" draggable="false">
    @endif
    {{ $slot }}
    <canvas class="inv-scratch__foil" aria-hidden="true"></canvas>
  </div>
  @if ($hasCaption)
    <p class="inv-scratch__cap">
      @if (filled($hint))<span class="inv-scratch__hint" aria-hidden="true">{{ $hint }}</span>@endif
      <span class="inv-scratch__txt">{{ $caption }}</span>
    </p>
  @endif
</div>
