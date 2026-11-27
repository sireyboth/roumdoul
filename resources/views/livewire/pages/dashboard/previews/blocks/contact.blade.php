{{-- CONTACT — the phone numbers guests can call, and the custom button.
     Options:
       side => true: the cards side by side (default false: one under the other)
       cta  => true: the custom button under the cards (default true) --}}
@php
  $phones = ['groom_phone', 'bride_phone', 'contact_phone'];
@endphp

<x-preview.section :label="__('Contact')" :show="$preview->hasAny([...$phones, 'cta_label'])">
  <div @class(['pv-cards', 'pv-cards--side' => $block['side'] ?? false])>
    @foreach ($phones as $phone)
      @if ($preview->has($phone))
        <div class="pv-card"><x-preview.text :key="$phone" size="sm" /></div>
      @endif
    @endforeach
  </div>
  @if ($block['cta'] ?? true)
    <x-preview.text key="cta_label" size="sm" />
  @endif
</x-preview.section>
