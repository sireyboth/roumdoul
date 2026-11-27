{{-- COVER — the first screen guests see, before they open the invitation.
     Options ($block, from config/live-preview.php):
       photo       => false | circle | oval | arch | portrait | square | wide   (default false: no photo)
       photo_key   => which photo field (default 'cover_image')
       initials    => true: the monogram at the top · 'bottom': at the bottom (default false)
       headline    => show the headline (default true)
       names       => false | stacked | columns | side   (default 'stacked')
       date, venue => show the event date / venue name (default true / false)
       guest       => show where the guest's name goes (default true) --}}
@php
  $photo = $block['photo'] ?? false;
  $initials = $block['initials'] ?? false;
  $names = $block['names'] ?? 'stacked';
@endphp

<x-preview.section :label="__('Cover')">
  @if ($initials === true)
    <x-preview.text key="couple_initials" size="xl" />
  @endif
  @if ($block['headline'] ?? true)
    <x-preview.text key="headline" size="sm" />
  @endif
  @if ($photo)
    <x-preview.photo :key="$block['photo_key'] ?? 'cover_image'" :shape="$photo" />
  @endif
  @if ($names)
    <x-preview.couple-names :style="$names" />
  @endif
  @if ($block['date'] ?? true)
    <x-preview.text key="event_date" size="sm" />
  @endif
  @if ($block['venue'] ?? false)
    <x-preview.text key="venue_name" size="sm" />
  @endif
  @if ($block['guest'] ?? true)
    <x-preview.guest />
  @endif
  @if ($initials === 'bottom')
    <x-preview.text key="couple_initials" size="md" />
  @endif
</x-preview.section>
