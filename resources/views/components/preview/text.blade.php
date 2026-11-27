{{-- One field's text — the slot the script fills while the customer types.
     - key:  the field, e.g. 'groom_name'. Its form input has name="groom_name": that shared name
             is the whole mapping (resources/js/modules/livePreview.js).
     - size: xs | sm | md | lg | xl | 2xl  (big names, small details)
     Empty → the field's label in grey. Not used by this template → nothing is drawn.
     Latin-letter names also follow the «Latin names» switch. --}}
@props(['key', 'size' => 'md'])
@aware(['preview'])

@if ($preview->has($key))
  @php
    $value = $preview->value($key);
    $label = $preview->label($key);
    $isLatin = $preview->isLatinName($key);
    $switch = \App\Models\InvitationTemplate::LATIN_NAMES_SWITCH;
  @endphp
  <p @class(['pv-text', 'pv-text--'.$size, 'pv-text--latin' => $isLatin, 'is-empty' => $value === ''])
    data-preview-slot="{{ $key }}" data-preview-kind="{{ $preview->kind($key) }}"
    data-preview-label="{{ $label }}" data-preview-value="{{ $value }}"
    @if ($isLatin) data-preview-switch="{{ $switch }}" @if (! $preview->switchOn($switch)) hidden @endif @endif>{{ $value !== '' ? $value : $label }}</p>
@endif
