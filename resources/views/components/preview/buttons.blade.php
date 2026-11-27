{{-- The template's own buttons (RSVP answers, Yes / No…) — part of the design, not fields.
     - labels:  the button texts (already translated)
     - stacked: true when the real template puts them one under the other --}}
@props(['labels' => [], 'stacked' => false])

<div @class(['pv-buttons', 'pv-buttons--stacked' => $stacked])>
  @foreach ($labels as $label)
    <span class="pv-button">{{ $label }}</span>
  @endforeach
</div>
