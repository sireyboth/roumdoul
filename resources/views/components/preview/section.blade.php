{{-- One section of the preview: a small pink label, then its content, then a divider.
     - label:  the section's name (already translated), or null for none
     - show:   false to skip the section (e.g. the template doesn't use its fields)
     - switch: a field key like 'rsvp_enabled' — the section hides while that switch is off
     A section with nothing inside (all its fields unused) is not drawn at all. --}}
@props(['label' => null, 'show' => true, 'switch' => null])
@aware(['preview'])

@php
  $content = trim((string) $slot);
  // Livewire leaves little HTML comments (<!--[if BLOCK]>…) around @if blocks: ignore them when
  // checking whether anything was drawn inside.
  $visibleContent = trim(preg_replace('/<!--.*?-->/s', '', $content));
@endphp

@if ($show && $visibleContent !== '')
  <section class="pv-section"
    @if ($switch) data-preview-switch="{{ $switch }}" @if (! $preview->switchOn($switch)) hidden @endif @endif>
    @if ($label)
      <p class="pv-label">{{ $label }}</p>
    @endif
    {!! $content !!}
  </section>
@endif
