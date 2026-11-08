{{-- Editorial section marker: "02 — Introduction" with a hairline. --}}
@props(['number' => null, 'as' => 'p'])
<{{ $as }} {{ $attributes->merge(['class' => 'pf-label']) }}>
  @if ($number !== null)
    <span class="pf-label__num">{{ $pf->num($number) }}</span>
  @endif
  <span class="pf-label__text">{{ $slot }}</span>
</{{ $as }}>
