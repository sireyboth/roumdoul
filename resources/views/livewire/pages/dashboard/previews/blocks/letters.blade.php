{{-- LETTERS — the thank-you message and the apology letter, each as a card.
     Options:
       side => true: the two cards side by side (default false: one under the other) --}}
@php
  $letters = ['thanks_message', 'apology_message'];
@endphp

<x-preview.section :label="__('Letters')" :show="$preview->hasAny($letters)">
  <div @class(['pv-cards', 'pv-cards--side' => $block['side'] ?? false])>
    @foreach ($letters as $letter)
      @if ($preview->has($letter))
        <div class="pv-card"><x-preview.text :key="$letter" size="sm" /></div>
      @endif
    @endforeach
  </div>
</x-preview.section>
