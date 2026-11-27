{{-- The groom's and bride's names, arranged like the real template:
     - stacked: groom, "&", bride — one under the other
     - columns: 3 columns side by side — small label over each name, "&" in the middle
     - side:    2 columns side by side, no labels
     - size:    size of the names (see <x-preview.text>)
     - bride_first: true when the template puts the bride first --}}
@props(['style' => 'stacked', 'size' => 'xl', 'brideFirst' => false])
@aware(['preview'])

@php
  $first = $brideFirst ? 'bride_name' : 'groom_name';
  $second = $brideFirst ? 'groom_name' : 'bride_name';
  $firstLabel = $brideFirst ? __('Bride') : __('Groom');
  $secondLabel = $brideFirst ? __('Groom') : __('Bride');
@endphp

@if ($preview->hasAny(['groom_name', 'bride_name']))
  @if ($style === 'columns')
    <x-preview.columns cols="3">
      <div><x-preview.fixed>{{ $firstLabel }}</x-preview.fixed><x-preview.name :key="$first" :size="$size" /></div>
      <x-preview.fixed>&amp;</x-preview.fixed>
      <div><x-preview.fixed>{{ $secondLabel }}</x-preview.fixed><x-preview.name :key="$second" :size="$size" /></div>
    </x-preview.columns>
  @elseif ($style === 'side')
    <x-preview.columns cols="2">
      <x-preview.name :key="$first" :size="$size" />
      <x-preview.name :key="$second" :size="$size" />
    </x-preview.columns>
  @else
    <x-preview.name :key="$first" :size="$size" />
    <x-preview.fixed>&amp;</x-preview.fixed>
    <x-preview.name :key="$second" :size="$size" />
  @endif
@endif
