@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Khmer Traditional Wedding. Everything personal comes from $fields (the standard
    // field catalog); all Khmer headings/labels below are template copy, not customer data.
    // On the unpurchased demo page $invitation is null, so every premium extra previews
    // unlocked (see docs/invitation-templates.md).
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomUpper = mb_strtoupper($groomName);
    $brideUpper = mb_strtoupper($brideName);
    $coupleTitle = collect([$groomUpper, $brideUpper])->filter()->implode(' & ');

    $eventDate = ! empty($fields['event_date']) ? Carbon::parse($fields['event_date']) : null;
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate
        ? 'ថ្ងៃ'.$khDays[$eventDate->dayOfWeek].' ទី'.$khDigits($eventDate->day).' ខែ'.$khMonths[$eventDate->month - 1].' ឆ្នាំ'.$khDigits($eventDate->year)
        : null;
    $timeKhmer = $eventDate
        ? 'ម៉ោង '.$khDigits($eventDate->format('g:i')).' '.($eventDate->hour < 12 ? 'ព្រឹក' : ($eventDate->hour < 18 ? 'រសៀល' : 'ល្ងាច'))
        : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    $venueName = $fields['venue_name'] ?? null;
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address'])) ? $fields['venue_address'] : null;
    $mapUrl = ($venueAddress || $venueName)
        ? 'https://maps.google.com/?q='.urlencode($venueAddress ?: $venueName)
        : null;
    $showVenue = $venueName || $venueAddress;

    $personalNote = ! empty($fields['message']) ? $fields['message'] : null;

    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : null;
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))
        ->values();
    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))
        ->values();
    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()->map($resolveUrl)->values();

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    $contacts = collect([
        ['name' => $groomUpper, 'phone' => trim($fields['groom_phone'] ?? ''), 'role' => 'ខាងកូនប្រុស'],
        ['name' => $brideUpper, 'phone' => trim($fields['bride_phone'] ?? ''), 'role' => 'ខាងកូនស្រី'],
    ])->filter(fn ($c) => $c['phone'] !== '')->values();

    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if ($musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '♡';
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Noto+Sans+Khmer:wght@400;500;600&family=Noto+Serif+Khmer:wght@400;600;700&display=swap">
<style>
  :root{
    color-scheme: light;
    --red:#7A1515; --gold:#C9A24D; --ivory:#F8F1E3; --maroon:#4A0D0D; --beige:#E8D8BD;
    --gold-soft:rgba(201,162,77,.4); --ink:#3a1a14;
    --f-kh-serif:'Noto Serif Khmer','Khmer OS Siemreap','Hanuman',serif;
    --f-kh-sans:'Noto Sans Khmer','Khmer OS Battambang','Battambang',sans-serif;
    --f-en:'Cormorant Garamond','Playfair Display','Times New Roman',serif;
    --ease:cubic-bezier(.22,.8,.3,1);
  }
  html{ background:var(--maroon); scroll-behavior:smooth; }
  body.kw-body{ margin:0; background:var(--maroon); color:var(--ink); font-family:var(--f-kh-sans); -webkit-font-smoothing:antialiased; overflow-x:hidden; }
  body.kw-locked{ overflow:hidden; height:100vh; }
  .kw *,.kw *::before,.kw *::after{ box-sizing:border-box; }
  .kw h1,.kw h2,.kw h3,.kw h4,.kw p{ margin:0; font-weight:inherit; }
  .kw svg{ display:block; }
  .kw button{ font:inherit; color:inherit; background:none; border:0; cursor:pointer; }
  .kw :focus-visible{ outline:2px solid var(--gold); outline-offset:3px; }

  /* the invitation is a phone-width column; on larger screens it sits on a maroon backdrop */
  .kw-page{ position:relative; max-width:520px; margin:0 auto; background:var(--ivory); overflow:hidden;
    box-shadow:0 0 0 1px rgba(201,162,77,.25), 0 30px 80px rgba(0,0,0,.45); }
  .kw-sec{ position:relative; padding:72px 26px; text-align:center; }
  .kw-pattern{ background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44'%3E%3Cpath d='M22 3 41 22 22 41 3 22Z' fill='none' stroke='%23C9A24D' stroke-opacity='.16'/%3E%3Ccircle cx='22' cy='22' r='1.6' fill='%23C9A24D' fill-opacity='.22'/%3E%3C/svg%3E"); }
  .kw-red{ background:radial-gradient(120% 80% at 50% 0%, #8f1c1c 0%, var(--red) 45%, var(--maroon) 100%); color:var(--ivory); }
  .kw-maroon{ background:var(--maroon); color:var(--ivory); }
  .kw-beige{ background:var(--beige); }

  /* type */
  .kw-eyebrow{ font-family:var(--f-kh-serif); font-size:.95rem; letter-spacing:.04em; color:var(--gold); line-height:1.9; }
  .kw-title{ font-family:var(--f-kh-serif); font-weight:700; font-size:1.65rem; line-height:1.7; color:var(--red); }
  .kw-red .kw-title,.kw-maroon .kw-title{ color:var(--gold); }
  .kw-names{ font-family:var(--f-en); font-weight:500; letter-spacing:.14em; line-height:1.15; overflow-wrap:anywhere; }
  .kw-body-text{ font-family:var(--f-kh-sans); font-size:1rem; line-height:2; text-wrap:pretty; overflow-wrap:break-word; }
  .kw-script{ font-family:var(--f-en); font-style:italic; color:var(--gold); }

  /* ornaments */
  .kw-divider{ display:flex; align-items:center; justify-content:center; gap:12px; margin:20px auto 28px; max-width:260px; color:var(--gold); }
  .kw-divider i{ flex:1; height:1px; background:linear-gradient(90deg,transparent,var(--gold)); }
  .kw-divider i:last-child{ transform:scaleX(-1); }
  .kw-divider svg{ width:44px; height:26px; flex:none; }
  .kw-corner{ position:absolute; width:54px; height:54px; color:var(--gold); pointer-events:none; }
  .kw-corner.tl{ top:0; left:0; } .kw-corner.tr{ top:0; right:0; transform:scaleX(-1); }
  .kw-corner.bl{ bottom:0; left:0; transform:scaleY(-1); } .kw-corner.br{ bottom:0; right:0; transform:scale(-1,-1); }
  .kw-frame{ position:absolute; inset:14px; border:1px solid var(--gold-soft); pointer-events:none; }

  /* opening gate */
  .kw-gate{ position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:22px; background:var(--maroon);
    transition:opacity .9s var(--ease), visibility .9s; }
  .kw-gate.open{ opacity:0; visibility:hidden; pointer-events:none; }
  .kw-gate-card{ position:relative; width:100%; max-width:420px; min-height:min(640px,calc(100svh - 44px)); background:var(--ivory); padding:48px 24px 40px; text-align:center;
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; }
  .kw-gate-card .kw-names{ font-size:clamp(1.5rem,7.2vw,2.4rem); letter-spacing:.1em; color:var(--red); }
  .kw-amp{ font-family:var(--f-en); font-style:italic; font-size:1.7rem; color:var(--gold); line-height:1.2; }
  .kw-date-chip{ margin-top:12px; font-family:var(--f-en); font-size:1.2rem; letter-spacing:.3em; color:var(--maroon); }
  .kw-btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:48px; padding:12px 30px; border-radius:999px; font-family:var(--f-kh-serif); font-weight:600; font-size:1rem;
    background:var(--red); color:var(--ivory); border:1px solid var(--gold); box-shadow:0 8px 24px rgba(122,21,21,.28); transition:transform .3s var(--ease), background .3s; text-decoration:none; }
  .kw-btn:hover{ background:var(--maroon); transform:translateY(-2px); }
  .kw-btn.gold{ background:var(--gold); color:var(--maroon); border-color:var(--gold); box-shadow:0 8px 24px rgba(0,0,0,.25); }
  .kw-btn.gold:hover{ background:#d8b565; }
  .kw-btn.ghost{ background:transparent; color:var(--red); border-color:var(--gold); box-shadow:none; }

  /* hero */
  .kw-hero{ padding-top:84px; padding-bottom:84px; }
  .kw-hero .kw-names{ font-size:clamp(1.6rem,8vw,2.8rem); letter-spacing:.1em; color:var(--ivory); }
  .kw-arch{ width:min(72%,280px); aspect-ratio:3/4; margin:0 auto 34px; border-radius:999px 999px 6px 6px; padding:6px; border:1px solid var(--gold); position:relative; }
  .kw-arch img,.kw-arch .ph{ width:100%; height:100%; object-fit:cover; border-radius:999px 999px 2px 2px; display:block; }
  .kw-guest{ font-family:var(--f-kh-serif); font-size:1.5rem; font-weight:700; color:var(--gold); line-height:1.8; margin:4px 0; overflow-wrap:anywhere; }
  .kw-date-block{ margin-top:30px; padding:22px 14px; border-top:1px solid var(--gold-soft); border-bottom:1px solid var(--gold-soft); }
  .kw-date-num{ font-family:var(--f-en); font-size:2.2rem; letter-spacing:.22em; color:var(--gold); }

  /* couple */
  .kw-couple-grid{ display:grid; gap:12px; justify-items:center; }
  .kw-portrait{ width:min(62%,230px); aspect-ratio:4/5; border-radius:999px 999px 8px 8px; padding:6px; border:1px solid var(--gold); background:var(--ivory); }
  .kw-portrait img,.kw-portrait .ph{ width:100%; height:100%; object-fit:cover; border-radius:999px 999px 4px 4px; display:flex; align-items:center; justify-content:center; }
  .kw-portrait .ph{ background:linear-gradient(160deg,var(--beige),#d9c39b); color:var(--red); font-family:var(--f-en); font-size:4rem; }
  .kw-role{ font-family:var(--f-kh-serif); color:var(--gold); font-size:.95rem; letter-spacing:.06em; margin-top:18px; }
  .kw-couple-grid .kw-names{ font-size:clamp(1.4rem,7vw,1.9rem); color:var(--red); }
  .kw-heart{ color:var(--gold); font-size:1.6rem; line-height:1; padding:6px 0; }

  /* story timeline */
  .kw-tl{ position:relative; text-align:left; margin-top:8px; padding-left:34px; }
  .kw-tl::before{ content:""; position:absolute; left:9px; top:6px; bottom:6px; width:1px; background:linear-gradient(var(--gold),var(--gold-soft)); }
  .kw-tl-item{ position:relative; padding-bottom:30px; }
  .kw-tl-item:last-child{ padding-bottom:0; }
  .kw-tl-item::before{ content:""; position:absolute; left:-30px; top:9px; width:11px; height:11px; background:var(--ivory); border:1px solid var(--gold); transform:rotate(45deg); }
  .kw-tl-item h3{ font-family:var(--f-kh-serif); font-weight:700; color:var(--red); font-size:1.15rem; line-height:1.8; }
  .kw-tl-item p{ font-size:.95rem; line-height:2; color:#5a3a30; }
  .kw-tl-num{ font-family:var(--f-en); color:var(--gold); font-size:.85rem; letter-spacing:.3em; }

  /* countdown */
  .kw-count{ display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:8px; }
  .kw-count > div{ border:1px solid var(--gold-soft); padding:16px 2px 12px; background:rgba(248,241,227,.04); }
  .kw-count b{ display:block; font-family:var(--f-en); font-weight:500; font-size:clamp(1.8rem,9vw,2.6rem); line-height:1.1; color:var(--gold); font-variant-numeric:tabular-nums; }
  .kw-count span{ display:block; margin-top:6px; font-family:var(--f-en); font-size:.62rem; letter-spacing:.2em; color:var(--beige); }

  /* schedule */
  .kw-sched{ display:flex; flex-direction:column; gap:14px; text-align:left; }
  .kw-sched-item{ position:relative; display:grid; grid-template-columns:96px 1fr; gap:14px; align-items:center; padding:16px 16px 16px 14px; background:#fffaf0; border:1px solid var(--gold-soft); }
  .kw-sched-item::after{ content:""; position:absolute; left:96px; top:14px; bottom:14px; width:1px; background:var(--gold-soft); margin-left:7px; }
  .kw-sched-time{ font-family:var(--f-en); font-weight:600; font-size:1.05rem; letter-spacing:.06em; color:var(--red); line-height:1.3; }
  .kw-sched-label{ font-family:var(--f-kh-serif); font-weight:600; font-size:1.05rem; line-height:1.8; color:var(--maroon); padding-left:12px; }

  /* gallery */
  .kw-gal{ columns:2; column-gap:12px; }
  .kw-gal figure{ margin:0 0 12px; break-inside:avoid; padding:7px; background:var(--ivory); border:1px solid var(--gold); position:relative; box-shadow:0 8px 20px rgba(74,13,13,.12); }
  .kw-gal img{ width:100%; display:block; }

  /* venue */
  .kw-venue-name{ font-family:var(--f-en); font-weight:500; font-size:clamp(1.6rem,8vw,2.2rem); letter-spacing:.1em; color:var(--ivory); line-height:1.25; overflow-wrap:anywhere; }

  /* rsvp */
  .kw-rsvp-opts{ display:flex; flex-direction:column; gap:12px; margin-top:22px; }
  .kw-opt{ min-height:52px; padding:12px 20px; border:1px solid var(--gold); background:transparent; font-family:var(--f-kh-serif); font-weight:600; font-size:1.05rem; color:var(--red); transition:background .3s, color .3s, transform .3s var(--ease); }
  .kw-opt:hover{ transform:translateY(-2px); }
  .kw-opt.chosen{ background:var(--red); color:var(--ivory); }
  .kw-rsvp-form{ display:none; margin-top:22px; gap:12px; flex-direction:column; }
  .kw-rsvp-form.show{ display:flex; }
  .kw-rsvp-form input{ width:100%; padding:13px 14px; border:1px solid var(--gold-soft); background:#fffaf0; font-family:var(--f-kh-sans); font-size:1rem; color:var(--ink); border-radius:0; }
  .kw-thanks{ display:none; margin-top:22px; font-family:var(--f-kh-serif); color:var(--red); font-size:1.1rem; line-height:1.9; }
  .kw-thanks.show{ display:block; }

  /* contact */
  .kw-contact{ display:grid; gap:14px; }
  .kw-contact a{ display:block; padding:18px 14px; border:1px solid var(--gold-soft); background:#fffaf0; text-decoration:none; color:inherit; transition:transform .3s var(--ease), border-color .3s; }
  .kw-contact a:hover{ transform:translateY(-2px); border-color:var(--gold); }
  .kw-contact .n{ font-family:var(--f-en); font-size:1.4rem; letter-spacing:.14em; color:var(--red); }
  .kw-contact .r{ font-family:var(--f-kh-serif); font-size:.85rem; color:var(--gold); }
  .kw-contact .p{ font-family:var(--f-en); font-size:1.15rem; letter-spacing:.06em; color:var(--maroon); margin-top:4px; }

  /* footer */
  .kw-foot .kw-names{ font-size:clamp(1.3rem,6.4vw,2rem); letter-spacing:.1em; color:var(--gold); margin-top:26px; }
  .kw-brand{ margin-top:34px; font-family:var(--f-en); font-size:.7rem; letter-spacing:.3em; color:rgba(232,216,189,.55); }

  /* music */
  .kw-music{ position:fixed; right:16px; bottom:16px; z-index:40; width:48px; height:48px; border-radius:50%; background:var(--gold); color:var(--maroon);
    display:none; align-items:center; justify-content:center; box-shadow:0 8px 22px rgba(0,0,0,.35); border:1px solid rgba(74,13,13,.25); text-decoration:none; }
  .kw-music.show{ display:flex; }
  .kw-music svg{ width:22px; height:22px; }
  .kw-music.playing::after{ content:""; position:absolute; inset:-6px; border-radius:50%; border:1px solid var(--gold); animation:kw-pulse 2.4s ease-out infinite; }
  .kw-music .off{ display:none; } .kw-music.paused .on{ display:none; } .kw-music.paused .off{ display:block; }

  /* motion — subtle, and fully off for reduced-motion users */
  .kw-js .kw-rev{ opacity:0; transform:translateY(22px); transition:opacity 1s var(--ease), transform 1s var(--ease); }
  .kw-js .kw-rev.in{ opacity:1; transform:none; }
  .kw-js .kw-rev.d1{ transition-delay:.12s; } .kw-js .kw-rev.d2{ transition-delay:.24s; } .kw-js .kw-rev.d3{ transition-delay:.36s; }
  .kw-gate-card > *{ animation:kw-rise 1.1s var(--ease) both; }
  .kw-gate-card > *:nth-child(2){ animation-delay:.1s } .kw-gate-card > *:nth-child(3){ animation-delay:.2s }
  .kw-gate-card > *:nth-child(4){ animation-delay:.3s } .kw-gate-card > *:nth-child(5){ animation-delay:.4s }
  .kw-gate-card > *:nth-child(6){ animation-delay:.5s } .kw-gate-card > *:nth-child(7){ animation-delay:.6s }
  @keyframes kw-rise{ from{ opacity:0; transform:translateY(16px) } to{ opacity:1; transform:none } }
  @keyframes kw-pulse{ from{ opacity:.7; transform:scale(1) } to{ opacity:0; transform:scale(1.5) } }
  @media (prefers-reduced-motion: reduce){
    html{ scroll-behavior:auto; }
    .kw-js .kw-rev{ opacity:1; transform:none; transition:none; }
    .kw-gate,.kw-gate-card > *,.kw-btn,.kw-opt,.kw-contact a{ animation:none; transition:none; }
    .kw-music.playing::after{ animation:none; }
  }
  @media (min-width:768px){
    .kw-sec{ padding:88px 40px; }
    .kw-title{ font-size:1.9rem; }
  }
</style>

<div class="kw">
  {{-- shared ornaments: one lotus + one corner flourish, reused everywhere via <use> --}}
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      <symbol id="kw-lotus" viewBox="0 0 64 36">
        <g fill="currentColor" fill-opacity=".14" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round">
          <path d="M32 2C26 10 26 22 32 32 38 22 38 10 32 2Z"/>
          <path d="M32 32C20 29 12 19 14 9 24 13 30 21 32 32Z"/>
          <path d="M32 32C44 29 52 19 50 9 40 13 34 21 32 32Z"/>
          <path d="M32 33C18 34 6 28 1 19 14 17 26 23 32 33Z"/>
          <path d="M32 33C46 34 58 28 63 19 50 17 38 23 32 33Z"/>
        </g>
      </symbol>
      <symbol id="kw-corner" viewBox="0 0 64 64">
        <g fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round">
          <path d="M2 62V28C2 13 13 2 28 2h34"/>
          <path d="M9 62V32C9 19 19 9 32 9h30" stroke-opacity=".55"/>
          <path d="M16 16 22 22 16 28 10 22Z" fill="currentColor" fill-opacity=".25"/>
          <circle cx="32" cy="2" r="1.6" fill="currentColor"/><circle cx="2" cy="32" r="1.6" fill="currentColor"/>
        </g>
      </symbol>
    </defs>
  </svg>

  {{-- ============ 1 · OPENING SCREEN ============ --}}
  <div class="kw-gate" id="kw-gate" role="dialog" aria-label="លិខិតអញ្ជើញ">
    <div class="kw-gate-card kw-pattern">
      <div class="kw-frame"></div>
      <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner bl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner br" aria-hidden="true"><use href="#kw-corner"/></svg>

      <p class="kw-eyebrow">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
      <div class="kw-divider"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
      <p class="kw-body-text" style="color:var(--maroon)">សូមគោរពអញ្ជើញ</p>
      <div style="margin:8px 0">
        @if ($groomUpper)<h1 class="kw-names">{{ $groomUpper }}</h1>@endif
        @if ($groomUpper && $brideUpper)<div class="kw-amp">&amp;</div>@endif
        @if ($brideUpper)<h1 class="kw-names">{{ $brideUpper }}</h1>@endif
      </div>
      @if ($dateNumeric)<p class="kw-date-chip">{{ $dateNumeric }}</p>@endif
      <div style="margin-top:26px">
        <button type="button" class="kw-btn" id="kw-open">បើកលិខិតអញ្ជើញ</button>
      </div>
    </div>
  </div>

  <main class="kw-page">

    {{-- ============ 2 · HERO ============ --}}
    <section class="kw-sec kw-red kw-hero" id="kw-top">
      <div class="kw-frame"></div>
      <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner bl" aria-hidden="true"><use href="#kw-corner"/></svg>
      <svg class="kw-corner br" aria-hidden="true"><use href="#kw-corner"/></svg>

      @if ($coverUrl)
        <div class="kw-arch kw-rev"><img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" loading="eager"></div>
      @endif

      <p class="kw-eyebrow kw-rev">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
      <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>

      <h2 class="kw-names kw-rev d1">{{ $coupleTitle }}</h2>

      <div class="kw-body-text kw-rev d2" style="margin-top:26px">
        <p>សូមគោរពអញ្ជើញ</p>
        @if (! empty($recipientName))
          <p class="kw-guest">{{ $recipientName }}</p>
        @else
          <p class="kw-guest">លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p>ចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស</p>
        <p>ក្នុងពិធីមង្គលការរបស់យើងខ្ញុំ</p>
      </div>

      @if ($personalNote)
        <p class="kw-body-text kw-script kw-rev" style="margin-top:20px; color:var(--beige); font-style:normal">{{ $personalNote }}</p>
      @endif

      @if ($eventDate)
        <div class="kw-date-block kw-rev d3">
          <p class="kw-date-num">{{ $dateNumeric }}</p>
          <p class="kw-body-text" style="color:var(--ivory)">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="kw-body-text" style="color:var(--beige); font-size:.9rem">{{ $khmerLunarDate }}</p>@endif
          <p class="kw-body-text" style="color:var(--gold)">{{ $timeKhmer }}</p>
        </div>
      @endif
    </section>

    {{-- ============ 3 · COUPLE ============ --}}
    @if ($groomName || $brideName)
      <section class="kw-sec kw-pattern" id="kw-couple">
        <p class="kw-eyebrow kw-rev">គូស្វាមីភរិយា</p>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-couple-grid">
          @if ($brideName)
            <div class="kw-rev">
              <div class="kw-portrait">@if ($bridePhoto)<img src="{{ $bridePhoto }}" alt="{{ $brideName }}" loading="lazy">@else<div class="ph">{{ $initial($brideName) }}</div>@endif</div>
              <p class="kw-role">កូនក្រមុំ · THE BRIDE</p>
              <h3 class="kw-names">{{ $brideUpper }}</h3>
            </div>
          @endif
          @if ($brideName && $groomName)<div class="kw-heart kw-rev" aria-hidden="true">♡</div>@endif
          @if ($groomName)
            <div class="kw-rev">
              <div class="kw-portrait">@if ($groomPhoto)<img src="{{ $groomPhoto }}" alt="{{ $groomName }}" loading="lazy">@else<div class="ph">{{ $initial($groomName) }}</div>@endif</div>
              <p class="kw-role">កូនកំលោះ · THE GROOM</p>
              <h3 class="kw-names">{{ $groomUpper }}</h3>
            </div>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 4 · STORY ============ --}}
    @if ($story->isNotEmpty())
      <section class="kw-sec kw-beige" id="kw-story">
        <h2 class="kw-title kw-rev">ដំណើរជីវិតរបស់យើង</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-tl">
          @foreach ($story as $i => $chapter)
            <div class="kw-tl-item kw-rev">
              <span class="kw-tl-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              @if (! empty($chapter['title']))<h3>{{ $chapter['title'] }}</h3>@endif
              @if (! empty($chapter['text']))<p>{{ $chapter['text'] }}</p>@endif
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 5 · COUNTDOWN ============ --}}
    @if ($showCountdown)
      <section class="kw-sec kw-maroon kw-pattern" id="kw-countdown">
        <h2 class="kw-title kw-rev">ថ្ងៃដ៏វិសេសរបស់យើង</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-count kw-rev" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
          <div><b data-u="d">00</b><span>DAYS</span></div>
          <div><b data-u="h">00</b><span>HOURS</span></div>
          <div><b data-u="m">00</b><span>MINUTES</span></div>
          <div><b data-u="s">00</b><span>SECONDS</span></div>
        </div>
      </section>
    @endif

    {{-- ============ 6 · PROGRAM ============ --}}
    @if ($schedule->isNotEmpty())
      <section class="kw-sec" id="kw-program">
        <h2 class="kw-title kw-rev">កម្មវិធីពិធីមង្គលការ</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-sched">
          @foreach ($schedule as $row)
            <div class="kw-sched-item kw-rev">
              <div class="kw-sched-time">{{ $row['time'] ?? '' }}</div>
              <div class="kw-sched-label">{{ $row['label'] ?? '' }}</div>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 7 · GALLERY ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="kw-sec kw-beige kw-pattern" id="kw-gallery">
        <h2 class="kw-title kw-rev">កម្រងរូបភាព</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-gal">
          @foreach ($gallery as $i => $url)
            <figure class="kw-rev"><img src="{{ $url }}" alt="{{ $coupleTitle }} {{ $i + 1 }}" loading="lazy"></figure>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 8 · VENUE ============ --}}
    @if ($showVenue)
      <section class="kw-sec kw-red" id="kw-venue">
        <div class="kw-frame"></div>
        <svg class="kw-corner tl" aria-hidden="true"><use href="#kw-corner"/></svg>
        <svg class="kw-corner tr" aria-hidden="true"><use href="#kw-corner"/></svg>
        <svg class="kw-corner bl" aria-hidden="true"><use href="#kw-corner"/></svg>
        <svg class="kw-corner br" aria-hidden="true"><use href="#kw-corner"/></svg>
        <h2 class="kw-title kw-rev">ទីតាំងប្រារព្ធពិធី</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        @if ($venueName)<p class="kw-venue-name kw-rev">{{ mb_strtoupper($venueName) }}</p>@endif
        @if ($venueAddress)<p class="kw-body-text kw-rev" style="color:var(--beige); margin-top:10px">{{ $venueAddress }}</p>@endif
        @if ($mapUrl && $venueAddress)
          <div class="kw-rev" style="margin-top:28px"><a class="kw-btn gold" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី</a></div>
        @endif
      </section>
    @endif

    {{-- ============ 9 · RSVP ============ --}}
    @if ($showRsvp)
      <section class="kw-sec kw-pattern" id="kw-rsvp">
        <h2 class="kw-title kw-rev">សូមអញ្ជើញបញ្ជាក់ការចូលរួម</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <p class="kw-body-text kw-rev">តើលោកអ្នកនឹងអញ្ជើញចូលរួមដែរឬទេ?</p>
        <div class="kw-rsvp-opts kw-rev" id="kw-rsvp-opts">
          <button type="button" class="kw-opt" data-status="yes">ចូលរួម</button>
          <button type="button" class="kw-opt" data-status="no">មិនអាចចូលរួម</button>
        </div>
        <form class="kw-rsvp-form" id="kw-rsvp-form">
          <input type="text" id="kw-rsvp-note" maxlength="500" placeholder="ផ្ញើសារជូនពរ (មិនចាំបាច់)" autocomplete="off">
          <button type="submit" class="kw-btn">បញ្ជូន</button>
        </form>
        <p class="kw-thanks" id="kw-thanks" role="status"></p>
      </section>
    @endif

    {{-- ============ 10 · CONTACT ============ --}}
    @if ($contacts->isNotEmpty())
      <section class="kw-sec kw-beige" id="kw-contact">
        <h2 class="kw-title kw-rev">ទំនាក់ទំនង</h2>
        <div class="kw-divider kw-rev"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
        <div class="kw-contact">
          @foreach ($contacts as $c)
            <a class="kw-rev" href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}">
              <div class="r">{{ $c['role'] }}</div>
              <div class="n">{{ $c['name'] }}</div>
              <div class="p">{{ $c['phone'] }}</div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 11 · FOOTER ============ --}}
    <footer class="kw-sec kw-maroon kw-foot kw-pattern">
      <div class="kw-divider kw-rev" style="margin-top:0"><i></i><svg aria-hidden="true"><use href="#kw-lotus"/></svg><i></i></div>
      <p class="kw-body-text kw-rev" style="color:var(--ivory)">សូមអរគុណសម្រាប់ក្តីស្រឡាញ់<br>និងការចូលរួមក្នុងថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
      @if ($coupleTitle)<p class="kw-names kw-rev d1">{{ $coupleTitle }}</p>@endif
      <p class="kw-rev d2" style="color:var(--gold); font-size:1.4rem; margin-top:14px" aria-hidden="true">♡</p>
      <p class="kw-brand">ROUMDOUL</p>
    </footer>
  </main>

  {{-- ============ MUSIC ============ --}}
  @if ($youtubeId)
    <iframe id="kw-yt" title="Background music" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;border:0"
      src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&mute=1&loop=1&playlist={{ $youtubeId }}&enablejsapi=1&playsinline=1"
      allow="autoplay; encrypted-media"></iframe>
    <button type="button" class="kw-music paused" id="kw-music" aria-label="Toggle music">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @elseif ($musicUrl)
    <a class="kw-music show" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Play music">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @endif
</div>

<script>
(function () {
  var root = document.documentElement, body = document.body;
  root.classList.add('kw-js');
  body.classList.add('kw-body', 'kw-locked');

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* --- section reveal on scroll --- */
  var revs = document.querySelectorAll('.kw-rev');
  if ('IntersectionObserver' in window && !reduced) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    revs.forEach(function (el) { io.observe(el); });
  } else {
    revs.forEach(function (el) { el.classList.add('in'); });
  }

  /* --- music (YouTube iframe is primed muted; the open tap is the user gesture that lets it play) --- */
  var yt = document.getElementById('kw-yt'), musicBtn = document.getElementById('kw-music'), playing = false;
  function ytCmd(fn) {
    if (yt && yt.contentWindow) yt.contentWindow.postMessage(JSON.stringify({ event: 'command', func: fn, args: [] }), '*');
  }
  function setPlaying(on) {
    playing = on;
    if (!musicBtn) return;
    musicBtn.classList.toggle('playing', on);
    musicBtn.classList.toggle('paused', !on);
  }
  if (musicBtn) {
    musicBtn.classList.add('show');
    musicBtn.addEventListener('click', function () {
      if (playing) { ytCmd('pauseVideo'); setPlaying(false); }
      else { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
    });
  }

  /* --- opening gate --- */
  var gate = document.getElementById('kw-gate');
  document.getElementById('kw-open').addEventListener('click', function () {
    gate.classList.add('open');
    body.classList.remove('kw-locked');
    window.scrollTo(0, 0);
    if (yt) { ytCmd('unMute'); ytCmd('playVideo'); setPlaying(true); }
  });

  /* --- countdown (uses the invitation's real event date) --- */
  var cd = document.querySelector('.kw-count');
  if (cd) {
    var target = new Date(cd.getAttribute('data-target')).getTime();
    var els = {};
    cd.querySelectorAll('[data-u]').forEach(function (b) { els[b.getAttribute('data-u')] = b; });
    var pad = function (n) { return String(n).padStart(2, '0'); };
    var tick = function () {
      var diff = Math.max(0, target - Date.now());
      els.d.textContent = pad(Math.floor(diff / 86400000));
      els.h.textContent = pad(Math.floor(diff / 3600000) % 24);
      els.m.textContent = pad(Math.floor(diff / 60000) % 60);
      els.s.textContent = pad(Math.floor(diff / 1000) % 60);
    };
    tick(); setInterval(tick, 1000);
  }

  /* --- RSVP: posts to the existing invitation RSVP endpoint (status yes|no + optional note) --- */
  var opts = document.getElementById('kw-rsvp-opts');
  if (opts) {
    var rsvpUrl = @json($rsvpUrl);
    var form = document.getElementById('kw-rsvp-form'), note = document.getElementById('kw-rsvp-note'), thanks = document.getElementById('kw-thanks');
    var chosen = null;
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.kw-opt'); if (!b) return;
      chosen = b.getAttribute('data-status');
      opts.querySelectorAll('.kw-opt').forEach(function (o) { o.classList.toggle('chosen', o === b); });
      form.classList.add('show'); thanks.classList.remove('show');
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); if (!chosen) return;
      var done = function (ok) {
        thanks.textContent = !ok ? 'សូមព្យាយាមម្ដងទៀត' : (chosen === 'yes' ? 'សូមអរគុណ! យើងខ្ញុំរង់ចាំស្វាគមន៍លោកអ្នក។' : 'សូមអរគុណដែលបានជូនដំណឹង។');
        thanks.classList.add('show'); if (ok) form.classList.remove('show');
      };
      if (!rsvpUrl) { done(true); return; }   // demo preview: no recipient, nothing to save
      fetch(rsvpUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || '' },
        body: JSON.stringify({ status: chosen, note: note.value || null })
      }).then(function (r) { done(r.ok); }).catch(function () { done(false); });
    });
  }
})();
</script>
