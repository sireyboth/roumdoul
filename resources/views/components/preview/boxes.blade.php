{{-- A row of small boxes: the countdown (days / hours / minutes / seconds) or a count-up.
     The first box shows the number of days left, worked out once by the script — it doesn't tick.
     - labels:    the box names (already translated)
     - days:      true to fill the first box with the days left until the event date
     - switch:    a field key like 'countdown_enabled' — hidden while that switch is off --}}
@props(['labels' => [], 'days' => false, 'switch' => null])
@aware(['preview'])

<div class="pv-boxes"
  @if ($switch) data-preview-switch="{{ $switch }}" @if (! $preview->switchOn($switch)) hidden @endif @endif>
  @foreach ($labels as $index => $label)
    <div class="pv-boxes-item">
      <span class="pv-boxes-value" @if ($days && $index === 0) data-preview-days-count="event_date" @endif>–</span>
      <span class="pv-boxes-label">{{ $label }}</span>
    </div>
  @endforeach
</div>
