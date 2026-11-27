{{-- COUNTDOWN on its own (templates where it is a separate section). Hidden while «Show countdown»
     is off; not drawn at all when the template has no countdown. --}}
<x-preview.section :label="__('Countdown')" :show="$preview->has('countdown_enabled') && $preview->has('event_date')" switch="countdown_enabled">
  <x-preview.boxes :labels="[__('Days'), __('Hours'), __('Minutes'), __('Seconds')]" :days="true" />
  <x-preview.days-left />
</x-preview.section>
