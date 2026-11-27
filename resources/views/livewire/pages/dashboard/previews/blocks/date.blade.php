{{-- SAVE THE DATE — the event date, «X days left», and the countdown when it is switched on.
     Options:
       box       => the name of the picture the template draws (e.g. 'Calendar'), or false (default 'Calendar')
       countdown => true: the 4 countdown boxes, following the «Show countdown» switch (default true) --}}
@php $box = $block['box'] ?? 'Calendar'; @endphp

<x-preview.section :label="__('Save the date')" :show="$preview->has('event_date')">
  @if ($box)
    <x-preview.box :label="__($box)" />
  @endif
  <x-preview.text key="event_date" size="lg" />
  <x-preview.days-left />
  <x-preview.text key="khmer_date" size="sm" />
  <x-preview.text key="end_time" size="sm" />
  @if (($block['countdown'] ?? true) && $preview->has('countdown_enabled'))
    <x-preview.boxes :labels="[__('Days'), __('Hours'), __('Minutes'), __('Seconds')]" :days="true" switch="countdown_enabled" />
  @endif
</x-preview.section>
