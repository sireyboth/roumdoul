{{-- GIFT — the text above the QR, the QR code, and the account details under it.
     Options:
       label => section name (default 'Gift') --}}
<x-preview.section :label="__($block['label'] ?? 'Gift')" :show="$preview->hasAny(['qr_code', 'gift_account', 'gift_message'])">
  <x-preview.text key="gift_message" size="sm" />
  <x-preview.photo key="qr_code" shape="square" />
  <x-preview.text key="gift_account" size="sm" />
</x-preview.section>
