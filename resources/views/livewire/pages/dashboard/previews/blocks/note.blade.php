{{-- A NOTE — the customer's message, as a quote.
     Options:
       label     => section name (default 'A note from you')
       signature => true: the sender's name under it (default false)
       cta       => true: the custom button under it (default false) --}}
<x-preview.section :label="__($block['label'] ?? 'A note from you')" :show="$preview->has('message')">
  <x-preview.text key="message" size="md" />
  @if ($block['signature'] ?? false)
    <x-preview.text key="sender_name" size="sm" />
  @endif
  @if ($block['cta'] ?? false)
    <x-preview.text key="cta_label" size="sm" />
  @endif
</x-preview.section>
