{{-- LIVE PREVIEW PAGE — ONE invitation layout shared by EVERY template (no per-template setup).

     It reads like an invitation, top to bottom: cover, invitation card, the couple, the note, the date,
     story, programme, gallery, location, notes for guests, RSVP, gift, contact, music, letters, the ending.

     Every piece draws itself only when the template uses that field and the plan includes it
     (App\Support\Invitations\LivePreviewData::has()), and a section with nothing in it isn't drawn.
     So a wedding shows the couple and the parents, a birthday shows the celebrant and the age, a
     ceremony shows the hosts — from the admin's field list alone. A new template needs nothing here.

     Built from the small components in resources/views/components/preview/. Section names are
     English, translated with lang/*.json. --}}
@php
  $timeBoxes = [__('Days'), __('Hours'), __('Minutes'), __('Seconds')];
@endphp

{{-- COVER — the first screen guests see --}}
<x-preview.section :label="__('Cover')">
  <x-preview.text key="couple_initials" size="xl" />
  <x-preview.text key="headline" size="sm" />
  <x-preview.photo key="cover_image" shape="arch" />
  <x-preview.couple-names style="stacked" />
  <x-preview.text key="celebrant_name" size="xl" />
  <x-preview.text key="turning_age" size="lg" />
  <x-preview.text key="event_date" size="sm" />
  <x-preview.guest />
</x-preview.section>

{{-- INVITATION CARD — parents, «you are invited», the guest, who invites, when and where --}}
<x-preview.section :label="__('Invitation card')">
  <x-preview.text key="opening_line" size="sm" />
  <x-preview.parents cols="2" />
  <x-preview.fixed>សូមគោរពអញ្ជើញ</x-preview.fixed>
  <x-preview.guest />
  <x-preview.text key="reception_message" size="sm" />
  <x-preview.couple-names style="columns" />
  <x-preview.text key="host_names" size="sm" />
  <x-preview.text key="sender_name" size="md" />
  <x-preview.text key="event_date" size="md" />
  <x-preview.text key="khmer_date" size="sm" />
  <x-preview.text key="end_time" size="sm" />
  <x-preview.text key="venue_name" size="sm" />
</x-preview.section>

{{-- THE COUPLE — each photo with its name under it --}}
<x-preview.section :label="__('The couple')" :show="$preview->hasAny(['groom_photo', 'bride_photo'])">
  <x-preview.columns cols="2">
    @foreach (['groom', 'bride'] as $person)
      <div class="pv-person">
        <x-preview.photo :key="$person.'_photo'" shape="oval" size="sm" />
        <x-preview.name :key="$person.'_name'" size="lg" />
      </div>
    @endforeach
  </x-preview.columns>
</x-preview.section>

{{-- A NOTE — the customer's message --}}
<x-preview.section :label="__('A note from you')">
  <x-preview.text key="message" size="md" />
  <x-preview.text key="love_reasons" size="sm" />
</x-preview.section>

{{-- SAVE THE DATE — date, «X days left», Khmer date, end time, the countdown (follows its switch) --}}
<x-preview.section :label="__('Save the date')" :show="$preview->has('event_date')">
  <x-preview.box :label="__('Calendar')" />
  <x-preview.text key="event_date" size="lg" />
  <x-preview.days-left />
  <x-preview.text key="khmer_date" size="sm" />
  <x-preview.text key="end_time" size="sm" />
  @if ($preview->has('countdown_enabled'))
    <x-preview.boxes :labels="$timeBoxes" :days="true" switch="countdown_enabled" />
  @endif
</x-preview.section>

{{-- TOGETHER SINCE — anniversaries / love letters --}}
<x-preview.section :label="__('Together since')" :show="$preview->has('together_since')">
  <x-preview.text key="together_since" size="lg" />
  <x-preview.boxes :labels="$timeBoxes" />
</x-preview.section>

{{-- OUR STORY --}}
<x-preview.section :label="__('Our story')" :show="$preview->has('story_chapters')">
  <x-preview.box :label="$preview->label('story_chapters')" :tall="true" />
</x-preview.section>

{{-- PROGRAMME — typed rows and / or the schedule picture --}}
<x-preview.section :label="__('Programme')" :show="$preview->hasAny(['event_schedule', 'schedule_image'])">
  <x-preview.box :label="$preview->label('event_schedule')" key="event_schedule" />
  <x-preview.photo key="schedule_image" shape="wide" />
</x-preview.section>

{{-- GALLERY --}}
<x-preview.section :label="__('Gallery')" :show="$preview->has('photo_gallery')">
  <x-preview.gallery cols="2" count="4" />
  <x-preview.text key="photo_captions" size="xs" />
</x-preview.section>

{{-- LOCATION — name, address, map --}}
<x-preview.section :label="__('Location')" :show="$preview->hasAny(['venue_name', 'venue_address', 'map_url'])">
  <x-preview.text key="venue_name" size="lg" />
  <x-preview.text key="venue_address" size="sm" />
  @if ($preview->hasAny(['venue_address', 'map_url']))
    <x-preview.box :label="__('Map')" :tall="true" />
  @endif
  <x-preview.text key="map_url" size="xs" />
</x-preview.section>

{{-- NOTES FOR GUESTS --}}
<x-preview.section :label="__('Notes for guests')" :show="$preview->hasAny(['dress_code', 'what_to_bring', 'rsvp_deadline'])">
  <x-preview.text key="dress_code" size="md" />
  <x-preview.columns cols="2">
    <x-preview.text key="what_to_bring" size="sm" />
    <x-preview.text key="rsvp_deadline" size="sm" />
  </x-preview.columns>
</x-preview.section>

{{-- RSVP — hidden while «Enable RSVP buttons» is off --}}
<x-preview.section :label="__('RSVP')" :show="$preview->has('rsvp_enabled')" switch="rsvp_enabled">
  <x-preview.text key="rsvp_message" size="md" />
  <x-preview.buttons :labels="[__('Attending'), __('Not attending')]" />
</x-preview.section>

{{-- GIFT — text, QR code, account --}}
<x-preview.section :label="__('Gift')">
  <x-preview.text key="gift_message" size="sm" />
  <x-preview.photo key="qr_code" shape="square" />
  <x-preview.text key="gift_account" size="sm" />
</x-preview.section>

{{-- CONTACT — phone numbers and the custom button --}}
<x-preview.section :label="__('Contact')" :show="$preview->hasAny(['groom_phone', 'bride_phone', 'contact_phone', 'cta_label'])">
  <div class="pv-cards pv-cards--side">
    @foreach (['groom_phone', 'bride_phone', 'contact_phone'] as $phone)
      @if ($preview->has($phone))
        <div class="pv-card"><x-preview.text :key="$phone" size="sm" /></div>
      @endif
    @endforeach
  </div>
  <x-preview.text key="cta_label" size="sm" />
</x-preview.section>

{{-- NOW PLAYING --}}
<x-preview.section :label="__('Now playing')" :show="$preview->has('song_title')">
  <div class="pv-card pv-card--row">
    <span class="pv-tile pv-tile--sm"></span>
    <x-preview.text key="song_title" size="md" />
  </div>
</x-preview.section>

{{-- LETTERS — thank-you and apology --}}
<x-preview.section :label="__('Letters')">
  @foreach (['thanks_message', 'apology_message'] as $letter)
    @if ($preview->has($letter))
      <div class="pv-card"><x-preview.text :key="$letter" size="sm" /></div>
    @endif
  @endforeach
</x-preview.section>

{{-- THE ENDING --}}
<x-preview.section :label="__('The ending')">
  <x-preview.photo key="background_image" shape="wide" size="lg" />
  <x-preview.text key="closing_line" size="xl" />
  <x-preview.couple-names style="stacked" size="lg" />
  <x-preview.text key="celebrant_name" size="lg" />
</x-preview.section>
