{{-- Puts its children side by side, like the real template does (2 or 3 columns).
     On the phone screen the columns stay side by side, as they do on the guest's phone. --}}
@props(['cols' => 2])

<div class="pv-cols pv-cols--{{ $cols }}">
  {{ $slot }}
</div>
