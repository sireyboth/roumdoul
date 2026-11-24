@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Love Bubble: a cute, playful Gen Z wedding invitation (candy pastels, chunky outlines, clay
    // shadows, stickers, Y2K sparkles, phone-UI nods). Cover: an Instagram/TikTok-style story of the
    // couple's photo (progress bars, profile bubble with their initials, stickers popping on, «You've
    // got an invite») with a squishy «Open» button; opening jelly-bounces the story, the stickers fly,
    // a wobbly blob hole grows through the story and the photo flies into the hero (shared element).
    // Page: chat-thread invite (typing dots), couple profile cards, boarding-pass ticket + barcode,
    // «loading wedding…» bar + slot countdown, now-playing music card, swipeable story cards, to-do
    // program, sticker-bomb gallery with a FLIP preview, cartoon map, colour-picker dress code with an
    // outfit that takes the colour, emoji RSVP + emoji rain, «scan me» gift sticker, chat contact cards,
    // a sticker sheet to play with and a heart-shaped finale. Everything personal comes from $fields.
    // On demo pages $invitation is null, so every premium extra previews.
    // CSS public/css/invitations/love-bubble.css · JS public/js/invitations/love-bubble.js
    // Debug: ?open=1 skips the cover.
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $lbAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $enMonthsShort = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    $khDate = fn ($d) => 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year);

    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' lb2-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#ff7eb6';
    $headline = trim($fields['headline'] ?? '') ?: 'We said yes!';
    $titleImg = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateDots = $eventDate ? $eventDate->format('d.m.Y') : null;
    $dateKhmer = $eventDate ? $khDate($eventDate) : null;
    $period = fn (int $h) => $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));
    $khTime = fn (int $h, string $min) => $khDigits(sprintf('%d:%s', $h % 12 ?: 12, $min)).' '.$period($h);
    $endTime = preg_match('/^(\d{1,2}):(\d{2})/', trim($fields['end_time'] ?? ''), $et) ? $khTime((int) $et[1], $et[2]) : null;
    $startTime = $eventDate ? $khTime($eventDate->hour, $eventDate->format('i')) : null;
    $timeKhmer = $eventDate ? 'វេលាម៉ោង '.$startTime.($endTime ? ' – '.$endTime : '') : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    $venueName = trim($fields['venue_name'] ?? '') ?: null;
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address'])) ? $fields['venue_address'] : null;
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $fields['map_url'] ?? '')) ? $fields['map_url'] : null;
    if ($venueAddress && preg_match('#^https?://\S+$#i', trim($venueAddress))) {
        $mapLink = $mapLink ?: trim($venueAddress);
        $venueAddress = null;
    }
    $mapQuery = $venueAddress ?: $venueName;
    $mapPinned = false;
    if ($mapLink && preg_match('#[@=](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#', urldecode($mapLink), $pin)) {
        $mapQuery = $pin[1].','.$pin[2];
        $mapPinned = true;
    }
    $mapUrl = $mapLink ?: ($mapQuery ? 'https://maps.google.com/?q='.urlencode($mapQuery) : null);
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed' : null;
    $showVenue = $venueName || $venueAddress || $mapLink;

    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? trim($fields['gift_account']) : null;
    $giftNumber = null;
    if ($giftAccount && preg_match_all('/\d[\d\s-]{4,}\d/', $giftAccount, $nums)) {
        $giftNumber = preg_replace('/\D/', '', collect($nums[0])->sortByDesc(fn ($n) => strlen($n))->first());
    }
    $giftMessage = trim($fields['gift_message'] ?? '') ?: 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!';

    $personalNote = trim($fields['message'] ?? '');
    $receptionText = trim($fields['reception_message'] ?? '')
        ?: 'អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ';
    $letterRows = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', 'Thank you notes', '💌', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', 'A little sorry', '🙏', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[3] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'See you there!';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    $finaleUrl = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : ($gallery->last() ?: $coverUrl);
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;
    $albumArt = $gallery->first(fn ($u) => $u !== $coverUrl) ?: $coverUrl;
    $boardPhoto = $gallery->count() > 2 ? $gallery[2] : ($gallery->first() ?: $coverUrl);

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))->values();
    $splitTitle = fn ($t) => array_pad(array_map('trim', explode('|', (string) $t, 2)), 2, '');
    $storyEmoji = ['☕', '🌅', '🏖️', '💍', '🏡', '✨', '💞', '🎈'];

    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))->values();
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    $schedTime = function (?string $t) use ($khTime): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }

        return $khTime($h, $m[2]);
    };
    $schedEmoji = ['🎺', '🙏', '🧵', '🕯️', '🥂', '💃', '🎂', '📸'];

    // guest notes. Dress code is always colour swatches (DressCode), with the text under them
    $dressCode = trim($fields['dress_code'] ?? '');
    $dressColours = \App\Support\Invitations\DressCode::colours($dressCode);
    $whatToBring = trim($fields['what_to_bring'] ?? '');
    $rsvpBy = null;
    if (! empty($fields['rsvp_deadline']) && ($d = \App\Support\Invitations\EventDate::parse($fields['rsvp_deadline']))) {
        $rsvpBy = $khDate($d);
    }
    $showNotes = $dressCode !== '' || $whatToBring !== '' || $rsvpBy;

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', $recipient) : null;
    $rsvpQuestion = trim($fields['rsvp_message'] ?? '') ?: 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមជាមួយយើងខ្ញុំដែរឬទេ?';

    $ctaLabel = $isUnlocked('cta_label') ? trim($fields['cta_label'] ?? '') : '';
    $ctaUrl = $isUnlocked('cta_url') && preg_match('#^https?://#i', trim($fields['cta_url'] ?? '')) ? trim($fields['cta_url']) : null;
    $showCta = $ctaLabel !== '' && $ctaUrl;

    $parent = function (string $key, string $title) use ($fields): ?string {
        $n = trim($fields[$key] ?? '');

        return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
    };
    $groomParents = array_values(array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]));
    $brideParents = array_values(array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]));

    $tel = fn (string $p) => preg_replace('/[^\d+]/', '', $p);
    $contacts = collect([
        ['role' => 'ខាងកូនប្រុស', 'name' => $groomPretty, 'phone' => trim($fields['groom_phone'] ?? ''), 'tone' => 'blue'],
        ['role' => 'ខាងកូនស្រី', 'name' => $bridePretty, 'phone' => trim($fields['bride_phone'] ?? ''), 'tone' => 'pink'],
        ['role' => 'ព័ត៌មានបន្ថែម', 'name' => '', 'phone' => trim($fields['contact_phone'] ?? ''), 'tone' => 'mint'],
    ])->filter(fn ($c) => $c['phone'] !== '')->unique('phone')->values();

    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }
    $songTitle = trim($fields['song_title'] ?? '');
    $hasSong = $musicFile || $youtubeId;

    // ---------------- doodle art ----------------
    // a barcode drawn from the date (boarding pass): every digit → a bar + a gap of its own width
    $barcode = '';
    if ($eventDate) {
        $code = $eventDate->format('YmdHi').strrev($eventDate->format('dmY'));
        $x = 0;
        foreach (str_split($code) as $k => $ch) {
            $w = 1 + ((int) $ch % 3);
            $barcode .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="40"/>';
            $x += $w + 1 + (((int) $ch + $k) % 2);
            $barcode .= '<rect x="'.$x.'" y="0" width="1" height="40"/>';
            $x += 3;
        }
        $barcodeW = $x;
    }

    // scattered doodles (sparkles, hearts, stars, squiggles, smileys, daisies) on a section's background,
    // drifting with scroll (data-speed); deterministic per section
    $doodleN = 0;
    $doodles = function (int $count = 7) use (&$doodleN): string {
        $n = $doodleN++;
        mt_srand(700 + $n * 13);
        $kinds = ['spark', 'heart', 'star', 'squig', 'smile', 'daisy', 'spark', 'heart'];
        $tones = ['var(--lb2-accent)', 'var(--lb2-lav-d)', 'var(--lb2-blue-d)', 'var(--lb2-butter-d)', 'var(--lb2-mint-d)'];
        $out = '';
        for ($k = 0; $k < $count; $k++) {
            $side = $k % 2;
            $left = $side ? mt_rand(80, 96) : mt_rand(1, 18);
            if ($k > 3) {
                $left = mt_rand(4, 94);
            }
            $out .= '<svg class="lb2-doodle" data-speed="'.(mt_rand(-12, 14) / 100).'" viewBox="-20 -20 40 40" style="left:'.$left.'%;top:'.mt_rand(3, 92).'%;--s:'.(mt_rand(70, 150) / 100).';--r:'.mt_rand(-30, 30).'deg;--d:'.(mt_rand(0, 50) / 10).'s;color:'.$tones[($n + $k) % 5].'" aria-hidden="true"><use href="#lb2-'.$kinds[($n * 3 + $k) % 8].'"/></svg>';
        }

        return '<div class="lb2-doodles" aria-hidden="true">'.$out.'</div>';
    };

    // section heading: a chunky sticker title (Latin bubble letters) + the Khmer line + an emoji
    $head = fn (string $en, string $khText, string $emoji) => '<div class="lb2-head lb2-r" data-r="slap">'
        .'<span class="lb2-head-emoji" aria-hidden="true">'.$emoji.'</span>'
        .'<p class="lb2-head-en">'.e($en).'</p><h2 class="lb2-head-kh">'.e($khText).'</h2></div>';

    // section backgrounds cycle through printed patterns
    $bgN = 0;
    $bg = function () use (&$bgN): string {
        return 'lb2-bg-'.['grid', 'check', 'dots', 'stripe', 'holo'][($bgN++) % 5];
    };

    $countdownStart = $eventDate ? $eventDate->copy()->subDays(120)->toIso8601String() : null;
    $skipGate = request()->boolean('open');
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Fredoka / Gochi Hand are Latin-only: the CSS stacks always follow them with Kantumruy Pro (or Koulen for big
     Khmer display). Kantumruy Pro carries both scripts for the body --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Gochi+Hand&family=Koulen&family=Kantumruy+Pro:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ $lbAsset('css/invitations/love-bubble.css') }}">
<noscript><style>.lb2-gate{display:none}.lb2-page{visibility:visible!important}.lb2-r{opacity:1!important;transform:none!important;clip-path:none!important}.lb2-msg{display:flex!important}</style></noscript>

<div class="lb2{{ $skipGate ? ' is-open' : '' }}" style="--lb2-accent: {{ $accent }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- reusable doodles as <g> (a <use> of a <symbol> without a size would scale it to the whole drawing) --}}
      <g id="lb2-spark"><path fill="currentColor" stroke="var(--lb2-ink)" stroke-width="1.6" stroke-linejoin="round" d="M0 -15C1.6 -5 5 -1.6 15 0C5 1.6 1.6 5 0 15C-1.6 5 -5 1.6 -15 0C-5 -1.6 -1.6 -5 0 -15Z"/></g>
      <g id="lb2-heart"><path fill="currentColor" stroke="var(--lb2-ink)" stroke-width="1.6" stroke-linejoin="round" d="M0 12C-14 3 -15 -6 -10.5 -10.5C-6.5 -14 -2 -11.5 0 -7.5C2 -11.5 6.5 -14 10.5 -10.5C15 -6 14 3 0 12Z"/><path d="M-8 -6C-7 -8.5 -5 -9.5 -3.5 -9" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".8"/></g>
      <g id="lb2-star"><path fill="currentColor" stroke="var(--lb2-ink)" stroke-width="1.6" stroke-linejoin="round" d="M0 -14L4 -4.5L14 -4L6.3 2.6L8.8 12.6L0 7L-8.8 12.6L-6.3 2.6L-14 -4L-4 -4.5Z"/></g>
      <g id="lb2-squig"><path fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" d="M-16 4C-12 -6 -8 -6 -5 3S2 12 5 3S12 -6 16 -2"/></g>
      <g id="lb2-smile"><circle r="13" fill="currentColor" stroke="var(--lb2-ink)" stroke-width="1.6"/><circle cx="-4.5" cy="-3" r="1.8" fill="var(--lb2-ink)"/><circle cx="4.5" cy="-3" r="1.8" fill="var(--lb2-ink)"/><path d="M-6 3C-3 8 3 8 6 3" fill="none" stroke="var(--lb2-ink)" stroke-width="1.8" stroke-linecap="round"/></g>
      <g id="lb2-daisy">
        <g fill="#fff" stroke="var(--lb2-ink)" stroke-width="1.4">@for ($p = 0; $p < 8; $p++)<ellipse cx="0" cy="-9" rx="4.2" ry="7" transform="rotate({{ $p * 45 }})"/>@endfor</g>
        <circle r="5.5" fill="currentColor" stroke="var(--lb2-ink)" stroke-width="1.4"/>
      </g>
      <g id="lb2-heart-s"><path fill="currentColor" d="M0 12C-14 3 -15 -6 -10.5 -10.5C-6.5 -14 -2 -11.5 0 -7.5C2 -11.5 6.5 -14 10.5 -10.5C15 -6 14 3 0 12Z"/></g>
      <clipPath id="lb2-heart-clip" clipPathUnits="objectBoundingBox">
        <path d="M.5 .96C.08 .68 0 .42 .1 .22C.2 .03 .41 .02 .5 .2C.59 .02 .8 .03 .9 .22C1 .42 .92 .68 .5 .96Z"/>
      </clipPath>
    </defs>
  </svg>

  {{-- ============ 0 · COVER: an Instagram/TikTok-style story ============ --}}
  @unless ($skipGate)
    <div class="lb2-gate" id="lb2-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      <div class="lb2-gate-bg" id="lb2-gate-bg">
        @if ($coverUrl)<img class="lb2-gate-blur" src="{{ $coverUrl }}" alt="" aria-hidden="true">@endif
        <div class="lb2-gate-side" aria-hidden="true">
          @foreach ([['spark', 8, 18, 'var(--lb2-butter)'], ['heart', 14, 62, 'var(--lb2-accent)'], ['star', 6, 82, 'var(--lb2-lav)'], ['daisy', 88, 14, 'var(--lb2-butter)'], ['smile', 90, 48, 'var(--lb2-mint)'], ['heart', 84, 80, 'var(--lb2-blue)']] as $k => [$kind, $x, $y, $c])
            <svg viewBox="-20 -20 40 40" style="left:{{ $x }}%;top:{{ $y }}%;color:{{ $c }};--d:{{ $k * .4 }}s"><use href="#lb2-{{ $kind }}"/></svg>
          @endforeach
        </div>
      </div>

      <div class="lb2-story" id="lb2-story">
        <div class="lb2-story-photo" id="lb2-story-photo">
          @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" fetchpriority="high" draggable="false">
          @else
            <div class="lb2-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>
          @endif
        </div>
        <div class="lb2-story-shade" aria-hidden="true"></div>

        {{-- story chrome: progress bars + profile bubble --}}
        <div class="lb2-story-top">
          <div class="lb2-bars" aria-hidden="true"><span class="is-on"><i></i></span><span><i></i></span><span><i></i></span></div>
          <div class="lb2-profile">
            <span class="lb2-avatar"><b class="{{ trim($kh($initials)) }}">{{ $initials }}</b></span>
            <span class="lb2-profile-name{{ $kh($coupleTitle) }}">{{ $coupleTitle ?: 'wedding' }}</span>
            <span class="lb2-profile-time">· now</span>
            <span class="lb2-profile-dots" aria-hidden="true">•••</span>
          </div>
        </div>

        {{-- stickers popping on --}}
        <div class="lb2-stickers-cover" aria-hidden="true">
          <span class="lb2-cs lb2-cs-head" style="--i:0">{{ $headline }} 💍</span>
          @if ($dateDots)<span class="lb2-cs lb2-cs-date" style="--i:1">📅 {{ $dateDots }}</span>@endif
          <span class="lb2-cs lb2-cs-emo" style="--i:2">🥹</span>
          <span class="lb2-cs lb2-cs-emo2" style="--i:3">💖</span>
          <span class="lb2-cs lb2-cs-poll" style="--i:4"><b>ទៅទេ?</b><i>🥳 ទៅ!</i><i>🥳 ទៅៗ!</i></span>
          <svg class="lb2-cs lb2-cs-spark" style="--i:5" viewBox="-20 -20 40 40"><use href="#lb2-spark"/></svg>
          <svg class="lb2-cs lb2-cs-spark2" style="--i:6" viewBox="-20 -20 40 40"><use href="#lb2-star"/></svg>
        </div>

        <div class="lb2-story-bottom">
          @if ($titleImg)
            <img class="lb2-gate-title" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
          @elseif ($coupleTitle)
            <h1 class="lb2-gate-names">
              @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
              @if ($groomPretty && $bridePretty)<em>&amp;</em>@endif
              @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
            </h1>
          @endif
          <p class="lb2-gate-dm">
            <span class="lb2-gate-dm-ico" aria-hidden="true">📩</span>
            <span><small>You've got an invite!</small><b class="{{ trim($kh($guest)) }}">{{ $guest }}</b> អ្នកទទួលបានការអញ្ជើញ!</span>
          </p>
          <button type="button" class="lb2-open" id="lb2-open">
            <span class="lb2-open-in">បើកមើល <em>Open</em> <span aria-hidden="true">💌</span></span>
          </button>
          <p class="lb2-gate-hint" aria-hidden="true">tap ☝ ចុចដើម្បីបើក</p>
        </div>
      </div>
    </div>
  @endunless

  {{-- the flying photo (shared element cover → hero) + confetti/emoji canvas --}}
  <div class="lb2-fly" id="lb2-fly" aria-hidden="true">@if ($coverUrl)<img src="{{ $coverUrl }}" alt="">@endif</div>
  <canvas class="lb2-confetti" id="lb2-confetti" aria-hidden="true"></canvas>

  <main class="lb2-page" id="lb2-top">

    {{-- ============ 1 · HERO ============ --}}
    <section class="lb2-hero lb2-bg-check" id="lb2-hero">
      {!! $doodles(9) !!}
      <div class="lb2-wrap lb2-hero-grid">
        <div class="lb2-hero-photo-wrap">
          <div class="lb2-hero-photo" id="lb2-hero-photo">
            @if ($coverUrl)<img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}">@else<div class="lb2-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>@endif
          </div>
          <span class="lb2-ring" aria-hidden="true"></span>
          {{-- stickers on the hero photo: drag them around --}}
          <span class="lb2-drag lb2-hs" style="--x:-6%;--y:6%;--r:-14deg" role="img" aria-label="sticker">💍</span>
          <span class="lb2-drag lb2-hs lb2-hs-txt" style="--x:62%;--y:-4%;--r:9deg">just married*</span>
          <span class="lb2-drag lb2-hs" style="--x:78%;--y:74%;--r:12deg" role="img" aria-label="sticker">🥂</span>
          <svg class="lb2-drag lb2-hs lb2-hs-svg" style="--x:-4%;--y:78%;--r:-8deg;color:var(--lb2-butter)" viewBox="-20 -20 40 40" aria-hidden="true"><use href="#lb2-daisy"/></svg>
        </div>
        <div class="lb2-hero-text">
          <p class="lb2-chip lb2-r" data-r="slap">✨ {{ $headline }} ✨</p>
          @if ($titleImg)
            <img class="lb2-hero-title lb2-r" data-r="pop" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
          @else
            <h1 class="lb2-hero-names lb2-r" data-r="pop">
              @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
              @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
              @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
            </h1>
          @endif
          <p class="lb2-hero-sub lb2-r" data-r="up">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
          <div class="lb2-hero-pills lb2-r" data-r="up">
            @if ($dateKhmer)<span class="lb2-pill pink">📅 {{ $dateKhmer }}</span>@endif
            @if ($venueName)<span class="lb2-pill blue{{ $kh($venueName) }}">📍 {{ $venueName }}</span>@endif
          </div>
          <a class="lb2-scroll" href="#lb2-chat" data-sfx="tap"><span>អានបន្ត</span> <b aria-hidden="true">👇</b></a>
        </div>
      </div>
    </section>

    {{-- marquee band, speeds up with scroll --}}
    <div class="lb2-marquee" aria-hidden="true">
      <div class="lb2-marquee-track" id="lb2-marquee">
        @for ($r = 0; $r < 2; $r++)
          <span>SAVE THE DATE</span><svg viewBox="-20 -20 40 40"><use href="#lb2-heart"/></svg>
          @if ($dateDots)<span>{{ $dateDots }}</span><svg viewBox="-20 -20 40 40"><use href="#lb2-spark"/></svg>@endif
          @if ($coupleTitle)<span class="{{ trim($kh($coupleTitle)) }}">{{ $coupleTitle }}</span><svg viewBox="-20 -20 40 40"><use href="#lb2-star"/></svg>@endif
          <span>WE SAID YES</span><svg viewBox="-20 -20 40 40"><use href="#lb2-heart"/></svg>
        @endfor
      </div>
    </div>

    {{-- ============ 2 · THE INVITE AS A CHAT THREAD ============ --}}
    <section class="lb2-sec {{ $bg() }}" id="lb2-chat">
      {!! $doodles() !!}
      <div class="lb2-wrap lb2-split">
        <div class="lb2-head-col">
        {!! $head('You have a new message', 'សារអញ្ជើញពិសេស', '💬') !!}
        <div class="lb2-split-art" aria-hidden="true"><span style="--r:-10deg;--d:0s">💌</span><span style="--r:8deg;--d:0.4s">📱</span><span style="--r:-4deg;--d:0.8s">🥹</span><b>new msg from the couple ✨</b></div>
        </div>
        <div class="lb2-phone lb2-r" data-r="fold" id="lb2-phone">
          <div class="lb2-phone-head">
            <span class="lb2-avatar sm"><b class="{{ trim($kh($initials)) }}">{{ $initials }}</b></span>
            <span class="lb2-phone-who"><b class="{{ trim($kh($coupleTitle)) }}">{{ $coupleTitle ?: 'Wedding' }}</b><small><i></i> online</small></span>
            <span class="lb2-phone-ico" aria-hidden="true">📞</span>
          </div>
          <ol class="lb2-thread" id="lb2-thread" aria-live="polite">
            <li class="lb2-day">Today</li>
            <li class="lb2-msg"><p class="lb2-bub">សួស្តី <b class="{{ trim($kh($guest)) }}">{{ $guest }}</b>! 👋 យើងមានដំណឹងល្អចង់ប្រាប់…</p></li>
            @if ($groomParents || $brideParents)
              <li class="lb2-msg">
                <div class="lb2-bub lb2-bub-card">
                  <p class="lb2-fwd">↪ Forwarded · ពីមាតាបិតា</p>
                  <div class="lb2-parents">
                    @if ($groomParents)<div><span class="lb2-mini">ខាងកូនប្រុស</span>@foreach ($groomParents as $line)<p>{{ $line }}</p>@endforeach</div>@endif
                    @if ($brideParents)<div><span class="lb2-mini">ខាងកូនស្រី</span>@foreach ($brideParents as $line)<p>{{ $line }}</p>@endforeach</div>@endif
                  </div>
                </div>
              </li>
            @endif
            <li class="lb2-msg"><p class="lb2-bub">សូមគោរពអញ្ជើញ {{ $receptionText }}</p></li>
            @if ($coupleTitle)
              <li class="lb2-msg">
                <div class="lb2-bub lb2-bub-couple">
                  @if ($groomPretty)<span><small>កូនប្រុសនាម</small><b class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</b></span>@endif
                  @if ($groomPretty && $bridePretty)<em aria-hidden="true">💞</em>@endif
                  @if ($bridePretty)<span><small>កូនស្រីនាម</small><b class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</b></span>@endif
                </div>
              </li>
            @endif
            @if ($eventDate)
              <li class="lb2-msg">
                <div class="lb2-bub lb2-bub-event">
                  <span class="lb2-cal-ico" aria-hidden="true"><small>{{ $enMonthsShort[$eventDate->month - 1] }}</small><b>{{ $eventDate->day }}</b></span>
                  <span>
                    <b>{{ $dateKhmer }}</b>
                    @if ($khmerLunarDate)<small>{{ $khmerLunarDate }}</small>@endif
                    <small>{{ $timeKhmer }}</small>
                    @if ($venueName)<small class="{{ trim($kh($venueName)) }}">📍 {{ $venueName }}</small>@endif
                  </span>
                </div>
              </li>
            @endif
            @if ($personalNote !== '')
              <li class="lb2-msg"><p class="lb2-bub">{{ $personalNote }} 🫶</p></li>
            @endif
            <li class="lb2-msg me"><p class="lb2-bub">🥹💖 ទទួលបានហើយ! អបអរសាទរ!</p><span class="lb2-seen">Seen ✓✓</span></li>
          </ol>
          <div class="lb2-typing" id="lb2-typing" aria-hidden="true"><i></i><i></i><i></i></div>
          <div class="lb2-phone-bar" aria-hidden="true"><span>ចុចលើសារ ដើម្បីដាក់ ❤️</span><b>➤</b></div>
        </div>
      </div>
    </section>

    {{-- ============ 3 · THE COUPLE: profile cards ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="lb2-sec {{ $bg() }}" id="lb2-couple">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head("It's a match!", 'កូនកំលោះ និងកូនក្រមុំ', '💘') !!}
          <div class="lb2-duo">
            @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ', $groomParents, 'blue'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ', $brideParents, 'pink']] as $k => [$photo, $name, $role, $parents, $tone])
              @if ($photo)
                <button type="button" class="lb2-profile-card {{ $tone }} lb2-r" data-r="{{ $k ? 'right' : 'left' }}">
                  <span class="lb2-pc-photo"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></span>
                  <span class="lb2-pc-role">{{ $role }} {{ $k ? '👰' : '🤵' }}</span>
                  @if ($name)<span class="lb2-pc-name{{ $kh($name) }}">{{ $name }}</span>@endif
                  @if ($parents)<span class="lb2-pc-parents">{{ $k ? 'កូនស្រីរបស់' : 'កូនប្រុសរបស់' }} {{ implode(' និង ', $parents) }}</span>@endif
                  <span class="lb2-pc-like" aria-hidden="true">❤</span>
                </button>
              @endif
              @if ($k === 0 && $groomPhoto && $bridePhoto)
                <span class="lb2-match lb2-r" data-r="pop" aria-hidden="true">💘</span>
              @endif
            @endforeach
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 4 · BOARDING PASS + LOADING BAR + SLOT COUNTDOWN ============ --}}
    @if ($eventDate)
      <section class="lb2-sec {{ $bg() }}" id="lb2-date">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head('Your ticket to love', 'សំបុត្រចូលរួម', '🎫') !!}
          <div class="lb2-date-grid">
            <div class="lb2-ticket lb2-r" data-r="fold" id="lb2-ticket">
              <div class="lb2-ticket-main">
                <div class="lb2-ticket-top">
                  <span class="lb2-ticket-air">LOVE AIR ✈</span>
                  <span class="lb2-ticket-cls">VIP GUEST</span>
                </div>
                <div class="lb2-ticket-route">
                  <span><small>FROM</small><b>ME + YOU</b><i>មិត្តភាព</i></span>
                  <span class="lb2-plane" aria-hidden="true">💗✈</span>
                  <span><small>TO</small><b>FOREVER</b><i>ជារៀងរហូត</i></span>
                </div>
                <div class="lb2-ticket-rows">
                  <p class="wide"><small>PASSENGER · ភ្ញៀវកិត្តិយស</small><b class="{{ trim($kh($guest)) }}">{{ $guest }}</b></p>
                  <p class="wide"><small>DATE · កាលបរិច្ឆេទ</small><b>{{ $dateKhmer }}</b></p>
                  @if ($khmerLunarDate)<p class="wide"><small>ចន្ទគតិ</small><b>{{ $khmerLunarDate }}</b></p>@endif
                  <p><small>BOARDING · ម៉ោង</small><b>{{ $startTime }}</b></p>
                  @if ($endTime)<p><small>UNTIL · ដល់</small><b>{{ $endTime }}</b></p>@endif
                  @if ($venueName)<p class="wide"><small>GATE · ទីកន្លែង</small><b class="{{ trim($kh($venueName)) }}">{{ $venueName }}</b></p>@endif
                </div>
              </div>
              <button type="button" class="lb2-ticket-stub" id="lb2-stub" aria-label="ហែកសំបុត្រ">
                <span class="lb2-stub-date">{{ $eventDate->format('d') }}<small>{{ $enMonthsShort[$eventDate->month - 1] }} {{ $eventDate->format('Y') }}</small></span>
                <svg class="lb2-barcode" viewBox="0 0 {{ $barcodeW }} 40" preserveAspectRatio="none" aria-hidden="true">{!! $barcode !!}</svg>
                <span class="lb2-stub-hint">ហែក ✂</span>
              </button>
            </div>

            @if ($showCountdown)
              <div class="lb2-count-card lb2-r" data-r="pop" id="lb2-count" data-target="{{ $eventDate->toIso8601String() }}" data-start="{{ $countdownStart }}">
                <p class="lb2-loading"><span>loading wedding…</span> <b id="lb2-pct">០%</b></p>
                <div class="lb2-bar" aria-hidden="true"><i id="lb2-bar-fill"></i><span class="lb2-bar-emoji" id="lb2-bar-emoji">🏃‍♀️</span><span class="lb2-bar-end">💒</span></div>
                <p class="lb2-mini center">កំពុងរាប់ថយក្រោយ ដល់ថ្ងៃសំខាន់</p>
                <div class="lb2-slots" role="timer" aria-live="off">
                  @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
                    <div class="lb2-slot {{ ['d' => 'pink', 'h' => 'lav', 'm' => 'blue', 's' => 'mint'][$u] }}"><span class="lb2-slot-win"><b data-u="{{ $u }}">០០</b></span><small>{{ $label }}</small></div>
                  @endforeach
                </div>
                <p class="lb2-count-done" id="lb2-count-done" hidden>ថ្ងៃនេះហើយ! 🎉</p>
              </div>
            @endif
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 5 · NOW PLAYING (Spotify-ish) ============ --}}
    @if ($hasSong)
      <section class="lb2-sec lb2-sec-tight {{ $bg() }}" id="lb2-music">
        {!! $doodles(5) !!}
        <div class="lb2-wrap">
          <div class="lb2-player lb2-r" data-r="slap" id="lb2-player">
            <span class="lb2-player-art">@if ($albumArt)<img src="{{ $albumArt }}" alt="" loading="lazy">@endif<i class="lb2-vinyl" aria-hidden="true"></i></span>
            <span class="lb2-player-info">
              <small>NOW PLAYING · កំពុងចាក់</small>
              <b class="{{ trim($kh($songTitle)) }}">{{ $songTitle !== '' ? $songTitle : 'Our song' }}</b>
              <span class="{{ trim($kh($coupleTitle)) }}">{{ $coupleTitle }}</span>
              <span class="lb2-eq" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </span>
            <button type="button" class="lb2-player-btn" id="lb2-player-btn" aria-label="ចាក់ / ផ្អាកបទចម្រៀង">
              <svg class="play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
              <svg class="pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z" fill="currentColor"/></svg>
            </button>
            <span class="lb2-player-line" aria-hidden="true"><i id="lb2-player-prog"></i></span>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 6 · OUR STORY: swipeable story cards ============ --}}
    @if ($story->isNotEmpty())
      <section class="lb2-sec {{ $bg() }}" id="lb2-storyline">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head('Our love story', 'ដំណើររឿងស្នេហារបស់យើង', '📖') !!}
          <div class="lb2-highlights lb2-r" data-r="up" role="tablist" aria-label="រឿងរ៉ាវ">
            @foreach ($story as $i => $c)
              @php([$place] = $splitTitle($c['title'] ?? ''))
              <button type="button" class="lb2-hl{{ $i === 0 ? ' is-on' : '' }}" data-go="{{ $i }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                <span class="lb2-hl-ring">@if ($gallery->isNotEmpty())<img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy">@else<i>{{ $storyEmoji[$i % 8] }}</i>@endif</span>
                <span class="lb2-hl-name{{ $kh($place) }}">{{ $place ?: $khDigits($i + 1) }}</span>
              </button>
            @endforeach
          </div>
        </div>
        <div class="lb2-cards" id="lb2-cards">
          @foreach ($story as $i => $c)
            @php([$place, $when] = $splitTitle($c['title'] ?? ''))
            <article class="lb2-scard {{ ['pink', 'lav', 'blue', 'butter', 'mint'][$i % 5] }}" data-i="{{ $i }}">
              <div class="lb2-scard-in">
                <span class="lb2-scard-num">{{ $khDigits($i + 1) }}/{{ $khDigits($story->count()) }}</span>
                @if ($gallery->isNotEmpty())
                  <span class="lb2-scard-photo"><img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy" draggable="false"></span>
                @endif
                <span class="lb2-scard-emoji" aria-hidden="true">{{ $storyEmoji[$i % 8] }}</span>
                @if ($when !== '')<span class="lb2-scard-when{{ $kh($when) }}">{{ $when }}</span>@endif
                @if ($place !== '')<h3 class="lb2-scard-title{{ $kh($place) }}">{{ $place }}</h3>@endif
                @if (! empty($c['text']))<p class="lb2-scard-text">{{ $c['text'] }}</p>@endif
              </div>
            </article>
          @endforeach
        </div>
        @if ($story->count() > 1)
          <div class="lb2-cards-ctrl">
            <button type="button" class="lb2-round" id="lb2-cards-prev" aria-label="មុន">←</button>
            <span class="lb2-mini" id="lb2-cards-count">១ / {{ $khDigits($story->count()) }}</span>
            <button type="button" class="lb2-round" id="lb2-cards-next" aria-label="បន្ទាប់">→</button>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 7 · PROGRAM: a to-do list that ticks itself ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="lb2-sec {{ $bg() }}" id="lb2-program">
        {!! $doodles() !!}
        <div class="lb2-wrap lb2-split">
          <div class="lb2-head-col">
          {!! $head('Wedding to-do list', 'កម្មវិធីសិរីមង្គល', '✅') !!}
          <div class="lb2-split-art" aria-hidden="true"><span style="--r:-8deg;--d:0s">📝</span><span style="--r:10deg;--d:0.5s">⏰</span><span style="--r:-4deg;--d:0.9s">💃</span><b>tick tick tick ✓</b></div>
          </div>
          @if ($scheduleImg)
            <figure class="lb2-sched-img lb2-r" data-r="slap">
              <span class="lb2-tape" aria-hidden="true"></span>
              <button type="button" class="lb2-ph" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីសិរីមង្គល" data-group="sched"><img src="{{ $scheduleImg }}" alt="កម្មវិធីសិរីមង្គល" loading="lazy"></button>
            </figure>
          @else
            <ol class="lb2-todo lb2-r" data-r="fold" id="lb2-todo">
              <li class="lb2-todo-head"><span>📝 to-do · ថ្ងៃមង្គល</span><b id="lb2-todo-count">០/{{ $khDigits($schedule->count()) }}</b></li>
              @foreach ($schedule as $i => $row)
                <li>
                  <button type="button" class="lb2-task" style="--i:{{ $i }}">
                    <span class="lb2-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
                    <span class="lb2-task-time">{{ $schedTime($row['time'] ?? '') }}</span>
                    <span class="lb2-task-label{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
                    <span class="lb2-task-emo" aria-hidden="true">{{ $schedEmoji[$i % 8] }}</span>
                  </button>
                </li>
              @endforeach
            </ol>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 8 · GALLERY: sticker-bomb grid + full preview ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="lb2-sec {{ $bg() }}" id="lb2-gallery">
        {!! $doodles(9) !!}
        <div class="lb2-wrap lb2-wrap-wide">
          {!! $head('Photo dump', 'កម្រងរូបភាពអនុស្សាវរីយ៍', '📸') !!}
          <div class="lb2-grid">
            @php($stick = ['💖', '✨', '🥰', '🌸', '😚', '💫', '🎀', '🫶', '⭐'])
            @foreach ($gallery as $i => $url)
              <figure class="lb2-gi g{{ $i % 6 }} lb2-r" data-r="{{ ['slap', 'wipe', 'pop'][$i % 3] }}" style="--t:{{ [-3, 2.5, -1.5, 3, -2, 1.5][$i % 6] }}deg">
                <button type="button" class="lb2-ph" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" data-group="gallery" aria-label="{{ ($captions[$i] ?? '') ?: 'រូបភាពទី '.$khDigits($i + 1) }}">
                  <img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="{{ $i < 4 ? 'eager' : 'lazy' }}" draggable="false">
                </button>
                @if (($captions[$i] ?? '') !== '')<figcaption class="{{ trim($kh($captions[$i])) }}">{{ $captions[$i] }}</figcaption>@endif
                <span class="lb2-gi-stick" aria-hidden="true">{{ $stick[$i % 9] }}</span>
                @if ($i % 3 === 1)<span class="lb2-tape" aria-hidden="true"></span>@endif
              </figure>
            @endforeach
          </div>
          <p class="lb2-hint">ចុចលើរូប ដើម្បីមើលធំ 🔍</p>
        </div>
      </section>
    @endif

    {{-- ============ 9 · VENUE: cartoon map + bouncing pin ============ --}}
    @if ($showVenue)
      <section class="lb2-sec {{ $bg() }}" id="lb2-venue">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head('Pin drop', 'ទីតាំងកម្មវិធី', '📍') !!}
          <div class="lb2-venue lb2-r" data-r="fold">
            <div class="lb2-map" id="lb2-map">
              <svg class="lb2-map-art" viewBox="0 0 360 220" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <rect width="360" height="220" fill="#d9f5e6"/>
                <path d="M-10 168C60 138 100 200 170 172S290 104 370 132" fill="none" stroke="#bfe3ff" stroke-width="26" stroke-linecap="round"/>
                <path d="M-10 168C60 138 100 200 170 172S290 104 370 132" fill="none" stroke="#fff" stroke-width="2" stroke-dasharray="6 9"/>
                <g fill="none" stroke="#fff" stroke-width="11" stroke-linecap="round"><path d="M0 70H360M190 0V220M40 0C60 80 30 140 70 220"/></g>
                <g stroke="#2b1d3a" stroke-width="2">
                  <rect x="22" y="18" width="52" height="36" rx="9" fill="#ffd3e6"/><rect x="104" y="20" width="62" height="38" rx="9" fill="#fff1a8"/>
                  <rect x="214" y="16" width="56" height="40" rx="9" fill="#d9c8ff"/><rect x="292" y="22" width="52" height="34" rx="9" fill="#bfe3ff"/>
                  <rect x="96" y="88" width="64" height="44" rx="9" fill="#bfe3ff"/><rect x="214" y="86" width="58" height="38" rx="9" fill="#ffd3e6"/>
                  <rect x="292" y="80" width="54" height="34" rx="9" fill="#fff1a8"/>
                </g>
                <g fill="#9ee0bb" stroke="#2b1d3a" stroke-width="1.6">@for ($t = 0; $t < 6; $t++)<circle cx="{{ 214 + $t * 26 }}" cy="{{ 196 + ($t % 2) * 8 }}" r="9"/>@endfor</g>
                <g transform="translate(36 110)"><use href="#lb2-daisy" style="color:#fff1a8"/></g>
                <g transform="translate(330 176) scale(.8)"><use href="#lb2-heart" style="color:{{ $accent }}"/></g>
              </svg>
              <span class="lb2-pin" aria-hidden="true"><svg viewBox="0 0 40 52"><path d="M20 50C20 50 4 32 4 19A16 16 0 0 1 36 19C36 32 20 50 20 50Z" fill="{{ $accent }}" stroke="#2b1d3a" stroke-width="2.4"/><circle cx="20" cy="19" r="7" fill="#fff" stroke="#2b1d3a" stroke-width="2"/></svg><i></i></span>
              @if ($mapEmbed)
                <iframe class="lb2-map-frame" data-src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe>
                <button type="button" class="lb2-map-load" id="lb2-map-load">🗺 មើលផែនទីពិត</button>
              @endif
            </div>
            <div class="lb2-venue-info">
              @if ($venueName)<p class="lb2-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
              @if ($venueAddress)<p class="lb2-body">{{ $venueAddress }}</p>@endif
              @if ($timeKhmer)<p class="lb2-pill butter">⏰ {{ $timeKhmer }}</p>@endif
              @if ($mapUrl)
                <a class="lb2-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">🧭 មើលផ្លូវ Google Maps</a>
              @endif
            </div>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES: colour-picker dress code + notifications ============ --}}
    @if ($showNotes)
      <section class="lb2-sec {{ $bg() }}" id="lb2-notes">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head('Pick your fit', 'ព័ត៌មានសម្រាប់ភ្ញៀវ', '🎨') !!}
          <div class="lb2-notes-grid">
            @if ($dressCode !== '')
              <div class="lb2-dress lb2-r" data-r="fold" id="lb2-dress">
                <div class="lb2-dress-top">
                  <p class="lb2-mini">ពណ៌សម្លៀកបំពាក់ · <span class="lb2-en">dress code</span></p>
                  <span class="lb2-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                </div>
                <div class="lb2-dress-body">
                  {{-- the outfit takes the colour of the tapped swatch --}}
                  <svg class="lb2-outfit" id="lb2-outfit" viewBox="0 0 200 150" aria-hidden="true">
                    <g class="lb2-shirt" transform="translate(52 78)">
                      <path d="M-30 -48L-12 -56C-8 -48 8 -48 12 -56L30 -48L44 -26L30 -18L26 -26V52H-26V-26L-30 -18L-44 -26Z" fill="var(--fit, #ffd3e6)" stroke="#2b1d3a" stroke-width="3" stroke-linejoin="round"/>
                      <path d="M-12 -56L0 -40L12 -56" fill="none" stroke="#2b1d3a" stroke-width="2.5"/>
                      <path d="M0 -40V48" stroke="#2b1d3a" stroke-width="2" stroke-dasharray="1 9" stroke-linecap="round"/>
                    </g>
                    <g class="lb2-gown" transform="translate(146 78)">
                      <path d="M-12 -56H12L16 -26C28 -4 44 30 50 54H-50C-44 30 -28 -4 -16 -26Z" fill="var(--fit, #ffd3e6)" stroke="#2b1d3a" stroke-width="3" stroke-linejoin="round"/>
                      <path d="M-16 -26C-6 -20 6 -20 16 -26" fill="none" stroke="#2b1d3a" stroke-width="2.5"/>
                      <path d="M-30 30C-14 36 14 36 30 30" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".6"/>
                    </g>
                  </svg>
                  @if ($dressColours)
                    <ul class="lb2-swatches" aria-label="ពណ៌សម្លៀកបំពាក់">
                      @foreach ($dressColours as $i => $c)
                        <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                          <button type="button" class="lb2-swatch{{ $i === 0 ? ' is-on' : '' }}" data-hex="{{ $c['hex'] }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}" aria-label="{{ $c['name'] }}"><span class="lb2-swatch-hex">{{ strtoupper($c['hex']) }}</span></button>
                          <span class="lb2-swatch-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                        </li>
                      @endforeach
                    </ul>
                  @endif
                </div>
                <p class="lb2-body">{{ $dressCode }}</p>
              </div>
            @endif
            @if ($whatToBring !== '' || $rsvpBy)
              <div class="lb2-notifs">
                @if ($whatToBring !== '')
                  <div class="lb2-notif lb2-r" data-r="right"><span class="lb2-notif-ico" aria-hidden="true">🎒</span><span><small>សូមយកមកជាមួយ · <span class="lb2-en">what to bring</span></small><b>{{ $whatToBring }}</b></span><em>now</em></div>
                @endif
                @if ($rsvpBy)
                  <div class="lb2-notif lb2-r" data-r="right"><span class="lb2-notif-ico b" aria-hidden="true">⏰</span><span><small>សូមឆ្លើយតបមុន · <span class="lb2-en">reply by</span></small><b>{{ $rsvpBy }}</b></span><em>reminder</em></div>
                @endif
              </div>
            @endif
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 11 · RSVP: big emoji buttons + emoji rain ============ --}}
    @if ($showRsvp)
      <section class="lb2-sec {{ $bg() }}" id="lb2-rsvp">
        {!! $doodles() !!}
        <div class="lb2-wrap lb2-split">
          <div class="lb2-head-col">
          {!! $head('Are you coming?', 'សូមបញ្ជាក់ការចូលរួម', '💌') !!}
          <div class="lb2-split-art" aria-hidden="true"><span style="--r:-8deg;--d:0s">🥳</span><span style="--r:8deg;--d:0.5s">🥲</span><span style="--r:-3deg;--d:0.9s">💌</span><b>tap an emoji!</b></div>
          </div>
          <div class="lb2-rsvp lb2-r" data-r="pop" id="lb2-rsvp-card">
            <p class="lb2-body center">{{ $rsvpQuestion }}</p>
            @if ($rsvpBy)<p class="lb2-mini center">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
            <div class="lb2-rsvp-opts" id="lb2-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
              <button type="button" class="lb2-opt yes" data-status="yes" role="radio" aria-checked="false"><span class="e" aria-hidden="true">🥳</span><b>ទៅហើយ!</b><small>count me in</small></button>
              <button type="button" class="lb2-opt no" data-status="no" role="radio" aria-checked="false"><span class="e" aria-hidden="true">🥲</span><b>មិនអាចទៅបាន</b><small>sending love</small></button>
            </div>
            <form class="lb2-rsvp-form" id="lb2-rsvp-form">
              <label class="lb2-sr" for="lb2-rsvp-note">សារជូនពរ</label>
              <textarea id="lb2-rsvp-note" rows="3" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់) ✍️"></textarea>
              <button type="submit" class="lb2-btn">ផ្ញើចម្លើយ 🚀</button>
            </form>
            <p class="lb2-err" id="lb2-rsvp-err" role="alert"></p>
          </div>
          <div class="lb2-rsvp lb2-rsvp-done" id="lb2-rsvp-done" hidden role="status">
            <span class="lb2-done-emoji" id="lb2-done-emoji" aria-hidden="true">🥳</span>
            <p class="lb2-done-title" id="lb2-rsvp-done-title">See you there!</p>
            <p class="lb2-body center" id="lb2-rsvp-done-text"></p>
            <button type="button" class="lb2-link" id="lb2-rsvp-again">កែប្រែការឆ្លើយតប</button>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT: «scan me» sticker ============ --}}
    @if ($giftQr)
      <section class="lb2-sec {{ $bg() }}" id="lb2-gift">
        {!! $doodles() !!}
        <div class="lb2-wrap lb2-split">
          <div class="lb2-head-col">
          {!! $head('Scan me', 'ចំណងដៃ', '🎁') !!}
          <div class="lb2-split-art" aria-hidden="true"><span style="--r:-8deg;--d:0s">🎁</span><span style="--r:8deg;--d:0.5s">💸</span><span style="--r:-3deg;--d:0.9s">🫶</span><b>with love ♡</b></div>
          </div>
          <p class="lb2-body center lb2-gift-msg lb2-r" data-r="up">{{ $giftMessage }}</p>
          <button type="button" class="lb2-qr lb2-r" data-r="slap" id="lb2-qr" aria-label="QR code សម្រាប់ផ្ញើចំណងដៃ">
            <span class="lb2-qr-label" aria-hidden="true">SCAN ME! 👀</span>
            <span class="lb2-qr-img"><img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy"><i class="lb2-scan" aria-hidden="true"></i></span>
            @if ($giftAccount)<span class="lb2-qr-acc">{{ $giftAccount }}</span>@endif
            <span class="lb2-qr-corner" aria-hidden="true"></span>
          </button>
          <div class="lb2-btn-row">
            @if ($giftNumber)<button type="button" class="lb2-btn" id="lb2-copy" data-copy="{{ $giftNumber }}">📋 ចម្លងលេខគណនី</button>@endif
            <a class="lb2-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">💾 រក្សាទុក QR</a>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACTS: chat-app contact cards + custom button ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="lb2-sec {{ $bg() }}" id="lb2-contact">
        {!! $doodles() !!}
        <div class="lb2-wrap">
          {!! $head('Slide into our DMs', 'ទំនាក់ទំនង', '📱') !!}
          <div class="lb2-contacts">
            @foreach ($contacts as $i => $c)
              <a class="lb2-contact {{ $c['tone'] }} lb2-r" data-r="{{ $i % 2 ? 'right' : 'left' }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
                <span class="lb2-avatar sm"><b class="{{ trim($kh($c['name'] ?: '☎')) }}">{{ $c['name'] !== '' ? $initial($c['name']) : '☎' }}</b></span>
                <span class="lb2-contact-txt">
                  <small>{{ $c['role'] }}</small>
                  @if ($c['name'] !== '')<b class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</b>@endif
                  <span>{{ $c['phone'] }}</span>
                </span>
                <span class="lb2-call" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
              </a>
            @endforeach
          </div>
          @if ($showCta)
            <a class="lb2-btn lb2-cta lb2-r" data-r="pop" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }} ↗</a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 14 · STICKER SHEET: decorate the photo ============ --}}
    @if ($boardPhoto)
      <section class="lb2-sec {{ $bg() }}" id="lb2-play">
        {!! $doodles() !!}
        <div class="lb2-wrap lb2-split">
          <div class="lb2-head-col">
          {!! $head('Decorate us!', 'តុបតែងរូបយើង', '🧸') !!}
          <div class="lb2-split-art" aria-hidden="true"><span style="--r:-8deg;--d:0s">🧸</span><span style="--r:8deg;--d:0.5s">🎀</span><span style="--r:-3deg;--d:0.9s">✨</span><b>drag · drop · giggle</b></div>
          </div>
          <div class="lb2-play lb2-r" data-r="fold">
            <div class="lb2-board" id="lb2-board">
              <img src="{{ $boardPhoto }}" alt="{{ $coupleTitle }}" loading="lazy" draggable="false">
              <span class="lb2-board-hint" id="lb2-board-hint">អូសស្ទីគ័រមកដាក់លើរូប 👇</span>
            </div>
            <div class="lb2-tray" id="lb2-tray" aria-label="ស្ទីគ័រ">
              @foreach (['💖', '💍', '🥂', '👑', '🌸', '😎', '🎀', '✨', '🥹'] as $e)
                <button type="button" class="lb2-tray-item" data-sticker="{{ $e }}" aria-label="sticker {{ $e }}">{{ $e }}</button>
              @endforeach
              @foreach (['I DO', 'BFF4EVER', 'ស្រលាញ់ណាស់', 'CUTE!!'] as $t)
                <button type="button" class="lb2-tray-item txt{{ $kh($t) }}" data-sticker="{{ $t }}" data-text="1">{{ $t }}</button>
              @endforeach
            </div>
            <button type="button" class="lb2-link" id="lb2-board-reset">🧽 សម្អាតរូប</button>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 15 · THANKS / APOLOGY: notes-app cards ============ --}}
    @if ($letterRows->isNotEmpty())
      <section class="lb2-sec {{ $bg() }}" id="lb2-letters">
        {!! $doodles() !!}
        <div class="lb2-wrap lb2-letters">
          @foreach ($letterRows as [$khHead, $en, $emoji, $text])
            <article class="lb2-note lb2-r" data-r="{{ $loop->index ? 'right' : 'left' }}">
              <div class="lb2-note-bar"><span>‹ Notes</span><b aria-hidden="true">{{ $emoji }}</b></div>
              <p class="lb2-note-en">{{ $en }}</p>
              <h3 class="lb2-note-kh">{{ $khHead }}</h3>
              <p class="lb2-note-text">{{ $text }}</p>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 16 · FINALE: heart photo + sparkles ============ --}}
    <section class="lb2-finale lb2-bg-holo" id="lb2-finale">
      {!! $doodles(10) !!}
      <div class="lb2-wrap">
        @if ($finaleUrl)
          <div class="lb2-heart-wrap lb2-r" data-r="pop">
            <svg class="lb2-heart-back" viewBox="0 0 100 100" aria-hidden="true"><path d="M50 96C8 68 0 42 10 22C20 3 41 2 50 20C59 2 80 3 90 22C100 42 92 68 50 96Z" fill="var(--lb2-accent)" stroke="var(--lb2-ink)" stroke-width="3"/></svg>
            <div class="lb2-heart-photo"><img src="{{ $finaleUrl }}" alt="" loading="lazy"></div>
            @for ($s = 0; $s < 6; $s++)
              <svg class="lb2-heart-spark" style="--a:{{ $s * 60 + 20 }}deg;--d:{{ $s * .35 }}s" viewBox="-20 -20 40 40" aria-hidden="true"><use href="#lb2-spark"/></svg>
            @endfor
          </div>
        @endif
        <p class="lb2-finale-line lb2-r{{ $kh($closingLine) }}" data-r="pop">{{ $closingLine }}</p>
        <p class="lb2-body center lb2-r" data-r="up">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ 🫶</p>
        @if ($coupleTitle)<p class="lb2-finale-names lb2-r{{ $kh($coupleTitle) }}" data-r="up">{{ $coupleTitle }}</p>@endif
        <button type="button" class="lb2-btn lb2-love" id="lb2-love"><span aria-hidden="true">💖</span> ផ្ញើក្តីស្រលាញ់ <b id="lb2-love-n" hidden>×០</b></button>
      </div>
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'lb2-kh' : ''" :date="$dateNumeric"
      class="lb2-footer"
      style="--inv-footer-bg:#2b1d3a; --inv-footer-text:#fff4fa; --inv-footer-muted:#d9c8ff; --inv-footer-accent:{{ $accent }}; --inv-footer-ring:rgba(255,255,255,.25); --inv-footer-names-font:'Fredoka','Kantumruy Pro',sans-serif; --inv-footer-caps-font:'Fredoka','Kantumruy Pro',sans-serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><svg viewBox="-20 -20 40 40" aria-hidden="true" style="color:{{ $accent }}"><use href="#lb2-heart"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview (FLIP zoom from the tapped photo) --}}
  <div class="lb2-lb" id="lb2-lb" hidden role="dialog" aria-modal="true" aria-label="មើលរូបភាព">
    <div class="lb2-lb-bg" aria-hidden="true"></div>
    <figure class="lb2-lb-fig" id="lb2-lb-fig"><img id="lb2-lb-img" alt="" draggable="false"></figure>
    <p class="lb2-lb-cap" id="lb2-lb-cap"></p>
    <p class="lb2-lb-count" id="lb2-lb-count"></p>
    <button type="button" class="lb2-lb-btn x" id="lb2-lb-x" aria-label="បិទ">✕</button>
    <button type="button" class="lb2-lb-btn prev" id="lb2-lb-prev" aria-label="រូបមុន">←</button>
    <button type="button" class="lb2-lb-btn next" id="lb2-lb-next" aria-label="រូបបន្ទាប់">→</button>
  </div>
  <div class="lb2-toast" id="lb2-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything --}}
  @if ($musicFile)<audio id="lb2-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
  <div class="lb2-sound-wrap">
    @if ($songTitle !== '' && $hasSong)
      <p class="lb2-now" id="lb2-now"><span class="disc" aria-hidden="true"></span><span class="t{{ $kh($songTitle) }}">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $hasSong)
      <a class="lb2-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></a>
    @else
      <button type="button" class="lb2-sound" id="lb2-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="lb2-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'pop' => $sfx('dragon-studio-pop-402324.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'paper' => $sfx('papercrackle1.mp3'),
          'send' => $sfx('envelope.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $lbAsset('js/invitations/love-bubble.js') }}"></script>
