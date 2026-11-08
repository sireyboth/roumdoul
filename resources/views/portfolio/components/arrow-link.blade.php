@props(['href', 'external' => false])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'pf-arrow-link']) }} @if ($external) target="_blank" rel="noopener" @endif>
  <span class="pf-arrow-link__text">{{ $slot }}</span>
  <svg class="pf-arrow-link__icon" width="18" height="10" viewBox="0 0 18 10" aria-hidden="true" focusable="false">
    @if ($external)
      <path d="M5 9 13 1M6 1h7v7" fill="none" stroke="currentColor" stroke-width="1.2"/>
    @else
      <path d="M0 5h16M12 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.2"/>
    @endif
  </svg>
</a>
