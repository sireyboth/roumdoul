{{-- One photo field. Empty: a pink dashed shape with the field's label. When a photo is chosen in the
     form, the script shows it here (URL.createObjectURL) — nothing is uploaded by the preview.
     - key:   the photo field, e.g. 'cover_image'
     - shape: circle | oval | arch | portrait | square | wide   (like the real template's frame)
     - size:  sm | md | lg   (wide is always full width) --}}
@props(['key', 'shape' => 'circle', 'size' => 'md'])
@aware(['preview'])

@if ($preview->has($key))
  @php $url = $preview->photoUrl($key); @endphp
  <div class="pv-photo pv-photo--{{ $shape }} pv-photo--{{ $size }}"
    data-preview-slot="{{ $key }}" data-preview-kind="photo" data-preview-label="{{ $preview->label($key) }}">
    <img src="{{ $url }}" alt="" @if (! $url) hidden @endif />
    <span class="pv-photo-label" @if ($url) hidden @endif>{{ $preview->label($key) }}</span>
  </div>
@endif
