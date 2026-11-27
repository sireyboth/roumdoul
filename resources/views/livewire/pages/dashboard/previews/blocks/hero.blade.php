{{-- TOP OF THE INVITATION — the first part of the page once it is open.
     Options:
       photo      => false | circle | oval | arch | portrait | square | wide   (default 'arch')
       photo_key  => which photo field (default 'cover_image')
       headline   => show the headline (default false)
       opening    => show the opening line under it (ceremonies, default false)
       names      => false | stacked | columns | side   (default 'stacked')
       guest      => show where the guest's name goes (default false)
       date, khmer_date, venue => (default true / false / true) --}}
@php
  $photo = $block['photo'] ?? 'arch';
  $names = $block['names'] ?? 'stacked';
@endphp

<x-preview.section :label="__('Top of the invitation')">
  @if ($block['headline'] ?? false)
    <x-preview.text key="headline" size="lg" />
  @endif
  @if ($block['opening'] ?? false)
    <x-preview.text key="opening_line" size="sm" />
  @endif
  @if ($photo)
    <x-preview.photo :key="$block['photo_key'] ?? 'cover_image'" :shape="$photo" size="lg" />
  @endif
  @if ($names)
    <x-preview.couple-names :style="$names" size="2xl" />
  @endif
  @if ($block['guest'] ?? false)
    <x-preview.guest />
  @endif
  @if ($block['date'] ?? true)
    <x-preview.text key="event_date" size="md" />
  @endif
  @if ($block['khmer_date'] ?? false)
    <x-preview.text key="khmer_date" size="sm" />
  @endif
  @if ($block['venue'] ?? true)
    <x-preview.text key="venue_name" size="sm" />
  @endif
</x-preview.section>
