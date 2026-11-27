{{-- RSVP — the question and the answer buttons. Hidden while «Enable RSVP buttons» is off.
     Options:
       buttons => how many answer buttons: 2 (yes / no) or 3 (yes / maybe / no) (default 2)
       stacked => true: the buttons one under the other (default false)
       always  => true: the template always shows its RSVP, whatever the switch (default false) --}}
@php
  $always = $block['always'] ?? false;
  $labels = ($block['buttons'] ?? 2) === 3
    ? [__('Attending'), __('Maybe'), __('Not attending')]
    : [__('Attending'), __('Not attending')];
@endphp

<x-preview.section :label="__('RSVP')" :show="$always || $preview->has('rsvp_enabled')" :switch="$always ? null : 'rsvp_enabled'">
  <x-preview.text key="rsvp_message" size="md" />
  <x-preview.buttons :labels="$labels" :stacked="$block['stacked'] ?? false" />
</x-preview.section>
