{{-- Text wordmark: monogram + name set in the display serif, with an accent full stop. --}}
@props(['href' => null, 'name'])
<{{ $href ? 'a' : 'span' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'pf-wordmark']) }}>
  <x-pf::monogram :size="26" />
  <span class="pf-wordmark__name">{{ $name }}<span class="pf-accent">.</span></span>
</{{ $href ? 'a' : 'span' }}>
