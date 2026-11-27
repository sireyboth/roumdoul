{{-- PROGRAMME — the event schedule (typed rows, or the schedule picture instead). --}}
<x-preview.section :label="__('Programme')" :show="$preview->hasAny(['event_schedule', 'schedule_image'])">
  <x-preview.box :label="$preview->label('event_schedule')" key="event_schedule" />
  <x-preview.photo key="schedule_image" shape="wide" />
</x-preview.section>
