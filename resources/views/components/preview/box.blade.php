{{-- A plain white box standing in for something the preview doesn't draw in detail: a map, a
     calendar, a schedule, scratch cards… so the layout still shows where it sits.
     - label: what it is (already translated)
     - key:   optional field: no field in this template → no box
     - tall:  true for a taller box (maps) --}}
@props(['label', 'key' => null, 'tall' => false])
@aware(['preview'])

@if ($key === null || $preview->has($key))
  <div @class(['pv-box', 'pv-box--tall' => $tall])>
    <span class="pv-box-label">{{ $label }}</span>
  </div>
@endif
