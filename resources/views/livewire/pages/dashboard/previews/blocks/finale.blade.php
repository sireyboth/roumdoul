{{-- THE ENDING — the last screen.
     Options:
       label     => section name (default 'The ending')
       photo     => false | circle | oval | arch | portrait | square | wide   (default false)
       photo_key => which photo field (default 'background_image')
       closing   => the closing line (default true)
       thanks    => the thank-you message (default false)
       names     => the couple's names (default true)
       date      => the event date (default false) --}}
@php $photo = $block['photo'] ?? false; @endphp

<x-preview.section :label="__($block['label'] ?? 'The ending')">
  @if ($photo)
    <x-preview.photo :key="$block['photo_key'] ?? 'background_image'" :shape="$photo" size="lg" />
  @endif
  @if ($block['closing'] ?? true)
    <x-preview.text key="closing_line" size="xl" />
  @endif
  @if ($block['thanks'] ?? false)
    <x-preview.text key="thanks_message" size="sm" />
  @endif
  @if ($block['names'] ?? true)
    <x-preview.couple-names style="stacked" size="lg" />
  @endif
  @if ($block['date'] ?? false)
    <x-preview.text key="event_date" size="sm" />
  @endif
</x-preview.section>
