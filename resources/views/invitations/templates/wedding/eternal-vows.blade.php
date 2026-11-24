@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Eternal Vows: a classic Western black-tie wedding (a grand hotel ballroom in Europe).
    // Ivory paper, deep ink and champagne-gold foil that shimmers as you scroll / move the pointer.
    // Cover: a stage framed by emerald velvet curtains tied back with gold rope; on ivory damask the
    // laurel monogram crest, the couple's photo in an ornate gilded arch frame, the guest's name
    // engraved on a gold plaque and an «open» button. Opening: the tiebacks release and the curtains
    // sweep closed behind the frame, a light sweep crosses the velvet, then the curtains part and lift
    // while the gilded frame flies (shared element, FLIP) into its place in the hero; gold dust bursts.
    // Inside: string lights over the hero, a foil-stamped invitation card, cameo portraits, a note,
    // a roman clock that sets its hands to the hour + engraved countdown, a story timeline that draws
    // in gold as you scroll, an «Order of service» menu card with clinking champagne coupes, a salon
    // gallery wall of gilded frames that sway with scroll speed (FLIP lightbox), an engraved map,
    // fabric-chip dress code swatches, an engraved reply card that gets a foil stamp and flips, a velvet
    // ring box that opens on the gift QR, calling cards, letters and a champagne toast finale.
    // Everything personal comes from $fields (the Khmer copy is template text). On demo pages
    // $invitation is null, so every premium extra previews.
    // CSS public/css/invitations/eternal-vows.css · JS public/js/invitations/eternal-vows.js
    // Debug: ?open=1 skips the cover.
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $evAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $khDate = fn ($d) => 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year);
    $roman = function (int $n): string {
        $out = '';
        foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $k => $v) {
            while ($n >= $v) {
                $out .= $k;
                $n -= $v;
            }
        }

        return $out;
    };

    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' ev-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');
    $initialParts = array_values(array_filter(array_map('trim', preg_split('/\s*(?:&|\+|♥|and)\s*/u', $initials, 2)), fn ($p) => $p !== ''));
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';

    // gold: the accent + a lighter and a deeper shade (for the foil gradient and the damask)
    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? strtolower($fields['accent_color']) : '#b08d57';
    $mix = function (string $hex, string $with, float $k): string {
        $a = sscanf($hex, '#%02x%02x%02x');
        $b = sscanf($with, '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', ...array_map(fn ($i) => (int) round($a[$i] + ($b[$i] - $a[$i]) * $k), [0, 1, 2]));
    };
    $goldHi = $mix($accent, '#fff6dc', .55);
    $goldLo = $mix($accent, '#2a1d0c', .45);
    $goldXlo = $mix($accent, '#1a1206', .66);

    $headline = trim($fields['headline'] ?? '') ?: 'Together with their families';
    $titleImg = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateRoman = $eventDate ? $roman($eventDate->day).' · '.$roman($eventDate->month).' · '.$roman($eventDate->year) : null;
    $dateKhmer = $eventDate ? $khDate($eventDate) : null;
    $period = fn (int $h) => $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));
    $khTime = fn (int $h, string $min) => $khDigits(sprintf('%d:%s', $h % 12 ?: 12, $min)).' '.$period($h);
    $endTime = preg_match('/^(\d{1,2}):(\d{2})/', trim($fields['end_time'] ?? ''), $et) ? $khTime((int) $et[1], $et[2]) : null;
    $timeKhmer = $eventDate ? 'វេលាម៉ោង '.$khTime($eventDate->hour, $eventDate->format('i')).($endTime ? ' – '.$endTime : '') : null;
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
        ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', 'With our apologies', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'Forever & always';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    $finaleBg = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : null;
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))->values();
    $splitTitle = fn ($t) => array_pad(array_map('trim', explode('|', (string) $t, 2)), 2, '');

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
        ['role' => 'ខាងកូនប្រុស', 'en' => 'The groom', 'name' => $groomPretty, 'phone' => trim($fields['groom_phone'] ?? '')],
        ['role' => 'ខាងកូនស្រី', 'en' => 'The bride', 'name' => $bridePretty, 'phone' => trim($fields['bride_phone'] ?? '')],
        ['role' => 'ព័ត៌មានបន្ថែម', 'en' => 'Enquiries', 'name' => '', 'phone' => trim($fields['contact_phone'] ?? '')],
    ])->filter(fn ($c) => $c['phone'] !== '')->unique('phone')->values();

    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }
    $songTitle = trim($fields['song_title'] ?? '');

    // ---------------- gold-foil art (all drawn here as SVG) ----------------
    // arch outline (rectangle with a round top) between x0..x1, from top to bottom
    $arch = function (float $x0, float $x1, float $top, float $bottom): string {
        $r = ($x1 - $x0) / 2;
        $cy = $top + $r;

        return "M{$x0} {$bottom}V{$cy}A{$r} {$r} 0 0 1 {$x1} {$cy}V{$bottom}Z";
    };

    // the gilded arch frame round a photo (cover, hero, finale). viewBox -24 -34 348 458 → photo window 34..266 × 34..366
    $frameN = 0;
    $frame = function (?string $url, string $alt, string $fallback) use (&$frameN, $arch, $kh): string {
        $id = 'ev-clip-'.(++$frameN);
        $photo = $arch(34, 266, 34, 366);
        $img = $url
            ? '<image href="'.e($url).'" x="34" y="34" width="232" height="332" preserveAspectRatio="xMidYMid slice" clip-path="url(#'.$id.')"/>'
            : '<path d="'.$photo.'" fill="#1f1b15"/><text x="150" y="215" text-anchor="middle" class="ev-frame-ini'.$kh($fallback).'">'.e($fallback).'</text>';

        return '<svg class="ev-frame-svg" viewBox="-24 -34 348 458" role="img" aria-label="'.e($alt).'">'
            .'<defs><clipPath id="'.$id.'"><path d="'.$photo.'"/></clipPath></defs>'
            .'<path d="'.$arch(30, 270, 30, 370).'" fill="#14120e"/>'
            .$img
            .'<path d="'.$photo.'" fill="url(#ev-vignette)"/>'
            // the moulding: outer band, bevel highlights, a beaded row and an ivory liner next to the photo
            .'<path fill-rule="evenodd" d="'.$arch(4, 296, 4, 396).$arch(30, 270, 30, 370).'" fill="url(#ev-foil-g)"/>'
            .'<path d="'.$arch(4, 296, 4, 396).'" fill="none" stroke="#fff3cf" stroke-opacity=".55" stroke-width="1"/>'
            .'<path d="'.$arch(11, 289, 11, 389).'" fill="none" stroke="#3a2a12" stroke-opacity=".35" stroke-width="1.2"/>'
            .'<path d="'.$arch(18, 282, 18, 382).'" fill="none" stroke="#fff6dc" stroke-opacity=".8" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="0 7.2"/>'
            .'<path d="'.$arch(25, 275, 25, 375).'" fill="none" stroke="#3a2a12" stroke-opacity=".4" stroke-width="1"/>'
            .'<path d="'.$arch(31.5, 268.5, 31.5, 368.5).'" fill="none" stroke="#f5ead0" stroke-width="2.4"/>'
            // carved ornaments: keystone crest on top, acanthus scrolls on the shoulders, a shell at the foot, corner rosettes
            .'<use href="#ev-keystone" transform="translate(150 6)"/>'
            .'<use href="#ev-acanthus" transform="translate(52 52) rotate(-42)"/>'
            .'<use href="#ev-acanthus" transform="translate(248 52) rotate(42) scale(-1 1)"/>'
            .'<use href="#ev-shell" transform="translate(150 398)"/>'
            .'<use href="#ev-rosette" transform="translate(10 390)"/><use href="#ev-rosette" transform="translate(290 390)"/>'
            .'</svg>';
    };

    // the laurel wreath of the monogram crest: two branches up from a bow, leaves alternating out / in
    $laurel = (function () {
        $leaf = 'M0 0C4.5 -5.2 13 -5.6 18 0C13 5.6 4.5 5.2 0 0Z';
        $r = 47;
        $branch = '';
        $stem = [];
        $n = 11;
        for ($k = 0; $k <= $n; $k++) {
            $a = deg2rad(78 - $k * (138 / $n)); // from the bottom (78°) up the right side to −60°
            $stem[] = round(cos($a) * $r, 2).' '.round(sin($a) * $r, 2);
            if ($k === 0) {
                continue;
            }
            $x = cos($a) * $r;
            $y = sin($a) * $r;
            $tan = rad2deg(atan2(-cos($a), sin($a))); // direction of travel (upwards)
            $s = round(1.05 - $k * .035, 2);
            $branch .= '<path d="'.$leaf.'" transform="translate('.round($x, 1).' '.round($y, 1).') rotate('.round($tan - 38).') scale('.$s.')"/>';
            $branch .= '<path d="'.$leaf.'" transform="translate('.round($x, 1).' '.round($y, 1).') rotate('.round($tan + 34).') scale('.round($s * .86, 2).')"/>';
            if ($k % 3 === 1) {
                $branch .= '<circle cx="'.round(cos($a) * ($r + 9), 1).'" cy="'.round(sin($a) * ($r + 9), 1).'" r="1.8"/>';
            }
        }
        $stemPath = '<path d="M'.implode(' L', $stem).'" fill="none" stroke="url(#ev-foil-g)" stroke-width="1.6"/>';

        return '<g fill="url(#ev-foil-g)">'.$stemPath.$branch.'</g><g transform="scale(-1 1)" fill="url(#ev-foil-g)">'.$stemPath.$branch.'</g>'
            .'<g fill="none" stroke="url(#ev-foil-g)" stroke-width="1.6" stroke-linecap="round">'
            .'<path d="M0 47C-6 41 -15 41 -14 47C-13 52 -5 51 0 47C5 51 13 52 14 47C15 41 6 41 0 47Z"/><path d="M-2 48L-9 60M2 48L9 60"/></g>'
            .'<path d="M0 -62L3 -56L0 -50L-3 -56Z" fill="url(#ev-foil-g)"/>';
    })();
    $crest = function (string $cls = '') use ($laurel, $initialParts, $initials, $isKh): string {
        $text = count($initialParts) === 2
            ? '<span class="a">'.e($initialParts[0]).'</span><em>&amp;</em><span class="b">'.e($initialParts[1]).'</span>'
            : '<span class="a">'.e($initials).'</span>';

        return '<div class="ev-crest '.$cls.($isKh($initials) ? ' is-kh' : '').'" aria-hidden="true"><svg viewBox="-70 -70 140 140">'
            .'<circle r="36" fill="none" stroke="url(#ev-foil-g)" stroke-width=".8"/><circle r="33" fill="none" stroke="url(#ev-foil-g)" stroke-width=".4"/>'
            .$laurel.'</svg><p class="ev-crest-ini">'.$text.'</p></div>';
    };

    // art-deco divider: double rules, a fan of rays and a diamond. Drawn in (pathLength) when revealed
    $deco = '<svg class="ev-deco" viewBox="-130 -24 260 40" aria-hidden="true"><g fill="none" stroke="url(#ev-foil-g)" stroke-width="1.1" stroke-linecap="round">'
        .'<path pathLength="1" d="M-128 0H-30M30 0H128"/><path pathLength="1" d="M-110 5H-36M36 5H110" stroke-width=".6"/>'
        .'<path pathLength="1" d="M-30 0L-22 -6L-14 0M30 0L22 -6L14 0"/>'
        .'<path pathLength="1" d="M0 -2L-8 -12M0 -2L-4 -16M0 -2L0 -19M0 -2L4 -16M0 -2L8 -12"/>'
        .'</g><path d="M0 -5L6 2L0 9L-6 2Z" fill="url(#ev-foil-g)"/><circle cx="-128" cy="0" r="1.8" fill="url(#ev-foil-g)"/><circle cx="128" cy="0" r="1.8" fill="url(#ev-foil-g)"/></svg>';

    // a deco fan for the section corners
    $fan = (function () {
        $out = '<g fill="none" stroke="url(#ev-foil-g)" stroke-width="1">';
        foreach ([22, 34, 46] as $r) {
            $out .= '<path d="M'.$r.' 0A'.$r.' '.$r.' 0 0 1 0 '.$r.'"/>';
        }
        for ($k = 0; $k <= 6; $k++) {
            $a = deg2rad($k * 15);
            $out .= '<path d="M'.round(cos($a) * 52, 1).' '.round(sin($a) * 52, 1).'L'.round(cos($a) * ($k % 2 ? 66 : 76), 1).' '.round(sin($a) * ($k % 2 ? 66 : 76), 1).'"/>';
        }

        return $out.'<path d="M0 0H96M0 0V96M5 5H88M5 5V88" stroke-width=".7"/></g><path d="M8 8L13 13L8 18L3 13Z" fill="url(#ev-foil-g)"/>';
    })();
    $decor = fn () => '<div class="ev-decor" aria-hidden="true">'
        .collect(['tl', 'tr', 'bl', 'br'])->map(fn ($c) => '<svg class="ev-corner '.$c.'" viewBox="0 0 100 100">'.$fan.'</svg>')->implode('')
        .'</div>';

    $head = fn (string $en, string $khText) => '<header class="ev-head ev-r" data-r="head">'.$deco
        .'<p class="ev-script ev-foil">'.e($en).'</p><h2 class="ev-head-kh">'.e($khText).'</h2></header>';

    // sections alternate ivory paper and black-tie ink, in the order they are shown
    $toneN = 0;
    $tone = function () use (&$toneN): string {
        return ($toneN++) % 2 ? 'ev-dark' : 'ev-light';
    };

    // damask wallpaper (gold on ivory / on ink), as a data-URI background
    $damask = function (string $color, float $op) {
        $half = '<path d="M0 -14C14 -24 30 -10 22 4C16 14 4 10 8 2C11 -3 17 0 15 4"/><path d="M3 -20C13 -34 27 -32 24 -20"/><path d="M0 12C9 16 15 28 0 44"/><path d="M6 26C18 22 28 30 24 42C22 48 15 46 17 41"/>';
        $motif = '<g fill="none" stroke="'.$color.'" stroke-width="1.5" stroke-linecap="round">'.$half.'<g transform="scale(-1 1)">'.$half.'</g></g>'
            .'<path d="M0 -42L6 -31L0 -22L-6 -31Z" fill="'.$color.'"/><circle cy="2" r="2.2" fill="'.$color.'"/><path d="M0 14C5 22 5 32 0 40C-5 32 -5 22 0 14Z" fill="'.$color.'"/>';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="140" viewBox="0 0 96 140"><g opacity="'.$op.'">'
            .'<g transform="translate(48 46)">'.$motif.'</g><g transform="translate(0 116)">'.$motif.'</g><g transform="translate(96 116)">'.$motif.'</g>'
            .'<g transform="translate(0 -24)">'.$motif.'</g><g transform="translate(96 -24)">'.$motif.'</g>'
            .'<circle cx="48" cy="116" r="1.4" fill="'.$color.'"/><circle cx="0" cy="46" r="1.4" fill="'.$color.'"/><circle cx="96" cy="46" r="1.4" fill="'.$color.'"/></g></svg>';

        return 'url("data:image/svg+xml,'.rawurlencode($svg).'")';
    };

    // the roman clock face (save the date): numerals + ticks
    $clockFace = (function () use ($roman) {
        $out = '';
        for ($k = 1; $k <= 12; $k++) {
            $a = deg2rad($k * 30 - 90);
            $out .= '<text x="'.round(cos($a) * 74, 1).'" y="'.round(sin($a) * 74 + 5, 1).'" text-anchor="middle">'.$roman($k).'</text>';
        }
        $ticks = '';
        for ($k = 0; $k < 60; $k++) {
            $a = deg2rad($k * 6);
            $r0 = $k % 5 ? 88 : 84;
            $ticks .= 'M'.round(cos($a) * $r0, 1).' '.round(sin($a) * $r0, 1).'L'.round(cos($a) * 92, 1).' '.round(sin($a) * 92, 1);
        }

        return '<path d="'.$ticks.'" stroke="currentColor" stroke-width="1"/><g class="ev-clock-num">'.$out.'</g>';
    })();

    // the champagne coupes over the order of service
    $coupe = '<g class="glass"><path d="M-27 0Q-25 17 0 19Q25 17 27 0Z" fill="#fff" fill-opacity=".14" stroke="url(#ev-foil-g)" stroke-width="1.6"/>'
        .'<path d="M-24 5Q-21 15 0 16Q21 15 24 5Z" fill="url(#ev-foil-g)" opacity=".75"/>'
        .'<path d="M0 19V52M-14 55Q0 49 14 55" fill="none" stroke="url(#ev-foil-g)" stroke-width="1.8" stroke-linecap="round"/>'
        .'<g class="bub" fill="#fff8e6"><circle cx="-8" cy="10" r="1.3"/><circle cx="3" cy="12" r="1"/><circle cx="10" cy="9" r="1.4"/><circle cx="-2" cy="8" r=".9"/></g></g>';

    $skipGate = request()->boolean('open');
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Cormorant Garamond / Cinzel / Pinyon Script are Latin-only: every CSS stack follows them with Noto Serif Khmer or
     Moulpali; Kantumruy Pro carries both scripts for the body --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Cinzel:wght@400;500;600&family=Pinyon+Script&family=Moulpali&family=Noto+Serif+Khmer:wght@400;500;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $evAsset('css/invitations/eternal-vows.css') }}">
<noscript><style>.ev-gate{display:none}.ev-page{visibility:visible!important}.ev-r{opacity:1!important;transform:none!important;clip-path:none!important}.ev-hero-frame{visibility:visible!important}</style></noscript>

<div class="ev{{ $skipGate ? ' is-open is-landed' : '' }}"
  style="--ev-gold: {{ $accent }}; --ev-gold-hi: {{ $goldHi }}; --ev-gold-lo: {{ $goldLo }}; --ev-gold-xlo: {{ $goldXlo }}; --ev-dmk-light: {{ $damask($goldLo, .16) }}; --ev-dmk-dark: {{ $damask($accent, .1) }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- the gold foil: its highlight slides with scroll and the pointer (JS moves the gradientTransform) --}}
      <linearGradient id="ev-foil-g" x1="0" y1="0" x2="1" y2="1" spreadMethod="reflect">
        <stop offset="0" stop-color="{{ $goldLo }}"/><stop offset=".22" stop-color="{{ $accent }}"/><stop offset=".42" stop-color="{{ $goldHi }}"/>
        <stop offset=".5" stop-color="#fff7e0"/><stop offset=".58" stop-color="{{ $goldHi }}"/><stop offset=".78" stop-color="{{ $accent }}"/><stop offset="1" stop-color="{{ $goldLo }}"/>
      </linearGradient>
      <radialGradient id="ev-vignette" cx=".5" cy=".45" r=".7">
        <stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".38"/>
      </radialGradient>
      {{-- carved frame ornaments (as <g>, never <symbol>) --}}
      <g id="ev-keystone">
        <path d="M-26 0C-20 -10 -10 -16 0 -26C10 -16 20 -10 26 0C18 6 8 8 0 18C-8 8 -18 6 -26 0Z" fill="url(#ev-foil-g)" stroke="#3a2a12" stroke-opacity=".35" stroke-width=".8"/>
        <path d="M-14 0C-9 -6 -4 -9 0 -14C4 -9 9 -6 14 0C9 3 4 5 0 10C-4 5 -9 3 -14 0Z" fill="none" stroke="#fff6dc" stroke-opacity=".75" stroke-width="1"/>
        <circle cy="-1" r="3.4" fill="#fff6dc" fill-opacity=".85"/>
        <path d="M-26 0C-34 -2 -40 -8 -38 -14C-36 -19 -29 -18 -30 -12M26 0C34 -2 40 -8 38 -14C36 -19 29 -18 30 -12" fill="none" stroke="url(#ev-foil-g)" stroke-width="2.4" stroke-linecap="round"/>
      </g>
      <g id="ev-acanthus" fill="none" stroke="url(#ev-foil-g)" stroke-width="3" stroke-linecap="round">
        <path d="M0 0C10 -16 32 -18 38 -4C42 6 32 14 24 8C18 3 23 -4 28 -1"/>
        <path d="M2 4C-10 10 -14 22 -8 30" stroke-width="2.2"/>
        <path d="M14 -10C16 -20 24 -26 32 -24" stroke-width="1.6"/>
      </g>
      <g id="ev-shell">
        <path d="M-24 0C-22 -14 -12 -20 0 -20C12 -20 22 -14 24 0C14 6 -14 6 -24 0Z" fill="url(#ev-foil-g)" stroke="#3a2a12" stroke-opacity=".35" stroke-width=".8"/>
        <path d="M0 2V-18M-8 2L-12 -15M8 2L12 -15M-16 1L-20 -9M16 1L20 -9" stroke="#3a2a12" stroke-opacity=".38" stroke-width="1"/>
      </g>
      <g id="ev-rosette"><circle r="9" fill="url(#ev-foil-g)"/><circle r="5.5" fill="none" stroke="#3a2a12" stroke-opacity=".4"/><circle r="2.2" fill="#fff6dc"/></g>
      <g id="ev-diamond"><path d="M0 -6L5 0L0 6L-5 0Z"/></g>
    </defs>
  </svg>

  {{-- ============ 0 · COVER: the stage behind velvet curtains ============ --}}
  @unless ($skipGate)
    <div class="ev-gate" id="ev-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      <div class="ev-gate-back" aria-hidden="true"><span class="ev-spot"></span></div>

      <div class="ev-gate-col">
        {!! $crest('ev-gate-crest ev-gate-in') !!}
        <p class="ev-gate-kicker ev-gate-in">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
        <p class="ev-gate-headline ev-gate-in{{ $kh($headline) }}">{{ $headline }}</p>
        <div class="ev-gate-frame ev-gate-in" id="ev-gate-frame">{!! $frame($coverUrl, $coupleTitle, $initials) !!}</div>
        @if ($titleImg)
          <img class="ev-gate-title ev-gate-in" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
        @elseif ($coupleTitle)
          <h1 class="ev-gate-names ev-gate-in">
            @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
            @if ($groomPretty && $bridePretty)<em>&amp;</em>@endif
            @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
          </h1>
        @endif
        @if ($dateRoman)<p class="ev-gate-date ev-gate-in">{{ $dateRoman }}</p>@endif
        {{-- the guest's name engraved on a gold plaque --}}
        <div class="ev-plaque ev-gate-in">
          <span class="ev-plaque-screw l" aria-hidden="true"></span><span class="ev-plaque-screw r" aria-hidden="true"></span>
          <p class="ev-plaque-to">សូមគោរពអញ្ជើញ</p>
          <p class="ev-plaque-name{{ $kh($guest) }}">{{ $guest }}</p>
        </div>
        <button type="button" class="ev-open ev-gate-in" id="ev-open">
          <span class="ev-open-kh">បើកសំបុត្រ</span><span class="ev-open-en">Open the invitation</span>
        </button>
      </div>

      {{-- emerald velvet curtains, tied back with gold rope; a swagged valance with tassels on top --}}
      <div class="ev-curtain l" aria-hidden="true"><span class="ev-fringe"></span></div>
      <div class="ev-curtain r" aria-hidden="true"><span class="ev-fringe"></span></div>
      <span class="ev-tie l" aria-hidden="true"><i></i></span><span class="ev-tie r" aria-hidden="true"><i></i></span>
      <div class="ev-valance" aria-hidden="true">
        <svg viewBox="0 0 400 60" preserveAspectRatio="none">
          <path d="M0 0H400V22C380 50 340 50 320 22C300 50 260 50 240 22C220 50 180 50 160 22C140 50 100 50 80 22C60 50 20 50 0 22Z" fill="#0d2c25"/>
          <path d="M0 22C20 50 60 50 80 22C100 50 140 50 160 22C180 50 220 50 240 22C260 50 300 50 320 22C340 50 380 50 400 22" fill="none" stroke="url(#ev-foil-g)" stroke-width="2.4" vector-effect="non-scaling-stroke"/>
          <path d="M0 6H400" stroke="url(#ev-foil-g)" stroke-width="1.4" vector-effect="non-scaling-stroke"/>
        </svg>
        <span class="ev-tassel" style="left:20%"></span><span class="ev-tassel" style="left:40%"></span><span class="ev-tassel" style="left:60%"></span><span class="ev-tassel" style="left:80%"></span>
      </div>
      <span class="ev-sweep" aria-hidden="true"></span>
      <div class="ev-grain" aria-hidden="true"></div>
    </div>
  @endunless

  {{-- gold dust: twinkles all the time, bursts of foil confetti on taps, champagne bubbles for the toast --}}
  <canvas class="ev-dust" id="ev-dust" aria-hidden="true"></canvas>

  <main class="ev-page" id="ev-top">

    {{-- ============ 1 · HERO: string lights over the gilded frame ============ --}}
    <section class="ev-hero ev-dark" id="ev-hero">
      <svg class="ev-lights" id="ev-lights" viewBox="0 0 1000 120" preserveAspectRatio="none" aria-hidden="true">
        <path d="M-10 6Q250 120 500 20Q750 120 1010 6" fill="none" stroke="#5c5141" stroke-width="1.4" vector-effect="non-scaling-stroke"/>
      </svg>
      <div class="ev-bulbs" aria-hidden="true">
        @for ($b = 0; $b < 21; $b++)
          @php($t = $b / 20)
          @php($half = $t < .5 ? $t * 2 : ($t - .5) * 2)
          @php($y = (1 - $half) * (1 - $half) * ($t < .5 ? 6 : 20) + 2 * (1 - $half) * $half * 120 + $half * $half * ($t < .5 ? 20 : 6))
          <span class="ev-bulb" style="left:{{ round($t * 100, 2) }}%; top:{{ round($y / 120 * 100, 2) }}%; --d:{{ ($b * 37 % 23) / 10 }}s"></span>
        @endfor
      </div>
      {!! $decor() !!}
      <div class="ev-hero-grid">
        <div class="ev-hero-frame" id="ev-hero-frame">
          <div class="ev-hero-frame-in" data-speed=".08">{!! $frame($coverUrl, $coupleTitle, $initials) !!}</div>
        </div>
        <div class="ev-hero-text">
          <p class="ev-eyebrow ev-r" data-r="up">The wedding celebration of</p>
          @if ($titleImg)
            <img class="ev-hero-title ev-r" data-r="rise" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
          @else
            <h2 class="ev-hero-names">
              @if ($groomPretty)<span class="ev-r ev-foil{{ $kh($groomPretty) }}" data-r="wipe">{{ $groomPretty }}</span>@endif
              @if ($groomPretty && $bridePretty)<em class="ev-r" data-r="rise" aria-hidden="true">&amp;</em>@endif
              @if ($bridePretty)<span class="ev-r ev-foil{{ $kh($bridePretty) }}" data-r="wipe">{{ $bridePretty }}</span>@endif
            </h2>
          @endif
          @if ($dateRoman)<p class="ev-hero-roman ev-r" data-r="up">{{ $dateRoman }}</p>@endif
          @if ($dateKhmer)<p class="ev-hero-date ev-r" data-r="up">{{ $dateKhmer }}</p>@endif
          @if ($venueName)<p class="ev-hero-venue ev-r{{ $kh($venueName) }}" data-r="up">{{ $venueName }}</p>@endif
          <a class="ev-scroll ev-r" data-r="up" href="#ev-invite" aria-label="អានបន្ត" data-sfx="tap"><span></span></a>
        </div>
      </div>
    </section>

    {{-- ============ 2 · THE INVITATION CARD (foil-stamped) ============ --}}
    <section class="ev-sec {{ $tone() }}" id="ev-invite">
      {!! $decor() !!}
      <article class="ev-card ev-invite ev-r" data-r="unfold">
        <span class="ev-card-corners" aria-hidden="true"></span>
        {!! $crest('ev-invite-crest') !!}
        <p class="ev-caps{{ $kh($headline) }}">{{ $headline }}</p>
        @if ($groomParents || $brideParents)
          <div class="ev-parents">
            <div><p class="ev-label">មាតាបិតាខាងកូនប្រុស</p>@foreach ($groomParents as $line)<p class="ev-parent">{{ $line }}</p>@endforeach</div>
            <svg class="ev-parents-orn" viewBox="-8 -40 16 80" aria-hidden="true"><path d="M0 -40V-10M0 10V40" stroke="url(#ev-foil-g)" stroke-width="1"/><use href="#ev-diamond" fill="url(#ev-foil-g)"/></svg>
            <div><p class="ev-label">មាតាបិតាខាងកូនស្រី</p>@foreach ($brideParents as $line)<p class="ev-parent">{{ $line }}</p>@endforeach</div>
          </div>
        @endif
        <h3 class="ev-invite-title">មានកិត្តិយសសូមគោរពអញ្ជើញ</h3>
        <div class="ev-plaque ev-plaque-sm">
          <span class="ev-plaque-screw l" aria-hidden="true"></span><span class="ev-plaque-screw r" aria-hidden="true"></span>
          <p class="ev-plaque-name{{ $kh($guest) }}">{{ $guest }}</p>
        </div>
        <p class="ev-body ev-r" data-r="words">{{ $receptionText }}</p>
        @if ($coupleTitle)
          <div class="ev-couple">
            @if ($groomPretty)<div><p class="ev-label">កូនប្រុសនាម</p><p class="ev-couple-name ev-foil{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>@endif
            @if ($groomPretty && $bridePretty)<p class="ev-couple-amp" aria-hidden="true">&amp;</p>@endif
            @if ($bridePretty)<div><p class="ev-label">កូនស្រីនាម</p><p class="ev-couple-name ev-foil{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>@endif
          </div>
        @endif
        @if ($eventDate || $venueName)
          <div class="ev-when">
            @if ($dateKhmer)<p>{{ $dateKhmer }}</p>@endif
            @if ($khmerLunarDate)<p class="ev-soft">{{ $khmerLunarDate }}</p>@endif
            @if ($timeKhmer)<p class="ev-gold-text">{{ $timeKhmer }}</p>@endif
            @if ($venueName)<p class="ev-soft{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          </div>
        @endif
      </article>
    </section>

    {{-- ============ 3 · BRIDE & GROOM (oval cameo frames) ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="ev-sec {{ $tone() }}" id="ev-couple">
        {!! $decor() !!}
        {!! $head('The bride & groom', 'កូនកំលោះ និងកូនក្រមុំ') !!}
        <div class="ev-duo">
          @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ', 'The groom'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ', 'The bride']] as $k => [$photo, $name, $role, $en])
            @if ($photo)
              <figure class="ev-cameo ev-r" data-r="iris" style="--i:{{ $k }}">
                <div class="ev-cameo-frame"><div class="ev-cameo-mat"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></div></div>
                <figcaption><span class="ev-caps-sm">{{ $en }}</span>@if ($name)<span class="ev-cameo-name ev-foil{{ $kh($name) }}">{{ $name }}</span>@endif<span class="ev-label">{{ $role }}</span></figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 4 · A NOTE FROM US ============ --}}
    @if ($personalNote !== '')
      <section class="ev-sec {{ $tone() }}" id="ev-note">
        {!! $decor() !!}
        <blockquote class="ev-quote ev-r" data-r="rise">
          <span class="ev-quote-mark" aria-hidden="true">&ldquo;</span>
          <p class="ev-quote-text ev-r" data-r="words">{{ $personalNote }}</p>
          {!! $deco !!}
          <p class="ev-script ev-foil ev-quote-sign">with love{{ $coupleTitle && ! $isKh($coupleTitle) ? ', '.$coupleTitle : '' }}</p>
          @if ($coupleTitle && $isKh($coupleTitle))<p class="ev-quote-kh">{{ $coupleTitle }}</p>@endif
        </blockquote>
      </section>
    @endif

    {{-- ============ 5 · SAVE THE DATE: the roman clock + engraved countdown ============ --}}
    @if ($eventDate)
      <section class="ev-sec {{ $tone() }}" id="ev-date">
        {!! $decor() !!}
        {!! $head('Save the date', 'ថ្ងៃ និងវេលាមង្គល') !!}
        <div class="ev-date-grid">
          <div class="ev-clock ev-r" data-r="rise" id="ev-clock" data-h="{{ $eventDate->hour }}" data-m="{{ (int) $eventDate->format('i') }}">
            <svg viewBox="-110 -110 220 220" aria-hidden="true">
              <circle r="106" fill="url(#ev-foil-g)"/><circle r="100" fill="#14120e"/><circle r="97" fill="none" stroke="url(#ev-foil-g)" stroke-width="1"/>
              <circle r="95" class="ev-clock-face"/>
              {!! $clockFace !!}
              <circle r="44" fill="none" stroke="currentColor" stroke-opacity=".35" stroke-width=".6"/>
              <text class="ev-clock-brand" y="-30" text-anchor="middle">{{ $enMonths[$eventDate->month - 1] }}</text>
              <text class="ev-clock-day" y="42" text-anchor="middle">{{ $roman($eventDate->day) }}</text>
              <g class="ev-hand h"><path d="M-3 10L0 -48L3 10Z" fill="url(#ev-foil-g)"/><circle cy="-36" r="4" fill="none" stroke="url(#ev-foil-g)" stroke-width="1.6"/></g>
              <g class="ev-hand m"><path d="M-2 12L0 -72L2 12Z" fill="url(#ev-foil-g)"/></g>
              <g class="ev-hand s"><path d="M0 16V-80" stroke="#9b2c2c" stroke-width="1"/><circle cy="16" r="3" fill="#9b2c2c"/></g>
              <circle r="5" fill="url(#ev-foil-g)"/><circle r="1.8" fill="#14120e"/>
            </svg>
          </div>
          <div class="ev-date-text">
            <p class="ev-date-roman ev-r ev-foil" data-r="wipe">{{ $dateRoman }}</p>
            <p class="ev-body ev-r" data-r="up">{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="ev-soft ev-r" data-r="up">{{ $khmerLunarDate }}</p>@endif
            <p class="ev-gold-text ev-r" data-r="up">{{ $timeKhmer }}</p>
            @if ($showCountdown)
              <div class="ev-count ev-r" data-r="stag" id="ev-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
                @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
                  <div class="ev-tile" style="--i:{{ $loop->index }}"><b data-u="{{ $u }}">០០</b><span>{{ $label }}</span></div>
                @endforeach
              </div>
              <p class="ev-count-done" id="ev-count-done" hidden>ថ្ងៃនេះហើយ! ✦</p>
            @endif
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 6 · OUR LOVE STORY: a timeline that draws in gold ============ --}}
    @if ($story->isNotEmpty())
      <section class="ev-sec ev-wide {{ $tone() }}" id="ev-story">
        {!! $decor() !!}
        {!! $head('Our love story', 'ដំណើររឿងស្នេហា') !!}
        <ol class="ev-time" id="ev-time">
          <li class="ev-time-rail" aria-hidden="true"><span class="ev-time-line"></span></li>
          @foreach ($story as $i => $c)
            @php([$place, $when] = $splitTitle($c['title'] ?? ''))
            <li class="ev-chapter {{ $i % 2 ? 'r' : 'l' }}" style="--i:{{ $i }}">
              <span class="ev-node" aria-hidden="true"><svg viewBox="-8 -8 16 16"><use href="#ev-diamond" fill="currentColor"/></svg></span>
              <div class="ev-chapter-card ev-r" data-r="{{ $i % 2 ? 'slide-r' : 'slide-l' }}">
                @if ($gallery->isNotEmpty())
                  <span class="ev-chapter-ph"><img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy"></span>
                @endif
                <p class="ev-chapter-num">{{ $roman($i + 1) }}</p>
                @if ($when !== '')<p class="ev-chapter-when{{ $kh($when) }}">{{ $when }}</p>@endif
                @if ($place !== '')<h3 class="ev-chapter-title{{ $kh($place) }}">{{ $place }}</h3>@endif
                @if (! empty($c['text']))<p class="ev-body">{{ $c['text'] }}</p>@endif
              </div>
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    {{-- ============ 7 · ORDER OF SERVICE: an engraved menu card + champagne coupes ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="ev-sec {{ $tone() }}" id="ev-program">
        {!! $decor() !!}
        <div class="ev-menu ev-r" data-r="unfold">
          <button type="button" class="ev-toast-glasses" id="ev-glasses" aria-label="លើកកែវអបអរ">
            <svg viewBox="-80 -40 160 110" aria-hidden="true">
              <g class="ev-coupe l" transform="translate(-31 4) rotate(16)">{!! $coupe !!}</g>
              <g class="ev-coupe r" transform="translate(31 4) rotate(-16)">{!! $coupe !!}</g>
              <g class="ev-clink" fill="url(#ev-foil-g)"><path d="M0 -30L2 -22L0 -14L-2 -22Z"/><path d="M-12 -24L-6 -18M12 -24L6 -18" stroke="url(#ev-foil-g)" stroke-width="1.4" stroke-linecap="round"/></g>
            </svg>
          </button>
          <p class="ev-caps">Order of service</p>
          <h2 class="ev-head-kh">កម្មវិធីសិរីមង្គល</h2>
          {!! $deco !!}
          @if ($scheduleImg)
            <button type="button" class="ev-sched-img ev-ph" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីសិរីមង្គល" data-group="sched">
              <img src="{{ $scheduleImg }}" alt="កម្មវិធីសិរីមង្គល" loading="lazy">
            </button>
          @else
            <ol class="ev-menu-list">
              @foreach ($schedule as $i => $row)
                <li class="ev-r" data-r="wipe" style="--i:{{ $i }}">
                  <span class="t">{{ $schedTime($row['time'] ?? '') }}</span>
                  <span class="dots" aria-hidden="true"></span>
                  <span class="l{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
                </li>
              @endforeach
            </ol>
          @endif
          <p class="ev-script ev-foil ev-menu-sign">{{ $coupleTitle && ! $isKh($coupleTitle) ? $coupleTitle : 'Cheers to love' }}</p>
        </div>
      </section>
    @endif

    {{-- ============ 8 · THE GALLERY: a salon wall of gilded frames ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="ev-sec ev-wide ev-salon {{ $tone() }}" id="ev-gallery">
        {!! $decor() !!}
        {!! $head('The gallery', 'វិចិត្រសាលអនុស្សាវរីយ៍') !!}
        <div class="ev-wall" id="ev-wall">
          @foreach ($gallery as $i => $url)
            <button type="button" class="ev-gf v{{ $i % 6 }} ev-r ev-ph" data-r="hang" data-group="gallery" data-i="{{ $i }}" style="--i:{{ $i % 6 }}"
              data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="{{ ($captions[$i] ?? '') ?: 'រូបភាពទី '.$khDigits($i + 1) }}">
              <span class="ev-gf-nail" aria-hidden="true"></span>
              <span class="ev-gf-frame"><span class="ev-gf-mat"><img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="lazy" draggable="false"></span></span>
            </button>
          @endforeach
        </div>
        <p class="ev-hint ev-r" data-r="up">ចុចលើរូប ដើម្បីមើលធំ</p>
      </section>
    @endif

    {{-- ============ 9 · THE VENUE: an engraved map ============ --}}
    @if ($showVenue)
      <section class="ev-sec {{ $tone() }}" id="ev-venue">
        {!! $decor() !!}
        {!! $head('The venue', 'ទីកន្លែងប្រារព្ធពិធី') !!}
        <div class="ev-card ev-venue ev-r" data-r="unfold">
          <span class="ev-card-corners" aria-hidden="true"></span>
          @if ($venueName)<p class="ev-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="ev-body">{{ $venueAddress }}</p>@endif
          @if ($timeKhmer)<p class="ev-gold-text">{{ $timeKhmer }}</p>@endif
          <div class="ev-map" id="ev-map">
            <svg class="ev-map-art" viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
              <rect width="320" height="200" fill="#15130f"/>
              <path d="M-10 150C50 120 90 175 150 150S260 90 330 112" fill="none" stroke="{{ $accent }}" stroke-opacity=".18" stroke-width="20" stroke-linecap="round"/>
              <g fill="none" stroke="{{ $accent }}" stroke-opacity=".5" stroke-width="1"><path d="M0 60H320M0 100C80 96 140 104 320 98M110 0V200M210 0C204 60 218 120 212 200M40 0L150 200M260 0L300 200"/></g>
              <g fill="none" stroke="{{ $accent }}" stroke-opacity=".25" stroke-width=".7">
                @for ($g = 0; $g < 9; $g++)<rect x="{{ 14 + ($g % 3) * 106 + ($g % 2) * 10 }}" y="{{ 10 + intdiv($g, 3) * 64 }}" width="{{ 34 + ($g * 7) % 20 }}" height="{{ 22 + ($g * 5) % 14 }}"/>@endfor
              </g>
              <g transform="translate(286 34)" fill="none" stroke="{{ $accent }}" stroke-opacity=".6" stroke-width=".8"><circle r="16"/><circle r="11" stroke-opacity=".3"/><path d="M0 -20L3 0L0 20L-3 0Z" fill="{{ $accent }}" fill-opacity=".5"/><path d="M-20 0L0 3L20 0L0 -3Z"/></g>
              <text x="286" y="-0" font-size="7" fill="{{ $accent }}" fill-opacity=".7" text-anchor="middle" transform="translate(0 64)">N</text>
              <g transform="translate(160 98)"><circle class="ev-map-pulse" r="14" fill="{{ $accent }}" fill-opacity=".25"/><path d="M0 8C-8 -2 -10 -8 -10 -12A10 10 0 0 1 10 -12C10 -8 8 -2 0 8Z" transform="translate(0 -6)" fill="url(#ev-foil-g)"/><circle cy="-18" r="3.2" fill="#15130f"/></g>
            </svg>
            @if ($mapEmbed)
              <iframe class="ev-map-frame" data-src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe>
              <button type="button" class="ev-map-open" id="ev-map-open">បង្ហាញផែនទី</button>
            @endif
          </div>
          @if ($mapUrl)
            <a class="ev-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
              មើលផ្លូវ Google Maps
            </a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES: fabric chips + tent cards ============ --}}
    @if ($showNotes)
      <section class="ev-sec {{ $tone() }}" id="ev-notes">
        {!! $decor() !!}
        {!! $head('Good to know', 'ព័ត៌មានសម្រាប់ភ្ញៀវកិត្តិយស') !!}
        @if ($dressCode !== '')
          <div class="ev-card ev-dress ev-r" data-r="unfold">
            <span class="ev-card-corners" aria-hidden="true"></span>
            <p class="ev-caps">Dress code</p>
            <p class="ev-label">ពណ៌សម្លៀកបំពាក់</p>
            @if ($dressColours)
              <ul class="ev-chips" aria-label="ពណ៌សម្លៀកបំពាក់">
                <li class="ev-rail" aria-hidden="true"></li>
                @foreach ($dressColours as $i => $c)
                  <li class="ev-chip-li" style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                    <button type="button" class="ev-chip" aria-label="{{ $c['name'] }}"><span class="ring" aria-hidden="true"></span><span class="cloth" aria-hidden="true"></span></button>
                    <span class="ev-chip-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="ev-body">{{ $dressCode }}</p>
          </div>
        @endif
        @if ($whatToBring !== '' || $rsvpBy)
          <div class="ev-tents">
            @if ($whatToBring !== '')
              <div class="ev-tent ev-r" data-r="fold"><p class="ev-script ev-foil">What to bring</p><p class="ev-label">សូមយកមកជាមួយ</p><p class="ev-body">{{ $whatToBring }}</p></div>
            @endif
            @if ($rsvpBy)
              <div class="ev-tent ev-r" data-r="fold" style="--i:1"><p class="ev-script ev-foil">Kindly reply by</p><p class="ev-label">សូមឆ្លើយតបមុន</p><p class="ev-body">{{ $rsvpBy }}</p></div>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 11 · RSVP: the engraved reply card gets a foil stamp and turns over ============ --}}
    @if ($showRsvp)
      <section class="ev-sec {{ $tone() }}" id="ev-rsvp">
        {!! $decor() !!}
        {!! $head('Kindly reply', 'សូមបញ្ជាក់ការចូលរួម') !!}
        <div class="ev-reply ev-r" data-r="unfold" id="ev-reply">
          <div class="ev-reply-in" id="ev-reply-in">
            <div class="ev-reply-face front">
              <span class="ev-card-corners" aria-hidden="true"></span>
              <p class="ev-caps">Répondez s'il vous plaît</p>
              <p class="ev-body">{{ $rsvpQuestion }}</p>
              @if ($rsvpBy)<p class="ev-soft">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
              <p class="ev-reply-m"><span class="m">M</span><span class="line{{ $kh($guest) }}">{{ $guest }}</span></p>
              <div class="ev-reply-opts" id="ev-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
                <button type="button" class="ev-opt" data-status="yes" role="radio" aria-checked="false"><span class="box" aria-hidden="true"></span>យល់ព្រមចូលរួមដោយរីករាយ</button>
                <button type="button" class="ev-opt" data-status="no" role="radio" aria-checked="false"><span class="box" aria-hidden="true"></span>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
              </div>
              <form class="ev-reply-form" id="ev-rsvp-form">
                <label class="ev-sr" for="ev-rsvp-note">សារជូនពរ</label>
                <textarea id="ev-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
                <button type="submit" class="ev-btn">ផ្ញើការឆ្លើយតប</button>
              </form>
              <p class="ev-err" id="ev-rsvp-err" role="alert"></p>
              {{-- the foil stamp pressed on the card when it is sent --}}
              <div class="ev-stamp" id="ev-stamp" aria-hidden="true">{!! $crest('ev-stamp-crest') !!}</div>
            </div>
            <div class="ev-reply-face back" role="status">
              <span class="ev-card-corners" aria-hidden="true"></span>
              {!! $crest('ev-reply-crest') !!}
              <p class="ev-script ev-foil" id="ev-rsvp-done-title">Merci</p>
              <p class="ev-body" id="ev-rsvp-done-text"></p>
              <button type="button" class="ev-link" id="ev-rsvp-again" tabindex="-1">កែប្រែការឆ្លើយតប</button>
            </div>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT: a velvet ring box that opens on the QR ============ --}}
    @if ($giftQr)
      <section class="ev-sec {{ $tone() }}" id="ev-gift-sec">
        {!! $decor() !!}
        {!! $head('With gratitude', 'ចំណងដៃ') !!}
        <p class="ev-body ev-gift-msg ev-r" data-r="up">{{ $giftMessage }}</p>
        <div class="ev-case ev-r" data-r="rise" id="ev-case">
          <div class="ev-case-card">
            <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
            @if ($giftAccount)<p class="ev-case-acc">{{ $giftAccount }}</p>@endif
          </div>
          <button type="button" class="ev-case-lid" id="ev-case-lid" aria-label="បើកប្រអប់ចំណងដៃ">
            <span class="ev-case-clasp" aria-hidden="true"></span>
            {!! $crest('ev-case-crest') !!}
            <span class="ev-case-hint">ចុចដើម្បីបើក</span>
          </button>
        </div>
        <div class="ev-gift-btns" id="ev-gift-btns">
          @if ($giftNumber)<button type="button" class="ev-btn" id="ev-copy" data-copy="{{ $giftNumber }}">ចម្លងលេខគណនី</button>@endif
          <a class="ev-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACT (calling cards) + CUSTOM BUTTON ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="ev-sec {{ $tone() }}" id="ev-contact">
        {!! $decor() !!}
        {!! $head('At your service', 'ទំនាក់ទំនង') !!}
        <div class="ev-calls">
          @foreach ($contacts as $i => $c)
            <a class="ev-call ev-r" data-r="{{ $i % 2 ? 'slide-r' : 'slide-l' }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="ev-caps-sm">{{ $c['en'] }}</span>
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n ev-foil{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
        @if ($showCta)
          <a class="ev-btn ev-cta ev-r" data-r="rise" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }}</a>
        @endif
      </section>
    @endif

    {{-- ============ 14 · THANKS / APOLOGY (letters on monogrammed paper) ============ --}}
    @if ($letterRows->isNotEmpty())
      <section class="ev-sec {{ $tone() }}" id="ev-letters">
        {!! $decor() !!}
        <div class="ev-letters">
          @foreach ($letterRows as [$khHead, $en, $text])
            <article class="ev-letter ev-r" data-r="{{ $loop->index ? 'slide-r' : 'slide-l' }}">
              {!! $crest('ev-letter-crest') !!}
              <p class="ev-script ev-foil">{{ $en }}</p>
              <h3 class="ev-letter-head">{{ $khHead }}</h3>
              <p class="ev-letter-text">{{ $text }}</p>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 15 · FINALE: a toast ============ --}}
    <section class="ev-finale ev-dark" id="ev-finale">
      {!! $decor() !!}
      @if ($finaleBg)
        <div class="ev-finale-frame ev-r" data-r="rise"><div data-speed=".06">{!! $frame($finaleBg, $coupleTitle, $initials) !!}</div></div>
      @else
        {!! $crest('ev-finale-crest ev-r') !!}
      @endif
      <p class="ev-finale-line ev-foil ev-r{{ $kh($closingLine) }}" data-r="wipe">{{ $closingLine }}</p>
      <p class="ev-body ev-r" data-r="up">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
      @if ($coupleTitle)<p class="ev-finale-names ev-r{{ $kh($coupleTitle) }}" data-r="up">{{ $coupleTitle }}</p>@endif
      <button type="button" class="ev-btn ev-raise" id="ev-raise">
        <svg viewBox="-40 -12 80 70" aria-hidden="true"><g class="ev-coupe l" transform="translate(-19 6) rotate(16) scale(.62)">{!! $coupe !!}</g><g class="ev-coupe r" transform="translate(19 6) rotate(-16) scale(.62)">{!! $coupe !!}</g></svg>
        លើកកែវអបអរ
      </button>
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'ev-kh' : ''" :date="$dateNumeric"
      class="ev-footer"
      style="--inv-footer-bg:#0e0d0a; --inv-footer-text:#f3ead6; --inv-footer-muted:#b9ab90; --inv-footer-accent:{{ $accent }}; --inv-footer-ring:rgba(176,141,87,.25); --inv-footer-names-font:'Pinyon Script','Moulpali',cursive; --inv-footer-caps-font:'Cormorant Garamond','Noto Serif Khmer',serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><svg viewBox="-70 -70 140 140" aria-hidden="true" style="width:46px;height:46px">{!! $laurel !!}</svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview (FLIP zoom from the tapped frame) --}}
  <div class="ev-lb" id="ev-lb" hidden role="dialog" aria-modal="true" aria-label="កម្រងរូបភាព">
    <div class="ev-lb-bg" aria-hidden="true"></div>
    <figure class="ev-lb-fig" id="ev-lb-fig"><img id="ev-lb-img" alt="" draggable="false"></figure>
    <p class="ev-lb-cap" id="ev-lb-cap"></p>
    <p class="ev-lb-count" id="ev-lb-count"></p>
    <button type="button" class="ev-lb-btn prev" id="ev-lb-prev" aria-label="រូបមុន"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
    <button type="button" class="ev-lb-btn next" id="ev-lb-next" aria-label="រូបបន្ទាប់"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
    <button type="button" class="ev-lb-x" id="ev-lb-x" aria-label="បិទ"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
  </div>
  <div class="ev-toast" id="ev-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything --}}
  @if ($musicFile)<audio id="ev-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
  <div class="ev-sound-wrap">
    @if ($songTitle !== '' && ($musicFile || $youtubeId))
      <p class="ev-now" id="ev-now"><span class="disc" aria-hidden="true"></span><span class="t{{ $kh($songTitle) }}">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $musicFile && ! $youtubeId)
      <a class="ev-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></a>
    @else
      <button type="button" class="ev-sound" id="ev-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="ev-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'paper' => $sfx('papercrackle1.mp3'),
          'send' => $sfx('envelope.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $evAsset('js/invitations/eternal-vows.js') }}"></script>
