@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    $resolveUrl = fn (string $path) => str_starts_with($path, 'http')
        ? $path
        : Storage::disk('s3')->url($path);

    // Premium extras are gated per FIELD_CATALOG key by the purchased plan's `features` list
    // (see InvitationTemplate::FREE_FIELDS / Invitation::fieldUnlocked()) — on the unpurchased
    // demo page $invitation is null, so every extra previews unlocked.
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;

    $brideName = $fields['bride_name'] ?? 'Elena';
    $groomName = $fields['groom_name'] ?? 'Julian';

    $eventDate = ! empty($fields['event_date']) ? Carbon::parse($fields['event_date']) : null;

    $venueName = $fields['venue_name'] ?? 'Maison Verrière';
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address']))
        ? $fields['venue_address']
        : null;

    $ceremonyTime = $eventDate ? $eventDate->format('g:i A') : '5:00 PM';
    $receptionTime = $eventDate ? $eventDate->copy()->addMinutes(150)->format('g:i A') : '7:30 PM';

    $message = $fields['message'] ?? "I never knew\nthat one person\ncould make ordinary moments\nfeel extraordinary —\nuntil every moment was ours.";
    $letterLines = collect(preg_split('/\r\n|\r|\n/', trim($message)))->filter()->values();
    if ($letterLines->count() <= 1 && mb_strlen($message) > 60) {
        $letterLines = collect(preg_split('/(?<=[.!?])\s+/', trim($message)))->filter()->values();
    }
    $signature = ! empty($fields['sender_name']) ? $fields['sender_name'] : mb_strtoupper(mb_substr($groomName, 0, 1)).'.';

    $scheduleUnlocked = $isUnlocked('event_schedule');
    $schedule = collect($scheduleUnlocked ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))
        ->values();
    if ($schedule->isEmpty()) {
        $schedule = collect([
            ['time' => '5:00 PM', 'label' => 'Ceremony'],
            ['time' => '6:30 PM', 'label' => 'Cocktail Hour'],
            ['time' => '7:30 PM', 'label' => 'Dinner'],
            ['time' => '9:00 PM', 'label' => 'Celebration'],
            ['time' => '12:00 AM', 'label' => 'Last Song'],
        ]);
    }

    // Photo frames throughout this template (reveal, story, gallery wall, carousel, finale…)
    // are cinematic placeholders by design, not one dedicated field each — so real photos are
    // drawn from the customer's uploaded gallery and assigned to frames deterministically by
    // index (not randomly: a re-render, e.g. after resizing the window, must show the same
    // photo in the same frame, not shuffle underneath the guest). Falls back to the single
    // cover image, then to the tone-only gradient placeholder when nothing was uploaded.
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : null;
    $galleryUnlocked = $isUnlocked('photo_gallery');
    $galleryUrls = collect($galleryUnlocked ? ($fields['photo_gallery'] ?? []) : [])
        ->filter()
        ->map($resolveUrl)
        ->values();
    if ($galleryUrls->isEmpty() && $coverUrl) {
        $galleryUrls = collect([$coverUrl]);
    }
    $frameImg = fn (int $i) => $galleryUrls->isNotEmpty() ? $galleryUrls[$i % $galleryUrls->count()] : null;

    $trackRsvp = $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && $recipient) ? route('invitation.rsvp', [$invitation, $recipient]) : null;

    $dateSteps = $eventDate
        ? [(string) $eventDate->day, $eventDate->format('d / m'), $eventDate->format('d / m / Y')]
        : ['12', '12 / 12', '12 / 12 / 2026'];
@endphp
<title>{{ $brideName }} & {{ $groomName }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Newsreader:ital,opsz,wght@0,6..72,300..600;1,6..72,300..600&family=JetBrains+Mono:wght@400;500;600&display=swap">
<style>
  /* ============================================================
     ELENA & JULIAN — "PRIVATE SCREENING"
     A cinematic invitation staged as a film print: a leader count-
     down, then fourteen reels, each framed like a viewfinder with
     a burned-in scene number and timecode. Grain, aperture, projec-
     tor light — the wedding as a title sequence, not a web template.
     Committed single dark world by design (see design notes) — the
     palette and every rule below are declared explicitly rather
     than left to the host theme.
     ============================================================ */

  :root{
    color-scheme: dark;
    --ink:      #0f0d0c;   /* base — exposed film stock */
    --ink-2:    #17130f;   /* lifted panel ground */
    --ink-3:    #211a15;   /* deepest card ground */
    --bone:     #f3ead6;   /* title-card paper */
    --bone-dim: #cabfa9;   /* secondary paper */
    --wine:     #5c1b27;   /* velvet seat / safelight */
    --wine-2:   #3a1017;
    --brass:    #ad8a55;   /* antique brass, not gold-foil */
    --brass-soft: rgba(173,138,85,.38);
    --rose:     #b98a86;   /* dusty rose-ash, used sparingly */
    --smoke:    #8d8175;   /* warm neutral, biased toward brass */
    --line:     rgba(243,234,214,.14);
    --line-strong: rgba(243,234,214,.28);

    --f-display: 'Instrument Serif', 'Times New Roman', serif;
    --f-body:    'Newsreader', 'Iowan Old Style', 'Georgia', serif;
    --f-mono:    'JetBrains Mono', ui-monospace, 'SFMono-Regular', Menlo, monospace;

    --ease-cine: cubic-bezier(.16,.84,.24,1);
    --container: min(92vw, 1180px);
  }

  *,*::before,*::after{ box-sizing:border-box; }
  html{ background:var(--ink); }
  body{
    background:var(--ink);
    color:var(--bone);
    font-family:var(--f-body);
    font-size:16px;
    line-height:1.5;
    -webkit-font-smoothing:antialiased;
    overflow-x:hidden;
    cursor:default;
  }
  ::selection{ background:var(--wine); color:var(--bone); }

  img,svg,canvas{ display:block; max-width:100%; }
  a{ color:inherit; }
  button{ font:inherit; color:inherit; background:none; border:0; cursor:pointer; }
  h1,h2,h3,p{ margin:0; }

  .eyebrow{
    font-family:var(--f-mono);
    font-size:.68rem;
    letter-spacing:.34em;
    text-transform:uppercase;
    color:var(--brass);
  }
  .display{
    font-family:var(--f-display);
    font-weight:400;
    letter-spacing:-.01em;
    text-wrap:balance;
  }
  .display.italic{ font-style:italic; }
  .lede{
    font-family:var(--f-body);
    font-style:italic;
    color:var(--bone-dim);
    font-optical-sizing:auto;
  }

  /* ---------- reduced motion ---------- */
  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:.01ms !important; animation-iteration-count:1 !important; transition-duration:.01ms !important; scroll-behavior:auto !important; }
  }

  /* ---------- fixed chrome: grain, rail, sound ---------- */
  #grain{
    position:fixed; inset:0; z-index:60; pointer-events:none;
    width:100%; height:100%;
    opacity:.05; mix-blend-mode:overlay;
  }
  #vignette{
    position:fixed; inset:0; z-index:55; pointer-events:none;
    background:radial-gradient(120% 100% at 50% 50%, transparent 55%, rgba(0,0,0,.55) 100%);
  }
  #rail{
    position:fixed; right:clamp(14px,3vw,34px); top:50%; transform:translateY(-50%);
    z-index:70; display:flex; flex-direction:column; align-items:center; gap:9px;
    opacity:0; transition:opacity .6s var(--ease-cine);
  }
  #rail.visible{ opacity:1; }
  #rail .tick{
    width:3px; height:3px; border-radius:50%; background:var(--line-strong);
    transition:background .35s, transform .35s, height .35s;
  }
  #rail .tick.active{ background:var(--brass); height:20px; border-radius:2px; transform:scale(1); }

  #sound-toggle{
    position:fixed; left:clamp(14px,3vw,34px); bottom:clamp(14px,3vw,30px); z-index:70;
    display:flex; align-items:center; gap:10px; font-family:var(--f-mono);
    font-size:.66rem; letter-spacing:.22em; text-transform:uppercase; color:var(--bone-dim);
    opacity:0; transition:opacity .6s var(--ease-cine), color .3s;
  }
  #sound-toggle.visible{ opacity:1; }
  #sound-toggle:hover{ color:var(--bone); }
  #sound-toggle .bars{ display:flex; align-items:flex-end; gap:2px; height:12px; }
  #sound-toggle .bars i{ width:2px; background:currentColor; display:block; height:4px; transition:height .18s; }
  #sound-toggle.playing .bars i{ animation:eq 900ms ease-in-out infinite; }
  #sound-toggle .bars i:nth-child(1){ animation-delay:-.6s; }
  #sound-toggle .bars i:nth-child(2){ animation-delay:-.3s; }
  #sound-toggle .bars i:nth-child(3){ animation-delay:0s; }
  @keyframes eq{ 0%,100%{ height:3px; } 50%{ height:12px; } }

  /* ---------- scene chrome (scene no. / timecode / corner ticks) ---------- */
  .reel{ position:relative; min-height:100vh; padding:clamp(90px,10vw,120px) 0; }
  .chrome{ position:absolute; inset:18px; pointer-events:none; z-index:5; }
  .chrome span{ position:absolute; font-family:var(--f-mono); font-size:.64rem; letter-spacing:.18em; color:var(--brass-soft); }
  .chrome .scene{ top:0; left:0; }
  .chrome .tc{ top:0; right:0; }
  .chrome i{ position:absolute; width:14px; height:14px; border-color:var(--line-strong); border-style:solid; border-width:0; opacity:.5; }
  .chrome i.tl{ top:0; left:0; border-top-width:1px; border-left-width:1px; }
  .chrome i.tr{ top:0; right:0; border-top-width:1px; border-right-width:1px; }
  .chrome i.bl{ bottom:0; left:0; border-bottom-width:1px; border-left-width:1px; }
  .chrome i.br{ bottom:0; right:0; border-bottom-width:1px; border-right-width:1px; }

  .wrap{ width:var(--container); margin:0 auto; position:relative; z-index:2; }

  /* ---------- frame / photo panels (stand-in for photography) ---------- */
  .frame{
    position:relative; overflow:hidden; border:1px solid var(--line-strong);
    background:linear-gradient(155deg,var(--t1),var(--t2));
  }
  .frame img{
    position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0;
  }
  .frame::after{
    content:''; position:absolute; inset:0; opacity:.35; mix-blend-mode:overlay;
    background-image:var(--grain-uri);
  }
  .frame::before{
    content:''; position:absolute; inset:0;
    box-shadow:inset 0 0 60px rgba(0,0,0,.5);
  }
  .frame .mono{
    position:absolute; bottom:10px; left:12px; font-family:var(--f-mono); font-size:.6rem;
    letter-spacing:.2em; color:rgba(243,234,214,.55); text-transform:uppercase; z-index:2;
  }
  .tone-wine{ --t1:#6b2230; --t2:#20090d; }
  .tone-ink{ --t1:#2a231c; --t2:#0c0a08; }
  .tone-brass{ --t1:#7a6136; --t2:#1a140c; }
  .tone-rose{ --t1:#8a5f5c; --t2:#1c0f0f; }

  /* =============== GATE / LEADER =============== */
  #gate{
    position:fixed; inset:0; z-index:100; background:var(--ink);
    display:flex; align-items:center; justify-content:center; flex-direction:column;
    transition:visibility 0s linear 1.4s;
  }
  #gate.done{ visibility:hidden; }
  #gate canvas{ position:absolute; inset:0; width:100%; height:100%; opacity:.7; }
  #leader-count{
    font-family:var(--f-mono); font-size:clamp(2.4rem,10vw,5rem); color:var(--brass-soft);
    letter-spacing:.1em; position:relative; z-index:2;
  }
  #gate-main{ position:relative; z-index:2; text-align:center; padding:0 24px; opacity:0; }
  .glow-point{
    width:6px; height:6px; border-radius:50%; margin:0 auto 34px; background:var(--bone);
    box-shadow:0 0 24px 8px var(--brass-soft),0 0 60px 22px rgba(173,138,85,.12);
  }
  #gate-main .eyebrow{ display:block; margin-bottom:16px; }
  #gate-main h1{
    font-family:var(--f-display); font-style:italic; font-weight:400;
    font-size:clamp(1.6rem,4.6vw,2.6rem); color:var(--bone-dim); margin-bottom:44px;
  }
  #enter-btn{
    position:relative; padding:18px 42px; border:1px solid var(--line-strong);
    font-family:var(--f-mono); font-size:.72rem; letter-spacing:.32em; text-transform:uppercase;
    color:var(--bone); overflow:hidden; transition:border-color .4s, letter-spacing .4s;
  }
  #enter-btn::before{
    content:''; position:absolute; inset:0; background:var(--wine); transform:translateY(101%);
    transition:transform .5s var(--ease-cine); z-index:-1;
  }
  #enter-btn:hover{ border-color:var(--brass); letter-spacing:.4em; }
  #enter-btn:hover::before{ transform:translateY(0); }
  #gate-hint{ margin-top:22px; font-family:var(--f-mono); font-size:.6rem; letter-spacing:.2em; color:var(--smoke); opacity:0; }

  /* =============== 01 REVEAL =============== */
  #reveal{ display:flex; align-items:center; justify-content:center; overflow:hidden; }
  #reveal .stage{ perspective:1400px; width:100%; height:78vh; position:relative; }
  #reveal .frame{ position:absolute; width:min(30vw,300px); height:min(40vw,400px); top:50%; left:50%; }
  #reveal .cap{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center; z-index:3; pointer-events:none; }
  #reveal .cap .display{ font-size:clamp(2.4rem,7vw,5rem); }
  #reveal .cap span{ display:block; overflow:hidden; }

  /* =============== 02 NAMES =============== */
  #names{ min-height:180vh; }
  #names .pin{ height:100vh; display:flex; align-items:center; justify-content:center; flex-direction:column; text-align:center; }
  #names .line{ font-family:var(--f-display); font-style:italic; font-size:clamp(1.5rem,4.4vw,2.6rem); color:var(--bone-dim); position:absolute; opacity:0; }
  #names .names{ font-family:var(--f-display); font-size:clamp(3rem,11vw,8.5rem); display:flex; gap:.28em; opacity:0; }
  #names .names .amp{ color:var(--brass); font-style:italic; }
  #names .char{ display:inline-block; }
  #names .sub{ margin-top:22px; opacity:0; }

  /* =============== 03 CAROUSEL =============== */
  #carousel{ min-height:260vh; background:var(--ink-2); }
  #carousel .pin{ height:100vh; overflow:hidden; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  #carousel .head{ text-align:center; margin-bottom:6vh; }
  #carousel .head .display{ font-size:clamp(1.8rem,5vw,3rem); margin-top:10px; }
  #carousel .ring-wrap{ perspective:1800px; width:100%; }
  #carousel .ring{ position:relative; width:1px; height:min(38vw,340px); margin:0 auto; transform-style:preserve-3d; }
  #carousel .ring .frame{ position:absolute; width:min(20vw,190px); height:min(32vw,300px); top:50%; left:50%; margin:calc(min(32vw,300px) / -2) 0 0 calc(min(20vw,190px) / -2); transition:filter .3s; }

  /* =============== 04 STORY =============== */
  #story{ min-height:340vh; }
  #story .pin{ height:100vh; display:flex; align-items:center; overflow:hidden; }
  #story .beat{ position:absolute; inset:0; display:flex; align-items:center; opacity:0; }
  #story .beat .txt{ width:var(--container); margin:0 auto; position:relative; z-index:2; }
  #story .beat .txt .eyebrow{ display:block; margin-bottom:14px; }
  #story .beat .txt .display{ font-size:clamp(2rem,6vw,4rem); max-width:12ch; }
  #story .beat .frame{ position:absolute; width:min(34vw,360px); height:min(24vw,260px); right:8%; top:50%; transform:translateY(-50%); z-index:1; }
  #story .beat:nth-child(even) .frame{ right:auto; left:8%; }

  /* =============== 05 GALLERY =============== */
  #gallery{ background:var(--ink-2); }
  #gallery .head{ text-align:center; margin-bottom:8vh; }
  #gallery .head .display{ font-size:clamp(1.8rem,5vw,3rem); margin-top:10px; }
  #gallery .grid{
    width:var(--container); margin:0 auto; display:grid;
    grid-template-columns:repeat(6,1fr); gap:14px; grid-auto-rows:110px;
  }
  #gallery .grid .frame{ cursor:pointer; opacity:0; transform:translateY(40px); }
  #gallery .g1{ grid-column:1/4; grid-row:1/5; }
  #gallery .g2{ grid-column:4/7; grid-row:1/3; }
  #gallery .g3{ grid-column:4/6; grid-row:3/6; }
  #gallery .g4{ grid-column:6/7; grid-row:3/5; }
  #gallery .g5{ grid-column:1/3; grid-row:5/8; }
  #gallery .g6{ grid-column:3/5; grid-row:6/8; }
  #gallery .g7{ grid-column:5/7; grid-row:5/8; }
  #gallery .full{ width:100%; height:88vh; margin-top:16px; position:relative; opacity:0; transform:scale(1.06); }
  #gallery .full .display{ position:absolute; bottom:40px; left:6%; font-size:clamp(1.6rem,4.4vw,2.8rem); z-index:2; max-width:14ch; }
  #lightbox{ position:fixed; inset:0; z-index:90; background:rgba(9,7,6,.9); display:none; align-items:center; justify-content:center; }
  #lightbox.open{ display:flex; }
  #lightbox .frame{ width:min(70vw,640px); height:min(70vh,640px); }
  #lightbox .close{ position:absolute; top:26px; right:34px; font-family:var(--f-mono); letter-spacing:.2em; font-size:.7rem; color:var(--bone-dim); }

  /* =============== 06 MOMENT =============== */
  #moment{ min-height:220vh; padding:0; }
  #moment .pin{ height:100vh; position:relative; overflow:hidden; }
  #moment .frame{ position:absolute; inset:0; border:0; }
  #moment .frame{ transform:scale(1); }
  #moment canvas{ position:absolute; inset:0; width:100%; height:100%; z-index:2; }
  #moment .cap{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center; z-index:3; }
  #moment .cap .display{ font-size:clamp(1.9rem,6vw,3.6rem); max-width:14ch; opacity:0; }

  /* =============== 07 LETTER =============== */
  #letter{ min-height:260vh; background:var(--ink-2); }
  #letter .pin{ height:100vh; display:flex; align-items:center; justify-content:center; }
  #letter .box{ width:min(70vw,640px); text-align:center; }
  #letter .ln{ font-family:var(--f-body); font-style:italic; font-size:clamp(1.3rem,3.4vw,2rem); color:var(--bone-dim); opacity:0; margin-bottom:.4em; filter:blur(6px); }
  #letter .sig{ margin-top:34px; opacity:0; }

  /* =============== 08 BOTANICAL =============== */
  #botanical{ min-height:220vh; overflow:hidden; }
  #botanical .pin{ height:100vh; position:relative; display:flex; align-items:center; justify-content:center; text-align:center; overflow:hidden; }
  #botanical canvas{ position:absolute; inset:0; width:100%; height:100%; }
  #botanical .cap{ position:relative; z-index:2; }
  #botanical .cap .display{ font-size:clamp(2rem,6vw,3.6rem); }
  #botanical .petal{ position:absolute; width:16px; height:22px; border-radius:70% 10% 70% 10%; background:var(--rose); opacity:.5; will-change:transform; }

  /* =============== 09 DATE =============== */
  #date{ min-height:220vh; }
  #date .pin{ height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }
  #date .save{ opacity:0; margin-bottom:24px; }
  #date .num{ font-family:var(--f-display); font-size:clamp(4rem,20vw,13rem); line-height:.9; opacity:0; letter-spacing:.02em; }
  #date .final{ margin-top:26px; opacity:0; }

  /* =============== 10 DETAILS =============== */
  #details .head{ text-align:center; margin-bottom:8vh; }
  #details .head .display{ font-size:clamp(1.8rem,5vw,3rem); margin-top:10px; }
  #details .grid{ width:var(--container); margin:0 auto; display:grid; grid-template-columns:repeat(3,1fr); border-top:1px solid var(--line); }
  #details .item{ padding:38px 26px; border-bottom:1px solid var(--line); border-right:1px solid var(--line); opacity:0; transform:translateY(24px); }
  #details .item:nth-child(3n){ border-right:0; }
  #details .item .display{ font-size:clamp(1.3rem,2.6vw,1.7rem); margin-top:12px; }
  #details .item p.detail{ margin-top:8px; color:var(--bone-dim); font-size:.92rem; }

  /* =============== 11 TIMELINE =============== */
  #timeline{ background:var(--ink-2); }
  #timeline .head{ text-align:center; margin-bottom:8vh; }
  #timeline .head .display{ font-size:clamp(1.8rem,5vw,3rem); margin-top:10px; }
  #timeline .rail-wrap{ width:var(--container); margin:0 auto; position:relative; padding-left:40px; }
  #timeline .spine{ position:absolute; left:0; top:6px; bottom:6px; width:1px; background:var(--line); }
  #timeline .spine i{ position:absolute; left:0; top:0; width:1px; background:var(--brass); height:0; }
  #timeline .ev{ position:relative; padding:34px 0; opacity:.4; transition:opacity .4s; }
  #timeline .ev.active{ opacity:1; }
  #timeline .ev::before{ content:''; position:absolute; left:-40px; top:40px; width:7px; height:7px; margin-left:-3px; border-radius:50%; background:var(--ink-2); border:1px solid var(--brass-soft); transition:background .3s, box-shadow .3s; }
  #timeline .ev.active::before{ background:var(--brass); box-shadow:0 0 0 5px rgba(173,138,85,.16); }
  #timeline .ev .time{ font-family:var(--f-mono); font-size:.72rem; letter-spacing:.2em; color:var(--brass); }
  #timeline .ev .display{ font-size:clamp(1.6rem,3.6vw,2.4rem); margin-top:8px; transition:font-size .35s; }
  #timeline .ev.active .display{ font-size:clamp(1.9rem,4.4vw,2.9rem); }
  #timeline .ev p.detail{ margin-top:6px; color:var(--bone-dim); max-width:44ch; }

  /* =============== 12 LOCATION =============== */
  #location .head{ text-align:center; margin-bottom:6vh; }
  #location .head .display{ font-size:clamp(1.8rem,5vw,3rem); margin-top:10px; }
  #location .stage{ width:var(--container); margin:0 auto; position:relative; height:56vh; border:1px solid var(--line-strong); overflow:hidden; }
  #location canvas{ width:100%; height:100%; }
  #location .venue{ width:var(--container); margin:34px auto 0; display:flex; justify-content:space-between; align-items:flex-end; gap:24px; flex-wrap:wrap; }
  #location .venue .display{ font-size:clamp(1.5rem,3.4vw,2.2rem); }
  #location .venue address{ font-style:normal; color:var(--bone-dim); margin-top:8px; }
  #location .open{ font-family:var(--f-mono); font-size:.68rem; letter-spacing:.24em; text-transform:uppercase; border-bottom:1px solid var(--brass-soft); padding-bottom:4px; white-space:nowrap; transition:border-color .3s,color .3s; }
  #location .open:hover{ border-color:var(--brass); color:var(--brass); }

  /* =============== 13 RSVP =============== */
  #rsvp{ background:var(--ink-2); }
  #rsvp .wrap{ text-align:center; }
  #rsvp .display{ font-size:clamp(2rem,6vw,3.6rem); margin-bottom:6vh; }
  #rsvp .opts{ display:flex; justify-content:center; gap:16px; flex-wrap:wrap; }
  #rsvp .opt{ padding:20px 30px; border:1px solid var(--line-strong); font-family:var(--f-mono); font-size:.72rem; letter-spacing:.2em; text-transform:uppercase; transition:border-color .3s,background .3s,transform .3s; }
  #rsvp .opt:hover{ border-color:var(--brass); transform:translateY(-2px); }
  #rsvp .opt.chosen{ background:var(--wine); border-color:var(--wine); }
  #rsvp .reply{ margin-top:40px; opacity:0; height:0; overflow:hidden; }
  #rsvp .reply .display{ font-size:clamp(1.4rem,3.4vw,2rem); margin-bottom:0; }
  #rsvp form{ margin-top:26px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
  #rsvp input{ background:transparent; border:0; border-bottom:1px solid var(--line-strong); color:var(--bone); padding:10px 4px; font-family:var(--f-body); font-style:italic; text-align:center; width:220px; }
  #rsvp input:focus{ outline:none; border-color:var(--brass); }
  #rsvp input::placeholder{ color:var(--smoke); }
  #rsvp .submit{ font-family:var(--f-mono); font-size:.66rem; letter-spacing:.2em; text-transform:uppercase; border-bottom:1px solid var(--brass); padding-bottom:2px; }
  #rsvp .status{ margin-top:18px; min-height:1em; color:var(--brass); }

  /* =============== 14 FINALE =============== */
  #finale{ min-height:200vh; }
  #finale .pin{ height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; overflow:hidden; position:relative; }
  #finale .row{ display:flex; gap:12px; margin-bottom:6vh; opacity:0; }
  #finale .row .frame{ width:min(11vw,120px); height:min(15vw,160px); }
  #finale .line1{ opacity:0; }
  #finale .names2{ font-family:var(--f-display); font-size:clamp(2.6rem,8vw,5.5rem); margin-top:16px; opacity:0; }
  #finale .names2 .amp{ color:var(--brass); font-style:italic; }
  #finale .curtain{ position:absolute; inset:0; background:var(--ink); opacity:0; z-index:9; pointer-events:none; }
  #finale .credit{ position:absolute; bottom:26px; font-family:var(--f-mono); font-size:.6rem; letter-spacing:.2em; color:var(--smoke); opacity:0; z-index:10; }

  /* generic reveal helpers used by JS/GSAP */
  .u-up{ opacity:0; transform:translateY(28px); }

  @media (max-width:860px){
    :root{ --container:92vw; }
    #reveal .frame{ width:52vw; height:68vw; }
    #carousel .ring{ height:52vw; }
    #carousel .ring .frame{ width:38vw; height:56vw; margin:calc(56vw / -2) 0 0 calc(38vw / -2); }
    #story .beat{ flex-direction:column; justify-content:center; padding:0 6vw; }
    #story .beat .frame{ position:relative; inset:auto; right:auto; left:auto; top:auto; transform:none; width:74vw; height:52vw; margin-top:26px; }
    #story .beat:nth-child(even) .frame{ left:auto; }
    #gallery .grid{ grid-template-columns:repeat(2,1fr); grid-auto-rows:150px; }
    #gallery .g1{ grid-column:1/3; grid-row:1/3; }
    #gallery .g2{ grid-column:1/2; grid-row:3/5; }
    #gallery .g3{ grid-column:2/3; grid-row:3/4; }
    #gallery .g4{ grid-column:2/3; grid-row:4/5; }
    #gallery .g5{ grid-column:1/2; grid-row:5/7; }
    #gallery .g6{ grid-column:2/3; grid-row:5/6; }
    #gallery .g7{ grid-column:2/3; grid-row:6/7; }
    #details .grid{ grid-template-columns:1fr; }
    #details .item{ border-right:0 !important; }
    #location .venue{ flex-direction:column; align-items:flex-start; }
    #finale .row{ display:none; }
    #rail{ display:none; }
  }
</style>

<canvas id="grain"></canvas>
<div id="vignette"></div>
<nav id="rail" aria-hidden="true"></nav>
<button id="sound-toggle" aria-pressed="false">
  <span class="bars"><i></i><i></i><i></i></span>
  <span class="label">Sound</span>
</button>

<div id="gate" class="gate">
  <canvas id="gate-canvas"></canvas>
  <div id="leader-count">5</div>
  <div id="gate-main">
    <div class="glow-point"></div>
    <span class="eyebrow">An Invitation</span>
    <h1>to a story worth remembering.</h1>
    <button id="enter-btn">Enter Experience</button>
    <div id="gate-hint">turn your sound on — best with volume up</div>
  </div>
</div>

<main id="film">

  <!-- 01 — REVEAL -->
  <section class="reel" id="reveal" data-scene="01" data-tc="00:00:08:12">
    <div class="stage">
      <div class="frame tone-wine" data-depth="1">@if($frameImg(0))<img src="{{ $frameImg(0) }}" alt="" loading="lazy">@endif<span class="mono">Frame 014</span></div>
      <div class="frame tone-ink" data-depth="2">@if($frameImg(1))<img src="{{ $frameImg(1) }}" alt="" loading="lazy">@endif<span class="mono">Frame 022</span></div>
      <div class="frame tone-brass" data-depth="3">@if($frameImg(2))<img src="{{ $frameImg(2) }}" alt="" loading="lazy">@endif<span class="mono">Frame 031</span></div>
      <div class="cap">
        <h2 class="display"><span>A story begins</span></h2>
      </div>
    </div>
  </section>

  <!-- 02 — NAMES -->
  <section class="reel" id="names" data-scene="02" data-tc="00:00:19:00">
    <div class="pin">
      <p class="line" id="ln1">Two lives.</p>
      <p class="line" id="ln2">One unexpected meeting.</p>
      <p class="line" id="ln3">One beautiful story.</p>
      <h2 class="names" id="names-final"><span class="a">{{ mb_strtoupper($brideName) }}</span><span class="amp">&amp;</span><span class="b">{{ mb_strtoupper($groomName) }}</span></h2>
      <p class="sub eyebrow" id="names-sub">{{ $eventDate ? $eventDate->format('F jS · Y') : 'December Twelfth · Two Thousand Twenty-Six' }}</p>
    </div>
  </section>

  <!-- 03 — CAROUSEL -->
  <section class="reel" id="carousel" data-scene="03" data-tc="00:00:34:18">
    <div class="pin">
      <div class="head"><span class="eyebrow">Chapter Three</span><h2 class="display">Frames from along the way</h2></div>
      <div class="ring-wrap"><div class="ring" id="ring"></div></div>
    </div>
  </section>

  <!-- 04 — STORY -->
  <section class="reel" id="story" data-scene="04" data-tc="00:00:52:05">
    <div class="pin" id="story-pin">
      <div class="beat" id="beat1">
        <div class="txt"><span class="eyebrow">It started with</span><h2 class="display">A single ordinary Tuesday.</h2></div>
        <div class="frame tone-ink">@if($frameImg(3))<img src="{{ $frameImg(3) }}" alt="" loading="lazy">@endif<span class="mono">Cambridge, 2019</span></div>
      </div>
      <div class="beat" id="beat2">
        <div class="txt"><span class="eyebrow">Then another</span><h2 class="display">Coffee that turned into hours.</h2></div>
        <div class="frame tone-wine">@if($frameImg(4))<img src="{{ $frameImg(4) }}" alt="" loading="lazy">@endif<span class="mono">Marseille, 2020</span></div>
      </div>
      <div class="beat" id="beat3">
        <div class="txt"><span class="eyebrow">And somehow</span><h2 class="display">Two cities became one address.</h2></div>
        <div class="frame tone-brass">@if($frameImg(5))<img src="{{ $frameImg(5) }}" alt="" loading="lazy">@endif<span class="mono">Lisbon, 2022</span></div>
      </div>
      <div class="beat" id="beat4">
        <div class="txt"><span class="eyebrow">Until, quietly</span><h2 class="display">Two separate stories became one.</h2></div>
        <div class="frame tone-rose">@if($frameImg(6))<img src="{{ $frameImg(6) }}" alt="" loading="lazy">@endif<span class="mono">Kyoto, 2024</span></div>
      </div>
    </div>
  </section>

  <!-- 05 — GALLERY -->
  <section class="reel" id="gallery" data-scene="05" data-tc="00:01:15:20">
    <div class="head"><span class="eyebrow">Chapter Five</span><h2 class="display">A wall of memory</h2></div>
    <div class="grid">
      <div class="frame tone-wine g1">@if($frameImg(7))<img src="{{ $frameImg(7) }}" alt="" loading="lazy">@endif<span class="mono">01</span></div>
      <div class="frame tone-ink g2">@if($frameImg(8))<img src="{{ $frameImg(8) }}" alt="" loading="lazy">@endif<span class="mono">02</span></div>
      <div class="frame tone-brass g3">@if($frameImg(9))<img src="{{ $frameImg(9) }}" alt="" loading="lazy">@endif<span class="mono">03</span></div>
      <div class="frame tone-rose g4">@if($frameImg(10))<img src="{{ $frameImg(10) }}" alt="" loading="lazy">@endif<span class="mono">04</span></div>
      <div class="frame tone-ink g5">@if($frameImg(11))<img src="{{ $frameImg(11) }}" alt="" loading="lazy">@endif<span class="mono">05</span></div>
      <div class="frame tone-wine g6">@if($frameImg(12))<img src="{{ $frameImg(12) }}" alt="" loading="lazy">@endif<span class="mono">06</span></div>
      <div class="frame tone-brass g7">@if($frameImg(13))<img src="{{ $frameImg(13) }}" alt="" loading="lazy">@endif<span class="mono">07</span></div>
    </div>
    <div class="full frame tone-wine">@if($frameImg(14))<img src="{{ $frameImg(14) }}" alt="" loading="lazy">@endif<h3 class="display">And this is where the forever begins.</h3></div>
  </section>

  <div id="lightbox"><button class="close">Close ✕</button><div class="frame tone-wine" id="lightbox-frame"></div></div>

  <!-- 06 — MOMENT -->
  <section class="reel" id="moment" data-scene="06" data-tc="00:01:38:02">
    <div class="pin">
      <div class="frame tone-ink">@if($frameImg(15))<img src="{{ $frameImg(15) }}" alt="" loading="lazy">@endif</div>
      <canvas id="moment-canvas"></canvas>
      <div class="cap"><h2 class="display">And this is where<br>the forever begins.</h2></div>
    </div>
  </section>

  <!-- 07 — LETTER -->
  <section class="reel" id="letter" data-scene="07" data-tc="00:01:52:14">
    <div class="pin">
      <div class="box">
        @foreach ($letterLines as $line)
          <p class="ln">{{ $line }}</p>
        @endforeach
        <p class="sig eyebrow">— {{ $signature }}</p>
      </div>
    </div>
  </section>

  <!-- 08 — BOTANICAL -->
  <section class="reel" id="botanical" data-scene="08" data-tc="00:02:07:09">
    <div class="pin" id="botanical-pin">
      <canvas id="botanical-canvas"></canvas>
      <div class="cap"><span class="eyebrow">Chapter Eight</span><h2 class="display">Grown slowly, on purpose</h2></div>
    </div>
  </section>

  <!-- 09 — DATE -->
  <section class="reel" id="date" data-scene="09" data-tc="00:02:24:00">
    <div class="pin">
      <p class="save eyebrow">Save this moment</p>
      <h2 class="num" id="date-num">{{ $eventDate ? $eventDate->day : '12' }}</h2>
      <p class="final eyebrow">The day we begin forever</p>
    </div>
  </section>

  <!-- 10 — DETAILS -->
  <section class="reel" id="details" data-scene="10" data-tc="00:02:41:16">
    <div class="head"><span class="eyebrow">Chapter Ten</span><h2 class="display">The particulars</h2></div>
    <div class="grid">
      <div class="item"><span class="eyebrow">Ceremony</span><h3 class="display">{{ $ceremonyTime }} in the evening</h3><p class="detail">The Glasshouse Chapel, under the west skylight</p></div>
      <div class="item"><span class="eyebrow">Reception</span><h3 class="display">{{ $receptionTime }} in the evening</h3><p class="detail">The Conservatory Lawn, weather permitting</p></div>
      <div class="item"><span class="eyebrow">Dress Code</span><h3 class="display">Black tie, softened</h3><p class="detail">Evening tones — champagne, ink, wine, brass</p></div>
      <div class="item"><span class="eyebrow">Date</span><h3 class="display">{{ $eventDate ? $eventDate->format('j F Y') : '12 December 2026' }}</h3><p class="detail">{{ $eventDate ? $eventDate->format('l') : 'A Saturday' }}</p></div>
      <div class="item"><span class="eyebrow">Venue</span><h3 class="display">{{ $venueName }}</h3><p class="detail">{{ $venueAddress ?? '14 Rue des Lilas, Marseille' }}</p></div>
      <div class="item"><span class="eyebrow">RSVP By</span><h3 class="display">1 November 2026</h3><p class="detail">Kindly reply below</p></div>
    </div>
  </section>

  <!-- 11 — TIMELINE -->
  <section class="reel" id="timeline" data-scene="11" data-tc="00:02:58:03">
    <div class="head"><span class="eyebrow">Chapter Eleven</span><h2 class="display">How the evening unfolds</h2></div>
    <div class="rail-wrap">
      <div class="spine"><i id="spine-fill"></i></div>
      @foreach ($schedule as $row)
        <div class="ev"><span class="time">{{ $row['time'] ?? '' }}</span><h3 class="display">{{ $row['label'] ?? '' }}</h3></div>
      @endforeach
    </div>
  </section>

  <!-- 12 — LOCATION -->
  <section class="reel" id="location" data-scene="12" data-tc="00:03:16:19">
    <div class="head"><span class="eyebrow">Meet us here</span><h2 class="display">{{ $venueName }}</h2></div>
    <div class="stage"><canvas id="map-canvas"></canvas></div>
    <div class="venue">
      <div><h3 class="display">{{ $venueName }}</h3><address>{{ $venueAddress ?? 'Marseille, France · 13006' }}</address></div>
      <a class="open" href="https://maps.google.com/?q={{ urlencode($venueAddress ?? $venueName) }}" target="_blank" rel="noopener">Open Location →</a>
    </div>
  </section>

  <!-- 13 — RSVP -->
  <section class="reel" id="rsvp" data-scene="13" data-tc="00:03:33:07">
    <div class="wrap">
      <span class="eyebrow">Chapter Thirteen</span>
      <h2 class="display">Will you join us?</h2>
      <div class="opts">
        <button class="opt" data-r="yes">Yes, I'll be there</button>
        <button class="opt" data-r="maybe">Maybe</button>
        <button class="opt" data-r="no">Sorry, I can't</button>
      </div>
      <div class="reply" id="rsvp-reply">
        <h3 class="display" id="rsvp-msg"></h3>
        <form id="rsvp-form">
          <input type="text" placeholder="Your name" required>
          <button class="submit" type="submit">Confirm →</button>
        </form>
        <p class="status eyebrow" id="rsvp-status"></p>
      </div>
    </div>
  </section>

  <!-- 14 — FINALE -->
  <section class="reel" id="finale" data-scene="14" data-tc="00:03:50:00">
    <div class="pin">
      <div class="row">
        <div class="frame tone-wine">@if($frameImg(16))<img src="{{ $frameImg(16) }}" alt="" loading="lazy">@endif</div>
        <div class="frame tone-ink">@if($frameImg(17))<img src="{{ $frameImg(17) }}" alt="" loading="lazy">@endif</div>
        <div class="frame tone-brass">@if($frameImg(18))<img src="{{ $frameImg(18) }}" alt="" loading="lazy">@endif</div>
        <div class="frame tone-rose">@if($frameImg(19))<img src="{{ $frameImg(19) }}" alt="" loading="lazy">@endif</div>
      </div>
      <p class="line1 eyebrow">We can't wait to celebrate with you</p>
      <h2 class="names2">{{ mb_strtoupper($brideName) }} <span class="amp">♥</span> {{ mb_strtoupper($groomName) }}</h2>
      <div class="curtain"></div>
      <p class="credit">Private Screening — Reel 01 of 01 — Fin.</p>
    </div>
  </section>

</main>

<script>
  window.__PS_GALLERY__ = @json($galleryUrls->values());
  window.__PS_DATE_STEPS__ = @json($dateSteps);
  window.__PS_RSVP__ = {
    enabled: @json($trackRsvp && $rsvpUrl),
    url: @json($rsvpUrl),
    token: document.querySelector('meta[name=csrf-token]')?.content ?? '',
  };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/MotionPathPlugin.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Flip.min.js"></script>
<script>
(function(){
  "use strict";
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  gsap.registerPlugin(ScrollTrigger, MotionPathPlugin, Flip);

  /* ---------------- shared: grain texture as a data URI ---------------- */
  function makeGrainURI(size){
    var c=document.createElement('canvas'); c.width=size; c.height=size;
    var x=c.getContext('2d'); var id=x.createImageData(size,size);
    for(var i=0;i<id.data.length;i+=4){
      var v=Math.random()*255;
      id.data[i]=v; id.data[i+1]=v; id.data[i+2]=v; id.data[i+3]=28;
    }
    x.putImageData(id,0,0);
    return 'url('+c.toDataURL()+')';
  }
  var grainURI = makeGrainURI(140);
  document.documentElement.style.setProperty('--grain-uri', grainURI);

  /* full-screen animated grain (fixed overlay) */
  (function grainField(){
    var canvas=document.getElementById('grain');
    var ctx=canvas.getContext('2d');
    function size(){ canvas.width=innerWidth; canvas.height=innerHeight; }
    size(); addEventListener('resize', size);
    if(reduced) return;
    var frame=0;
    function draw(){
      frame++;
      if(frame % 2 === 0){
        var w=canvas.width, h=canvas.height;
        var id=ctx.createImageData(w,h);
        for(var i=0;i<id.data.length;i+=4){
          var v = Math.random()*255;
          id.data[i]=v; id.data[i+1]=v; id.data[i+2]=v; id.data[i+3]= Math.random()<0.5?10:0;
        }
        ctx.putImageData(id,0,0);
      }
      requestAnimationFrame(draw);
    }
    requestAnimationFrame(draw);
  })();

  /* ---------------- gate: leader countdown + dust + enter ---------------- */
  (function gate(){
    var gateEl=document.getElementById('gate');
    var countEl=document.getElementById('leader-count');
    var mainEl=document.getElementById('gate-main');
    var canvas=document.getElementById('gate-canvas');
    var ctx=canvas.getContext('2d');
    function size(){ canvas.width=innerWidth; canvas.height=innerHeight; }
    size(); addEventListener('resize', size);

    var dust=[]; var n = matchMedia('(max-width:700px)').matches ? 26 : 60;
    for(var i=0;i<n;i++){
      dust.push({ x:Math.random()*innerWidth, y:Math.random()*innerHeight, r:Math.random()*1.6+.3, s:Math.random()*.3+.05, o:Math.random()*.5+.1 });
    }
    var speed=1;
    function draw(){
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.fillStyle='rgba(243,234,214,1)';
      dust.forEach(function(p){
        p.y -= p.s*speed; p.x += Math.sin(p.y*0.01)*0.15*speed;
        if(p.y<-10){ p.y=innerHeight+10; p.x=Math.random()*innerWidth; }
        ctx.globalAlpha=p.o;
        ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fill();
      });
      requestAnimationFrame(draw);
    }
    requestAnimationFrame(draw);

    var seq=gsap.timeline({ delay:.2 });
    if(!reduced){
      ["5","4","3","2","1"].forEach(function(n){
        seq.call(function(){ countEl.textContent = n; })
           .fromTo(countEl,{ opacity:0, scale:1.3 },{ opacity:1, scale:1, duration:.28, ease:'power2.out' })
           .to(countEl,{ opacity:0, duration:.2 }, '+=.12');
      });
    } else { seq.set(countEl,{ opacity:0 }); }
    seq.set(countEl,{ display:'none' })
       .to(mainEl,{ opacity:1, duration:1.1, ease:'power2.out' });

    var entered=false;
    document.getElementById('enter-btn').addEventListener('click', function(){
      if(entered) return; entered=true;
      startAmbient();
      speed=6;
      var tl=gsap.timeline({ onComplete:function(){
        gateEl.classList.add('done');
        document.getElementById('rail').classList.add('visible');
        document.getElementById('sound-toggle').classList.add('visible');
        ScrollTrigger.refresh();
        playReveal();
      }});
      tl.to('#enter-btn',{ letterSpacing:'.5em', duration:.3 })
        .to(mainEl,{ scale:1.06, filter:'blur(14px)', opacity:0, duration:.9, ease:'power2.in' },0)
        .to(canvas,{ opacity:0, duration:.9 },0)
        .to(gateEl,{ opacity:0, duration:.5 }, '-=.3');
    });
    gsap.to('#gate-hint',{ opacity:1, delay:2.6, duration:1 });
  })();

  /* ---------------- ambient generated sound ---------------- */
  var audioCtx=null, master=null, playing=false, ambientNodes=[];
  function startAmbient(){
    try{
      audioCtx = new (window.AudioContext||window.webkitAudioContext)();
      master = audioCtx.createGain(); master.gain.value=0; master.connect(audioCtx.destination);
      var freqs=[110,164.81,220,277.18];
      freqs.forEach(function(f,i){
        var o=audioCtx.createOscillator(); o.type= i%2? 'sine':'triangle'; o.frequency.value=f;
        var g=audioCtx.createGain(); g.gain.value = 0.05/(i+1);
        var lfo=audioCtx.createOscillator(); lfo.frequency.value=.05+i*.02;
        var lfoGain=audioCtx.createGain(); lfoGain.gain.value=3;
        lfo.connect(lfoGain); lfoGain.connect(o.frequency);
        var filter=audioCtx.createBiquadFilter(); filter.type='lowpass'; filter.frequency.value=800;
        o.connect(filter); filter.connect(g); g.connect(master);
        o.start(); lfo.start();
        ambientNodes.push(o,lfo);
      });
      master.gain.linearRampToValueAtTime(0.5, audioCtx.currentTime+2.5);
      playing=true;
      document.getElementById('sound-toggle').classList.add('playing');
      document.getElementById('sound-toggle').setAttribute('aria-pressed','true');
    }catch(e){ /* audio unavailable — fail silent */ }
  }
  function whoosh(){
    if(!audioCtx) return;
    var bufferSize = audioCtx.sampleRate*0.4;
    var buffer = audioCtx.createBuffer(1,bufferSize,audioCtx.sampleRate);
    var data = buffer.getChannelData(0);
    for(var i=0;i<bufferSize;i++){ data[i]=(Math.random()*2-1)*(1-i/bufferSize); }
    var src=audioCtx.createBufferSource(); src.buffer=buffer;
    var f=audioCtx.createBiquadFilter(); f.type='bandpass'; f.frequency.value=500;
    var g=audioCtx.createGain(); g.gain.value=0.08;
    src.connect(f); f.connect(g); g.connect(audioCtx.destination);
    src.start();
  }
  document.getElementById('sound-toggle').addEventListener('click', function(){
    if(!audioCtx){ startAmbient(); return; }
    playing = !playing;
    master.gain.linearRampToValueAtTime(playing?0.5:0, audioCtx.currentTime+.6);
    this.classList.toggle('playing', playing);
    this.setAttribute('aria-pressed', String(playing));
  });

  /* ---------------- rail: scene progress dots ---------------- */
  var scenes = Array.prototype.slice.call(document.querySelectorAll('.reel'));
  var rail = document.getElementById('rail');
  scenes.forEach(function(s,i){
    var t=document.createElement('div'); t.className='tick'; t.dataset.i=i;
    t.addEventListener('click', function(){ s.scrollIntoView({behavior: reduced?'auto':'smooth'}); });
    rail.appendChild(t);
  });
  var ticks = rail.querySelectorAll('.tick');

  /* ---------------- inject chrome (scene no. / timecode / corner ticks) ---------------- */
  scenes.forEach(function(s){
    var c=document.createElement('div'); c.className='chrome';
    c.innerHTML =
      '<span class="scene">SCENE '+ s.dataset.scene +'</span>'+
      '<span class="tc">'+ s.dataset.tc +'</span>'+
      '<i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>';
    s.appendChild(c);
    ScrollTrigger.create({
      trigger:s, start:'top 55%', end:'bottom 45%',
      onToggle:function(self){
        if(self.isActive){
          ticks.forEach(function(t){ t.classList.remove('active'); });
          var idx=scenes.indexOf(s);
          if(ticks[idx]) ticks[idx].classList.add('active');
        }
      }
    });
  });

  function playReveal(){ /* triggers ScrollTrigger.refresh already called; reveal scene plays on natural scroll */ }

  /* ================= 01 REVEAL ================= */
  (function(){
    var frames = document.querySelectorAll('#reveal .frame');
    gsap.set(frames[0], { xPercent:-140, rotateY:-30, z:-200, opacity:0 });
    gsap.set(frames[1], { xPercent:0, rotateY:0, z:100, opacity:0, scale:1.1 });
    gsap.set(frames[2], { xPercent:140, rotateY:30, z:-200, opacity:0 });
    gsap.set('#reveal .cap span', { yPercent:110 });

    ScrollTrigger.create({
      trigger:'#reveal', start:'top top', end:'+=120%', pin:true, scrub:1,
      animation: gsap.timeline()
        .to(frames[0], { xPercent:-34, rotateY:-14, z:0, opacity:1, duration:1 },0)
        .to(frames[2], { xPercent:34, rotateY:14, z:0, opacity:1, duration:1 },0)
        .to(frames[1], { opacity:1, scale:1, duration:1 },0)
        .to('#reveal .cap span', { yPercent:0, duration:.6 }, .3)
        .to(frames.length? frames:[], { filter:'blur(2px)', opacity:.5, duration:.5 }, 1.1)
    });
  })();

  /* ================= 02 NAMES ================= */
  (function(){
    function splitChars(sel){
      var el=document.querySelector(sel);
      var text=el.textContent;
      el.innerHTML='';
      text.split('').forEach(function(ch){
        var span=document.createElement('span'); span.className='char';
        span.textContent = ch===' ' ? ' ' : ch;
        span.style.opacity=0;
        span.style.display='inline-block';
        span.style.transform='translateY(40px) rotateX(45deg)';
        el.appendChild(span);
      });
    }
    splitChars('#names-final .a');
    splitChars('#names-final .b');

    var tl=gsap.timeline({
      scrollTrigger:{ trigger:'#names', start:'top top', end:'+=100%', pin:true, scrub:1 }
    });
    tl.to('#ln1',{ opacity:1, duration:.6 })
      .to('#ln1',{ opacity:0, y:-16, duration:.5 }, '+=.3')
      .to('#ln2',{ opacity:1, duration:.6 })
      .to('#ln2',{ opacity:0, y:-16, duration:.5 }, '+=.3')
      .to('#ln3',{ opacity:1, duration:.6 })
      .to('#ln3',{ opacity:0, y:-16, duration:.5 }, '+=.3')
      .to('#names-final', { opacity:1, duration:.2 })
      .to('#names-final .char', { opacity:1, y:0, rotateX:0, duration:.7, stagger:.035, ease:'power3.out' },'<')
      .to('#names-final .amp', { opacity:1, duration:.4 }, '<')
      .to('#names-sub', { opacity:1, duration:.6 }, '+=.1');
  })();

  /* ================= 03 CAROUSEL ================= */
  (function(){
    var ring=document.getElementById('ring');
    var tones=['tone-wine','tone-ink','tone-brass','tone-rose','tone-wine','tone-ink','tone-brass','tone-rose'];
    var labels=['First Trip','That Winter','The Proposal','Sunday Mornings','Her Family','His Family','The Rehearsal','Just Us'];
    var gallery=window.__PS_GALLERY__ || [];
    var count=tones.length;
    var radius = matchMedia('(max-width:700px)').matches ? 260 : 420;
    var cards=[];
    for(var i=0;i<count;i++){
      var f=document.createElement('div');
      f.className='frame '+tones[i];
      if(gallery.length){
        var im=document.createElement('img');
        im.src=gallery[i % gallery.length]; im.alt=''; im.loading='lazy';
        f.appendChild(im);
      }
      var mono=document.createElement('span'); mono.className='mono'; mono.textContent=labels[i];
      f.appendChild(mono);
      var angle = (360/count)*i;
      f.style.transform='rotateY('+angle+'deg) translateZ('+radius+'px)';
      f.dataset.angle=angle;
      ring.appendChild(f);
      cards.push(f);
    }
    gsap.timeline({
      scrollTrigger:{ trigger:'#carousel', start:'top top', end:'+=180%', pin:true, scrub:1 }
    }).to(ring, { rotateY:-360, ease:'none' });

    gsap.ticker.add(function(){
      var ry = gsap.getProperty(ring,'rotateY') || 0;
      cards.forEach(function(c){
        var a = ((parseFloat(c.dataset.angle) + ry) % 360 + 360) % 360;
        var front = 1 - Math.min(Math.abs(a>180?360-a:a),90)/90;
        gsap.set(c, { filter:'blur('+ (1-front)*4 +'px)', opacity: .4 + front*.6 });
      });
    });
  })();

  /* ================= 04 STORY ================= */
  (function(){
    var beats=document.querySelectorAll('#story .beat');
    var tl=gsap.timeline({
      scrollTrigger:{ trigger:'#story', start:'top top', end:'+=300%', pin:true, scrub:1 }
    });
    gsap.set(beats[0], { opacity:1 });
    gsap.set(beats[0].querySelector('.frame'), { opacity:1, x:0 });
    beats.forEach(function(b,i){
      var frame=b.querySelector('.frame');
      var txt=b.querySelector('.txt');
      if(i>0){
        tl.fromTo(b, { opacity:0 }, { opacity:1, duration:.4 })
          .fromTo(frame, { opacity:0, x: i%2?-60:60, rotateY: i%2? -20:20 }, { opacity:1, x:0, rotateY:0, duration:.6 }, '<')
          .fromTo(txt, { opacity:0, y:24 }, { opacity:1, y:0, duration:.6 }, '<0.1');
      }
      if(i<beats.length-1){
        tl.to(b, { opacity:0, duration:.4 }, '+=.5');
      }
    });
  })();

  /* ================= 05 GALLERY ================= */
  (function(){
    gsap.utils.toArray('#gallery .grid .frame').forEach(function(f,i){
      gsap.to(f, { opacity:1, y:0, duration:.8, ease:'power3.out',
        scrollTrigger:{ trigger:f, start:'top 90%' }, delay:(i%4)*.05 });
      gsap.to(f, { yPercent: (i%2? -8:8), ease:'none',
        scrollTrigger:{ trigger:f, start:'top bottom', end:'bottom top', scrub:1 } });
    });
    gsap.to('#gallery .full', { opacity:1, scale:1, duration:1.1, ease:'power3.out',
      scrollTrigger:{ trigger:'#gallery .full', start:'top 80%' } });

    var lightbox=document.getElementById('lightbox');
    var lbFrame=document.getElementById('lightbox-frame');
    document.querySelectorAll('#gallery .grid .frame').forEach(function(f){
      f.addEventListener('click', function(){
        var state=Flip.getState(f);
        lbFrame.className='frame '+(Array.prototype.find.call(f.classList,function(c){return c.indexOf('tone-')===0;})||'tone-wine');
        lightbox.classList.add('open');
        Flip.from(state, { target:lbFrame, duration:.6, ease:'power3.inOut', absolute:true });
      });
    });
    document.querySelector('#lightbox .close').addEventListener('click', function(){
      lightbox.classList.remove('open');
    });
    lightbox.addEventListener('click', function(e){ if(e.target===lightbox) lightbox.classList.remove('open'); });
  })();

  /* ================= 06 MOMENT ================= */
  (function(){
    var canvas=document.getElementById('moment-canvas');
    var ctx=canvas.getContext('2d');
    function size(){ canvas.width=canvas.offsetWidth; canvas.height=canvas.offsetHeight; }
    var motes=[];
    ScrollTrigger.create({ trigger:'#moment', start:'top bottom', once:true, onEnter:function(){
      size();
      var n = matchMedia('(max-width:700px)').matches ? 18 : 40;
      for(var i=0;i<n;i++){ motes.push({ x:Math.random()*canvas.width, y:Math.random()*canvas.height, r:Math.random()*2+.5, s:Math.random()*.4+.1 }); }
      if(!reduced) requestAnimationFrame(draw);
    }});
    function draw(){
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.fillStyle='rgba(243,234,214,.5)';
      motes.forEach(function(p){ p.y-=p.s; if(p.y<0) p.y=canvas.height; ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fill(); });
      requestAnimationFrame(draw);
    }
    gsap.timeline({
      scrollTrigger:{ trigger:'#moment', start:'top top', end:'+=140%', pin:true, scrub:1 }
    }).to('#moment .frame', { scale:1.14, ease:'none' },0)
      .to('#moment .cap .display', { opacity:1, duration:.3 }, .15)
      .to('#moment .cap .display', { opacity:0, duration:.3 }, .75);
  })();

  /* ================= 07 LETTER ================= */
  (function(){
    var tl=gsap.timeline({
      scrollTrigger:{ trigger:'#letter', start:'top top', end:'+=220%', pin:true, scrub:1 }
    });
    gsap.utils.toArray('#letter .ln').forEach(function(ln){
      tl.to(ln, { opacity:1, filter:'blur(0px)', duration:.6 })
        .to(ln, { opacity:.25, duration:.4 }, '+=.35');
    });
    tl.to('#letter .sig', { opacity:1, duration:.5 });
  })();

  /* ================= 08 BOTANICAL ================= */
  (function(){
    var canvas=document.getElementById('botanical-canvas');
    var ctx=canvas.getContext('2d');
    function size(){ canvas.width=canvas.offsetWidth; canvas.height=canvas.offsetHeight; }
    size();

    function drawStem(x0,y0,x1,y1,color){
      ctx.strokeStyle=color; ctx.lineWidth=1.4; ctx.beginPath();
      ctx.moveTo(x0,y0); ctx.quadraticCurveTo((x0+x1)/2, y0-40, x1,y1); ctx.stroke();
    }
    function drawLeaf(x,y,ang,len,color){
      ctx.save(); ctx.translate(x,y); ctx.rotate(ang);
      ctx.fillStyle=color; ctx.beginPath();
      ctx.moveTo(0,0);
      ctx.quadraticCurveTo(len*0.3,-len*0.35, len,0);
      ctx.quadraticCurveTo(len*0.3,len*0.35, 0,0);
      ctx.fill(); ctx.restore();
    }
    function drawBloom(x,y,r,petals,color,rot){
      for(var i=0;i<petals;i++){
        var a = rot + (Math.PI*2/petals)*i;
        ctx.save(); ctx.translate(x,y); ctx.rotate(a);
        ctx.fillStyle=color; ctx.beginPath();
        ctx.ellipse(r*0.62,0,r*0.62,r*0.34,0,0,Math.PI*2); ctx.fill();
        ctx.restore();
      }
      ctx.fillStyle='rgba(173,138,85,.9)'; ctx.beginPath(); ctx.arc(x,y,r*0.22,0,Math.PI*2); ctx.fill();
    }
    var plants=[];
    var palette=['rgba(185,138,134,.55)','rgba(173,138,85,.5)','rgba(92,27,39,.55)'];
    var leafColor='rgba(141,129,117,.4)';
    function seed(){
      plants=[];
      var n = matchMedia('(max-width:700px)').matches ? 5 : 9;
      for(var i=0;i<n;i++){
        plants.push({
          x: (canvas.width/(n+1))*(i+1) + (Math.random()*60-30),
          baseY: canvas.height+20,
          h: Math.random()*canvas.height*0.4 + canvas.height*0.25,
          r: Math.random()*20+18,
          petals: 5+Math.floor(Math.random()*3),
          color: palette[i%palette.length],
          rot: Math.random()*Math.PI,
          speed: (Math.random()*.4+.2) * (i%2?1:-1),
          sway: Math.random()*8+4
        });
      }
    }
    seed();

    var resizeTimer=null;
    addEventListener('resize', function(){
      size();
      clearTimeout(resizeTimer);
      resizeTimer=setTimeout(seed, 150);
    });

    var t=0;
    function render(){
      t+=0.006;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      plants.forEach(function(p){
        var sway=Math.sin(t*p.speed)*p.sway;
        var topX=p.x+sway, topY=p.baseY-p.h;
        drawStem(p.x,p.baseY,topX,topY, leafColor);
        drawLeaf(p.x+(p.x>canvas.width/2?-6:6), p.baseY-p.h*0.4, (p.x>canvas.width/2?1:-1)*0.9, 34, leafColor);
        drawBloom(topX, topY, p.r, p.petals, p.color, p.rot + t*0.15*(p.speed>0?1:-1));
      });
      requestAnimationFrame(render);
    }
    if(!reduced) requestAnimationFrame(render); else render();

    var pin=document.getElementById('botanical-pin');
    var petals=[]; var pn = matchMedia('(max-width:700px)').matches?4:8;
    for(var i=0;i<pn;i++){
      var el=document.createElement('div'); el.className='petal';
      el.style.left=(Math.random()*90+5)+'%'; el.style.top='-5%';
      pin.appendChild(el); petals.push(el);
      if(!reduced){
        gsap.to(el, {
          motionPath:{ path:[ {x:0,y:0}, {x:(Math.random()*160-80),y:innerHeight*.4}, {x:(Math.random()*220-110), y:innerHeight*.85} ], curviness:1.6 },
          rotation: Math.random()*360, duration: 8+Math.random()*6, repeat:-1, delay:Math.random()*6, ease:'none'
        });
      }
    }
    gsap.timeline({ scrollTrigger:{ trigger:'#botanical', start:'top top', end:'+=120%', pin:true, scrub:1 } })
      .fromTo('#botanical .cap', { opacity:0, y:20 }, { opacity:1, y:0, duration:1 });
  })();

  /* ================= 09 DATE ================= */
  (function(){
    var el=document.getElementById('date-num');
    var steps=window.__PS_DATE_STEPS__ || ['12','12 / 12','12 / 12 / 2026'];
    var tl=gsap.timeline({
      scrollTrigger:{ trigger:'#date', start:'top top', end:'+=160%', pin:true, scrub:1 }
    });
    tl.to('.save', { opacity:1, duration:.4 })
      .to(el, { opacity:1, duration:.4 }, '+=.1');
    steps.forEach(function(s,i){
      if(i===0) return;
      tl.to(el, { opacity:0, duration:.2 })
        .call(function(){ el.textContent=s; })
        .to(el, { opacity:1, duration:.3 });
    });
    tl.to('.final', { opacity:1, duration:.5 }, '+=.2');
  })();

  /* ================= 10 DETAILS ================= */
  (function(){
    gsap.utils.toArray('#details .item').forEach(function(it,i){
      gsap.to(it, { opacity:1, y:0, duration:.7, ease:'power3.out',
        scrollTrigger:{ trigger:it, start:'top 88%' }, delay:(i%3)*.06 });
    });
  })();

  /* ================= 11 TIMELINE ================= */
  (function(){
    var evs=gsap.utils.toArray('#timeline .ev');
    ScrollTrigger.create({
      trigger:'#timeline .rail-wrap', start:'top 60%', end:'bottom 60%', scrub:1,
      onUpdate:function(self){
        gsap.set('#spine-fill', { height:(self.progress*100)+'%' });
      }
    });
    evs.forEach(function(ev){
      ScrollTrigger.create({ trigger:ev, start:'top 65%', end:'bottom 45%',
        onToggle:function(self){ ev.classList.toggle('active', self.isActive); } });
    });
  })();

  /* ================= 12 LOCATION (procedural map) ================= */
  (function(){
    var canvas=document.getElementById('map-canvas');
    var ctx=canvas.getContext('2d');
    var drawn=false;
    ScrollTrigger.create({ trigger:'#location', start:'top 70%', once:true, onEnter:function(){
      canvas.width=canvas.offsetWidth*2; canvas.height=canvas.offsetHeight*2; ctx.scale(2,2);
      var w=canvas.offsetWidth, h=canvas.offsetHeight;
      ctx.fillStyle='#17130f'; ctx.fillRect(0,0,w,h);
      ctx.strokeStyle='rgba(243,234,214,.08)';
      for(var i=0;i<7;i++){
        ctx.beginPath();
        for(var x=0;x<=w;x+=10){
          var y = h/2 + Math.sin(x*0.01 + i)*30 + i*22 - 70;
          x===0? ctx.moveTo(x,y): ctx.lineTo(x,y);
        }
        ctx.stroke();
      }
      var route=[{x:w*.12,y:h*.82},{x:w*.35,y:h*.55},{x:w*.58,y:h*.62},{x:w*.5,y:h*.4}];
      ctx.setLineDash([6,6]); ctx.strokeStyle='rgba(173,138,85,.8)'; ctx.lineWidth=1.4;
      ctx.beginPath(); route.forEach(function(p,i){ i? ctx.lineTo(p.x,p.y): ctx.moveTo(p.x,p.y); }); ctx.stroke();
      ctx.setLineDash([]);
      var pin=route[route.length-1];
      ctx.fillStyle='#ad8a55';
      ctx.beginPath(); ctx.arc(pin.x,pin.y-8,5,0,Math.PI*2); ctx.fill();
      ctx.strokeStyle='rgba(173,138,85,.4)';
      var pulse=0;
      (function anim(){
        pulse+=0.03;
        ctx.clearRect(pin.x-40,pin.y-60,80,80);
        var r=6+((Math.sin(pulse)+1)/2)*14;
        ctx.beginPath(); ctx.arc(pin.x,pin.y-8,r,0,Math.PI*2); ctx.strokeStyle='rgba(173,138,85,'+(1-((r-6)/14))+')'; ctx.stroke();
        ctx.fillStyle='#ad8a55'; ctx.beginPath(); ctx.arc(pin.x,pin.y-8,5,0,Math.PI*2); ctx.fill();
        if(!reduced) requestAnimationFrame(anim);
      })();
    }});
  })();

  /* ================= 13 RSVP (posts to the real invitation RSVP endpoint) ================= */
  (function(){
    var opts=document.querySelectorAll('#rsvp .opt');
    var reply=document.getElementById('rsvp-reply');
    var msg=document.getElementById('rsvp-msg');
    var form=document.getElementById('rsvp-form');
    var input=form.querySelector('input');
    var status=document.getElementById('rsvp-status');
    var msgs={ yes:'Wonderful. We\'ll save you a seat.', maybe:'We understand — let us know when you can.', no:'You\'ll be missed. Thank you for letting us know.' };
    var rsvpConf=window.__PS_RSVP__ || { enabled:false };
    var chosen=null;
    opts.forEach(function(o){
      o.addEventListener('click', function(){
        opts.forEach(function(x){ x.classList.remove('chosen'); });
        o.classList.add('chosen');
        chosen=o.dataset.r;
        msg.textContent = msgs[chosen];
        status.textContent='';
        gsap.to(reply, { height:'auto', opacity:1, duration:.5, ease:'power2.out' });
      });
    });
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var name=input.value.trim();
      if(!chosen) return;
      whoosh();
      // The backend only tracks a firm yes/no; "maybe" stays a courtesy reply on-page only.
      if(rsvpConf.enabled && rsvpConf.url && (chosen==='yes' || chosen==='no')){
        status.textContent='Saving your reply…';
        fetch(rsvpConf.url, {
          method:'POST',
          headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':rsvpConf.token, 'Accept':'application/json' },
          body:JSON.stringify({ status:chosen, note:name || null }),
        }).then(function(r){
          status.textContent = r.ok ? 'Saved — thank you.' : 'Couldn’t save that just now — please try again.';
        }).catch(function(){
          status.textContent='Couldn’t save that just now — please try again.';
        });
      }
      msg.textContent='Thank you — we can\'t wait to see you there.';
      input.value='';
    });
  })();

  /* ================= 14 FINALE ================= */
  (function(){
    gsap.timeline({
      scrollTrigger:{ trigger:'#finale', start:'top top', end:'+=160%', pin:true, scrub:1 }
    }).to('#finale .row', { opacity:1, duration:.5 })
      .to('#finale .line1', { opacity:1, duration:.5 }, '+=.1')
      .to('#finale .names2', { opacity:1, duration:.6 }, '+=.1')
      .to('#finale .curtain', { opacity:1, duration:.6 }, '+=.4')
      .to('#finale .credit', { opacity:1, duration:.6 }, '-=.2');
  })();

  /* ---------------- responsive: simplify heavy scenes on mobile ---------------- */
  ScrollTrigger.matchMedia({
    "(max-width: 700px)": function(){
      gsap.set('#reveal .frame:nth-child(2)', { display:'none' });
    }
  });

  addEventListener('load', function(){ ScrollTrigger.refresh(); });
})();
</script>
