{{-- LOCATION — the venue's name and address, the map, and the map link.
     Options:
       map        => true: the template shows a map box (default true)
       dress_code => true: the template puts the dress code here (default false) --}}
<x-preview.section :label="__('Location')" :show="$preview->hasAny(['venue_name', 'venue_address', 'map_url'])">
  <x-preview.text key="venue_name" size="lg" />
  <x-preview.text key="venue_address" size="sm" />
  @if ($block['map'] ?? true)
    <x-preview.box :label="__('Map')" :tall="true" />
  @endif
  <x-preview.text key="map_url" size="xs" />
  @if ($block['dress_code'] ?? false)
    <x-preview.text key="dress_code" size="sm" />
  @endif
</x-preview.section>
