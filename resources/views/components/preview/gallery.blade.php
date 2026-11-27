{{-- The photo gallery's place and arrangement, drawn as empty photo tiles.
     - cols:   1 | 2 | 3   tiles per row (like the real template)
     - count:  how many tiles to draw
     - strip:  true for a row that scrolls sideways (film strips, polaroids)
     - key:    the field it stands for (default photo_gallery) --}}
@props(['cols' => 2, 'count' => 4, 'strip' => false, 'key' => 'photo_gallery'])
@aware(['preview'])

@if ($preview->has($key))
  <div @class(['pv-gallery', 'pv-gallery--'.$cols => ! $strip, 'pv-gallery--strip' => $strip])>
    @for ($tile = 1; $tile <= $count; $tile++)
      <span class="pv-tile">@if ($tile === 1){{ $preview->label($key) }}@endif</span>
    @endfor
  </div>
@endif
