{{-- CHAT — the invitation written as chat bubbles (Love Bubble): the parents, the reception text, the
     couple, the date and place, then the message — each in its own bubble. --}}
<x-preview.section :label="__('Chat')">
  <div class="pv-bubble"><x-preview.guest /></div>
  @if ($preview->hasAny(['groom_father', 'groom_mother', 'bride_father', 'bride_mother']))
    <div class="pv-bubble"><x-preview.parents cols="2" /></div>
  @endif
  @if ($preview->has('reception_message'))
    <div class="pv-bubble"><x-preview.text key="reception_message" size="sm" /></div>
  @endif
  <div class="pv-bubble"><x-preview.couple-names style="columns" size="lg" /></div>
  <div class="pv-bubble">
    <x-preview.text key="event_date" size="sm" />
    <x-preview.text key="khmer_date" size="xs" />
    <x-preview.text key="venue_name" size="xs" />
  </div>
  @if ($preview->has('message'))
    <div class="pv-bubble"><x-preview.text key="message" size="sm" /></div>
  @endif
</x-preview.section>
