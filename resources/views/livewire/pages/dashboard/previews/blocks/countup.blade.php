{{-- TOGETHER SINCE — the day the relationship began and a count-up (4 boxes). --}}
<x-preview.section :label="__('Together since')" :show="$preview->has('together_since')">
  <x-preview.text key="together_since" size="lg" />
  <x-preview.boxes :labels="[__('Days'), __('Hours'), __('Minutes'), __('Seconds')]" />
</x-preview.section>
