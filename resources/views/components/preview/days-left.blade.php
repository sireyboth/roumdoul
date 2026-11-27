{{-- «X days left» under the event date. The script works it out once from the date (no timer). --}}
@aware(['preview'])

@if ($preview->has('event_date'))
  <p class="pv-days" data-preview-days-left="event_date" hidden></p>
@endif
