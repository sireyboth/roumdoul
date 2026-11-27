{{-- THE INVITATION CARD — parents, «you are invited», the guest, the couple, the date.
     Options:
       label     => section name (default 'Invitation card')
       headline  => show the headline at the top (default false)
       parents   => false | 2 | 3   columns for the parents' names (default 2)
       guest     => show where the guest's name goes (default true)
       reception => show the reception invitation text (default true)
       names     => false | stacked | columns | side   (default 'columns')
       message   => show the message inside the card (default false)
       date      => the date block: date, Khmer date, end time, venue (default true)
       hosts     => the ceremony hosts list (default false) --}}
@php
  $parents = $block['parents'] ?? 2;
  $names = $block['names'] ?? 'columns';
@endphp

<x-preview.section :label="__($block['label'] ?? 'Invitation card')">
  @if ($block['headline'] ?? false)
    <x-preview.text key="headline" size="sm" />
  @endif
  @if ($parents)
    <x-preview.parents :cols="$parents" />
  @endif
  @if ($block['guest'] ?? true)
    <x-preview.fixed>សូមគោរពអញ្ជើញ</x-preview.fixed>
    <x-preview.guest />
  @endif
  @if ($block['reception'] ?? true)
    <x-preview.text key="reception_message" size="sm" />
  @endif
  @if ($names)
    <x-preview.couple-names :style="$names" />
  @endif
  @if ($block['message'] ?? false)
    <x-preview.text key="message" size="sm" />
  @endif
  @if ($block['date'] ?? true)
    <x-preview.text key="event_date" size="md" />
    <x-preview.text key="khmer_date" size="sm" />
    <x-preview.text key="end_time" size="sm" />
    <x-preview.text key="venue_name" size="sm" />
  @endif
  @if ($block['hosts'] ?? false)
    <x-preview.text key="host_names" size="sm" />
  @endif
</x-preview.section>
