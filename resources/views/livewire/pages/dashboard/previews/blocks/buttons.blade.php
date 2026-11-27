{{-- The template's own buttons (e.g. Yes / No), side by side.
     Options:
       labels => the button texts (English, translated here) --}}
@php
  $labels = [];
  foreach ($block['labels'] as $text) {
      $labels[] = __($text);
  }
@endphp

<x-preview.section>
  <x-preview.buttons :labels="$labels" />
</x-preview.section>
