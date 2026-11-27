{{-- FIELDS — a section built from a plain list, for anything the other blocks don't cover
     (birthday tickets, love letters, the first screen of smaller templates…).
     Options:
       label => section name (English, translated here), or null for none
       items => [field key => what to draw], top to bottom:
                  xs | sm | md | lg | xl | 2xl                       → the field's text, in that size
                  circle | oval | arch | portrait | square | wide    → the photo field, in that shape
                  '@guest' as the key                                 → where the guest's name goes
                  '@days' as the key                                  → «X days left»
       side  => true: the items side by side in columns instead of one under the other --}}
@php
  $photoShapes = ['circle', 'oval', 'arch', 'portrait', 'square', 'wide'];
  $label = isset($block['label']) ? __($block['label']) : null;
@endphp

<x-preview.section :label="$label">
  {{-- one under the other (pv-stack), or side by side in columns (pv-cols) --}}
  <div @class([
    'pv-cols pv-cols--'.count($block['items']) => $block['side'] ?? false,
    'pv-stack' => ! ($block['side'] ?? false),
  ])>
    @foreach ($block['items'] as $key => $draw)
      @if ($key === '@guest')
        <x-preview.guest />
      @elseif ($key === '@days')
        <x-preview.days-left />
      @elseif (in_array($draw, $photoShapes, true))
        <x-preview.photo :key="$key" :shape="$draw" />
      @else
        <x-preview.text :key="$key" :size="$draw" />
      @endif
    @endforeach
  </div>
</x-preview.section>
