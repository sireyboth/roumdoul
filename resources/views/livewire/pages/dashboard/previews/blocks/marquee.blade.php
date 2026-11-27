{{-- MOVING BANNER — a strip that scrolls the couple's names and the date (drawn still here). --}}
<x-preview.section :show="$preview->hasAny(['groom_name', 'bride_name'])">
  <div class="pv-strip">
    <x-preview.text key="groom_name" size="xs" />
    <x-preview.fixed>&amp;</x-preview.fixed>
    <x-preview.text key="bride_name" size="xs" />
    <x-preview.text key="event_date" size="xs" />
  </div>
</x-preview.section>
