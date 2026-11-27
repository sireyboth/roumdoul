{{-- NOTES FOR GUESTS — the dress code, then «what to bring» and «reply by».
     Options:
       side => true: «what to bring» and «reply by» side by side (default true), false: one under the other --}}
<x-preview.section :label="__('Notes for guests')" :show="$preview->hasAny(['dress_code', 'what_to_bring', 'rsvp_deadline'])">
  <x-preview.text key="dress_code" size="md" />
  @if ($block['side'] ?? true)
    <x-preview.columns cols="2">
      <x-preview.text key="what_to_bring" size="sm" />
      <x-preview.text key="rsvp_deadline" size="sm" />
    </x-preview.columns>
  @else
    <x-preview.text key="what_to_bring" size="sm" />
    <x-preview.text key="rsvp_deadline" size="sm" />
  @endif
</x-preview.section>
