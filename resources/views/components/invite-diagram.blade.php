{{--
  A tiny phone mock of an invitation with the part a field fills highlighted, so a customer can
  see *where* a field shows up instead of reading about it. Positions are a generic layout, not
  any one template's real design. Usage: <x-invite-diagram field="cover_image" class="w-32" />
--}}
@props(['field' => null])

@php
  // which mock parts each FIELD_CATALOG key fills; keys not listed just show the plain mock
  $map = [
    'cover_image' => ['hero'],
    'music_url' => ['music'],
    'headline' => ['headline'],
    'sender_name' => ['names'], 'groom_name' => ['names'], 'bride_name' => ['names'],
    'celebrant_name' => ['names'], 'turning_age' => ['names'],
    'groom_photo' => ['photos'], 'bride_photo' => ['photos'],
    'message' => ['message'],
    'event_date' => ['date'], 'khmer_date' => ['khmer'],
    'countdown_enabled' => ['countdown'],
    'event_schedule' => ['schedule'], 'schedule_image' => ['schedule'],
    'story_chapters' => ['story'],
    'photo_gallery' => ['gallery'], 'gallery_feed' => ['gallery'],
    'venue_name' => ['venue'], 'venue_address' => ['venue'], 'map_url' => ['venue'],
    'qr_code' => ['qr'], 'gift_account' => ['gift'],
    'rsvp_enabled' => ['rsvp'],
    'groom_phone' => ['phones'], 'bride_phone' => ['phones'],
    'cta_label' => ['cta'], 'cta_url' => ['cta'],
    'accent_color' => ['names', 'date', 'rsvp', 'cta'],
  ];
  // [x, y, w, h] of each part, for the highlight frame
  $boxes = [
    'hero' => [10, 12, 100, 46], 'music' => [93, 43, 14, 14], 'headline' => [30, 64, 60, 4],
    'names' => [20, 71, 80, 7], 'photos' => [30, 83, 60, 18], 'message' => [18, 106, 84, 13],
    'date' => [20, 124, 38, 8], 'khmer' => [62, 124, 38, 8], 'countdown' => [18, 137, 84, 10],
    'schedule' => [18, 153, 84, 18], 'story' => [18, 177, 84, 18], 'gallery' => [18, 201, 84, 23],
    'venue' => [18, 229, 84, 18], 'qr' => [22, 253, 18, 18], 'gift' => [44, 256, 56, 12],
    'rsvp' => [18, 277, 84, 8], 'phones' => [18, 290, 84, 7], 'cta' => [30, 302, 60, 8],
  ];
  $lit = $map[$field] ?? [];
  $fill = fn (string $part) => in_array($part, $lit, true)
    ? 'fill-brand-500'
    : 'fill-plum-200 dark:fill-plum-700';
@endphp

<svg viewBox="0 0 120 318" role="img" aria-label="Where this appears on the invitation" {{ $attributes->merge(['class' => 'h-auto']) }}>
  <rect x="1" y="1" width="118" height="316" rx="12" class="fill-white stroke-plum-300 dark:fill-plum-950 dark:stroke-plum-600" stroke-width="1.5" />
  <rect x="48" y="5" width="24" height="3" rx="1.5" class="fill-plum-200 dark:fill-plum-700" />

  {{-- hero photo, with a little landscape glyph --}}
  <rect x="10" y="12" width="100" height="46" rx="5" class="{{ $fill('hero') }}" />
  <path d="M22 50 38 32l10 11 7-6 15 13Z" class="fill-white/60 dark:fill-plum-950/50" />
  <circle cx="84" cy="25" r="5" class="fill-white/60 dark:fill-plum-950/50" />
  <circle cx="100" cy="50" r="6" class="{{ $fill('music') }} stroke-white dark:stroke-plum-950" stroke-width="1.5" />
  <path d="M98.5 47v5.5m0-5.5 3-1v5" class="stroke-white" stroke-width="1" fill="none" />

  <rect x="30" y="64" width="60" height="4" rx="2" class="{{ $fill('headline') }}" />
  <rect x="20" y="71" width="80" height="7" rx="3.5" class="{{ $fill('names') }}" />
  <circle cx="44" cy="92" r="8" class="{{ $fill('photos') }}" />
  <circle cx="76" cy="92" r="8" class="{{ $fill('photos') }}" />
  <path d="M56 92h8" class="stroke-plum-300 dark:stroke-plum-600" stroke-width="1.5" />

  @foreach ([106, 111, 116] as $i => $y)
    <rect x="{{ $i === 2 ? 34 : 18 }}" y="{{ $y }}" width="{{ $i === 2 ? 52 : 84 }}" height="2.5" rx="1.25" class="{{ $fill('message') }}" />
  @endforeach

  <rect x="20" y="124" width="38" height="8" rx="4" class="{{ $fill('date') }}" />
  <rect x="62" y="124" width="38" height="8" rx="4" class="{{ $fill('khmer') }}" />

  @foreach ([18, 40, 62, 84] as $x)
    <rect x="{{ $x }}" y="137" width="18" height="10" rx="2" class="{{ $fill('countdown') }}" />
  @endforeach

  @foreach ([153, 160, 167] as $y)
    <rect x="18" y="{{ $y }}" width="18" height="3" rx="1.5" class="{{ $fill('schedule') }}" />
    <circle cx="42" cy="{{ $y + 1.5 }}" r="1.8" class="{{ $fill('schedule') }}" />
    <rect x="48" y="{{ $y }}" width="54" height="3" rx="1.5" class="{{ $fill('schedule') }}" />
  @endforeach

  <rect x="18" y="177" width="18" height="18" rx="3" class="{{ $fill('story') }}" />
  @foreach ([180, 186, 192] as $y)
    <rect x="40" y="{{ $y - 1 }}" width="{{ $y === 192 ? 40 : 62 }}" height="2.5" rx="1.25" class="{{ $fill('story') }}" />
  @endforeach

  @foreach ([201, 214] as $y)
    @foreach ([18, 47, 76] as $x)
      <rect x="{{ $x }}" y="{{ $y }}" width="26" height="10" rx="2" class="{{ $fill('gallery') }}" />
    @endforeach
  @endforeach

  <rect x="18" y="229" width="84" height="18" rx="3" class="{{ $fill('venue') }}" />
  <path d="M60 233a4 4 0 0 0-4 4c0 3 4 7 4 7s4-4 4-7a4 4 0 0 0-4-4Z" class="fill-white/70 dark:fill-plum-950/60" />

  <rect x="22" y="253" width="18" height="18" rx="2" class="{{ $fill('qr') }}" />
  <path d="M25 256h5v5h-5Zm7 0h5v5h-5Zm-7 7h5v5h-5Zm8 1h3v3h-3Z" class="fill-white/70 dark:fill-plum-950/60" />
  @foreach ([256, 261, 266] as $y)
    <rect x="44" y="{{ $y }}" width="{{ $y === 266 ? 34 : 56 }}" height="2.5" rx="1.25" class="{{ $fill('gift') }}" />
  @endforeach

  <rect x="18" y="277" width="40" height="8" rx="4" class="{{ $fill('rsvp') }}" />
  <rect x="62" y="277" width="40" height="8" rx="4" class="{{ $fill('rsvp') }}" />
  <rect x="18" y="290" width="40" height="7" rx="3.5" class="{{ $fill('phones') }}" />
  <rect x="62" y="290" width="40" height="7" rx="3.5" class="{{ $fill('phones') }}" />
  <rect x="30" y="302" width="60" height="8" rx="4" class="{{ $fill('cta') }}" />

  {{-- pulsing frame around whatever this field fills --}}
  @foreach ($lit as $part)
    @php [$x, $y, $w, $h] = $boxes[$part]; @endphp
    <rect x="{{ $x - 3 }}" y="{{ $y - 3 }}" width="{{ $w + 6 }}" height="{{ $h + 6 }}" rx="4"
      class="animate-pulse fill-brand-500/10 stroke-brand-500" stroke-width="1.2" stroke-dasharray="3 2" />
  @endforeach
</svg>
